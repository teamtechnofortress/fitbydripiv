<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileProgressController extends Controller
{
    private const TOTAL_STEPS = 5;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Profile progress fetched successfully.',
            'data' => $this->progressPayload($user),
        ]);
    }

    public function saveStep2(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_step < 2) {
            return $this->stepOrderError(2, $user);
        }

        $validated = $request->validate([
            'birthday' => ['required', 'date'],
            'ssn' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $updates = [
            'birthday' => $validated['birthday'],
            'ssn' => $validated['ssn'],
            'gender' => $validated['gender'],
            'phone' => $validated['phone'],
        ];

        if (! $user->profile_completed_at && $user->profile_step === 2) {
            $updates['profile_step'] = 3;
        }

        $user->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Step 2 completed successfully.',
            'data' => $this->progressPayload($user->fresh()),
        ]);
    }

    public function saveStep3(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_step < 3) {
            return $this->stepOrderError(3, $user);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:150'],
            'state' => ['required', 'string', 'max:150'],
            'zip' => ['required', 'string', 'max:20'],
        ]);

        $updates = [
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip' => $validated['zip'],
        ];

        if (! $user->profile_completed_at && $user->profile_step === 3) {
            $updates['profile_step'] = 4;
        }

        $user->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Step 3 completed successfully.',
            'data' => $this->progressPayload($user->fresh()),
        ]);
    }

    public function saveStep4(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_step < 4) {
            return $this->stepOrderError(4, $user);
        }

        $validated = $request->validate([
            'emergency' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
        ]);

        $updates = [
            'emergency' => $validated['emergency'],
            'contact' => $validated['contact'],
        ];

        if (! $user->profile_completed_at && $user->profile_step === 4) {
            $updates['profile_step'] = 5;
        }

        $user->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Step 4 completed successfully.',
            'data' => $this->progressPayload($user->fresh()),
        ]);
    }

    public function saveStep5(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_step < self::TOTAL_STEPS) {
            return $this->stepOrderError(self::TOTAL_STEPS, $user);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'hiring_date' => ['nullable', 'date'],
            'hourly_rate' => ['nullable', 'numeric'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'bank' => ['nullable', 'string', 'max:255'],
            'routing' => ['nullable', 'string', 'max:100'],
            'signature' => ['nullable', 'string'],
            'require_signature' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'title' => $validated['title'] ?? null,
            'hiring_date' => $validated['hiring_date'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'bank' => $validated['bank'] ?? null,
            'routing' => $validated['routing'] ?? null,
            'signature' => $validated['signature'] ?? null,
            'require_signature' => $validated['require_signature'] ?? $user->require_signature ?? 0,
        ];

        if (! $user->profile_completed_at) {
            $updates['profile_step'] = self::TOTAL_STEPS;
            $updates['profile_completed_at'] = now();
        }

        $user->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Profile completed successfully.',
            'data' => $this->progressPayload($user->fresh()),
        ]);
    }

    public function skip(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_completed_at) {
            return response()->json([
                'success' => true,
                'message' => 'Profile is already completed.',
                'data' => $this->progressPayload($user),
            ]);
        }

        $validated = $request->validate([
            'step' => ['required', 'integer', 'min:2', 'max:' . self::TOTAL_STEPS],
        ]);

        $step = (int) $validated['step'];

        if ($user->profile_step !== $step) {
            return response()->json([
                'success' => false,
                'message' => 'You can only skip your current active step.',
                'data' => $this->progressPayload($user),
            ], 422);
        }

        if ($step === self::TOTAL_STEPS) {
            $user->update([
                'profile_step' => self::TOTAL_STEPS,
                'profile_completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Final step skipped. Profile marked as completed.',
                'data' => $this->progressPayload($user->fresh()),
            ]);
        }

        $user->update([
            'profile_step' => $step + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Step {$step} skipped successfully.",
            'data' => $this->progressPayload($user->fresh()),
        ]);
    }

    public function showStep(Request $request, int $step): JsonResponse
    {
        if ($step < 1 || $step > self::TOTAL_STEPS) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid step requested.',
            ], 422);
        }

        $user = $request->user();
        $maxAccessible = $user->profile_completed_at ? self::TOTAL_STEPS : max(1, (int) $user->profile_step);

        if ($step !== 1 && $step > $maxAccessible) {
            return response()->json([
                'success' => false,
                'message' => "Step {$step} is not available yet.",
                'data' => $this->progressPayload($user),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Step {$step} data fetched successfully.",
            'data' => [
                'step' => $step,
                'fields' => $this->stepData($user, $step),
            ],
        ]);
    }

    private function progressPayload($user): array
    {
        if ($user->profile_completed_at) {
            return [
                'current_step' => self::TOTAL_STEPS,
                'completed_steps' => self::TOTAL_STEPS,
                'total_steps' => self::TOTAL_STEPS,
                'is_completed' => true,
                'completion_percentage' => 100,
                'profile_completed_at' => $user->profile_completed_at,
            ];
        }

        $currentStep = max(2, (int) $user->profile_step);
        $completedSteps = max(1, $currentStep - 1);

        return [
            'current_step' => $currentStep,
            'completed_steps' => $completedSteps,
            'total_steps' => self::TOTAL_STEPS,
            'is_completed' => false,
            'completion_percentage' => (int) round(($completedSteps / self::TOTAL_STEPS) * 100),
            'profile_completed_at' => null,
        ];
    }

    private function stepOrderError(int $requiredStep, $user): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "You must complete previous steps before step {$requiredStep}.",
            'data' => $this->progressPayload($user),
        ], 422);
    }

    private function alreadyCompletedResponse($user, int $step): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => "Step {$step} is already completed.",
            'data' => $this->progressPayload($user),
        ]);
    }

    private function stepData($user, int $step): array
    {
        $fields = match ($step) {
            1 => ['firstName', 'lastName', 'email'],
            2 => ['birthday', 'ssn', 'gender', 'phone'],
            3 => ['address', 'city', 'state', 'zip'],
            4 => ['emergency', 'contact'],
            5 => ['title', 'hiring_date', 'hourly_rate', 'payment_method', 'bank', 'routing', 'signature', 'require_signature'],
            default => [],
        };

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $user->{$field};
        }

        return $data;
    }
}
