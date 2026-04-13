<?php

namespace App\Http\Controllers;

use App\Models\Intervention; 
use Illuminate\Http\Request;

class InterventionController extends Controller
{

    public function update($id, Request $request)
    {
        $intervention = Intervention::findOrFail($id);

        $request->validate([
            'note' => 'required',
        ]);

        // Update the intervention
        $intervention->update([
            'note' => $request->note,
        ]);

        return response()->json([
            'message' => 'Intervention updated successfully',
            'intervention' => $intervention,
        ]);
    }

    public function complete($id, Request $request)
    {
        $intervention = Intervention::findOrFail($id);

        $request->validate([
            'note' => 'required',
        ]);

        // Update the intervention
        $intervention->update([
            'note' => $request->note,
        ]);

        $intervention->ticket->update(['status' => 'resolved', 'completed_at' => now()]);

        return response()->json([
            'message' => 'Intervention completed successfully',
            'intervention' => $intervention,
        ]);
    }

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
