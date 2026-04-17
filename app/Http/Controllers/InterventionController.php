<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use App\Events\TicketResolved;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | POST /api/technician/{id}/intervention/update
    |--------------------------------------------------------------------------
    */
    public function update($id, Request $request)
    {
        $intervention = Intervention::findOrFail($id);

        $request->validate(['note' => 'required|string']);

        $intervention->update(['note' => $request->note]);

        return response()->json([
            'message'      => 'Intervention updated successfully',
            'intervention' => $intervention,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/technician/{id}/intervention/complete
    | Marks the intervention done, ticket resolved, fires broadcast
    |--------------------------------------------------------------------------
    */
    public function complete($id, Request $request)
    {
        $intervention = Intervention::with(['ticket.assigments.dispatcher'])->findOrFail($id);

        $request->validate(['note' => 'required|string']);

        $intervention->update(['note' => $request->note]);

        $ticket = $intervention->ticket;
        $ticket->update(['status' => 'resolved', 'completed_at' => now()]);

        // ── Collect recipients: reporter + all dispatchers ──
        $recipientIds = collect([$ticket->reporter_id]);

        foreach ($ticket->assigments as $assignment) {
            if ($assignment->dispatcher_id) {
                $recipientIds->push($assignment->dispatcher_id);
            }
        }

        $notifData = [
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title ?? "Ticket #{$ticket->id}",
            'resolved_by'  => auth()->user()->name,
            'resolved_at'  => now()->toIso8601String(),
        ];

        broadcast(new TicketResolved(
            $notifData,
            $recipientIds->unique()->values()->toArray()
        ));

        return response()->json([
            'message'      => 'Intervention completed successfully',
            'intervention' => $intervention,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/technician/appointment
    |--------------------------------------------------------------------------
    */
    public function makeAppointment(Request $request)
    {
        $request->validate([
            'ticket_id'   => 'required|exists:tickets,id',
            'appointment' => 'required|date',
        ]);

        $intervention = Intervention::create([
            'ticket_id'   => $request->ticket_id,
            'leader_id'   => auth()->id(),
            'appointment' => $request->appointment,
            'note'        => null,
        ]);

        $intervention->ticket->update(['status' => 'in_progress']);

        return response()->json([
            'message'      => 'Appointment created successfully',
            'intervention' => $intervention,
        ]);
    }
}
