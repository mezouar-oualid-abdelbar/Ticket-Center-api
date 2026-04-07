<?php

namespace App\Http\Controllers;

use App\Models\Intervention; 
use Illuminate\Http\Request;

class InterventionController extends Controller
{


public function makeAppointment(Request $request)
{
    $request->validate([
        'ticket_id' => 'required|exists:tickets,id', 
        'appointment' => 'required|date',
    ]);

    // Create the intervention
    $intervention = Intervention::create([
        'ticket_id' => $request->ticket_id,
        'leader_id' => auth()->id(),
        'appointment' => $request->appointment,
        'note' => null, // note can be added later
    ]);

    // Optional: update ticket status
    $intervention->ticket->update(['status' => 'in_progress']);

    return response()->json([
        'message' => 'Appointment created successfully',
        'intervention' => $intervention,
    ]);
}
}
