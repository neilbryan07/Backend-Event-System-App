<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class EventDay extends Model
{
    protected $fillable = [
        'event_id',
        'day_name',   // new string
        'event_start',   // new datetime
        'qr_token',
        'event_cutoff',
        'qr_image'
    ];

    protected $casts = [
        'event_start' => 'datetime',
        'event_cutoff' => 'datetime',
    ];

    public function event() { return $this->belongsTo(Event::class); }
    public function userEventStatuses() { return $this->hasMany(UserEventStatus::class); }
}