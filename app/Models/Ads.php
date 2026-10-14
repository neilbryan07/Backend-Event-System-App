<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file',
        'description',
        'created_by',
    ];

public function user()
{
    return $this->belongsTo(User::class, 'created_by');
}

}
