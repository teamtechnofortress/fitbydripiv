<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientIntake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientIntakeController extends Controller
{
    public function fetchByEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $patient = Patient::with('latestIntake')
            ->where('email', $validated['email'])
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.',
            ], 404);
        }

        $intake = $patient->latestIntake;

        $formPayload = [
            'firstName' => $patient->first_name,
            'middleName' => $patient->middle_name,
            'lastName' => $patient->last_name,
            'address' => $patient->address,
            'city' => $patient->city,
            'state' => $patient->state,
            'zip' => $patient->zip,
            'email' => $patient->email,
            'dateOfBirth' => $patient->birthday,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'ethnicity' => $patient->ethnicity,
            'patientType' => $intake->patient_type ?? 'new',
            'medicalScreening' => [
                'diabetes' => $intake->diabetes ?? null,
                'bloodThinners' => $intake->blood_thinners ?? null,
                'alcohol' => $intake->alcohol ?? null,
                'glpHistory' => $intake->glp_history ?? null,
                'pancreatitis' => $intake->pancreatitis ?? null,
                'thyroidCancer' => $intake->thyroid_cancer ?? null,
                'renalImpairment' => $intake->renal_impairment ?? null,
            ],
            'currentConditions' => $intake?->current_conditions ?? [],
            'additionalConditions' => $intake?->additional_conditions ?? [],
            'goals' => $intake?->goals ?? [],
            'medicalHistory' => $intake?->medical_history ?? [],
            'medications' => $intake?->medications,
            'currentConditionsNotes' => $intake?->current_conditions_notes,
            'allergies' => $intake?->allergies,
            'allergyReactions' => $intake?->allergy_reactions,
        ];

        return response()->json([
            'message' => 'Patient intake data fetched successfully.',
            'data' => $formPayload,
            'patient' => $patient,
            'intake' => $intake,
        ]);
    }

