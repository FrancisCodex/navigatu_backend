<?php

namespace App\Http\Controllers;

use App\Models\Mentors;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MentorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Mentors::all(), 200);
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
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:mentors',
            'phoneNumber' => 'required|string',
            'organization' => 'required|string',
            'expertise' => 'required|string',
            'yearsOfExperience' => 'required|integer|min:0',
            'availability' => 'string',
        ]);

        $validated['id'] = Str::uuid(); // Generate UUID

        //check if the user is authenticated or logged in

        $mentor = Mentors::create($validated);

        return response()->json($mentor, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $mentor = Mentors::findOrFail($id);
        return response()->json($mentor, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mentors $mentors)
    {
        //
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $mentor = Mentors::findOrFail($id);

        $validated = $request->validate([
            'firstName' => 'string|max:255',
            'lastName' => 'string|max:255',
            'email' => 'email|unique:mentors,email,' . $mentor->id,
            'phoneNumber' => 'string',
            'organization' => 'string',
            'expertise' => 'string',
            'yearsOfExperience' => 'integer|min:0',
            'availability' => 'string',
        ]);

        $mentor->update($validated);

        return response()->json($mentor, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $mentor = Mentors::findOrFail($id);
        $mentor->delete();

        return response()->json(['message' => 'Mentor deleted'], 200);
    }
}
