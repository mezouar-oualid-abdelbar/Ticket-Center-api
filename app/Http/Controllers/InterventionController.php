<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use App\Events\TicketResolved;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SystemMessage;

class InterventionController extends Controller
{
    private function createSystemMessage(int $ticketId, string $text)
    {
        $systemMessage = Message::create([
            'ticket_id' => $ticketId,
            'sender_id' => null,
            'message'   => $text,
            'type'      => 'system',
        ]);

        broadcast(new SystemMessage($ticketId, $text, $systemMessage->id));

        return $systemMessage;
    }

    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'note' => 'required|string'
        ]);

        $intervention = Intervention::findOrFail($id);
        $intervention->update(['note' => $validated['note']]);

        $systemText = "Intervention note updated by " . auth()->user()->name . ": " . $validated['note'];
        $systemMessage = $this->createSystemMessage($intervention->ticket_id, $systemText);

        return response()->json([
            'message'        => 'Intervention updated successfully',
            'intervention'   => $intervention,
            'system_message' => $systemMessage,
        ]);
    }

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

        $systemText = "Ticket marked as resolved by " . auth()->user()->name;
        $systemMessage = $this->createSystemMessage($ticket->id, $systemText);

        return response()->json([
            'message'        => 'Intervention completed successfully',
            'intervention'   => $intervention,
            'system_message' => $systemMessage,
        ]);
    }

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

        $systemText = "Appointment scheduled for " . (new \DateTime($request->appointment))->format('Y-m-d H:i') . " by " . auth()->user()->name;
        $systemMessage = $this->createSystemMessage($request->ticket_id, $systemText);

        return response()->json([
            'message'        => 'Appointment created successfully',
            'intervention'   => $intervention,
            'system_message' => $systemMessage,
        ]);
    }
}