public function submitIntakeForm(Request $request)
{
    $validated = $request->validate([
        'firstName' => ['required', 'string', 'max:255'],
        'middleName' => ['nullable', 'string', 'max:255'],
        'lastName' => ['required', 'string', 'max:255'],
        'address' => ['required', 'string', 'max:255'],
        'city' => ['required', 'string', 'max:255'],
        'state' => ['required', 'string', 'max:255'],
        'zip' => ['required', 'string', 'max:20'],
        'email' => ['required', 'email', 'max:255'],
        'dateOfBirth' => ['required', 'date'],
        'age' => ['nullable', 'integer'],
        'gender' => ['nullable', 'string', 'max:50'],
        'ethnicity' => ['nullable', 'string', 'max:100'],
        'patientType' => ['nullable', 'in:new,existing'],
        'medicalScreening' => ['array'],
        'medicalScreening.*' => ['nullable', 'in:yes,no'],
        'currentConditions' => ['array'],
        'additionalConditions' => ['array'],
        'goals' => ['required', 'array', 'min:1'],  // At least one goal required
        'goals.*' => ['string'],
        'medicalHistory' => ['array'],
        'medications' => ['nullable', 'string'],
        'currentConditionsNotes' => ['nullable', 'string'],
        'allergies' => ['nullable', 'string'],
        'allergyReactions' => ['nullable', 'string'],
    ]);
 
    // Get medical screening data
        $medical = $request->input('medicalScreening', []);

        $hasPositiveScreening = false;
        foreach ($medical as $value) {
            if ($value === 'yes') {
                $hasPositiveScreening = true;
                break;
            }
        }

        if ($hasPositiveScreening) {
            return response()->json([
                'message' => 'One or more medical screening answers require manual review. Please contact support.',
                'errors' => [
                    'medicalScreening' => ['All medical screening answers must be "no" to continue.'],
                ],
            ], 422);
        }

        $currentConditions = $request->input('currentConditions', []);
        $additionalConditions = $request->input('additionalConditions', []);
        $medicalHistory = $request->input('medicalHistory', []);

        if (empty($currentConditions)) {
            $currentConditions = ['none'];
        }

        if (empty($additionalConditions)) {
            $additionalConditions = ['none'];
        }

        if (empty($medicalHistory)) {
            $medicalHistory = ['none'];
        }

        $medications = trim((string) ($request->input('medications') ?? ''));
        $currentConditionsNotes = trim((string) ($request->input('currentConditionsNotes') ?? ''));
        $allergies = trim((string) ($request->input('allergies') ?? ''));
        $allergyReactions = trim((string) ($request->input('allergyReactions') ?? ''));

        if ($medications === '') {
            $medications = 'none';
        }

        if ($currentConditionsNotes === '') {
            $currentConditionsNotes = 'none';
        }

        if ($allergies === '') {
            $allergies = 'none';
        }

        if ($allergyReactions === '') {
            $allergyReactions = 'none';
        }

        [$patient, $intake] = DB::transaction(function () use ($validated, $request, $medical, $currentConditions, $additionalConditions, $medicalHistory, $medications, $currentConditionsNotes, $allergies, $allergyReactions) {
            // ─────────────────────────────────────────────────────────────
            // 1. CREATE OR UPDATE PATIENT
            // ─────────────────────────────────────────────────────────────
            $patient = Patient::firstOrNew(['email' => $validated['email']]);
            $patient->fill([
            'first_name' => $validated['firstName'],
            'middle_name' => $validated['middleName'] ?? null,
            'last_name' => $validated['lastName'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip' => $validated['zip'],
            'birthday' => $validated['dateOfBirth'],
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'ethnicity' => $validated['ethnicity'] ?? null,
        ]);
        $patient->save();
 
        // ─────────────────────────────────────────────────────────────
        // 2. PREPARE MEDICAL SCREENING WITH DEFAULTS
        // ─────────────────────────────────────────────────────────────
        // All medical screening questions default to "no" if not provided
        $processedMedical = [
            'diabetes' => $medical['diabetes'] ?? 'no',
            'blood_thinners' => $medical['bloodThinners'] ?? 'no',
            'alcohol' => $medical['alcohol'] ?? 'no',
            'glp_history' => $medical['glpHistory'] ?? 'no',
            'pancreatitis' => $medical['pancreatitis'] ?? 'no',
            'thyroid_cancer' => $medical['thyroidCancer'] ?? 'no',
            'renal_impairment' => $medical['renalImpairment'] ?? 'no',
        ];
 
            // ─────────────────────────────────────────────────────────────
            // 3. PREPARE INTAKE DATA
            // ─────────────────────────────────────────────────────────────
            $intakeData = [
                'patient_type' => $validated['patientType'] ?? 'new',
            // Medical screening with defaults
            'diabetes' => $processedMedical['diabetes'],
            'blood_thinners' => $processedMedical['blood_thinners'],
            'alcohol' => $processedMedical['alcohol'],
            'glp_history' => $processedMedical['glp_history'],
            'pancreatitis' => $processedMedical['pancreatitis'],
            'thyroid_cancer' => $processedMedical['thyroid_cancer'],
            'renal_impairment' => $processedMedical['renal_impairment'],
                // Goals (required, validated above)
                'goals' => $request->input('goals', []),
                // Other optional fields (default to empty/null if not provided)
                'current_conditions' => $currentConditions,
                'additional_conditions' => $additionalConditions,
                'medical_history' => $medicalHistory,
                'medications' => $medications,
                'current_conditions_notes' => $currentConditionsNotes,
                'allergies' => $allergies,
                'allergy_reactions' => $allergyReactions,
            ];
 
        // ─────────────────────────────────────────────────────────────
        // 4. CREATE OR UPDATE INTAKE
        // ─────────────────────────────────────────────────────────────
        $intake = $patient->latestIntake()->first();
 
        if ($intake) {
            $intake->update($intakeData);
        } else {
            $intake = $patient->intakes()->create($intakeData);
        }
 
        return [$patient, $intake];
    });
 
    return response()->json([
        'message' => 'Patient intake submitted successfully.',
        'patient' => $patient,
        'intake' => $intake,
    ], 201);
}


    public function store(Request $request, int $patientId): JsonResponse
    {
        $intake = PatientIntake::create([
            'patient_id' => $patientId,
            'patient_type' => $request->input('page3.patientType', 'new'),
            'diabetes' => $request->input('page1.medicalScreening.diabetes'),
            'blood_thinners' => $request->input('page1.medicalScreening.bloodThinners'),
            'alcohol' => $request->input('page1.medicalScreening.alcohol'),
            'glp_history' => $request->input('page1.medicalScreening.glpHistory'),
            'pancreatitis' => $request->input('page1.medicalScreening.pancreatitis'),
            'thyroid_cancer' => $request->input('page1.medicalScreening.thyroidCancer'),
            'renal_impairment' => $request->input('page1.medicalScreening.renalImpairment'),
            'current_conditions' => $request->input('page1.currentConditions', []),
            'additional_conditions' => $request->input('page1.additionalConditions', []),
            'goals' => $request->input('page2.goals', []),
            'medical_history' => $request->input('page3.medicalHistory', []),
            'medications' => $request->input('page1.medications'),
            'current_conditions_notes' => $request->input('page3.health.currentConditions'),
            'allergies' => $request->input('page3.health.allergies'),
            'allergy_reactions' => $request->input('page3.health.allergyReactions'),
        ]);

        return response()->json([
            'message' => 'Intake submitted successfully.',
            'data' => $intake,
        ], 201);
    }

    public function index(int $patientId): JsonResponse
    {
        $intakes = PatientIntake::where('patient_id', $patientId)
            ->latest()
            ->get();

        return response()->json($intakes);
    }

    public function show(int $patientId, int $intakeId): JsonResponse
    {
        $intake = PatientIntake::where('patient_id', $patientId)
            ->findOrFail($intakeId);

        return response()->json($intake);
    }
}
