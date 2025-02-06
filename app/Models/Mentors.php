<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Mentors extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mentors';
    protected $fillable = [
        'firstName', 
        'lastName', 
        'email', 
        'phoneNumber', 
        'organization', 
        'expertise', 
        'yearsOfExperience', 
        'availability'
    ];

    protected $casts = [
        'yearsOfExperience' => 'integer',
    ];
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}