<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Document extends Model
{
    //

    use HasFactory;

    protected $table = 'business_documents';
    protected $fillable = [
        'dti_registration',
        'bir_registration',
        'sec_registration',
        'startup_profile_id',
    ];

    public function startupprofile()
    {
        return $this->belongsTo(Startupprofile::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
