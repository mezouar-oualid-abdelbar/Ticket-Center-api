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
            'title'        => 'required|string|max:255',
            'priority'     => 'required|in:low,normal,high,critical',
            'leader_id'    => 'nullable|exists:users,id',
            'technicians'  => 'array',
            'technicians.*'=> 'exists:users,id',
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

        $techIds = $request->technicians ?? [];
        if ($request->leader_id) {
            $techIds = array_unique(array_merge($techIds, [$request->leader_id]));
        }

        if (!empty($techIds)) {
            $assignment->technicians()->sync($techIds);
        }

        // ── Broadcast notification to each technician ──
        $notifData = [
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title,
            'priority'     => $request->priority,
            'assigned_by'  => auth()->user()->name,
        ];

        $allRecipients = collect($techIds)
            ->when($request->leader_id, fn($c) => $c->push($request->leader_id))
            ->unique()
            ->values()
            ->toArray();

        if (!empty($allRecipients)) {
            broadcast(new TicketAssigned($notifData, $allRecipients));
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
}
