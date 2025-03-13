<?php

namespace App\Http\Controllers\Incubatees;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StartupProfile;
use Illuminate\Http\Request;

class IncubateesController extends Controller
{
    // This Controller is meant for Admins to view all incubatees

    // Get all users with role incubatee
    public function index()
    {
        $incubatees = User::where('role', 'incubatee')->get();
        return response()->json($incubatees, 200);
    }

    // Get a specific incubatee
    public function show($id)
    {
        $incubatee = User::findOrFail($id);
        return response()->json($incubatee, 200);
    }

    // Get all incubatees without a startup profile
    public function incubateesWithoutStartupProfile()
    {
        // Get all incubatees
        $incubatees = User::where('role', 'incubatee')->get();

        // Get all leader_ids from startup_profiles
        $leadersWithStartup = StartupProfile::pluck('leader_id')->toArray();

        // Filter incubatees who do not have a startup profile
        $incubateesWithoutStartup = $incubatees->filter(function ($incubatee) use ($leadersWithStartup) {
            return !in_array($incubatee->id, $leadersWithStartup);
        });

        // Transform the collection to only include id, name, and email
        $incubateesWithoutStartup = $incubateesWithoutStartup->map(function ($incubatee) {
            return [
                'id' => $incubatee->id,
                'name' => $incubatee->name,
                'email' => $incubatee->email,
            ];
        });

        return response()->json($incubateesWithoutStartup, 200);
    }
}