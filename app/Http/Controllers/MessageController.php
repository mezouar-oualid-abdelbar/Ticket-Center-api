<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NewMessageNotification;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * GET /api/messages/{ticketId}
     * Returns message history oldest → newest.
     */
    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);

        $messages = Message::with('sender:id,name')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => $this->fmt($m));

        return response()->json($messages);
    }

    /**
     * POST /api/messages/{ticketId}
     * Stores message, broadcasts on ticket channel AND notifies each
     * participant on their personal users.{id} channel (for nav badge).
     */
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);

        $request->validate(['message' => 'required|string|max:2000']);

        $msg = Message::create([
            'ticket_id' => (int) $ticketId,
            'sender_id' => Auth::id(),
            'message'   => $request->message,
        ]);

        $msg->load('sender:id,name');
        $payload = $this->fmt($msg);

        // 1) Broadcast message to the ticket channel (all chat participants)
        broadcast(new MessageSent((int) $ticketId, $payload))->toOthers();

        // 2) Notify each participant on their personal channel for the nav badge
        $participantIds = $this->getParticipantIds($ticket);
        foreach ($participantIds as $uid) {
            if ($uid !== Auth::id()) {
                broadcast(new NewMessageNotification($uid, [
                    'ticket_id'    => $ticket->id,
                    'ticket_title' => $ticket->title ?? "Ticket #{$ticket->id}",
                    'sender_name'  => Auth::user()->name,
                    'preview'      => mb_substr($request->message, 0, 60),
                ]));
            }
        }

        return response()->json($payload, 201);
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function fmt(Message $m): array
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
     * Collect all user IDs who are participants of this ticket:
     * reporter + dispatchers + leaders + technicians
     */
    private function getParticipantIds(Ticket $ticket): array
    {
        $ticket->loadMissing('assigments.technicians');

        $ids = collect([$ticket->reporter_id]);

        foreach ($ticket->assigments as $a) {
            $ids->push($a->dispatcher_id, $a->leader_id);
            $a->technicians->each(fn($t) => $ids->push($t->id));
        }

        return $ids->filter()->unique()->values()->toArray();
    }

    /**
     * Abort 403 if the authenticated user is not a participant.
     */
    private function authorizeAccess(Ticket $ticket): void
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) return;
        if ((int) $ticket->reporter_id === $user->id) return;

        $ticket->loadMissing('assigments.technicians');

        foreach ($ticket->assigments as $a) {
            if ((int) $a->dispatcher_id === $user->id) return;
            if ((int) $a->leader_id     === $user->id) return;
            if ($a->technicians->contains('id', $user->id)) return;
        }

        abort(403, 'You are not a participant of this ticket.');
    }
}
