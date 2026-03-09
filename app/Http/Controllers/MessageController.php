<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoremessageRequest;
use App\Http\Requests\UpdatemessageRequest;
use App\Models\message;

class MessageController extends Controller
{
 public function index(Request $request)
    {
        $request->validate([
            'mission_id' => 'required|exists:missions,id',
        ]);

        $messages = Message::where('chat_id', $request->mission_id)
            ->orderBy('created_at', 'asc') // oldest first
            ->get();

        return response()->json($messages);
    }

    /**
     * Store a new message.
     */
    public function store(Request $request)
    {
        $request->validate([
            'mission_id' => 'required|exists:missions,id',
            'msg_content' => 'required|string',
        ]);

        $message = Message::create([
            'chat_id' => $request->mission_id,
            'msg_content' => $request->msg_content,
            'sender_id' => Auth::id(), // assumes sender_id column exists
        ]);

        return response()->json($message, 201);
    }

    /**
     * Show a single message.
     */
    public function show($id)
    {
        $message = Message::findOrFail($id);
        return response()->json($message);
    }

    /**
     * Update a message (only sender can update).
     */
    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        // Ensure the authenticated user is the sender
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'msg_content' => 'required|string',
        ]);

        $message->update(['msg_content' => $request->msg_content]);

        return response()->json($message);
    }

    /**
     * Delete a message (only sender can delete).
     */
    public function destroy($id)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
