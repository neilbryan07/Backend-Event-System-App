<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name'];

    // Optional: if you want users associated with the role
    public function users()
    {
        return $this->hasMany(User::class);
    }
}

