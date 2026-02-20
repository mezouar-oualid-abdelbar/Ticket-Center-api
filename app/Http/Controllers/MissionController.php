<?php

namespace App\Http\Controllers;

use App\Models\mission;
use App\Http\Requests\StoremissionRequest;
use App\Http\Requests\UpdatemissionRequest;

class MissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoremissionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(mission $mission)
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(mission $mission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatemissionRequest $request, mission $mission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(mission $mission)
    {
        //
    }
}
