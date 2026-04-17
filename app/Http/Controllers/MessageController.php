<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * GET /api/messages/{ticketId}
     *
     * Returns message history for a ticket (oldest → newest).
     * Authorised users: ticket reporter, dispatcher, leader, any technician.
     */
    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $this->authorizeTicketAccess($ticket);

        $messages = Message::with('sender:id,name')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => $this->format($m));

        return response()->json($messages);
    }

    /**
     * POST /api/messages/{ticketId}
     *
     * Stores a message and broadcasts it on the private ticket channel
     * so all other participants receive it in real time.
     */
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $this->authorizeTicketAccess($ticket);

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $msg = Message::create([
            'ticket_id' => (int) $ticketId,
            'sender_id' => Auth::id(),
            'message'   => $request->message,
        ]);

        $msg->load('sender:id,name');

        $payload = $this->format($msg);

        // Broadcast to everyone else on the ticket channel
        broadcast(new MessageSent((int) $ticketId, $payload))->toOthers();

        return response()->json($payload, 201);
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Format a message for the API / broadcast payload.
     */
    private function format(Message $m): array
    {
        return [
            'id'          => $m->id,
            'ticket_id'   => $m->ticket_id,
            'sender_id'   => $m->sender_id,
            'sender_name' => $m->sender?->name ?? 'Unknown',
            'message'     => $m->message,
            'created_at'  => $m->created_at?->toIso8601String(),
        ];
    }

    /**
     * Throw 403 if the authenticated user is not allowed to access this ticket's chat.
     *
     * Allowed:
     *   • The ticket reporter (user who created it)
     *   • The dispatcher (manager who assigned it)
     *   • The leader (lead technician)
     *   • Any technician attached via assignment_user pivot
     *   • Any admin
     */
    private function authorizeTicketAccess(Ticket $ticket): void
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) return;

        // Reporter
        if ($ticket->reporter_id === $user->id) return;

        // Load assignments with their pivot technicians
        $ticket->loadMissing('assigments.technicians');

        foreach ($ticket->assigments as $assignment) {
            if ($assignment->dispatcher_id === $user->id) return;
            if ($assignment->leader_id     === $user->id) return;
            if ($assignment->technicians->contains('id', $user->id)) return;
        }

        abort(403, 'You are not a participant of this ticket.');
    }
}
