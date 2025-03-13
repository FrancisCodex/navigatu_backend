<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    // index function
    public function index()
    {
        return response()->json(Activity::all(), 200);
    }

    // store function
    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_name' => 'required|string',
            'module' => 'required|string',
            'session' => 'required|string',
            'activity_description' => 'nullable|string',
            'speaker_name' => 'required|string',
            'TBI' => 'required|string',
            'due_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:20240', // 20MB only pdf or word files
        ]);

        $filepath = null;
        if ($request->hasFile('file')) {
            $filepath = $request->file('file')->store('activities');
        }

        $activity = Activity::create(
            array_merge($validated, ['activityFile_path' => $filepath])
        );

        return response()->json(['message' => 'Activity Created', 'activity' => $activity], 201);
    }

    // show function
    public function show($id)
    {
        $activity = Activity::findOrFail($id);

        // Include the file URL in the response
        $activity->file_url = $activity->activityFile_path ? Storage::url($activity->activityFile_path) : null;

        return response()->json($activity, 200);
    }

    // update function
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $validated = $request->validate([
            'activity_name' => 'required|string',
            'module' => 'required|string',
            'session' => 'required|string',
            'activity_description' => 'nullable|string',
            'speaker_name' => 'required|string',
            'TBI' => 'required|string',
            'due_date' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:20240', // 20MB only pdf or word files
        ]);

        if ($request->hasFile('file')) {
            // Delete the old file if it exists
            if ($activity->activityFile_path) {
                Storage::delete($activity->activityFile_path);
            }

            // Store the new file
            $filepath = $request->file('file')->store('activities');
            $validated['activityFile_path'] = $filepath;
        }

        $activity->update($validated);

        return response()->json($activity, 200);
    }

    // destroy function
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return response()->json(null, 204);
    }

    // Check if how many incubatees have submitted to this activity
    public function activitysubmission($id)
    {
        $activity = Activity::findOrFail($id);
        // check how many users are incubatees to check a total of submissions
        $incubatees = User::where('role', 'incubatee')->get();
        $totalIncubatees = $incubatees->count();
        $totalSubmissions = Submission::where('activity_id', $activity->id)->count();
        return response()->json([
            'total_incubatees' => $totalIncubatees,
            'total_submissions' => $totalSubmissions
        ], 200);
    }

    public function activityReport()
    {
        // Fetch all activities
        $activities = Activity::all();

        // Get total number of incubatees
        $totalIncubatees = User::where('role', 'incubatee')->count();

        // Prepare the response array
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

        return response()->json($report, 200);
    }


}