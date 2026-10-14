<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEventDayVerified extends Model
{
    protected $fillable = [
        'user_id',
        'event_day_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function eventDay()
    {
        return $this->belongsTo(EventDay::class);
    }
}