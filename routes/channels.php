<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Per-user private channel
| Used for: assignment notifications, ticket-resolved notifications
|
|   Frontend:  echo.private(`users.${user.id}`)
|--------------------------------------------------------------------------
*/
Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Per-ticket private channel
| Used for: real-time chat
|
|   Frontend:  echo.private(`ticket.${ticketId}`)
|
| Authorised if the authenticated user is ANY of:
|   • The ticket reporter   (user who created the ticket)
|   • The dispatcher        (manager who assigned it)
|   • The leader            (lead technician on the assignment)
|   • A technician          (any member of assignment_user pivot)
|   • An admin              (always allowed)
|--------------------------------------------------------------------------
*/
Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    // Admins always have access
    if ($user->hasRole('admin')) {
        return true;
    }

    $ticket = Ticket::with('assigments.technicians')
        ->find($ticketId);

    if (! $ticket) {
        return false;
    }

    // Reporter — the person who created the ticket
    if ((int) $ticket->reporter_id === (int) $user->id) {
        return true;
    }

    foreach ($ticket->assigments as $assignment) {
        // Dispatcher / manager who assigned the ticket
        if ((int) $assignment->dispatcher_id === (int) $user->id) {
            return true;
        }

        // Leader technician
        if ((int) $assignment->leader_id === (int) $user->id) {
            return true;
        }

        // Any technician in the pivot table
        if ($assignment->technicians->contains('id', $user->id)) {
            return true;
        }
    }

    return false;
});
