<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Mentors;
use App\Models\Leader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaderId = Auth::user()->id; // Assuming 'leader_id' is the user's ID
        $appointments = Appointment::where('leader_id', $leaderId)->get();

        return response()->json($appointments, 200);
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mentor_id' => 'required|exists:mentors,id',
            'appointment_date' => 'required|date|after:today',
        ]);

        $leaderId = Auth::user()->id;

        // Check if leader already has an active appointment
        $existingAppointment = Appointment::where('leader_id', $leaderId)
            ->whereIn('status', ['pending', 'approved']) // Active appointments
            ->first();

        if ($existingAppointment) {
            return response()->json(['error' => 'You already have an active appointment.'], 400);
        }

        $appointment = Appointment::create([
            'id' => Str::uuid(),
            'mentor_id' => $validated['mentor_id'],
            'leader_id' => $leaderId,
            'appointment_date' => $validated['appointment_date'],
            'status' => 'pending',
        ]);

        return response()->json($appointment, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('leader_id', Auth::user()->id)
            ->first();

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found or unauthorized'], 404);
        }

        return response()->json($appointment, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Appointment $appointment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,cancelled',
        ]);

        $appointment->update(['status' => $validated['status']]);

        return response()->json($appointment, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $appointment = Appointment::where('id', $id)
        ->where('leader_id', Auth::user()->id)
        ->first();

    if (!$appointment) {
        return response()->json(['error' => 'Unauthorized or appointment not found'], 403);
    }

    $appointment->delete();

    return response()->json(['message' => 'Appointment cancelled'], 200);
    }
}
