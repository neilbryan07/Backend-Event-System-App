<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
 protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'campaign_id',
        'gender',
        'branch_id',
        'mobile_no',
        'company_information',
        'school',
        'status',
        'age',
        'date_hired'
    ];

    public function role(){ return $this->belongsTo(Role::class); }
    public function campaign(){ return $this->belongsTo(Campaign::class); }
    public function userEventStatuses(){ return $this->hasMany(UserEventStatus::class); }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function branch()
{
    return $this->belongsTo(Branch::class);
}

}
