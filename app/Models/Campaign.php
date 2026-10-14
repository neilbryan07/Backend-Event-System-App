<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['name', 'om_id', 'branch_id'];

    public function om()
    {
        return $this->belongsTo(User::class, 'om_id');
    }
public function omUser()
{
    return $this->belongsTo(User::class, 'om_id');
}
    // ✅ Relationship to branch
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'campaign_id');
    }
}