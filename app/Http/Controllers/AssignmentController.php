<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Ticket;
use App\Events\TicketAssigned;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SystemMessage;

class AssignmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | POST /api/manager/ticket/{id}/assign
    |--------------------------------------------------------------------------
    */
public function assign(Request $request, $id)
{
    $request->validate([
        'title'           => 'required|string|max:255',
        'priority'        => 'required|in:low,normal,high,critical',
        'leader_id'       => 'nullable|exists:users,id',
        'technician_ids'  => 'array',
        'technician_ids.*'=> 'exists:users,id',
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
 
    // Notify technicians
    if (!empty($techIds)) {
        broadcast(new TicketAssigned([
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title,
            'priority'     => $request->priority,
            'assigned_by'  => auth()->user()->name,
        ], $techIds));
    }
 
    // ── Build the system message text ────────────────────────
    $dispatcherName = auth()->user()->name;
 
    $leader = $request->leader_id
        ? \App\Models\User::find($request->leader_id)
        : null;
 
    // Technicians = everyone except the leader
    $technicians = !empty($request->technician_ids)
        ? \App\Models\User::whereIn('id', $request->technician_ids)->get()
        : collect();
 
    if ($leader) {
        $techPart = $technicians->isNotEmpty()
            ? ' assisted by: ' . $technicians->pluck('name')->join(', ')
            : '';
 
        $systemText = "{$dispatcherName} assigned a group to solve this issue — "
            . "Leader: {$leader->name}{$techPart}";
    } else {
        $systemText = "{$dispatcherName} assigned this ticket";
    }
 
    // ── Save to DB so it survives refresh ────────────────────
    $systemMessage = Message::create([
        'ticket_id' => $ticket->id,
        'sender_id' => null,
        'message'   => $systemText,
        'type'      => 'system',
    ]);
 
    // ── Broadcast it live into the chat ──────────────────────
    broadcast(new \App\Events\SystemMessage($ticket->id, $systemText, $systemMessage->id));
 
    return response()->json(['message' => 'Assignment created successfully']);
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

    public function update(Request $request, $id)
{
    $request->validate([
        'title'           => 'required|string|max:255',
        'priority'        => 'required|in:low,normal,high,critical',
        'leader_id'       => 'nullable|exists:users,id',
        'technician_ids'  => 'array',
        'technician_ids.*'=> 'exists:users,id',
    ]);
 
    $ticket = Ticket::findOrFail($id);
 
    $priorityChanged = $ticket->priority !== $request->priority;
    $titleChanged    = $ticket->title    !== $request->title;
 
    $ticket->update([
        'title'    => $request->title,
        'priority' => $request->priority,
    ]);
 
    $assignment = $ticket->assignment;
    if (!$assignment) {
        return response()->json(['message' => 'Assignment not found.'], 404);
    }
 
    $oldLeaderId = $assignment->leader_id;
    $assignment->update(['leader_id' => $request->leader_id]);
 
    $techIds = collect($request->technician_ids ?? [])
        ->when($request->leader_id, fn($c) => $c->push($request->leader_id))
        ->unique()
        ->values()
        ->all();
 
    $assignment->technicians()->sync($techIds);
 
    // Notify technicians
    if (!empty($techIds)) {
        broadcast(new TicketAssigned([
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title,
            'priority'     => $request->priority,
            'assigned_by'  => auth()->user()->name,
        ], $techIds));
    }
 
    // ── System messages for what actually changed ────────────
    $dispatcherName = auth()->user()->name;
 
    if ($titleChanged) {
        broadcast(new \App\Events\SystemMessage(
            $ticket->id,
            "{$dispatcherName} renamed the ticket to \"{$request->title}\""
        ));
    }
 
    if ($priorityChanged) {
        broadcast(new \App\Events\SystemMessage(
            $ticket->id,
            "{$dispatcherName} changed priority to {$request->priority}"
        ));
    }
 
    if ($oldLeaderId !== $request->leader_id) {
        $newLeaderName = $request->leader_id
            ? optional(\App\Models\User::find($request->leader_id))->name
            : 'nobody';
 
        broadcast(new \App\Events\SystemMessage(
            $ticket->id,
            "{$dispatcherName} changed the leader to {$newLeaderName}"
        ));
    }
 
    return response()->json(['message' => 'Assignment updated successfully']);
}

}
