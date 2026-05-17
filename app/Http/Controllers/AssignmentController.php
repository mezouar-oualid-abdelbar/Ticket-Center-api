<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Ticket;
use App\Events\TicketAssigned;
use Illuminate\Http\Request;

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

    // Merge leader + technicians into one unique list for the pivot table
    $techIds = collect($request->technician_ids ?? [])
        ->when($request->leader_id, fn($c) => $c->push($request->leader_id))
        ->unique()
        ->values()
        ->all();

    if (!empty($techIds)) {
        $assignment->technicians()->sync($techIds);
    }

    // Notify all recipients (same merged list — no need to rebuild)
    if (!empty($techIds)) {
        broadcast(new TicketAssigned([
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title,
            'priority'     => $request->priority,
            'assigned_by'  => auth()->user()->name,
        ], $techIds));
    }

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
    $ticket->update([
        'title'    => $request->title,
        'priority' => $request->priority,
    ]);

    $assignment = $ticket->assignment;
    if (!$assignment) {
        return response()->json(['message' => 'Assignment not found.'], 404);
    }

    $assignment->update([
        'leader_id' => $request->leader_id,
    ]);

    // Merge leader + technicians into one unique list
    $techIds = collect($request->technician_ids ?? [])
        ->when($request->leader_id, fn($c) => $c->push($request->leader_id))
        ->unique()
        ->values()
        ->all();

    $assignment->technicians()->sync($techIds);

    // Notify all recipients of the update
    if (!empty($techIds)) {
        broadcast(new TicketAssigned([
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title,
            'priority'     => $request->priority,
            'assigned_by'  => auth()->user()->name,
        ], $techIds));
    }

    return response()->json(['message' => 'Assignment updated successfully']);
}
}
