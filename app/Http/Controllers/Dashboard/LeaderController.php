<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\StartupProfile;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;

class LeaderController extends Controller
{
    // get All Leader Details with their startup profile and role in the startup
    // Display only startup_name, industry, name, role(from member table), phone, email
    public function index()
    {
        $leaders = User::where('role', 'incubatee')->paginate(8);

        $leaders->getCollection()->transform(function ($leader) {
            $startupProfile = StartupProfile::where('leader_id', $leader->id)->first();
            if (!$startupProfile) {
                return null; // Skip if no startup profile found
            }

            $member = Member::where('startup_profile_id', $startupProfile->id)->where('name', $leader->name)->first();
            if (!$member) {
                return null; // Skip if no member found
            }

            return [
                'startup_name' => $startupProfile->startup_name,
                'industry' => $startupProfile->industry,
                'name' => $leader->name,
                'role' => $member->role,
                'phone' => $leader->phone,
                'email' => $leader->email,
                'startup_status' => $startupProfile->status,
                'startupId' => $startupProfile->id,
            ];
        });

        // Remove null values
        $leaders->setCollection($leaders->getCollection()->filter());

        return response()->json($leaders);
    }
}