<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\StartupProfile;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;

class LeaderController extends Controller
{
    // Get all leader details with their startup profile and role in the startup
    // Display only startup_name, industry, name, role (from member table), phone, email
    public function index(Request $request)
    {
        $query = User::where('role', 'incubatee');

        // Check if a search query is provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                ->orWhereHas('startupProfile', function ($q) use ($search) {
                    $q->where('startup_name', 'ILIKE', "%{$search}%")
                        ->orWhere('industry', 'ILIKE', "%{$search}%");
                });
            });
        }

        // Paginate the results
        $leaders = $query->paginate(6);

        $leaders->getCollection()->transform(function ($leader) {
            $startupProfile = StartupProfile::where('leader_id', $leader->id)->first();

            if ($startupProfile) {
                $member = Member::where('startup_profile_id', $startupProfile->id)->where('name', $leader->name)->first();
                $role = $member ? $member->role : 'No Role';
                $startupName = $startupProfile->startup_name;
                $startupStatus = $startupProfile->status;
                $startupId = $startupProfile->id;
            } else {
                $role = 'No Role';
                $startupName = 'No Startup';
                $startupStatus = 'Inactive';
                $startupId = null;
            }

            return [
                'startup_name' => $startupName,
                'industry' => $startupProfile ? $startupProfile->industry : 'N/A',
                'name' => $leader->name,
                'role' => $role,
                'phone' => $leader->phone,
                'email' => $leader->email,
                'startup_status' => $startupStatus,
                'startupId' => $startupId,
            ];
        });

        return response()->json($leaders);
    }
}