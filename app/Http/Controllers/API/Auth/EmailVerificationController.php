<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiEmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    /**
     * Return the verification status for the authenticated user.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
        ]);
    }

    /**
     * Send the verification notification if the user is not already verified.
     */
    public function send(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    /**
     * Fulfill the verification request coming from the signed URL.
     */
    public function verify(ApiEmailVerificationRequest $request)
    {
        $user = $request->user();

        Log::info('Email verification link accessed.', [
            'user_id' => optional($user)->id,
            'email' => optional($user)->email,
            'request_url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        if (! $user) {
            Log::warning('Email verification attempted but no user could be resolved.', [
                'request_url' => $request->fullUrl(),
            ]);

            abort(404);
        }

        if (! $user->hasVerifiedEmail()) {
            $request->fulfill();

            Log::info('Email verification fulfilled.', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } else {
            Log::info('Email verification skipped because the user is already verified.', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }
}
