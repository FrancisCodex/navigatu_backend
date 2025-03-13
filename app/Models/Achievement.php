<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Achievement extends Model
{
    //

    use HasFactory;

    protected $fillable = [
        'competition_name',
        'organized_by',
        'date_achieved',
        'prize_amount',
        'startup_profile_id',
        'photos',
        'category',
        'description',
        'event_location',
        'article_link'
    ];
    
    public function startupprofile()
    {
        return $this->belongsTo(Startupprofile::class);
    }

}
