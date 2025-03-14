<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Activity;
use App\Models\User;
use App\Models\StartupProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    //
    public function index($activityId)
    {
        //get all the submissions for a specific activity get the user name, startup name, activity name
        $submissions = Submission::where('activity_id', $activityId)->get();

        //get the name of the users who submitted the output
        $submissions->transform(function ($submission) {
            $user = User::findOrFail($submission->user_id);
            $activity = Activity::findOrFail($submission->activity_id);
            $startupProfile = StartupProfile::where('leader_id', $user->id)->first();

            return [
                'id' => $submission->id,
                'leader_name' => $user->name,
                'startup_name' => $startupProfile->startup_name,
                'activity_name' => $activity->activity_name,
                'activity_id' => $submission->activity_id,
                'user_id' => $submission->user_id,
                'file_path' => $submission->file_path,
                'submission_date' => $submission->created_at,
                'graded' => $submission->graded,
            ];
        });

        return response()->json($submissions);
    }

    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:20240', // 20MB only pdf or word files
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs('submissions', $fileName, 'public');

        $submission = Submission::create([
            'user_id' => Auth::id(),
            'activity_id' => $id,
            'file_path' => $filePath,
        ]);

        return response()->json(['message' => 'Submission Created', 'submission' => $submission], 201);
    }

    public function show($id)
    {
        $submission = Submission::findOrFail($id);
        $user = User::findOrFail($submission->user_id);
        $activity = Activity::findOrFail($submission->activity_id);
        $startupProfile = StartupProfile::where('leader_id', $user->id)->first();

        $submissionData = [
            'id' => $submission->id,
            'leader_name' => $user->name,
            'startup_name' => $startupProfile ? $startupProfile->startup_name : 'N/A',
            'activity_name' => $activity->activity_name,
            'activity_id' => $submission->activity_id,
            'user_id' => $submission->user_id,
            'file_path' => $submission->file_path,
            'submission_date' => $submission->created_at,
            'graded' => $submission->graded,
        ];

        return response()->json($submissionData, 200);
    }
    

    //destroy

    public function destroy($id)
    {
        $submission = Submission::findOrFail($id);
        Storage::delete($submission->file_path);
        $submission->delete();
        return response()->json(['message' => 'Submission Deleted'], 200);
    }

    
    //download
    public function download($id)
    {
        $submission = Submission::findOrFail($id);

        if (!$submission || !$submission->file_path) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $path = storage_path('app/public/' . $submission->file_path);
        $filename = basename($submission->file_path); // Get the correct filename

        if (!file_exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($path, $filename, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function gradeSubmission(Request $request, $id)
    {
        // Validate the request to ensure 'grade' is present and is a boolean
        $validated = $request->validate([
            'grade' => 'required|boolean',
        ]);

        // Check if the grade is true
        if ($validated['grade'] === true) {
            $submission = Submission::findOrFail($id);
            $submission->update(['graded' => true]);
            return response()->json(['message' => 'Submission Graded', 'submission' => $submission], 200);
        }

        return response()->json(['message' => 'Invalid grade value'], 400);
    }



    // check if user has submitted an output for an activity
    public function checkSubmission($activity_id)
    {
        $submission = Submission::where('activity_id', $activity_id)
            ->where('user_id', Auth::id())
            ->first();

        if ($submission) {
            $file_url = Storage::url($submission->file_path);
            $filename = basename($submission->file_path);
            return response()->json([
                'submitted' => true,
                'submission' => $submission,
                'file_url' => $file_url,
                'filename' => $filename
            ], 200);
        } else {
            return response()->json(['submitted' => false], 200);
        }
    }

}
