<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;
    protected $table = 'activity';
    protected $fillable = [
        'activity_name',
        'module',
        'session',
        'activity_description',
        'speaker_name',
        'TBI',
        'due_date',
        'activityFile_path',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];


}
