<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Ticket;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function assign(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,normal,high,critical',
            'leader_id' => 'nullable|exists:users,id',
            'technicians' => 'array',
            'technicians.*' => 'exists:users,id',
        ]);

        $ticket = Ticket::findOrFail($id);

        // Update ticket info
        $ticket->update([
            'title' => $request->title, 
            'status' => 'assigned',
        ]);

        // Create Assignment
        $assignment = Assignment::create([
            'ticket_id' => $ticket->id,
            'leader_id' => $request->leader_id,
            'priority' => $request->priority,
            'dispatcher_id' => auth()->id(),
        ]);

        // Attach technicians
        if ($request->technicians) {
            $assignment->technicians()->sync($request->technicians);
        }

        return response()->json(['message' => 'Assignment created successfully']);
    }
    public function assigments(){
        $assignment = Assignment::with('ticket')->where( 'leader_id' , auth()->id())->get();
        return response()->json($assignment);
    }
    public function assigment($id)
{
    $assignment = Assignment::with([
        'ticket',
        'ticket.interventions'
    ])
    ->where('id', $id)                 // filter by assignment id
    ->where('leader_id', auth()->id()) // ensure the authenticated user is the leader
    ->firstOrFail();                   // get single record or fail

    return response()->json($assignment);
}
}