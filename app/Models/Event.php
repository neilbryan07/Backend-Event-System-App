<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['name','description','instruction','address','image','total_attendees','registration_cutoff'];

    public function days()
    {
        return $this->hasMany(EventDay::class);
    }
}