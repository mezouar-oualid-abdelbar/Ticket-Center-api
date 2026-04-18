<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Assignment;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * GET /api/ticket
     * Tickets the auth user CREATED (reporter)
     */
    public function index()
    {
        $tickets = Ticket::where('reporter_id', auth()->id())->get();
        return response()->json($tickets);
    }

    /**
     * GET /api/my-tickets
     * All UNRESOLVED tickets where the user is:
     *   - reporter (created it)
     *   - leader   (assigned as lead technician)
     *   - technician (member of assignment_user pivot)
     *
     * Returns unique tickets with the ticket's assignment info for chat context.
     */
    public function myTickets()
    {
        $userId = auth()->id();

        // Tickets where user is reporter
        $reporterIds = Ticket::where('reporter_id', $userId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->pluck('id');

        // Assignments where user is leader OR technician
        $assignmentTicketIds = Assignment::where('leader_id', $userId)
            ->orWhereHas('technicians', fn($q) => $q->where('users.id', $userId))
            ->pluck('ticket_id');

        // Merge & deduplicate
        $ticketIds = $reporterIds->merge($assignmentTicketIds)->unique()->values();

        $tickets = Ticket::with(['assigments' => fn($q) => $q->select('id', 'ticket_id', 'leader_id', 'dispatcher_id')])
            ->whereIn('id', $ticketIds)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->orderByRaw("FIELD(status, 'in_progress', 'assigned', 'open')")
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    /**
     * GET /api/manager/ticket  (all tickets — manager/admin only)
     */
    public function all()
    {
        $tickets = Ticket::all();
        return response()->json($tickets);
    }

    /**
     * GET /api/manager/ticket/{id}/progress
     */
    public function progress($id)
    {
        $ticket = Ticket::with([
            'assigments.technicians',
            'assigments.leader',
            'assigments.dispatcher',
            'interventions',
        ])->findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * POST /api/ticket
     */
    public function create(Request $request)
    {
        $request->validate(['description' => 'required|string']);

        $ticket = Ticket::create([
            'description' => $request->description,
            'reporter_id' => auth()->id(),
        ]);

        return response()->json($ticket, 201);
    }

    /**
     * GET /api/manager/ticket/{id}
     */
    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);
        return response()->json($ticket);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $request->validate([
            'description' => 'sometimes|string',
            'status'      => 'sometimes|in:open,assigned,in_progress,resolved,closed',
            'priority'    => 'sometimes|nullable|in:low,normal,high,critical',
        ]);
        $ticket->update($request->only(['description', 'status', 'priority']));
        return response()->json($ticket);
    }

    public function destroy($id)
    {
        Ticket::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
