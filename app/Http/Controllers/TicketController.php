<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\User;
class TicketController extends Controller
{
    public function index(){
        $tickets = Ticket::where('status', 'open');
        return $tickets;
    }
    public function store(Request $request){
        $request->validate([
            'title' => 'required|string',
            'priority' => '',
            'description' => 'required'
        ]);
        $ticket = Ticket::create([
            'title' => $request->title,
            'priority' => $request->priority,
            'description' => $request->description,

        ]);
        return response()->json($ticket, 201);

    }
    public function update(Request $request, $id){
        
    }
    public function show($id){
        $ticket = Ticket::findOrFail($id);
        return response()->json($Ticket);
    }
    public function destroy($id){
        $Ticket = Ticket::findOrFail($id);
        $Ticket->delete();
        return response()->json(['ticket' => 'Deleted successfully']);
    }
}