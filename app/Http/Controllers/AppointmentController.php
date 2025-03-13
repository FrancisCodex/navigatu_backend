<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Mentors;
use App\Models\Leader;
use App\Models\StartupProfile;
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
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Admin can view all appointments
            $appointments = Appointment::with(['leader', 'mentor'])->get();
        } else {
            // Leader can view only their own appointments
            $leaderId = $user->id;
            $appointments = Appointment::with(['leader', 'mentor'])->where('leader_id', $leaderId)->get();
        }

        // Format the response
        $formattedAppointments = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'incubateeName' => $appointment->leader ? $appointment->leader->name : 'N/A', // Fetch incubatee name from the leader relationship
                'mentorName' => $appointment->mentor ? $appointment->mentor->firstName . ' ' . $appointment->mentor->lastName : 'N/A',
                'mentor_expertise' => $appointment->mentor ? $appointment->mentor->expertise : 'N/A',
                'date' => $appointment->date,
                'status' => $appointment->status,
                'requestedAt' => $appointment->created_at,
            ];
        });

        return response()->json($formattedAppointments, 200);
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
            'date' => 'required|date|after:today',
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
            'mentor_id' => $validated['mentor_id'],
            'leader_id' => $leaderId,
            'date' => $validated['date'],
            'status' => 'pending',
        ]);

        return response()->json($appointment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $appointment = Appointment::with('mentor')->findOrFail($id);
        } else {
            $appointment = Appointment::where('id', $id)
                ->where('leader_id', $user->id)
                ->with('mentor') // Eager load the mentor relationship
                ->first();

            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found or unauthorized'], 404);
            }
        }

        $formattedAppointment = [
            'id' => $appointment->id,
            'mentorName' => $appointment->mentor ? $appointment->mentor->firstName . ' ' . $appointment->mentor->lastName : 'N/A',
            'mentor_expertise' => $appointment->mentor ? $appointment->mentor->expertise : 'N/A',
            'date' => $appointment->date,
            'status' => $appointment->status,
            'requestedAt' => $appointment->created_at,
        ];

        return response()->json($formattedAppointment, 200);
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
            'status' => 'required|in:accepted,declined,cancelled,completed',
        ]);

        $appointment->update(['status' => $validated['status']]);

        return response()->json($appointment, 200);
    }

    public function cancelAppointment(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $appointment = Appointment::findOrFail($id);
        } else {
            $appointment = Appointment::where('id', $id)
                ->where('leader_id', $user->id)
                ->firstOrFail();
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json($appointment, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Admin can delete any appointment
            $appointment = Appointment::find($id);
        } else {
            // Leader can delete only their own appointments
            $appointment = Appointment::where('id', $id)
                ->where('leader_id', $user->id)
                ->first();
        }

        if (!$appointment) {
            return response()->json(['error' => 'Unauthorized or appointment not found'], 403);
        }

        $appointment->delete();

        return response()->json(['message' => 'Appointment cancelled'], 200);
    }

    // Get all appointments made by a leader in a startup group
    public function getLeaderAppointments($startup_profile_id)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $appointments = Appointment::with('mentor')->get();
        } else {
            $startupProfile = StartupProfile::findOrFail($startup_profile_id);
            $leaderId = $startupProfile->leader_id;

            if ($user->id !== $leaderId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $appointments = Appointment::where('leader_id', $leaderId)
                ->with('mentor') // Eager load the mentor relationship
                ->get();
        }

        // Format the response to include mentorName and mentor_expertise
        $formattedAppointments = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'mentorName' => $appointment->mentor ? $appointment->mentor->firstName . ' ' . $appointment->mentor->lastName : 'N/A',
                'mentor_expertise' => $appointment->mentor ? $appointment->mentor->expertise : 'N/A',
                'date' => $appointment->date,
                'status' => $appointment->status,
                'requestedAt' => $appointment->created_at,
            ];
        });

        return response()->json($formattedAppointments, 200);
    }
}