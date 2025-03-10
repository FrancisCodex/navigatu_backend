<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    //

    use HasFactory;
    
    protected $fillable = [
        'activity_id',
        'user_id',
        'file_path',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
