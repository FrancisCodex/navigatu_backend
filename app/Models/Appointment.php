<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'leader_id',
        'date', 
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function mentor()
    {
        return $this->belongsTo(Mentors::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class);
    }
}
