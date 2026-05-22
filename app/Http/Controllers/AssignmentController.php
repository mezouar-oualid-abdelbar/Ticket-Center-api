<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Ticket;
use App\Events\TicketAssigned;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SystemMessage;
use App\Services\NotificationService;
use App\Models\User;

class AssignmentController extends Controller
{
    private function priorityValue(mixed $priority): string
    {
        return $priority instanceof \BackedEnum ? $priority->value : (string) $priority;
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/manager/ticket/{id}/assign
    |--------------------------------------------------------------------------
    */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'priority'         => 'required|in:low,normal,high,critical',
            'leader_id'        => 'nullable|exists:users,id',
            'technician_ids'   => 'array',
            'technician_ids.*' => 'exists:users,id',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'title'    => $request->title,
            'priority' => $request->priority,
            'status'   => 'assigned',
        ]);

        $assignment = Assignment::create([
            'ticket_id'     => $ticket->id,
            'leader_id'     => $request->leader_id,
            'dispatcher_id' => auth()->id(),
        ]);

        $techIds = collect($request->technician_ids ?? [])
            ->when($request->leader_id, fn($c) => $c->push($request->leader_id))
            ->unique()
            ->values()
            ->all();

        if (!empty($techIds)) {
            $assignment->technicians()->sync($techIds);
        }

        // Broadcast WebSocket event
        if (!empty($techIds)) {
            broadcast(new TicketAssigned([
                'ticket_id'    => $ticket->id,
                'ticket_title' => $ticket->title,
                'priority'     => $request->priority,
                'assigned_by'  => auth()->user()->name,
            ], $techIds));
        }

        // ── System message ────────────────────────────────────────
        $dispatcherName = auth()->user()->name;

        $leader = $request->leader_id
            ? User::find($request->leader_id)
            : null;

        $technicians = !empty($request->technician_ids)
            ? User::whereIn('id', $request->technician_ids)->get()
            : collect();

        if ($leader) {
            $techPart   = $technicians->isNotEmpty()
                ? ' assisted by: ' . $technicians->pluck('name')->join(', ')
                : '';
            $systemText = "{$dispatcherName} assigned a group to solve this issue — "
                . "Leader: {$leader->name}{$techPart}";
        } else {
            $systemText = "{$dispatcherName} assigned this ticket";
        }

        $systemMessage = Message::create([
            'ticket_id' => $ticket->id,
            'sender_id' => null,
            'message'   => $systemText,
            'type'      => 'system',
        ]);

        broadcast(new SystemMessage($ticket->id, $systemText, $systemMessage->id));

        $priority = $request->priority; // plain string from request

        // ── Notifications ─────────────────────────────────────────

        // 1. Reporter
        if ($ticket->reporter_id) {
            NotificationService::send(
                userId:  $ticket->reporter_id,
                title:   'Your ticket has been assigned',
                message: "Your ticket \"{$ticket->title}\" has been assigned by {$dispatcherName}.",
                type:    'assignment',
                related: $ticket,
            );
        }

        // 2. Leader
        if ($leader) {
            NotificationService::send(
                userId:  $leader->id,
                title:   'You are leading a ticket',
                message: "You have been set as leader for ticket \"{$ticket->title}\" (Priority: {$priority}).",
                type:    'assignment',
                related: $ticket,
            );
        }

        // 3. Technicians (excluding leader)
        foreach ($technicians as $tech) {
            if ($leader && $tech->id === $leader->id) continue;

            NotificationService::send(
                userId:  $tech->id,
                title:   'New ticket assignment',
                message: "You have been assigned to ticket \"{$ticket->title}\" (Priority: {$priority}).",
                type:    'assignment',
                related: $ticket,
            );
        }

        return response()->json(['message' => 'Assignment created successfully']);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/manager/ticket/{id}/assign
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
{
    $request->validate([
        'title'            => 'required|string|max:255',
        'priority'         => 'required|in:low,normal,high,critical',
        'leader_id'        => 'nullable|exists:users,id',
        'technician_ids'   => 'array',
        'technician_ids.*' => 'exists:users,id',
    ]);

    // $id here is the TICKET id (from the route /assignments/{id})
    $ticket = Ticket::findOrFail($id);

    $currentPriority = $this->priorityValue($ticket->priority);
    $currentTitle    = $ticket->title;

    $priorityChanged = $currentPriority !== $request->priority;
    $titleChanged    = $currentTitle    !== $request->title;

    $ticket->update([
        'title'    => $request->title,
        'priority' => $request->priority,
    ]);

    // ── Use the correct relationship name ─────────────────────
    $assignment = $ticket->assigments()->latest()->first(); // ← typo matches your DB relation
    if (!$assignment) {
        return response()->json(['message' => 'Assignment not found.'], 404);
    }

    $oldLeaderId      = $assignment->leader_id;
    $oldTechnicianIds = $assignment->technicians->pluck('id')->toArray();

    $assignment->update(['leader_id' => $request->leader_id]);

    $techIds = collect($request->technician_ids ?? [])
        ->when($request->leader_id, fn($c) => $c->push($request->leader_id))
        ->unique()
        ->values()
        ->all();

    $assignment->technicians()->sync($techIds);

    if (!empty($techIds)) {
        broadcast(new TicketAssigned([
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title,
            'priority'     => $request->priority,
            'assigned_by'  => auth()->user()->name,
        ], $techIds));
    }

    $dispatcherName = auth()->user()->name;
    $newPriority    = $request->priority;

    if ($titleChanged) {
        broadcast(new SystemMessage(
            $ticket->id,
            "{$dispatcherName} renamed the ticket to \"{$request->title}\""
        ));
    }

    if ($priorityChanged) {
        broadcast(new SystemMessage(
            $ticket->id,
            "{$dispatcherName} changed priority to {$newPriority}"
        ));
    }

    if ($oldLeaderId !== $request->leader_id) {
        $newLeaderName = $request->leader_id
            ? optional(User::find($request->leader_id))->name
            : 'nobody';

        broadcast(new SystemMessage(
            $ticket->id,
            "{$dispatcherName} changed the leader to {$newLeaderName}"
        ));
    }

    $newLeader      = $request->leader_id ? User::find($request->leader_id) : null;
    $newTechnicians = !empty($request->technician_ids)
        ? User::whereIn('id', $request->technician_ids)->get()
        : collect();

    $newTechIds = collect($techIds);
    $addedIds   = $newTechIds->diff($oldTechnicianIds);
    $removedIds = collect($oldTechnicianIds)->diff($newTechIds);

    if ($ticket->reporter_id && ($titleChanged || $priorityChanged)) {
        $changes = collect([
            $titleChanged    ? "renamed to \"{$ticket->title}\""    : null,
            $priorityChanged ? "priority changed to {$newPriority}" : null,
        ])->filter()->join(' and ');

        NotificationService::send(
            userId:  $ticket->reporter_id,
            title:   'Your ticket was updated',
            message: "Your ticket has been updated: {$changes}.",
            type:    'info',
            related: $ticket,
        );
    }

    if ($newLeader && $oldLeaderId !== $newLeader->id) {
        NotificationService::send(
            userId:  $newLeader->id,
            title:   'You are now leading a ticket',
            message: "You have been set as leader for ticket \"{$ticket->title}\" (Priority: {$newPriority}).",
            type:    'assignment',
            related: $ticket,
        );
    }

    if ($oldLeaderId && $oldLeaderId !== $request->leader_id) {
        NotificationService::send(
            userId:  $oldLeaderId,
            title:   'Leadership removed',
            message: "You are no longer the leader for ticket \"{$ticket->title}\".",
            type:    'info',
            related: $ticket,
        );
    }

    foreach ($addedIds as $techId) {
        if ($newLeader && $techId === $newLeader->id) continue;

        NotificationService::send(
            userId:  $techId,
            title:   'New ticket assignment',
            message: "You have been assigned to ticket \"{$ticket->title}\" (Priority: {$newPriority}).",
            type:    'assignment',
            related: $ticket,
        );
    }

    foreach ($removedIds as $techId) {
        NotificationService::send(
            userId:  $techId,
            title:   'Removed from ticket',
            message: "You have been removed from ticket \"{$ticket->title}\".",
            type:    'info',
            related: $ticket,
        );
    }

    if ($priorityChanged || $titleChanged) {
        $stayingIds = $newTechIds->intersect($oldTechnicianIds);
        $changes    = collect([
            $titleChanged    ? "renamed to \"{$ticket->title}\""    : null,
            $priorityChanged ? "priority changed to {$newPriority}" : null,
        ])->filter()->join(' and ');

        foreach ($stayingIds as $techId) {
            NotificationService::send(
                userId:  $techId,
                title:   'Ticket updated',
                message: "Ticket \"{$ticket->title}\" was updated: {$changes}.",
                type:    'info',
                related: $ticket,
            );
        }
    }

    return response()->json(['message' => 'Assignment updated successfully']);
}

    /*
    |--------------------------------------------------------------------------
    | GET /api/technician/assignments
    |--------------------------------------------------------------------------
    */
    public function assigments()
    {
        $assignments = Assignment::with('ticket')
            ->where('leader_id', auth()->id())
            ->get();

        return response()->json($assignments);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/technician/assignment/{id}
    |--------------------------------------------------------------------------
    */
    public function assigment($id)
    {
        $assignment = Assignment::with(['ticket', 'ticket.interventions'])
            ->where('id', $id)
            ->where('leader_id', auth()->id())
            ->firstOrFail();

        return response()->json($assignment);
    }
}