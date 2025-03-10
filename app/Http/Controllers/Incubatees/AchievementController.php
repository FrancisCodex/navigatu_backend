<?php

namespace App\Http\Controllers\Incubatees;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\StartupProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AchievementController extends Controller
{
    // Fetch achievements of a specific startup profile
    public function index($startup_profile_id)
    {
        $user = Auth::user();

        $startupProfile = StartupProfile::findOrFail($startup_profile_id);

        if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Fetch all achievements for the specific startup profile
        $achievements = Achievement::where('startup_profile_id', $startup_profile_id)->get();

        return response()->json($achievements);
    }

    // View a specific achievement
    public function show($startup_profile_id, $achievementId)
    {
        $user = Auth::user();

        $startupProfile = StartupProfile::findOrFail($startup_profile_id);

        if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Fetch the specific achievement
        $achievement = $startupProfile->achievements()->findOrFail($achievementId);

        return response()->json($achievement);
    }

    // Store a new achievement in a specific startup profile
    public function store(Request $request, $startup_profile_id)
    {
        try {
            $user = Auth::user();

            // Retrieve the startup profile
            $startupProfile = StartupProfile::findOrFail($startup_profile_id);

            // Check if the user is the leader of the startup group or an admin
            if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'date_achieved' => 'required|date',
                'competition_name' => 'required|string|max:255',
                'organized_by' => 'required|string|max:255',
                'prize_amount' => 'required|numeric',
                'category' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'event_location' => 'nullable|string|max:255',
                'article_link' => 'nullable|url',
                'photos.*' => 'nullable|file|mimes:jpg,jpeg,png|max:10240', // Validate each photo
            ]);

            // Handle photo uploads
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = $photo->getClientOriginalName();
                    $filePath = $photo->storeAs('achievements', $fileName, 'public');
                    $photoPaths[] = $filePath;
                }
            }

            // Explicitly set startup_profile_id and photos
            $validatedData['startup_profile_id'] = $startupProfile->id;
            $validatedData['photos'] = json_encode($photoPaths);

            $achievement = Achievement::create($validatedData);

            return response()->json([
                'message' => 'Achievement added successfully',
                'achievement' => $achievement
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating achievement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing achievement of a specific startup profile
    public function update(Request $request, $startup_profile_id, $achievementId)
    {
        $user = Auth::user();

        // Retrieve the startup profile
        $startupProfile = StartupProfile::findOrFail($startup_profile_id);

        // Check if the user is the leader of the startup group or an admin
        if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $achievement = $startupProfile->achievements()->findOrFail($achievementId);

        $validatedData = $request->validate([
            'date_achieved' => 'required|date',
            'competition_name' => 'required|string|max:255',
            'organized_by' => 'required|string|max:255',
            'prize_amount' => 'required|numeric',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'event_location' => 'nullable|string|max:255',
            'article_link' => 'nullable|url',
            'photos.*' => 'nullable|file|mimes:jpg,jpeg,png|max:10240', // Validate each photo
        ]);

        // Handle photo uploads
        $photoPaths = json_decode($achievement->photos, true) ?? [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $fileName = $photo->getClientOriginalName();
                $filePath = $photo->storeAs('achievements', $fileName, 'public');
                $photoPaths[] = $filePath;
            }
        }

        // Explicitly set startup_profile_id and photos
        $validatedData['startup_profile_id'] = $startupProfile->id;
        $validatedData['photos'] = json_encode($photoPaths);

        $achievement->update($validatedData);

        return response()->json(['message' => 'Achievement updated successfully', 'achievement' => $achievement]);
    }

    // Delete an achievement from a specific startup profile
    public function destroy($startup_profile_id, $achievementId)
    {
        $user = Auth::user();

        // Retrieve the startup profile
        $startupProfile = StartupProfile::findOrFail($startup_profile_id);

        // Check if the user is the leader of the startup group or an admin
        if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $achievement = $startupProfile->achievements()->findOrFail($achievementId);
        $achievement->delete();

        return response()->json(['message' => 'Achievement deleted successfully']);
    }
}