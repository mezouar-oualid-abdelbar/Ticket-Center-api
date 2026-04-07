<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where( 'reporter_id' , auth()->id())->get();
        return response()->json($tickets);
    }

    public function all()
    {
        $tickets = Ticket::All();
        return response()->json($tickets);
    }

    public function progress($id) {
        $ticket = Ticket::with([
            'assigments.technicians',
            'assigments.leader',
            'assigments.dispatcher',
            'interventions' 
        ])->findOrFail($id);

        return response()->json($ticket);
    }

    
    public function create(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        $ticket = Ticket::create([
            'description' => $request->description,
            'reporter_id' => auth()->id(),
        ]);

        return response()->json($ticket, 201);
    }

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
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}