<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    //
    use HasFactory;


    protected $fillable = [
        'name',
        'role',
        'course',
        'startup_profile_id',
    ];  

    public function startupprofile()
    {
        return $this->belongsTo(Startupprofile::class);
    }

}
