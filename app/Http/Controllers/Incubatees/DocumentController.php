<?php

namespace App\Http\Controllers\Incubatees;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\StartupProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index($startup_profile_id)
    {
        $user = Auth::user();

        $startupProfile = StartupProfile::findOrFail($startup_profile_id);

        if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $documents = Document::where('startup_profile_id', $startup_profile_id)->first();

        return response()->json($documents);
    }

    // Show a specific document
    public function show($startup_profile_id, $documentType)
    {
        $user = Auth::user();

        $startupProfile = StartupProfile::findOrFail($startup_profile_id);

        if ($startupProfile->leader_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $documents = Document::where('startup_profile_id', $startup_profile_id)->first();

        if (!$documents || !$documents->$documentType) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $fileUrl = Storage::disk('public')->url($documents->$documentType);

        return response()->json(['file_url' => $fileUrl]);
    }

    public function upload(Request $request, $startup_profile_id)
    {
        $request->validate([
            'dti_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'bir_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'sec_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        try {
            $startupProfile = StartupProfile::findOrFail($startup_profile_id);

            $documents = Document::firstOrCreate(
                ['startup_profile_id' => $startupProfile->id]
            );

            $documentTypes = ['dti_registration', 'bir_registration', 'sec_registration'];
            $uploadedPaths = [];
            $userFolder = "business-documents/user_{$startupProfile->leader_id}";

            // Check if all required files are already uploaded
            $allFilesUploaded = true;
            foreach ($documentTypes as $documentType) {
                if (empty($documents->$documentType)) {
                    $allFilesUploaded = false;
                    break;
                }
            }

            if ($allFilesUploaded) {
                return response()->json([
                    'message' => 'Already uploaded all required files'
                ], 400);
            }

            foreach ($documentTypes as $documentType) {
                if ($request->hasFile($documentType)) {
                    if (!empty($documents->$documentType)) {
                        return response()->json([
                            'message' => "The {$documentType} file has already been uploaded"
                        ], 400);
                    }

                    $file = $request->file($documentType);
                    $extension = $file->getClientOriginalExtension();
                    $fileName = "{$startupProfile->startup_name}_{$documentType}.{$extension}";
                    $filePath = $file->storeAs($userFolder, $fileName, 'public');
                    $documents->$documentType = $filePath;
                    $uploadedPaths[$documentType] = $filePath;
                }
            }

            $documents->save();

            return response()->json([
                'message' => 'Documents uploaded successfully',
                'paths' => $uploadedPaths
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error uploading documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($documentId)
    {
        try {
            // Find the document
            $document = Document::findOrFail($documentId);

            // Delete the document files from storage
            $documentTypes = ['dti_registration', 'bir_registration', 'sec_registration'];
            foreach ($documentTypes as $documentType) {
                if ($document->$documentType) {
                    Storage::disk('public')->delete($document->$documentType);
                }
            }

            // Delete the document record from the database
            $document->delete();

            return response()->json(['message' => 'Document deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting document', 'error' => $e->getMessage()], 500);
        }
    }

    public function download($documentId)
    {
        try {
            // Find the document
            $document = Document::findOrFail($documentId);

            // Get the startup profile details
            $startupProfile = StartupProfile::findOrFail($document->startup_profile_id);

            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is an admin or the leader of the startup
            if ($user->role !== 'admin' && $user->id !== $startupProfile->leader_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Determine which document type has the file path
            $documentTypes = ['dti_registration', 'bir_registration', 'sec_registration'];
            $filePath = null;
            $fileType = null;

            foreach ($documentTypes as $documentType) {
                if ($document->$documentType) {
                    $filePath = $document->$documentType;
                    $fileType = $documentType;
                    break;
                }
            }

            if (!$filePath) {
                return response()->json(['message' => 'Document not found'], 404);
            }

            $path = storage_path("app/public/{$filePath}");

            if (!file_exists($path)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            // Use PHP's built-in function to get the MIME type
            $mimeType = mime_content_type($path);

            // Fallback: Define MIME types manually for common extensions
            if (!$mimeType) {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $mimeTypes = [
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'pdf'  => 'application/pdf',
                    'doc'  => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ];
                $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            }

            return response()->download($path, basename($path), ['Content-Type' => $mimeType]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error downloading document', 'error' => $e->getMessage()], 500);
        }
    }
}