<?php

namespace App\Http\Controllers\Incubatees;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\StartupProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    // Fetch members of a specific startup profile
    public function index($startup_profile_id)
    {
        // Fetch all members for the specific startup profile
        $members = Member::where('startup_profile_id', $startup_profile_id)->get();
        return response()->json($members);
    }
    
    // Store a new member in a specific startup profile
    public function store(Request $request, $startup_profile_id)
    {
        try {
            $user = Auth::user();

            // Check if the user is the leader of the startup group
            $startupProfile = StartupProfile::findOrFail($startup_profile_id);
            if ($startupProfile->leader_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized, You are not the leader or a Member of this Team'], 403);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'course' => 'required|string|max:255',
                'role' => 'required|string|max:255',
            ]);

            $member = Member::create(
                array_merge($validatedData, ['startup_profile_id' => $startupProfile->id])
            );

            return response()->json([
                'message' => 'Member added successfully',
                'member' => $member
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing member of a specific startup profile
    public function update(Request $request, StartupProfile $startupProfile, $memberId)
    {
        $member = $startupProfile->members()->findOrFail($memberId);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'role' => 'required|string|max:255',
        ]);

        // Explicitly set startup_profile_id
        $validatedData['startup_profile_id'] = $startupProfile->id;

        $member->update($validatedData);

        return response()->json(['message' => 'Member updated successfully', 'member' => $member]);
    }

    // Delete a member from a specific startup profile
    public function destroy(StartupProfile $startupProfile, $memberId)
    {
        $member = $startupProfile->members()->findOrFail($memberId);
        $member->delete();

        return response()->json(['message' => 'Member deleted successfully']);
    }

    // Count members of a specific startup profile
    public function countMembers($startup_profile_id)
    {
        $startupProfile = StartupProfile::findOrFail($startup_profile_id);
        return response()->json(['total_members' => $startupProfile->members->count()]);
    }
}