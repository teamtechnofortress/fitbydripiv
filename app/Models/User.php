<?php

namespace App\Models;

use App\Models\LoginHistory;
use App\Models\StaffPayroll;
use App\Traits\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

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
        'require_signature',
        'profile_step',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'profile_completed_at' => 'datetime',
    ];

    public function personalInfo()
    {
        return $this->hasOne('App\\Models\\PersonalInfo', 'userId', 'id');
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function staffpayroll()
    {
        return $this->hasOne(StaffPayroll::class, 'staff_id', 'id');
    }

    /**
     * Validate the provided OTP against the stored two factor secret.
     */
    public function validateTwoFactorCode(string $code): bool
    {
        if (! $this->two_factor_secret || ! $code) {
            return false;
        }

        $provider = app(TwoFactorAuthenticationProvider::class);

        return $provider->verify(
            Fortify::currentEncrypter()->decrypt($this->two_factor_secret),
            $code
        );
    }
}
