<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
    ];

    // Optional: if you want campaigns linked to a branch
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
    public function branch() {
    return $this->belongsTo(Branch::class, 'branch_id');
}
}