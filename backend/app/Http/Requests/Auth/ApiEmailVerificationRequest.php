<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

class ApiEmailVerificationRequest extends BaseEmailVerificationRequest
{
    /**
     * Cache the resolved user instance.
     */
    protected ?User $resolvedUser = null;

    /**
     * Resolve the user associated with the verification link.
     */
    public function user($guard = null)
    {
        if ($this->resolvedUser) {
            return $this->resolvedUser;
        }

        if ($guardUser = parent::user($guard)) {
            return $this->resolvedUser = $guardUser;
        }

        $userId = $this->route('id');

        if (is_null($userId)) {
            abort(404);
        }

        $this->resolvedUser = User::findOrFail($userId);

        return $this->resolvedUser;
    }
}
