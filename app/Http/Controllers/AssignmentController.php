<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;

class AssignmentController extends Controller
{
    public function index()
    {
        $assignments = Assignment::with(['ticket', 'leader', 'dispatcher'])->get();
        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer',
            'leader_id' => 'required|integer',
            'dispatcher_id' => 'required|integer',
        ]);

        $assignment = Assignment::create($validated);

        return response()->json($assignment, 201);
    }

    public function show(Assignment $assignment)
    {
        return response()->json($assignment->load(['ticket', 'leader', 'dispatcher']));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'ticket_id' => 'sometimes|integer',
            'leader_id' => 'sometimes|integer',
            'dispatcher_id' => 'sometimes|integer',
        ]);

        $assignment->update($validated);

        return response()->json($assignment);
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(null, 204);
    }

    public function index()
    {
        $assignments = Assignment::with(['ticket', 'leader', 'dispatcher'])->get();
        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer',
           'leader_id' => 'required|integer',
           'dispatcher_id' => 'required|integer',
        ]);

        $assignment = Assignment::create($validated);

        return response()->json($assignment, 201);
    }

    public function show(Assignment $assignment)
    {
        return response()->json($assignment->load(['ticket', 'leader', 'dispatcher']));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'ticket_id' => 'sometimes|integer',
            'leader_id' => 'sometimes|integer',
            'dispatcher_id' => 'sometimes|integer',
            'members'       => 'sometimes|array',
        ]);

        $assignment->update($validated);

        return response()->json($assignment);
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(null, 204);
    }
}