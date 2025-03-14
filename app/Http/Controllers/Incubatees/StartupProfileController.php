<?php

namespace App\Http\Controllers\Incubatees;

use App\Http\Controllers\Controller;
use App\Models\StartupProfile;
use App\Models\User;
use App\Models\Member;
use App\Models\Achievement;
use App\Models\Submission;
use App\Models\Appointment;
use App\Models\Activity;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StartupProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Assume the user is an admin and can access all startup profiles with pagination
        $query = StartupProfile::query();

        // Check if a search query is provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('startup_name', 'ILIKE', "%{$search}%")
                ->orWhere('industry', 'ILIKE', "%{$search}%")
                ->orWhereHas('leader', function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                });
        }

        // Paginate the results
        $startupProfiles = $query->paginate(6);

        // Add total_members and leader_name to each startup profile
        $startupProfiles->getCollection()->transform(function ($startupProfile) {
            $totalMembers = Member::where('startup_profile_id', $startupProfile->id)->count();
            $startupProfile->total_members = $totalMembers;
            // Add leader's name
            $leader = User::findOrFail($startupProfile->leader_id);
            $startupProfile->leader_name = $leader->name;
            return $startupProfile;
        });

        return response()->json($startupProfiles);
    }

    public function store(Request $request)
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'startup_name' => 'required|string|max:255',
                'industry' => 'required|string|max:255',
                'leader_id' => 'required|exists:users,id',
                'date_registered_dti' => 'nullable|date',
                'date_registered_bir' => 'nullable|date',
                'startup_founded' => 'required|string|max:255',
                'startup_description' => 'nullable|string',
                'status' => 'in:Active,Inactive',
            ]);

            // Create the startup profile
            $startupProfile = StartupProfile::create($validated);

            // Add the new leader as a member of the startup
            $leader = User::findOrFail($validated['leader_id']);
            $member = new Member();
            $member->name = $leader->name;
            $member->course = 'N/A';
            $member->role = 'CEO';
            $member->startup_profile_id = $startupProfile->id;
            $member->save();

            // Return a success response
            return response()->json($startupProfile, 201);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON error message
            return response()->json(['error' => 'Something went wrong! ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $startupProfile = StartupProfile::findOrFail($id);

        // Count the total members associated with this startup profile
        $totalMembers = Member::where('startup_profile_id', $id)->count();

        // Add the total_members and leader_name to the startup profile data
        $startupProfileData = $startupProfile->toArray();
        $startupProfileData['total_members'] = $totalMembers;
        $leader = User::findOrFail($startupProfile->leader_id);
        $startupProfileData['leader_name'] = $leader->name;

        return response()->json($startupProfileData);
    }

    public function update(Request $request, $id)
    {
        $profile = StartupProfile::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'admin') {
            // Admin can only update the status
            $validated = $request->validate([
                'status' => 'required|in:Active,Inactive',
            ]);
        } else {
            // Other users can update all fields
            $validated = $request->validate([
                'startup_name' => 'required|string|max:255',
                'industry' => 'required|string|max:255',
                'leader_id' => 'required|exists:users,id',
                'date_registered_dti' => 'nullable|date',
                'date_registered_bir' => 'nullable|date',
                'startup_founded' => 'required|string|max:255',
                'startup_description' => 'nullable|string',
                'status' => 'required|in:Active,Inactive',
            ]);
        }

        $profile->update($validated);

        return response()->json($profile);
    }

    public function myStartupProfile()
    {
        $user = Auth::user();
        $startupProfile = StartupProfile::where('leader_id', $user->id)->first();

        if (!$startupProfile) {
            return response()->json(['error' => 'Unauthorized or no startup profile found'], 403);
        }

        // Get members of the startup profile
        $members = Member::where('startup_profile_id', $startupProfile->id)->get();

        // Get achievements of the startup profile
        $achievements = Achievement::where('startup_profile_id', $startupProfile->id)->get();

        // Get activities submitted by the user
        $activitiesSubmitted = Submission::where('user_id', $user->id)
            ->with('activity:id,activity_name,module,TBI,due_date')
            ->get()
            ->map(function ($submission) {
                return [
                    'submission_id' => $submission->id,
                    'activity_id' => $submission->activity_id,
                    'activity_name' => $submission->activity->activity_name,
                    'module' => $submission->activity->module,
                    'TBI' => $submission->activity->TBI,
                    'due_date' => $submission->activity->due_date,
                    'submitted_at' => $submission->created_at,
                ];
            });

        // Get appointments made by the leader
        $appointments = Appointment::where('leader_id', $user->id)->get();

        // Get documents of the startup profile
        $documents = Document::where('startup_profile_id', $startupProfile->id)->get();

        return response()->json([
            'startup_profile' => $startupProfile,
            'members' => $members,
            'achievements' => $achievements,
            'activities_submitted' => $activitiesSubmitted,
            'appointments' => $appointments,
            'documents' => $documents
        ]);
    }
}