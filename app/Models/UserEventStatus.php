<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEventStatus extends Model {
    protected $fillable = ['user_id','event_day_id','status_id','scanned_at'];
    public function user(){ return $this->belongsTo(User::class); }
    public function eventDay(){ return $this->belongsTo(EventDay::class); }
        public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}