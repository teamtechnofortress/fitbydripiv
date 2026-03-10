<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Crypt;
    use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
    use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
    use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class TwoFactorController extends Controller
{
    /**
     * Enable two-factor authentication for the authenticated user.
     */
    public function enable(Request $request): JsonResponse
    {
        app(EnableTwoFactorAuthentication::class)($request->user());

        $user = $request->user()->fresh();

        return response()->json([
            'secret' => $user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null,
            'qr' => $user->twoFactorQrCodeSvg(),
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    /**
     * Confirm the user's two-factor authentication configuration.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        app(ConfirmTwoFactorAuthentication::class)(
            $request->user(),
            $validated['code']
        );

        return response()->json(['message' => '2FA confirmed']);
    }

    /**
     * Return the current two-factor authentication status for the user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'enabled' => (bool) $user->two_factor_confirmed_at,
            'confirmed_at' => $user->two_factor_confirmed_at,
        ]);
    }

    /**
     * Disable two-factor authentication after verifying the OTP code.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->two_factor_confirmed_at) {
            return response()->json(['message' => 'Two-factor authentication is not enabled.'], 400);
        }

        app(DisableTwoFactorAuthentication::class)($user);

        return response()->json([
            'message' => 'Two-factor authentication disabled',
            'enabled' => false,
        ]);
    }

    /**
     * Regenerate a new secret, QR, and recovery codes after verifying OTP.
     */
    public function regenerate(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->two_factor_confirmed_at) {
            return response()->json(['message' => 'Two-factor authentication is not enabled.'], 400);
        }

        app(DisableTwoFactorAuthentication::class)($user);
        app(EnableTwoFactorAuthentication::class)($user);

        $freshUser = $user->fresh();

        return response()->json([
            'message' => 'Two-factor authentication secret regenerated',
            'secret' => $freshUser->two_factor_secret ? Crypt::decryptString($freshUser->two_factor_secret) : null,
            'qr' => $freshUser->twoFactorQrCodeSvg(),
            'recovery_codes' => $freshUser->recoveryCodes(),
        ]);
    }

    /**
     * Verify OTP during the login process and issue an API token.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->two_factor_confirmed_at) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $usedRecoveryCode = false;

        $codeInput = $validated['code'] ?? null;
        $recoveryInput = $validated['recovery_code'] ?? null;

        if (! $codeInput && ! $recoveryInput) {
            return response()->json(['message' => 'Two-factor code is required'], 422);
        }

        $attemptRecovery = function (string $code) use ($user, &$usedRecoveryCode) {
            $availableCodes = $user->two_factor_recovery_codes ? $user->recoveryCodes() : [];

            if (! in_array($code, $availableCodes, true)) {
                return false;
            }

            $user->replaceRecoveryCode($code);
            $usedRecoveryCode = true;

            return true;
        };

        if ($recoveryInput) {
            if (! $attemptRecovery($recoveryInput)) {
                return response()->json(['message' => 'Invalid recovery code'], 401);
            }
        } else {
            $totpValid = $codeInput && $user->validateTwoFactorCode($codeInput);

            if (! $totpValid) {
                if (! $attemptRecovery((string) $codeInput)) {
                    return response()->json(['message' => 'Invalid OTP'], 401);
                }
            }
        }

        $token = $user->createToken('MyAuthApp')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'userAbilities' => [
                [
                    'action' => 'manage',
                    'subject' => 'all',
                ],
            ],
            'used_recovery_code' => $usedRecoveryCode,
        ]);
    }
}
