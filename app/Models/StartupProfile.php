<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StartupProfile extends Model
{
     //
     use HasFactory;

     protected $table = 'startup_profiles';
     protected $fillable = [
         'startup_name',
         'industry',
         'leader_id',
         'date_registered_dti',
         'date_registered_bir',
         'startup_founded',
         'startup_description',
         'status'
     ];
 
     public function members()
     {
         return $this->hasMany(Member::class);
     }
 
     public function achievements()
     {
         return $this->hasMany(Achievement::class);
     }

     public function leader()
     {
         return $this->belongsTo(User::class, 'leader_id');
     }
}
