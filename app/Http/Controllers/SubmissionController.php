<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    //
    public function index()
    {
        return response()->json(Submission::all(), 200);
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
        return response()->json($submission, 200);
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

        return response()->download($path, $filename)->setHeaders([
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
