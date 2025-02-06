<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Appointment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'mentor_id',
        'leader_id',
        'appointment_date',
        'status',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
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
