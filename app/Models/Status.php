<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['name'];

    public function userEventStatuses()
    {
        return $this->hasMany(UserEventStatus::class, 'status_id');
    }
}