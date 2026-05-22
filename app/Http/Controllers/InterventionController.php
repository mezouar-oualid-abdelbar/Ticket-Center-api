<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use App\Events\TicketResolved;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SystemMessage;
use App\Services\NotificationService;
use App\Models\Ticket;

class InterventionController extends Controller
{
    private function createSystemMessage(int $ticketId, string $text): Message
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

    /*
    |--------------------------------------------------------------------------
    | PUT /api/interventions/{id}
    | Technician updates the intervention note
    |--------------------------------------------------------------------------
    */
    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $intervention = Intervention::with(['ticket.assigments.technicians'])->findOrFail($id);
        $intervention->update(['note' => $validated['note']]);

        $ticket         = $intervention->ticket;
        $technicianName = auth()->user()->name;

        $systemText = "Intervention note updated by {$technicianName}: " . $validated['note'];
        $systemMessage = $this->createSystemMessage($ticket->id, $systemText);

        // ── Notify dispatcher(s) that a note was added ────────────
        foreach ($ticket->assigments as $assignment) {
            if ($assignment->dispatcher_id) {
                NotificationService::send(
                    userId:  $assignment->dispatcher_id,
                    title:   'Intervention note updated',
                    message: "{$technicianName} updated the intervention note for ticket \"{$ticket->title}\".",
                    type:    'info',
                    related: $ticket,
                );
            }
        }

        // ── Notify reporter ───────────────────────────────────────
        if ($ticket->reporter_id) {
            NotificationService::send(
                userId:  $ticket->reporter_id,
                title:   'Update on your ticket',
                message: "A technician has added a progress note to your ticket \"{$ticket->title}\".",
                type:    'info',
                related: $ticket,
            );
        }

        return response()->json([
            'message'        => 'Intervention updated successfully',
            'intervention'   => $intervention,
            'system_message' => $systemMessage,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/interventions/{id}/complete
    | Technician marks the ticket as resolved
    |--------------------------------------------------------------------------
    */
    public function complete($id, Request $request)
    {
        $intervention = Intervention::with([
            'ticket.assigments.dispatcher',
            'ticket.assigments.technicians',
        ])->findOrFail($id);

        $request->validate(['note' => 'required|string']);

        $intervention->update(['note' => $request->note]);

        $ticket       = $intervention->ticket;
        $resolvedBy   = auth()->user()->name;

        $ticket->update(['status' => 'resolved', 'completed_at' => now()]);

        // ── Collect dispatcher IDs for the legacy broadcast ───────
        $dispatcherIds = collect();
        foreach ($ticket->assigments as $assignment) {
            if ($assignment->dispatcher_id) {
                $dispatcherIds->push($assignment->dispatcher_id);
            }
        }

        $notifData = [
            'ticket_id'    => $ticket->id,
            'ticket_title' => $ticket->title ?? "Ticket #{$ticket->id}",
            'resolved_by'  => $resolvedBy,
            'resolved_at'  => now()->toIso8601String(),
        ];

        broadcast(new TicketResolved(
            $notifData,
            collect([$ticket->reporter_id])
                ->merge($dispatcherIds)
                ->unique()
                ->values()
                ->toArray()
        ));

        $systemText    = "Ticket marked as resolved by {$resolvedBy}";
        $systemMessage = $this->createSystemMessage($ticket->id, $systemText);

        // ── Notify reporter ───────────────────────────────────────
        if ($ticket->reporter_id) {
            NotificationService::send(
                userId:  $ticket->reporter_id,
                title:   'Your ticket has been resolved',
                message: "Your ticket \"{$ticket->title}\" has been marked as resolved by {$resolvedBy}.",
                type:    'resolved',
                related: $ticket,
            );
        }

        // ── Notify each dispatcher ────────────────────────────────
        foreach ($dispatcherIds->unique() as $dispatcherId) {
            NotificationService::send(
                userId:  $dispatcherId,
                title:   'Ticket resolved',
                message: "Ticket \"{$ticket->title}\" has been resolved by {$resolvedBy}.",
                type:    'resolved',
                related: $ticket,
            );
        }

        // ── Notify all technicians on the ticket ──────────────────
        foreach ($ticket->assigments as $assignment) {
            foreach ($assignment->technicians as $tech) {
                // Skip the one who resolved it — they know already
                if ($tech->id === auth()->id()) continue;

                NotificationService::send(
                    userId:  $tech->id,
                    title:   'Ticket resolved',
                    message: "Ticket \"{$ticket->title}\" has been marked as resolved by {$resolvedBy}.",
                    type:    'resolved',
                    related: $ticket,
                );
            }
        }

        return response()->json([
            'message'        => 'Intervention completed successfully',
            'intervention'   => $intervention,
            'system_message' => $systemMessage,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/interventions/appointment
    | Technician schedules an appointment
    |--------------------------------------------------------------------------
    */
    public function makeAppointment(Request $request)
    {
        $request->validate([
            'ticket_id'   => 'required|exists:tickets,id',
            'appointment' => 'required|date',
        ]);

        $ticket = Ticket::with('assigments.technicians')->findOrFail($request->ticket_id);

        $intervention = Intervention::create([
            'ticket_id'   => $request->ticket_id,
            'leader_id'   => auth()->id(),
            'appointment' => $request->appointment,
            'note'        => null,
        ]);

        $ticket->update(['status' => 'in_progress']);

        $technicianName  = auth()->user()->name;
        $formattedDate   = (new \DateTime($request->appointment))->format('Y-m-d H:i');

        $systemText    = "Appointment scheduled for {$formattedDate} by {$technicianName}";
        $systemMessage = $this->createSystemMessage($request->ticket_id, $systemText);

        // ── Notify reporter ───────────────────────────────────────
        if ($ticket->reporter_id) {
            NotificationService::send(
                userId:  $ticket->reporter_id,
                title:   'Appointment scheduled',
                message: "A technician has scheduled an appointment for your ticket \"{$ticket->title}\" on {$formattedDate}.",
                type:    'info',
                related: $ticket,
            );
        }

        // ── Notify dispatcher(s) ──────────────────────────────────
        foreach ($ticket->assigments as $assignment) {
            if ($assignment->dispatcher_id) {
                NotificationService::send(
                    userId:  $assignment->dispatcher_id,
                    title:   'Appointment scheduled',
                    message: "{$technicianName} scheduled an appointment for ticket \"{$ticket->title}\" on {$formattedDate}.",
                    type:    'info',
                    related: $ticket,
                );
            }
        }

        // ── Notify other technicians on the ticket ────────────────
        foreach ($ticket->assigments as $assignment) {
            foreach ($assignment->technicians as $tech) {
                if ($tech->id === auth()->id()) continue; // skip self

                NotificationService::send(
                    userId:  $tech->id,
                    title:   'Appointment scheduled',
                    message: "{$technicianName} scheduled an appointment for ticket \"{$ticket->title}\" on {$formattedDate}.",
                    type:    'info',
                    related: $ticket,
                );
            }
        }

        return response()->json([
            'message'        => 'Appointment created successfully',
            'intervention'   => $intervention,
            'system_message' => $systemMessage,
        ]);
    }
}