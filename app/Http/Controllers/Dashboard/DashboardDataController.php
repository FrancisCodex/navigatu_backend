<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StartupProfile;
use App\Models\Member;
use App\Models\Appointment;
use App\Models\Submission;
use App\Models\Activity;
use App\Models\Document;
use App\Models\Achievement;
use App\Models\Mentors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DashboardDataController extends Controller
{
    public function index()
    {
        $startups = User::where('role', 'incubatee')->get();
        return response()->json($startups, 200);
    }

    public function getStartupProfile($id)
    {
        // Get the startup profile of the specific startup
        $startupProfile = StartupProfile::findOrFail($id);

        // Get the leader of the startup
        $leader = User::findOrFail($startupProfile->leader_id);

        // Get members of the startup team
        $members = Member::where('startup_profile_id', $startupProfile->id)->get();

        // Get achievements of the startup team
        $achievements = Achievement::where('startup_profile_id', $startupProfile->id)->get();
        $achievements = $achievements->map(function ($achievement) {
            $startupProfile = StartupProfile::findOrFail($achievement->startup_profile_id);
            $photoPaths = json_decode($achievement->photos, true) ?? [];
            $photoUrls = array_map(fn($path) => Storage::url($path), $photoPaths);
            return [
                'id' => $achievement->id,
                'startup_profile_id' => $achievement->startup_profile_id,
                'competition_name' => $achievement->competition_name,
                'description' => $achievement->description,
                'date_achieved' => $achievement->date_achieved,
                'achievement_photos' => $photoUrls,
                'startup_name' => $startupProfile->startup_name,
                'category' => $achievement->category,
                'event_location' => $achievement->event_location,
                'prize_amount' => $achievement->prize_amount,
                'created_at' => $achievement->created_at,
                'article_link' => $achievement->article_link,
            ];
        });

        // Get appointments made by the leader of the startup team
        $appointments = Appointment::where('leader_id', $leader->id)->get();

        // Map appointments to include mentor's name
        $appointments = $appointments->map(function ($appointment) {
            $mentor = Mentors::findOrFail($appointment->mentor_id);
            return [
                'id' => $appointment->id,
                'mentorName' => $mentor->firstName . ' ' . $mentor->lastName,
                'mentor_email' => $mentor->email,
                'mentor_id' => $mentor->id,
                'leader_id' => $appointment->leader_id,
                'date' => $appointment->date,
                'requestedAt' => $appointment->created_at,
                'status' => $appointment->status,
            ];
        });

        // Get documents of the startup team
        $documents = Document::where('startup_profile_id', $startupProfile->id)->get();

        // Get activities submitted by the startup team and include leader's name
        $submissions = Submission::where('user_id', $startupProfile->leader_id)
            ->with('activity:id,activity_name,module,TBI,due_date', 'user:id,name')
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
                    'graded' => $submission->graded,
                    'submission_file' => $submission->file_path,
                    'leader_name' => $submission->user->name,
                ];
            });

        return response()->json([
            'startup_profile' => $startupProfile,
            'leader' => $leader,
            'members' => $members,
            'achievements' => $achievements,
            'appointments' => $appointments,
            'documents' => $documents,
            'submissions' => $submissions,
        ], 200);
    }

    public function getDashboardDetails()
    {
        // check if the user is an admin
        $user = Auth::user();
        if ($user->isAdmin()) {
            // get all only 3 startup details for the dashboard
            $startups = User::where('role', 'incubatee')->limit(3)->get();
            // get all recent appointments
            $appointments = Appointment::orderBy('created_at', 'desc')->limit(3)->get();

            $formattedAppointments = $appointments->map(function ($appointment) {
                // include incubateeName from joining the user table
                $incubatee = User::findOrFail($appointment->leader_id);
                $mentor = Mentors::findOrFail($appointment->mentor_id);
                return [
                    'id' => $appointment->id,
                    'incubateeName' => $incubatee->name,
                    'mentorName' => $mentor->firstName . ' ' . $mentor->lastName,
                    'mentor_expertise' => $mentor->expertise,
                    'mentor_id' => $mentor->id,
                    'date' => $appointment->date,
                    'status' => $appointment->status,
                    'requestedAt' => $appointment->created_at,
                ];
            });
            // get 3 activities
            $activities = Activity::limit(3)->get();

            $totalIncubatees = User::where('role', 'incubatee')->count();

            $report = $activities->map(function ($activity) use ($totalIncubatees) {
                $totalSubmissions = Submission::where('activity_id', $activity->id)->count();
                $progress = $totalIncubatees > 0 ? ($totalSubmissions / $totalIncubatees) * 100 : 0;

                return [
                    'id' => $activity->id,
                    'activity_name' => $activity->activity_name,
                    'module' => $activity->module,
                    'session' => $activity->session,
                    'activity_description' => $activity->activity_description,
                    'speaker_name' => $activity->speaker_name,
                    'TBI' => $activity->TBI,
                    'due_date' => $activity->due_date,
                    'activityFile_path' => $activity->activityFile_path ? Storage::url($activity->activityFile_path) : 'No file uploaded',
                    'created_at' => $activity->created_at,
                    'updated_at' => $activity->updated_at,
                    'total_incubatees' => $totalIncubatees,
                    'total_submissions' => $totalSubmissions,
                    'progress' => round($progress, 2), // Round progress to 2 decimal places
                    'completed' => $progress >= 100 ? 1 : 0 // Mark as completed if 100%
                ];
            });

            //get 3 startup profiles include leader_name by joining the user table
            $startupProfiles = StartupProfile::limit(3)->get();
            $startupProfiles = $startupProfiles->map(function ($startupProfile) {
                $leader = User::findOrFail($startupProfile->leader_id);
                $members = Member::where('startup_profile_id', $startupProfile->id)->count();
                return [
                    'id' => $startupProfile->id,
                    'leader_name' => $leader->name,
                    'startup_name' => $startupProfile->startup_name,
                    'startup_founded' => $startupProfile->startup_founded,
                    'industry' => $startupProfile->industry,
                    'status' => $startupProfile->status,
                    'total_members' => $members,
                ];
            });

            // get 3 mentors details
            $mentors = Mentors::limit(3)->get();

            return response()->json([
                'startups' => $startups,
                'appointments' => $formattedAppointments,
                'activities' => $report,
                'startupProfiles' => $startupProfiles,
                'mentors' => $mentors,
            ], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function getIncubateeDashboardDetails()
    {
        // check if the user is an incubatee
        $user = Auth::user();
        if ($user->isLeader()) {
            // get the startup profile of the incubatee
            $startupProfile = StartupProfile::where('leader_id', $user->id)->first();
            // get the leader of the startup
            $leader = User::findOrFail($startupProfile->leader_id);
            // get members of the startup team
            $members = Member::where('startup_profile_id', $startupProfile->id)->get();
            // get achievements of the startup team
            $achievements = Achievement::where('startup_profile_id', $startupProfile->id)->get();
            // get appointments made by the leader of the startup team with mentors name
            $appointments = Appointment::where('leader_id', $leader->id)->get();
            $appointments = $appointments->map(function ($appointment) {
                $mentor = Mentors::findOrFail($appointment->mentor_id);
                return [
                    'id' => $appointment->id,
                    'mentorName' => $mentor->firstName . ' ' . $mentor->lastName,
                    'mentor_email' => $mentor->email,
                    'mentor_id' => $mentor->id,
                    'leader_id' => $appointment->leader_id,
                    'date' => $appointment->date,
                    'requestedAt' => $appointment->created_at,
                    'status' => $appointment->status,
                ];
            });
            // get documents of the startup team
            $documents = Document::where('startup_profile_id', $startupProfile->id)->get();
            // get activities submitted by the startup team and also get leaders name for user table
            $submissions = Submission::where('user_id', $leader->id)
                ->with('activity:id,activity_name,module,TBI,due_date')
                ->with('user:id,name')
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
                        'graded' => $submission->graded,
                        'submission_file' => $submission->file_path,
                        'leader_name' => $submission->user->name,
                    ];
                });
            //get all activities that the user has not submitted
            $activities = Activity::whereNotIn('id', $submissions->pluck('activity_id'))->get();

            //get 3 mentors details make sure to mentorName combine firstName and lastName
            $mentors = Mentors::limit(3)->get();
            $mentors = $mentors->map(function ($mentor) {
                return [
                    'id' => $mentor->id,
                    'mentorName' => $mentor->firstName . ' ' . $mentor->lastName,
                    'email' => $mentor->email,
                    'phoneNumber' => $mentor->phoneNumber,
                    'yearsOfExperience' => $mentor->yearsOfExperience,
                    'expertise' => $mentor->expertise,
                    'created_at' => $mentor->created_at,
                    'updated_at' => $mentor->updated_at,
                ];
            });

            return response()->json([
                'startup_profile' => $startupProfile,
                'leader' => $leader,
                'members' => $members,
                'achievements' => $achievements,
                'appointments' => $appointments,
                'documents' => $documents,
                'submissions' => $submissions,
                'activities' => $activities,
                'mentors' => $mentors,
            ], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function getIncubateeStartupProfile()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get the startup profile of the authenticated user
        $startupProfile = StartupProfile::where('leader_id', $user->id)->first();

        if (!$startupProfile) {
            return response()->json(['error' => 'Startup profile not found'], 404);
        }

        // Get the leader of the startup
        $leader = User::findOrFail($startupProfile->leader_id);

        // Get members of the startup team
        $members = Member::where('startup_profile_id', $startupProfile->id)->get();

        // Get achievements of the startup team
        $achievements = Achievement::where('startup_profile_id', $startupProfile->id)->get();
        $achievements = $achievements->map(function ($achievement) {
            $startupProfile = StartupProfile::findOrFail($achievement->startup_profile_id);
            $photoPaths = json_decode($achievement->photos, true) ?? [];
            $photoUrls = array_map(fn($path) => Storage::url($path), $photoPaths);
            return [
                'id' => $achievement->id,
                'startup_profile_id' => $achievement->startup_profile_id,
                'competition_name' => $achievement->competition_name,
                'description' => $achievement->description,
                'date_achieved' => $achievement->date_achieved,
                'achievement_photos' => $photoUrls,
                'startup_name' => $startupProfile->startup_name,
                'category' => $achievement->category,
                'event_location' => $achievement->event_location,
                'prize_amount' => $achievement->prize_amount,
                'created_at' => $achievement->created_at,
                'article_link' => $achievement->article_link,
            ];
        });

        // Get appointments made by the leader of the startup team
        $appointments = Appointment::where('leader_id', $leader->id)->get();

        // Map appointments to include mentor's name
        $appointments = $appointments->map(function ($appointment) {
            $mentor = Mentors::findOrFail($appointment->mentor_id);
            return [
                'id' => $appointment->id,
                'mentorName' => $mentor->firstName . ' ' . $mentor->lastName,
                'mentor_email' => $mentor->email,
                'mentor_id' => $mentor->id,
                'leader_id' => $appointment->leader_id,
                'date' => $appointment->date,
                'requestedAt' => $appointment->created_at,
                'status' => $appointment->status,
            ];
        });

        // Get documents of the startup team
        $documents = Document::where('startup_profile_id', $startupProfile->id)->get();
        $documents = $documents->map(function ($document) {
            $fileDetails = [];

            if ($document->dti_registration) {
                $fileDetails[] = [
                    'file_path' => Storage::disk('public')->url($document->dti_registration),
                    'file_name' => basename($document->dti_registration),
                    'file_type' => 'DTI Registration',
                    'file_size' => Storage::disk('public')->size($document->dti_registration),
                ];
            }

            if ($document->bir_registration) {
                $fileDetails[] = [
                    'file_path' => Storage::disk('public')->url($document->bir_registration),
                    'file_name' => basename($document->bir_registration),
                    'file_type' => 'BIR Registration',
                    'file_size' => Storage::disk('public')->size($document->bir_registration),
                ];
            }

            if ($document->sec_registration) {
                $fileDetails[] = [
                    'file_path' => Storage::disk('public')->url($document->sec_registration),
                    'file_name' => basename($document->sec_registration),
                    'file_type' => 'SEC Registration',
                    'file_size' => Storage::disk('public')->size($document->sec_registration),
                ];
            }

            return [
                'id' => $document->id,
                'startup_profile_id' => $document->startup_profile_id,
                'files' => $fileDetails,
            ];
        });

        // Get activities submitted by the startup team and include leader's name
        $submissions = Submission::where('user_id', $leader->id)
            ->with('activity:id,activity_name,module,TBI,due_date', 'user:id,name')
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
                    'graded' => $submission->graded,
                    'submission_file' => $submission->file_path,
                    'leader_name' => $submission->user->name,
                ];
            });

        return response()->json([
            'startup_profile' => $startupProfile,
            'leader' => $leader,
            'members' => $members,
            'achievements' => $achievements,
            'appointments' => $appointments,
            'documents' => $documents,
            'submissions' => $submissions,
        ], 200);
    }

}