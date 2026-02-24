<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Notifiable;
use App\Models\LoginHistory;
use App\Models\StaffPayroll;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'role',
        'password',
        'birthday',
        'ssn',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'emergency',
        'contact',
        'gender',
        'hourly_rate',
        'hiring_date',
        'title',
        'payment_method',
        'bank',
        'routing',
        'signature',
        'status',
        'password_reset',
        'require_signature'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function personalInfo(){
        return $this->hasOne('App\Models\PersonalInfo', 'userId', 'id');
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function staffpayroll(){        
        return $this->hasOne(StaffPayroll::class, 'staff_id', 'id');
    }

}
