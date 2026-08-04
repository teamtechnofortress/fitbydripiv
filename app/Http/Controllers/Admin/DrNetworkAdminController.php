<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DrNetwork\ProcessNetworkWebhook;
use App\Models\DocumentType;
use App\Models\DrNetwork;
use App\Models\DrNetworkConfigValue;
use App\Models\DrNetworkFlowRun;
use App\Models\DrNetworkFlowRunStep;
use App\Models\DrNetworkWebhookEvent;
use App\Models\NetworkDocumentRule;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkIntakeQuestion;
use App\Models\NetworkIntakeQuestionSet;
use App\Models\NetworkProductMapping;
use App\Models\NetworkStateMapping;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\Product;
use App\Models\State;
use App\Services\DrNetwork\Admin\ConfigAuditLogger;
use App\Services\DrNetwork\ConsultationManagement\ConsultationStatusService;
use App\Services\DrNetwork\IntakeQuestions\IntakeAnswerBlockingRuleEvaluator;
use App\Services\DrNetwork\IntakeQuestions\IntakeQuestionRuleEvaluator;
use App\Services\DrNetwork\Resolvers\NetworkAdapterResolver;
use App\Support\Money\DecimalMoney;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class DrNetworkAdminController extends Controller
{
    private const CONFIG_WRITE_ROLES = ['admin', 'super_admin', 'network_admin'];

    private const CREDENTIAL_WRITE_ROLES = ['admin', 'super_admin'];

    private const READ_ROLES = ['admin', 'super_admin', 'network_admin', 'clinical_reviewer', 'support'];

    private const STEP_KEYS = [
        'checkout',
        'awaiting_payment_confirmation',
        'document_upload',
        'intake',
        'intake_questions',
        'slot_selection',
        'review_and_submit',
        'provider_review',
        'video_consultation',
    ];

    public function __construct(
        private ConfigAuditLogger $auditLogger,
        private NetworkAdapterResolver $adapterResolver,
        private IntakeQuestionRuleEvaluator $questionRuleEvaluator,
        private IntakeAnswerBlockingRuleEvaluator $blockingRuleEvaluator,
        private ConsultationStatusService $consultationStatusService,
    ) {}

    public function indexNetworks(Request $request): JsonResponse
    {
        $this->authorizeRead($request);

        $networks = DrNetwork::query()
            ->withCount(['flowDefinitions', 'mappings', 'productMappings', 'intakeQuestionSets', 'documentRules'])
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($networks);
    }

    public function storeNetwork(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:dr_networks,slug'],
            'adapter_key' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:dr_networks,adapter_key'],
            'integration_mode' => ['required', Rule::in(DrNetwork::INTEGRATION_MODES)],
            'status' => ['sometimes', Rule::in(DrNetwork::STATUSES)],
            'is_default' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'feature_flags' => ['sometimes', 'nullable', 'array'],
        ]);

        $network = DB::transaction(function () use ($validated): DrNetwork {
            $network = DrNetwork::query()->create(array_merge([
                'status' => DrNetwork::STATUS_INACTIVE,
                'is_default' => false,
                'config_version' => 1,
            ], $validated));

            $this->auditLogger->log($network, 'created', null, $network->fresh()->toArray());

            return $network;
        });

        return response()->json($network->load('configValues'), 201);
    }

    public function showNetwork(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $network->load([
            'configValues',
            'flowDefinitions' => fn ($query) => $query->orderBy('flow_key'),
        ]);

        return response()->json(array_merge(Arr::except($network->toArray(), ['config_values']), [
            'credentials' => $this->maskedConfigValues($network),
        ]));
    }

    public function updateNetwork(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(DrNetwork::STATUSES)],
            'integration_mode' => ['sometimes', Rule::in(DrNetwork::INTEGRATION_MODES)],
            'is_default' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'feature_flags' => ['sometimes', 'nullable', 'array'],
        ]);

        $network = DB::transaction(function () use ($network, $validated): DrNetwork {
            $before = $network->toArray();
            $network->fill($validated);
            $network->config_version = ((int) $network->config_version) + 1;
            $network->save();

            $this->auditLogger->log($network, 'updated', $before, $network->fresh()->toArray());

            return $network->refresh();
        });

        return response()->json($network);
    }

    public function toggleNetwork(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $targetStatus = array_key_exists('enabled', $validated)
            ? ((bool) $validated['enabled'] ? DrNetwork::STATUS_ACTIVE : DrNetwork::STATUS_INACTIVE)
            : ($network->status === DrNetwork::STATUS_ACTIVE ? DrNetwork::STATUS_INACTIVE : DrNetwork::STATUS_ACTIVE);

        $network = DB::transaction(function () use ($network, $targetStatus): DrNetwork {
            $before = $network->toArray();
            $network->status = $targetStatus;
            $network->config_version = ((int) $network->config_version) + 1;
            $network->save();

            $this->auditLogger->log(
                $network,
                $targetStatus === DrNetwork::STATUS_ACTIVE ? 'activated' : 'deactivated',
                $before,
                $network->fresh()->toArray()
            );

            return $network->refresh();
        });

        return response()->json([
            'id' => $network->id,
            'status' => $network->status,
            'enabled' => $network->status === DrNetwork::STATUS_ACTIVE,
            'config_version' => $network->config_version,
        ]);
    }

    public function destroyNetwork(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        if ($network->mappings()->active()->exists() || $network->productMappings()->active()->exists()) {
            return response()->json([
                'message' => 'Network cannot be deleted while active state or product mappings exist.',
            ], 422);
        }

        DB::transaction(function () use ($network): void {
            $before = $network->toArray();
            $network->delete();
            $this->auditLogger->log($network, 'deleted', $before, null);
        });

        return response()->json(['deleted' => true]);
    }

    public function showCredentials(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $network->loadMissing('configValues');

        return response()->json([
            'dr_network_id' => $network->id,
            'settings' => [
                'api_base_url' => $network->settings['api_base_url'] ?? null,
                'webhook_signatures_enabled' => (bool) ($network->settings['webhook_signatures_enabled'] ?? false),
                'webhook_endpoint_token_configured' => filled($network->settings['webhook_endpoint_token_hash'] ?? null),
            ],
            'credentials' => $this->maskedConfigValues($network),
        ]);
    }

    public function updateCredentials(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeCredentialWrite($request);

        $validated = $request->validate([
            'auth_token' => ['sometimes', 'nullable', 'string'],
            'secret_token' => ['sometimes', 'nullable', 'string'],
            'tenant' => ['sometimes', 'nullable', 'string'],
            'api_base_url' => ['sometimes', 'nullable', 'url'],
            'webhook_endpoint_token' => ['sometimes', 'nullable', 'string'],
            'webhook_signatures_enabled' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($network, $validated): void {
            $before = [
                'network' => $network->toArray(),
                'credentials' => $this->maskedConfigValues($network->loadMissing('configValues')),
            ];

            foreach (['auth_token', 'secret_token', 'tenant'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $this->upsertConfigValue(
                        $network,
                        $key,
                        $validated[$key],
                        $key === 'tenant' ? DrNetworkConfigValue::TYPE_STRING : DrNetworkConfigValue::TYPE_STRING,
                        $key !== 'tenant',
                        str_replace('_', ' ', ucfirst($key))
                    );
                }
            }

            $settings = $network->settings ?? [];

            if (array_key_exists('api_base_url', $validated)) {
                $settings['api_base_url'] = $validated['api_base_url'];
            }

            if (array_key_exists('webhook_endpoint_token', $validated)) {
                $settings['webhook_endpoint_token_hash'] = filled($validated['webhook_endpoint_token'])
                    ? DrNetworkConfigValue::lookupHash((string) $validated['webhook_endpoint_token'])
                    : null;
            }

            if (array_key_exists('webhook_signatures_enabled', $validated)) {
                $settings['webhook_signatures_enabled'] = (bool) $validated['webhook_signatures_enabled'];
            }

            $network->settings = $settings;
            $network->config_version = ((int) $network->config_version) + 1;
            $network->save();

            $this->auditLogger->log($network, 'credentials_updated', $before, [
                'network' => $network->fresh()->toArray(),
                'credentials' => $this->maskedConfigValues($network->refresh()->load('configValues')),
            ]);
        });

        return $this->showCredentials($request, $network->refresh());
    }

    public function testCredentials(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        try {
            $adapter = $this->adapterResolver->resolve($network->load('configValues'));

            if (! method_exists($adapter, 'testAuthentication')) {
                throw new RuntimeException('This network adapter does not expose an authentication test.');
            }

            return response()->json($adapter->testAuthentication());
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'adapter_key' => $network->adapter_key,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function listFlows(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        return response()->json([
            'data' => $network->flowDefinitions()->orderBy('flow_key')->get(),
        ]);
    }

    public function flowContentCoverage(Request $request, DrNetwork $network, NetworkFlowDefinition $flow): JsonResponse
    {
        $this->authorizeRead($request);

        if ((int) $flow->dr_network_id !== (int) $network->id) {
            abort(404);
        }

        $products = NetworkProductMapping::query()
            ->forNetwork($network->id)
            ->forFlow($flow->id)
            ->active()
            ->with('product:id,name,slug')
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $productSlugs = $products->pluck('slug')->filter()->values();
        $stepKeys = collect($flow->steps ?? [])->pluck('step_key')->filter()->values();

        $defaultQuestionSetCount = NetworkIntakeQuestionSet::query()
            ->forNetwork($network->id)
            ->published()
            ->where('flow_id', $flow->id)
            ->where('product_code', NetworkIntakeQuestionSet::ALL_SCOPE)
            ->count();

        $stateSpecificDefaultQuestionSetCount = NetworkIntakeQuestionSet::query()
            ->forNetwork($network->id)
            ->published()
            ->where('flow_id', $flow->id)
            ->where('product_code', NetworkIntakeQuestionSet::ALL_SCOPE)
            ->where('state_code', '!=', NetworkIntakeQuestionSet::ALL_SCOPE)
            ->count();

        $defaultQuestionSet = NetworkIntakeQuestionSet::query()
            ->forNetwork($network->id)
            ->published()
            ->where('flow_id', $flow->id)
            ->where('product_code', NetworkIntakeQuestionSet::ALL_SCOPE)
            ->where('state_code', NetworkIntakeQuestionSet::ALL_SCOPE)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        $questionOverrideCodes = NetworkIntakeQuestionSet::query()
            ->forNetwork($network->id)
            ->published()
            ->where('flow_id', $flow->id)
            ->whereIn('product_code', $productSlugs)
            ->pluck('product_code')
            ->unique()
            ->values();

        $defaultDocumentRuleCount = NetworkDocumentRule::query()
            ->forNetwork($network->id)
            ->active()
            ->where('flow_key', $flow->flow_key)
            ->whereNull('product_code')
            ->whereNull('state_code')
            ->count();

        $stateSpecificDefaultDocumentRuleCount = NetworkDocumentRule::query()
            ->forNetwork($network->id)
            ->active()
            ->where('flow_key', $flow->flow_key)
            ->whereNull('product_code')
            ->whereNotNull('state_code')
            ->count();

        $documentOverrideCodes = NetworkDocumentRule::query()
            ->forNetwork($network->id)
            ->active()
            ->where('flow_key', $flow->flow_key)
            ->whereIn('product_code', $productSlugs)
            ->pluck('product_code')
            ->unique()
            ->values();

        return response()->json([
            'network' => [
                'id' => $network->id,
                'name' => $network->name,
                'slug' => $network->slug,
            ],
            'flow' => [
                'id' => $flow->id,
                'flow_key' => $flow->flow_key,
                'name' => $flow->name,
                'steps' => $flow->steps ?? [],
            ],
            'products_total' => $products->count(),
            'products' => $products->map(fn (Product $product): array => $this->coverageProductPayload($product))->values(),
            'steps' => [
                'intake_questions' => $this->contentCoveragePayload(
                    $products,
                    $questionOverrideCodes->all(),
                    $defaultQuestionSet !== null,
                    [
                        'step_enabled' => $stepKeys->contains('intake_questions'),
                        'has_default_set' => $defaultQuestionSet !== null,
                        'default_set_count' => $defaultQuestionSetCount,
                        'state_specific_default_set_count' => $stateSpecificDefaultQuestionSetCount,
                        'default_set' => $defaultQuestionSet ? [
                            'id' => $defaultQuestionSet->id,
                            'set_key' => $defaultQuestionSet->set_key,
                            'set_name' => $defaultQuestionSet->set_name,
                            'version' => $defaultQuestionSet->version,
                            'status' => $defaultQuestionSet->status,
                        ] : null,
                    ]
                ),
                'document_upload' => $this->contentCoveragePayload(
                    $products,
                    $documentOverrideCodes->all(),
                    $defaultDocumentRuleCount > 0,
                    [
                        'step_enabled' => $stepKeys->contains('document_upload'),
                        'has_default_rules' => $defaultDocumentRuleCount > 0,
                        'has_default_set' => $defaultDocumentRuleCount > 0,
                        'default_rule_count' => $defaultDocumentRuleCount,
                        'state_specific_default_rule_count' => $stateSpecificDefaultDocumentRuleCount,
                    ]
                ),
            ],
        ]);
    }

    public function storeFlow(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $this->validateFlow($request, [
            'flow_key' => ['required', 'string', 'max:100', 'alpha_dash'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $exists = NetworkFlowDefinition::query()
            ->forNetwork($network->id)
            ->forKey($validated['flow_key'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Flow key already exists for this network.'], 422);
        }

        $flow = DB::transaction(function () use ($network, $validated): NetworkFlowDefinition {
            $flow = $network->flowDefinitions()->create($validated);
            $this->auditLogger->log($flow, 'created', null, $flow->fresh()->toArray());

            return $flow;
        });

        return response()->json($flow, 201);
    }

    public function showFlow(Request $request, NetworkFlowDefinition $flow): JsonResponse
    {
        $this->authorizeRead($request);

        return response()->json($flow->load('drNetwork'));
    }

    public function updateFlow(Request $request, NetworkFlowDefinition $flow): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $this->validateFlow($request);

        $flow = DB::transaction(function () use ($flow, $validated): NetworkFlowDefinition {
            $before = $flow->toArray();
            $flow->update($validated);
            $this->auditLogger->log($flow, 'updated', $before, $flow->fresh()->toArray());

            return $flow->refresh();
        });

        return response()->json($flow);
    }

    public function destroyFlow(Request $request, NetworkFlowDefinition $flow): JsonResponse
    {
        $this->authorizeWrite($request);

        if ($flow->mappings()->active()->exists() || $flow->productMappings()->active()->exists()) {
            return response()->json(['message' => 'Flow cannot be deleted while active mappings reference it.'], 422);
        }

        DB::transaction(function () use ($flow): void {
            $before = $flow->toArray();
            $flow->delete();
            $this->auditLogger->log($flow, 'deleted', $before, null);
        });

        return response()->json(['deleted' => true]);
    }

    public function validateFlowDefinition(Request $request, NetworkFlowDefinition $flow): JsonResponse
    {
        $this->authorizeRead($request);

        $steps = $request->input('steps', $flow->steps ?? []);
        $errors = $this->flowStepErrors(is_array($steps) ? $steps : []);

        return response()->json([
            'valid' => $errors === [],
            'errors' => $errors,
            'known_step_keys' => self::STEP_KEYS,
        ], $errors === [] ? 200 : 422);
    }

    public function cloneFlow(Request $request, NetworkFlowDefinition $flow): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'flow_key' => ['required', 'string', 'max:100', 'alpha_dash'],
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $exists = NetworkFlowDefinition::query()
            ->forNetwork($flow->dr_network_id)
            ->forKey($validated['flow_key'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Flow key already exists for this network.'], 422);
        }

        $clone = DB::transaction(function () use ($flow, $validated): NetworkFlowDefinition {
            $clone = $flow->replicate();
            $clone->flow_key = $validated['flow_key'];
            $clone->name = $validated['name'] ?? ($flow->name.' Copy');
            $clone->is_active = false;
            $clone->save();

            $this->auditLogger->log($clone, 'created_from_clone', null, $clone->fresh()->toArray());

            return $clone;
        });

        return response()->json($clone, 201);
    }

    public function listStates(Request $request): JsonResponse
    {
        $this->authorizeRead($request);

        $query = State::query()->orderBy('country_code')->orderBy('state_code');

        if ($request->filled('country_code')) {
            $query->where('country_code', strtoupper((string) $request->query('country_code')));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function storeState(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'state_code' => ['required', 'string', 'max:10'],
            'state_name' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $state = DB::transaction(function () use ($validated): State {
            $state = State::query()->updateOrCreate([
                'country_code' => strtoupper($validated['country_code']),
                'state_code' => strtoupper($validated['state_code']),
            ], [
                'state_name' => $validated['state_name'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->auditLogger->log($state, $state->wasRecentlyCreated ? 'created' : 'updated', null, $state->toArray());

            return $state;
        });

        return response()->json($state, $state->wasRecentlyCreated ? 201 : 200);
    }

    public function listStateMappings(Request $request): JsonResponse
    {
        $this->authorizeRead($request);

        $query = NetworkStateMapping::query()
            ->with(['state', 'drNetwork', 'flowDefinition'])
            ->when($request->filled('state_code'), function (Builder $query) use ($request): void {
                $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->forCode((string) $request->query('state_code')));
            })
            ->when($request->filled('network_id'), fn (Builder $query) => $query->where('dr_network_id', $request->query('network_id')))
            ->when($request->filled('flow_id'), fn (Builder $query) => $query->where('flow_id', $request->query('flow_id')))
            ->ordered();

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function coverageCheck(Request $request): JsonResponse
    {
        $this->authorizeRead($request);

        $networkId = $request->integer('network_id') ?: null;

        $states = State::query()->active()->where('country_code', 'US')->orderBy('state_code')->get();
        $coveredStateIds = NetworkStateMapping::query()
            ->active()
            ->when($networkId, fn (Builder $query) => $query->where('dr_network_id', $networkId))
            ->whereIn('state_id', $states->pluck('id'))
            ->pluck('state_id')
            ->unique();

        $unmapped = $states->whereNotIn('id', $coveredStateIds)->values();

        return response()->json([
            'network_id' => $networkId,
            'total_states' => $states->count(),
            'covered_states' => $states->count() - $unmapped->count(),
            'unmapped_count' => $unmapped->count(),
            'unmapped_states' => $unmapped->values(),
        ]);
    }

    public function storeStateMapping(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'dr_network_id' => ['required', 'integer', 'exists:dr_networks,id'],
            'flow_id' => ['required', 'integer', 'exists:network_flow_definitions,id'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->assertFlowBelongsToNetwork((int) $validated['flow_id'], (int) $validated['dr_network_id']);

        $mapping = DB::transaction(function () use ($validated): NetworkStateMapping {
            $mapping = NetworkStateMapping::query()->create(array_merge([
                'priority' => 100,
                'is_active' => true,
            ], $validated));

            $this->auditLogger->log($mapping, 'created', null, $mapping->fresh()->toArray());

            return $mapping;
        });

        return response()->json($mapping->load(['state', 'drNetwork', 'flowDefinition']), 201);
    }

    public function updateStateMapping(Request $request, NetworkStateMapping $mapping): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'state_id' => ['sometimes', 'integer', 'exists:states,id'],
            'dr_network_id' => ['sometimes', 'integer', 'exists:dr_networks,id'],
            'flow_id' => ['sometimes', 'integer', 'exists:network_flow_definitions,id'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $networkId = (int) ($validated['dr_network_id'] ?? $mapping->dr_network_id);
        $flowId = (int) ($validated['flow_id'] ?? $mapping->flow_id);
        $this->assertFlowBelongsToNetwork($flowId, $networkId);

        $mapping = DB::transaction(function () use ($mapping, $validated): NetworkStateMapping {
            $before = $mapping->toArray();
            $mapping->update($validated);
            $this->auditLogger->log($mapping, 'updated', $before, $mapping->fresh()->toArray());

            return $mapping->refresh();
        });

        return response()->json($mapping->load(['state', 'drNetwork', 'flowDefinition']));
    }

    public function destroyStateMapping(Request $request, NetworkStateMapping $mapping): JsonResponse
    {
        $this->authorizeWrite($request);

        DB::transaction(function () use ($mapping): void {
            $before = $mapping->toArray();
            $mapping->delete();
            $this->auditLogger->log($mapping, 'deleted', $before, null);
        });

        return response()->json(['deleted' => true]);
    }

    public function toggleStateMapping(Request $request, NetworkStateMapping $mapping): JsonResponse
    {
        $this->authorizeWrite($request);

        $mapping = DB::transaction(function () use ($mapping): NetworkStateMapping {
            $before = $mapping->toArray();
            $mapping->update(['is_active' => ! $mapping->is_active]);
            $this->auditLogger->log($mapping, $mapping->is_active ? 'activated' : 'deactivated', $before, $mapping->fresh()->toArray());

            return $mapping->refresh();
        });

        return response()->json($mapping->load(['state', 'drNetwork', 'flowDefinition']));
    }

    public function listProductMappings(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $query = $network->productMappings()
            ->with(['product', 'flowDefinition'])
            ->when($request->filled('product_id'), fn (Builder $query) => $query->where('product_id', $request->query('product_id')))
            ->when($request->filled('flow_id'), fn (Builder $query) => $query->where('flow_id', $request->query('flow_id')))
            ->orderBy('product_id')
            ->orderBy('flow_id');

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function productMappingMatrix(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $flows = $network->flowDefinitions()->active()->orderBy('flow_key')->get();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'slug']);
        $mappings = $network->productMappings()->with('flowDefinition')->get()->groupBy('product_id');

        $rows = $products->map(function (Product $product) use ($flows, $mappings): array {
            $productMappings = $mappings->get($product->id, collect())->keyBy('flow_id');

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'cells' => $flows->mapWithKeys(function (NetworkFlowDefinition $flow) use ($productMappings): array {
                    $mapping = $productMappings->get($flow->id);

                    return [$flow->flow_key => $mapping ? [
                        'mapping_id' => $mapping->id,
                        'flow_id' => $flow->id,
                        'external_service_id' => $mapping->external_service_id,
                        'external_service_key' => $mapping->external_service_key,
                        'external_config' => $mapping->external_config,
                        'is_active' => $mapping->is_active,
                    ] : null];
                })->all(),
            ];
        });

        return response()->json([
            'network' => $network,
            'flows' => $flows,
            'rows' => $rows,
        ]);
    }

    public function storeProductMapping(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'flow_id' => ['required', 'integer', 'exists:network_flow_definitions,id'],
            'external_service_id' => ['required', 'string', 'max:255'],
            'external_service_key' => ['nullable', 'string', 'max:255'],
            'external_config' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->assertFlowBelongsToNetwork((int) $validated['flow_id'], $network->id);

        $mapping = DB::transaction(function () use ($network, $validated): NetworkProductMapping {
            $mapping = $network->productMappings()->create(array_merge(['is_active' => true], $validated));
            $this->auditLogger->log($mapping, 'created', null, $mapping->fresh()->toArray());

            return $mapping;
        });

        return response()->json($mapping->load(['product', 'flowDefinition']), 201);
    }

    public function updateProductMapping(Request $request, NetworkProductMapping $mapping): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'product_id' => ['sometimes', 'uuid', 'exists:products,id'],
            'flow_id' => ['sometimes', 'integer', 'exists:network_flow_definitions,id'],
            'external_service_id' => ['sometimes', 'string', 'max:255'],
            'external_service_key' => ['nullable', 'string', 'max:255'],
            'external_config' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->assertFlowBelongsToNetwork((int) ($validated['flow_id'] ?? $mapping->flow_id), $mapping->dr_network_id);

        $mapping = DB::transaction(function () use ($mapping, $validated): NetworkProductMapping {
            $before = $mapping->toArray();
            $mapping->update($validated);
            $this->auditLogger->log($mapping, 'updated', $before, $mapping->fresh()->toArray());

            return $mapping->refresh();
        });

        return response()->json($mapping->load(['product', 'flowDefinition']));
    }

    public function destroyProductMapping(Request $request, NetworkProductMapping $mapping): JsonResponse
    {
        $this->authorizeWrite($request);

        DB::transaction(function () use ($mapping): void {
            $before = $mapping->toArray();
            $mapping->delete();
            $this->auditLogger->log($mapping, 'deleted', $before, null);
        });

        return response()->json(['deleted' => true]);
    }

    public function toggleProductMapping(Request $request, NetworkProductMapping $mapping): JsonResponse
    {
        $this->authorizeWrite($request);

        $mapping = DB::transaction(function () use ($mapping): NetworkProductMapping {
            $before = $mapping->toArray();
            $mapping->update(['is_active' => ! $mapping->is_active]);
            $this->auditLogger->log($mapping, $mapping->is_active ? 'activated' : 'deactivated', $before, $mapping->fresh()->toArray());

            return $mapping->refresh();
        });

        return response()->json($mapping->load(['product', 'flowDefinition']));
    }

    public function listQuestionSets(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $query = $network->intakeQuestionSets()
            ->with('flow')
            ->when($request->filled('flow_id'), fn (Builder $query) => $query->where('flow_id', $request->query('flow_id')))
            ->when($request->filled('product_code'), fn (Builder $query) => $query->where('product_code', $request->query('product_code')))
            ->when($request->filled('product_id'), function (Builder $query) use ($request): void {
                $slug = Product::query()->whereKey($request->query('product_id'))->value('slug');
                $query->where('product_code', $slug ?: '__missing_product__');
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->orderBy('set_key')
            ->orderByDesc('version');

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function storeQuestionSet(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'flow_id' => ['nullable', 'integer', 'exists:network_flow_definitions,id'],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'product_code' => ['nullable', 'string', 'max:100'],
            'state_code' => ['nullable', 'string', 'max:10'],
            'set_key' => ['required', 'string', 'max:150'],
            'set_name' => ['required', 'string', 'max:150'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (isset($validated['flow_id'])) {
            $this->assertFlowBelongsToNetwork((int) $validated['flow_id'], $network->id);
        }

        $validated['product_code'] = $this->productScopeCode($validated);
        unset($validated['product_id']);

        $set = DB::transaction(function () use ($network, $validated): NetworkIntakeQuestionSet {
            $set = $network->intakeQuestionSets()->create(array_merge([
                'state_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
                'version' => 1,
                'status' => NetworkIntakeQuestionSet::STATUS_DRAFT,
            ], $validated));

            $this->auditLogger->log($set, 'created', null, $set->fresh()->toArray());

            return $set;
        });

        return response()->json($set->load('flow'), 201);
    }

    public function showQuestionSet(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeRead($request);

        return response()->json($set->load(['flow', 'allQuestions']));
    }

    public function updateQuestionSet(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'set_name' => ['sometimes', 'string', 'max:150'],
            'status' => ['sometimes', Rule::in(NetworkIntakeQuestionSet::STATUSES)],
            'metadata' => ['nullable', 'array'],
            'state_code' => ['sometimes', 'nullable', 'string', 'max:10'],
        ]);

        $set = DB::transaction(function () use ($set, $validated): NetworkIntakeQuestionSet {
            $before = $set->toArray();
            $set->update($validated);
            $this->auditLogger->log($set, 'updated', $before, $set->fresh()->toArray());

            return $set->refresh();
        });

        return response()->json($set->load(['flow', 'allQuestions']));
    }

    public function publishQuestionSet(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeWrite($request);

        $errors = $this->questionSetValidationErrors($set);
        if ($errors !== []) {
            return response()->json(['message' => 'Question set is not publishable.', 'errors' => $errors], 422);
        }

        $set = DB::transaction(function () use ($set): NetworkIntakeQuestionSet {
            NetworkIntakeQuestionSet::query()
                ->where('dr_network_id', $set->dr_network_id)
                ->where('flow_id', $set->flow_id)
                ->where('product_code', $set->product_code)
                ->where('state_code', $set->state_code)
                ->where('id', '!=', $set->id)
                ->where('status', NetworkIntakeQuestionSet::STATUS_PUBLISHED)
                ->update(['status' => NetworkIntakeQuestionSet::STATUS_ARCHIVED]);

            $before = $set->toArray();
            $set->update(['status' => NetworkIntakeQuestionSet::STATUS_PUBLISHED]);
            $this->auditLogger->log($set, 'published', $before, $set->fresh()->toArray());

            return $set->refresh();
        });

        return response()->json($set->load(['flow', 'allQuestions']));
    }

    public function archiveQuestionSet(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeWrite($request);

        $set = DB::transaction(function () use ($set): NetworkIntakeQuestionSet {
            $before = $set->toArray();
            $set->update(['status' => NetworkIntakeQuestionSet::STATUS_ARCHIVED]);
            $this->auditLogger->log($set, 'archived', $before, $set->fresh()->toArray());

            return $set->refresh();
        });

        return response()->json($set);
    }

    public function cloneQuestionSet(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'set_key' => ['required', 'string', 'max:150'],
            'set_name' => ['sometimes', 'string', 'max:150'],
        ]);

        $clone = DB::transaction(function () use ($set, $validated): NetworkIntakeQuestionSet {
            $clone = $set->replicate();
            $clone->set_key = $validated['set_key'];
            $clone->set_name = $validated['set_name'] ?? ($set->set_name.' Copy');
            $clone->version = $set->version + 1;
            $clone->status = NetworkIntakeQuestionSet::STATUS_DRAFT;
            $clone->save();

            foreach ($set->allQuestions as $question) {
                $clone->allQuestions()->create(Arr::except($question->toArray(), ['id', 'question_set_id', 'created_at', 'updated_at']));
            }

            $this->auditLogger->log($clone, 'created_from_clone', null, $clone->fresh()->toArray());

            return $clone;
        });

        return response()->json($clone->load('allQuestions'), 201);
    }

    public function listQuestions(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeRead($request);

        return response()->json(['data' => $set->allQuestions()->get()]);
    }

    public function storeQuestion(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $this->validateQuestion($request, true);

        $question = DB::transaction(function () use ($set, $validated): NetworkIntakeQuestion {
            $question = $set->allQuestions()->create(array_merge([
                'sort_order' => ($set->allQuestions()->max('sort_order') ?? 0) + 10,
                'is_active' => true,
            ], $validated));

            $this->auditLogger->log($question, 'created', null, $question->fresh()->toArray());

            return $question;
        });

        return response()->json($question, 201);
    }

    public function updateQuestion(Request $request, NetworkIntakeQuestion $question): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $this->validateQuestion($request, false);

        $question = DB::transaction(function () use ($question, $validated): NetworkIntakeQuestion {
            $before = $question->toArray();
            $question->update($validated);
            $this->auditLogger->log($question, 'updated', $before, $question->fresh()->toArray());

            return $question->refresh();
        });

        return response()->json($question);
    }

    public function destroyQuestion(Request $request, NetworkIntakeQuestion $question): JsonResponse
    {
        $this->authorizeWrite($request);

        $question = DB::transaction(function () use ($question): NetworkIntakeQuestion {
            $before = $question->toArray();
            $question->update(['is_active' => false]);
            $this->auditLogger->log($question, 'deactivated', $before, $question->fresh()->toArray());

            return $question->refresh();
        });

        return response()->json($question);
    }

    public function reorderQuestion(Request $request, NetworkIntakeQuestion $question): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate(['new_sort_order' => ['required', 'integer', 'min:0']]);

        $question = DB::transaction(function () use ($question, $validated): NetworkIntakeQuestion {
            $before = $question->toArray();
            $question->update(['sort_order' => $validated['new_sort_order']]);
            $this->auditLogger->log($question, 'reordered', $before, $question->fresh()->toArray());

            return $question->refresh();
        });

        return response()->json($question);
    }

    public function reorderQuestionsBulk(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['integer', 'min:0'],
        ]);

        DB::transaction(function () use ($set, $validated): void {
            foreach ($validated['orders'] as $questionId => $sortOrder) {
                $question = $set->allQuestions()->whereKey($questionId)->first();

                if (! $question) {
                    continue;
                }

                $before = $question->toArray();
                $question->update(['sort_order' => $sortOrder]);
                $this->auditLogger->log($question, 'reordered', $before, $question->fresh()->toArray());
            }
        });

        return response()->json(['data' => $set->allQuestions()->get()]);
    }

    public function previewQuestionSet(Request $request, NetworkIntakeQuestionSet $set): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'patient' => ['sometimes', 'array'],
            'prior_answers' => ['sometimes', 'array'],
        ]);

        $context = $this->syntheticIntakeContext($validated['patient'] ?? [], $validated['prior_answers'] ?? []);

        $questions = $set->questions()->get()
            ->filter(fn (NetworkIntakeQuestion $question): bool => $this->questionRuleEvaluator->applies($question, $context))
            ->values();

        return response()->json([
            'set_id' => $set->id,
            'set_key' => $set->set_key,
            'context' => $context,
            'questions' => $questions,
        ]);
    }

    public function testBlockingRule(Request $request, NetworkIntakeQuestion $question): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'answer_value' => ['present'],
            'patient' => ['sometimes', 'array'],
            'prior_answers' => ['sometimes', 'array'],
        ]);

        $answers = $validated['prior_answers'] ?? [];
        $answers[$question->question_key] = $validated['answer_value'];
        $context = $this->syntheticIntakeContext($validated['patient'] ?? [], $answers);
        $triggered = $this->blockingRuleEvaluator->triggeredRules($question, $context);

        return response()->json([
            'would_trigger' => $triggered !== [],
            'triggered_rules' => $triggered,
        ]);
    }

    public function listDocumentRules(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $query = $network->documentRules()
            ->when($request->filled('flow_key'), fn (Builder $query) => $query->where('flow_key', $request->query('flow_key')))
            ->when($request->filled('flow_id'), function (Builder $query) use ($request): void {
                $flowKey = NetworkFlowDefinition::query()->whereKey($request->query('flow_id'))->value('flow_key');
                $query->where('flow_key', $flowKey ?: '__missing_flow__');
            })
            ->when($request->filled('state_code'), fn (Builder $query) => $query->where('state_code', strtoupper((string) $request->query('state_code'))))
            ->when($request->filled('product_code'), fn (Builder $query) => $query->where('product_code', $request->query('product_code')))
            ->ordered();

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function storeDocumentRule(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $this->validateDocumentRule($request, true);

        $rule = DB::transaction(function () use ($network, $validated): NetworkDocumentRule {
            $rule = $network->documentRules()->create(array_merge([
                'conditions' => [],
                'is_required' => true,
                'is_active' => true,
            ], $validated));

            $this->auditLogger->log($rule, 'created', null, $rule->fresh()->toArray());

            return $rule;
        });

        return response()->json($rule, 201);
    }

    public function updateDocumentRule(Request $request, NetworkDocumentRule $rule): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $this->validateDocumentRule($request, false);

        $rule = DB::transaction(function () use ($rule, $validated): NetworkDocumentRule {
            $before = $rule->toArray();
            $rule->update($validated);
            $this->auditLogger->log($rule, 'updated', $before, $rule->fresh()->toArray());

            return $rule->refresh();
        });

        return response()->json($rule);
    }

    public function destroyDocumentRule(Request $request, NetworkDocumentRule $rule): JsonResponse
    {
        $this->authorizeWrite($request);

        $rule = DB::transaction(function () use ($rule): NetworkDocumentRule {
            $before = $rule->toArray();
            $rule->update(['is_active' => false]);
            $this->auditLogger->log($rule, 'deactivated', $before, $rule->fresh()->toArray());

            return $rule->refresh();
        });

        return response()->json($rule);
    }

    public function previewDocumentRule(Request $request, NetworkDocumentRule $rule): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'uploaded_document_type_ids' => ['sometimes', 'array'],
            'uploaded_document_type_ids.*' => ['integer', 'exists:document_types,id'],
        ]);

        $uploaded = collect($validated['uploaded_document_type_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $required = collect($rule->document_ids ?? [])->map(fn ($id) => (int) $id)->unique();
        $matched = $required->intersect($uploaded)->values();

        $satisfied = match ($rule->operator) {
            NetworkDocumentRule::OPERATOR_ANY => $matched->isNotEmpty(),
            NetworkDocumentRule::OPERATOR_ALL, NetworkDocumentRule::OPERATOR_EXACT => $matched->count() === $required->count(),
            default => false,
        };

        return response()->json([
            'rule_id' => $rule->id,
            'operator' => $rule->operator,
            'satisfied' => $satisfied,
            'required_document_type_ids' => $required->values(),
            'uploaded_document_type_ids' => $uploaded->values(),
            'matched_document_type_ids' => $matched,
        ]);
    }

    public function listDocumentTypes(Request $request): JsonResponse
    {
        $this->authorizeRead($request);

        return response()->json([
            'data' => DocumentType::query()->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function storeDocumentType(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:document_types,key'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(DocumentType::CATEGORIES)],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $type = DB::transaction(function () use ($validated): DocumentType {
            $type = DocumentType::query()->create(array_merge(['is_active' => true], $validated));
            $this->auditLogger->log($type, 'created', null, $type->fresh()->toArray());

            return $type;
        });

        return response()->json($type, 201);
    }

    public function webhookConfig(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        return response()->json([
            'endpoint_url_hint' => url('/api/v1/webhooks/dr-networks/{endpointToken}'),
            'webhook_signatures_enabled' => (bool) ($network->settings['webhook_signatures_enabled'] ?? false),
            'webhook_endpoint_token_configured' => filled($network->settings['webhook_endpoint_token_hash'] ?? null),
        ]);
    }

    public function updateWebhookConfig(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeCredentialWrite($request);

        $validated = $request->validate([
            'webhook_endpoint_token' => ['sometimes', 'nullable', 'string'],
            'webhook_signatures_enabled' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($network, $validated): void {
            $before = $network->toArray();
            $settings = $network->settings ?? [];

            if (array_key_exists('webhook_endpoint_token', $validated)) {
                $settings['webhook_endpoint_token_hash'] = filled($validated['webhook_endpoint_token'])
                    ? DrNetworkConfigValue::lookupHash((string) $validated['webhook_endpoint_token'])
                    : null;
            }

            if (array_key_exists('webhook_signatures_enabled', $validated)) {
                $settings['webhook_signatures_enabled'] = (bool) $validated['webhook_signatures_enabled'];
            }

            $network->settings = $settings;
            $network->config_version = ((int) $network->config_version) + 1;
            $network->save();

            $this->auditLogger->log($network, 'webhook_config_updated', $before, $network->fresh()->toArray());
        });

        return $this->webhookConfig($request, $network->refresh());
    }

    public function webhookLog(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $query = $network->webhookEvents()
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->latest('id');

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function replayWebhook(Request $request, DrNetwork $network, DrNetworkWebhookEvent $event): JsonResponse
    {
        $this->authorizeSupportAction($request);

        if ((int) $event->dr_network_id !== (int) $network->id) {
            abort(404);
        }

        $event->update([
            'status' => DrNetworkWebhookEvent::STATUS_PENDING,
            'failure_reason' => null,
            'processed_at' => null,
        ]);

        ProcessNetworkWebhook::dispatch($event->id);
        $this->auditLogger->log($event, 'replayed', null, $event->fresh()->toArray());

        return response()->json(['queued' => true, 'event_id' => $event->id]);
    }

    public function flowRuns(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $query = $network->flowRuns()
            ->with(['order:id,order_uuid,state_code,product_id,network_flow_key', 'flowDefinition'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->when($request->filled('stuck_since'), fn (Builder $query) => $query->where('updated_at', '<=', $request->query('stuck_since')))
            ->latest('id');

        $flowRuns = $query->paginate($this->perPage($request));
        $flowRuns->getCollection()->transform(fn (DrNetworkFlowRun $flowRun): array => $this->flowRunPayload($flowRun));

        return response()->json($flowRuns);
    }

    public function cases(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:100'],
            'payment_status' => ['sometimes', 'nullable', 'string', 'max:100'],
            'flow_status' => ['sometimes', 'nullable', Rule::in(DrNetworkFlowRun::STATUSES)],
            'current_step_key' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'product_id' => ['sometimes', 'nullable', 'uuid', 'exists:products,id'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::query()
            ->where('dr_network_id', $network->id)
            ->with([
                'patient:id,first_name,middle_name,last_name,email,phone,cell,state,city,zip',
                'product:id,name,slug',
                'networkFlow:id,flow_key,name',
                'flowRun:id,order_id,dr_network_id,flow_id,status,current_step_key,context,pause_reason,failure_reason,started_at,paused_at,completed_at,failed_at,updated_at',
                'flowRun.flowDefinition:id,flow_key,name',
                'flowRun.steps:id,flow_run_id,step_key,status,error_message,started_at,completed_at',
                'consultationRecord:id,order_id,dr_network_id,network_case_id,network_status,internal_status,submitted_at,resolved_at,payable_amount,currency',
                'drNetworkTransaction:id,order_id,consultation_record_id,patient_paid_amount,network_owed_amount,currency,status,void_reason,voided_at,occurred_at',
                'payments:id,order_id,amount,currency,status,created_at',
            ])
            ->when(! empty($validated['status']), fn (Builder $query) => $query->where('status', $validated['status']))
            ->when(! empty($validated['payment_status']), fn (Builder $query) => $query->where('payment_status', $validated['payment_status']))
            ->when(! empty($validated['state_code']), fn (Builder $query) => $query->where('state_code', strtoupper((string) $validated['state_code'])))
            ->when(! empty($validated['product_id']), fn (Builder $query) => $query->where('product_id', $validated['product_id']))
            ->when(! empty($validated['date_from']), fn (Builder $query) => $query->where('created_at', '>=', $validated['date_from']))
            ->when(! empty($validated['date_to']), fn (Builder $query) => $query->where('created_at', '<=', $validated['date_to']))
            ->when(! empty($validated['flow_status']), function (Builder $query) use ($validated): void {
                $query->whereHas('flowRun', fn (Builder $flowRunQuery) => $flowRunQuery->where('status', $validated['flow_status']));
            })
            ->when(! empty($validated['current_step_key']), function (Builder $query) use ($validated): void {
                $query->whereHas('flowRun', fn (Builder $flowRunQuery) => $flowRunQuery->where('current_step_key', $validated['current_step_key']));
            })
            ->when(! empty($validated['search']), function (Builder $query) use ($validated): void {
                $search = (string) $validated['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('order_uuid', 'like', "%{$search}%")
                        ->when(is_numeric($search), fn (Builder $query) => $query->orWhere('id', (int) $search))
                        ->orWhereHas('patient', function (Builder $patientQuery) use ($search): void {
                            $patientQuery
                                ->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('cell', 'like', "%{$search}%");
                        })
                        ->orWhereHas('consultationRecord', function (Builder $recordQuery) use ($search): void {
                            $recordQuery->where('network_case_id', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id');

        $cases = $query->paginate($this->perPage($request));
        $cases->getCollection()->transform(fn (Order $order): array => $this->drNetworkCaseListPayload($order));

        return response()->json($cases);
    }

    public function showCase(Request $request, DrNetwork $network, Order $order): JsonResponse
    {
        $this->authorizeRead($request);

        if ((int) $order->dr_network_id !== (int) $network->id) {
            abort(404);
        }

        $order->load([
            'patient:id,first_name,middle_name,last_name,email,phone,cell,state,city,zip,address,birthday,age,gender',
            'product:id,name,slug',
            'networkFlow:id,flow_key,name,steps',
            'flowRun:id,order_id,dr_network_id,flow_id,status,current_step_key,context,pause_reason,failure_reason,started_at,paused_at,completed_at,failed_at,updated_at',
            'flowRun.flowDefinition:id,flow_key,name',
            'flowRun.steps:id,flow_run_id,step_key,status,output,error_message,started_at,completed_at',
            'consultationRecord:id,order_id,dr_network_id,network_case_id,network_status,internal_status,network_metadata,submitted_at,resolved_at,payable_amount,currency',
            'drNetworkTransaction:id,order_id,consultation_record_id,patient_paid_amount,network_owed_amount,currency,status,void_reason,voided_at,occurred_at,metadata',
            'payments:id,order_id,amount,currency,status,failure_reason,created_at',
            'intakeAnswers:id,order_id,question_id,answer_value,created_at',
            'intakeAnswers.question:id,question_set_id,question_key,question_text,input_type',
            'documents:id,order_id,document_type_id,original_filename,file_path,mime_type,status,metadata,verified_at,created_at',
            'documents.documentType:id,key,name,category',
        ]);

        return response()->json($this->drNetworkCasePayload($network, $order));
    }

    public function previewCaseDocument(Request $request, DrNetwork $network, Order $order, OrderDocument $document)
    {
        $this->authorizeRead($request);
        $this->assertCaseDocumentBelongsToNetworkOrder($network, $order, $document);

        return $this->streamCaseDocument($document, 'inline');
    }

    public function downloadCaseDocument(Request $request, DrNetwork $network, Order $order, OrderDocument $document)
    {
        $this->authorizeRead($request);
        $this->assertCaseDocumentBelongsToNetworkOrder($network, $order, $document);

        return $this->streamCaseDocument($document, 'attachment');
    }

    public function showFlowRun(Request $request, DrNetwork $network, DrNetworkFlowRun $run): JsonResponse
    {
        $this->authorizeRead($request);

        if ((int) $run->dr_network_id !== (int) $network->id) {
            abort(404);
        }

        $run->load(['order', 'flowDefinition', 'steps']);

        return response()->json($this->flowRunPayload($run, includeSteps: true));
    }

    public function retryFlowRunPoll(Request $request, DrNetwork $network, DrNetworkFlowRun $run): JsonResponse
    {
        $this->authorizeSupportAction($request);

        if ((int) $run->dr_network_id !== (int) $network->id) {
            abort(404);
        }

        $run->loadMissing(['order.drNetwork']);
        $networkCaseId = $run->context['network_case_id'] ?? null;

        if (! $run->order || ! $networkCaseId) {
            return response()->json(['message' => 'Flow run has no order or network_case_id to poll.'], 422);
        }

        try {
            $adapter = $this->adapterResolver->resolve($network->load('configValues'));
            $statusPayload = $adapter->getCaseStatus((string) $networkCaseId);
            $this->consultationStatusService->handleNetworkStatusUpdate(
                $run->order,
                $statusPayload['network_status'] ?? 'unknown',
                $statusPayload['raw'] ?? []
            );

            return response()->json([
                'ok' => true,
                'case_id' => $statusPayload['case_id'] ?? (string) $networkCaseId,
                'network_status' => $statusPayload['network_status'] ?? 'unknown',
                'raw' => $statusPayload['raw'] ?? [],
                'flow_run' => $run->fresh(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function validateFlow(Request $request, array $extraRules = []): array
    {
        $validated = $request->validate(array_merge([
            'description' => ['sometimes', 'nullable', 'string'],
            'steps' => ['sometimes', 'array'],
            'steps.*.step_key' => ['required_with:steps', 'string'],
            'steps.*.name' => ['sometimes', 'string'],
            'steps.*.description' => ['sometimes', 'nullable', 'string'],
            'steps.*.required' => ['sometimes', 'boolean'],
            'steps.*.order' => ['sometimes', 'integer', 'min:1'],
            'network_fee_amount' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'patient_fee_amount' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ], $extraRules));

        if (array_key_exists('steps', $validated)) {
            $errors = $this->flowStepErrors($validated['steps']);
            if ($errors !== []) {
                abort(response()->json(['message' => 'Flow steps are invalid.', 'errors' => $errors], 422));
            }
        }

        return $validated;
    }

    private function flowStepErrors(array $steps): array
    {
        $errors = [];
        $keys = collect($steps)->pluck('step_key')->filter()->values();

        foreach ($keys as $key) {
            if (! in_array($key, self::STEP_KEYS, true)) {
                $errors[] = "Unknown step_key [{$key}].";
            }
        }

        if ($keys->duplicates()->isNotEmpty()) {
            $errors[] = 'Step keys must be unique within a flow.';
        }

        $positions = $keys->flip();

        if ($positions->has('slot_selection') && $positions->has('intake_questions') && $positions['slot_selection'] < $positions['intake_questions']) {
            $errors[] = 'slot_selection cannot appear before intake_questions.';
        }

        foreach (['provider_review', 'video_consultation'] as $terminalStep) {
            if ($positions->has($terminalStep) && $positions->has('review_and_submit') && $positions[$terminalStep] < $positions['review_and_submit']) {
                $errors[] = "{$terminalStep} cannot appear before review_and_submit.";
            }
        }

        return $errors;
    }

    private function validateQuestion(Request $request, bool $creating): array
    {
        return $request->validate([
            'question_key' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'question_text' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'help_text' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'input_type' => [$creating ? 'required' : 'sometimes', Rule::in(NetworkIntakeQuestion::INPUT_TYPES)],
            'options' => ['sometimes', 'nullable', 'array'],
            'is_required' => ['sometimes', 'boolean'],
            'validation_rules' => ['sometimes', 'nullable', 'array'],
            'is_conditional' => ['sometimes', 'boolean'],
            'condition_rules' => ['sometimes', 'nullable', 'array'],
            'network_field_mapping' => ['sometimes', 'nullable', 'string', 'max:150'],
            'network_validation' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function validateDocumentRule(Request $request, bool $creating): array
    {
        return $request->validate([
            'flow_key' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'state_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'product_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rule_key' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'rule_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'priority' => ['sometimes', 'integer'],
            'requirement_type' => [$creating ? 'required' : 'sometimes', Rule::in(NetworkDocumentRule::REQUIREMENT_TYPES)],
            'operator' => ['sometimes', Rule::in(NetworkDocumentRule::OPERATORS)],
            'document_ids' => [$creating ? 'required' : 'sometimes', 'array'],
            'document_ids.*' => ['integer', 'exists:document_types,id'],
            'is_required' => ['sometimes', 'boolean'],
            'conditions' => ['sometimes', 'nullable', 'array'],
            'error_message' => ['sometimes', 'nullable', 'string', 'max:255'],
            'help_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function questionSetValidationErrors(NetworkIntakeQuestionSet $set): array
    {
        $errors = [];
        $questions = $set->allQuestions()->get();
        $activeQuestions = $questions->where('is_active', true)->values();
        $activeQuestionKeys = $activeQuestions
            ->pluck('question_key')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($activeQuestions->isEmpty()) {
            $errors[] = 'Question set must have at least one active question.';
        }

        $duplicateKeys = $questions->groupBy('question_key')->filter(fn ($items) => $items->count() > 1)->keys();
        if ($duplicateKeys->isNotEmpty()) {
            $errors[] = 'Duplicate question keys: '.$duplicateKeys->implode(', ');
        }

        foreach ($activeQuestions as $question) {
            $label = "Question [{$question->question_key}]";
            $conditionRules = $question->condition_rules;

            if ($question->is_conditional && empty($conditionRules)) {
                $errors[] = "{$label} is conditional but has no condition_rules.";
            }

            if (! $question->is_conditional && ! empty($conditionRules)) {
                $errors[] = "{$label} has condition_rules but is_conditional is false.";
            }

            if (! empty($conditionRules) && ! is_array($conditionRules)) {
                $errors[] = "{$label} condition_rules must be an array.";
            }

            if (is_array($conditionRules)) {
                foreach ($this->conditionValidationErrors($conditionRules, $activeQuestionKeys, "{$label} condition_rules") as $error) {
                    $errors[] = $error;
                }
            }

            foreach ($this->blockingRuleValidationErrors($question, $activeQuestionKeys) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function blockingRuleValidationErrors(NetworkIntakeQuestion $question, array $activeQuestionKeys): array
    {
        $errors = [];
        $label = "Question [{$question->question_key}]";
        $networkValidation = $question->network_validation;

        if ($networkValidation === null || $networkValidation === []) {
            return [];
        }

        if (! is_array($networkValidation)) {
            return ["{$label} network_validation must be an object."];
        }

        if (! array_key_exists('blocking_rules', $networkValidation)) {
            return [];
        }

        $blockingRules = $networkValidation['blocking_rules'];

        if (! is_array($blockingRules)) {
            return ["{$label} network_validation.blocking_rules must be an array."];
        }

        foreach ($blockingRules as $index => $rule) {
            $path = "{$label} blocking_rules[{$index}]";

            if (! is_array($rule)) {
                $errors[] = "{$path} must be an object.";

                continue;
            }

            $hardStopType = $rule['hard_stop_type'] ?? null;

            if (! is_string($hardStopType) || $hardStopType === '') {
                $errors[] = "{$path}.hard_stop_type is required.";
            } elseif (! in_array($hardStopType, IntakeAnswerBlockingRuleEvaluator::HARD_STOP_TYPES, true)) {
                $errors[] = "{$path}.hard_stop_type [{$hardStopType}] is invalid. Allowed values: ".implode(', ', IntakeAnswerBlockingRuleEvaluator::HARD_STOP_TYPES).'.';
            }

            $conditions = $rule['conditions'] ?? null;

            if (empty($conditions)) {
                $errors[] = "{$path}.conditions must contain at least one condition.";
            } elseif (! is_array($conditions)) {
                $errors[] = "{$path}.conditions must be an array.";
            } else {
                foreach ($this->conditionValidationErrors($conditions, $activeQuestionKeys, "{$path}.conditions") as $error) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    private function conditionValidationErrors(array $conditions, array $activeQuestionKeys, string $path): array
    {
        $errors = [];

        foreach ($conditions as $index => $condition) {
            $conditionPath = "{$path}[{$index}]";

            if (! is_array($condition)) {
                $errors[] = "{$conditionPath} must be an object.";

                continue;
            }

            $source = $condition['source'] ?? $this->legacyConditionSource($condition);

            if (! is_string($source) || $source === '') {
                $errors[] = "{$conditionPath}.source is required.";
            } elseif (str_starts_with($source, 'answers.')) {
                $questionKey = substr($source, strlen('answers.'));

                if ($questionKey === '') {
                    $errors[] = "{$conditionPath}.source must include a question key after answers.";
                } elseif (! in_array($questionKey, $activeQuestionKeys, true)) {
                    $errors[] = "{$conditionPath}.source references missing or inactive question_key [{$questionKey}].";
                }
            }

            $operator = $condition['operator'] ?? $this->legacyConditionOperator($condition);

            if (! in_array($operator, IntakeQuestionRuleEvaluator::OPERATORS, true)) {
                $errors[] = "{$conditionPath}.operator [{$operator}] is invalid. Allowed values: ".implode(', ', IntakeQuestionRuleEvaluator::OPERATORS).'.';
            }

            if (! in_array($operator, [IntakeQuestionRuleEvaluator::OPERATOR_EXISTS, IntakeQuestionRuleEvaluator::OPERATOR_MISSING], true)
                && ! array_key_exists('value', $condition)
                && ! $this->hasLegacyConditionValue($condition)
            ) {
                $errors[] = "{$conditionPath}.value is required for operator [{$operator}].";
            }
        }

        return $errors;
    }

    private function legacyConditionSource(array $condition): ?string
    {
        $questionKey = $condition['when'] ?? null;

        return is_string($questionKey) && $questionKey !== '' ? 'answers.'.$questionKey : null;
    }

    private function legacyConditionOperator(array $condition): string
    {
        foreach ([
            IntakeQuestionRuleEvaluator::OPERATOR_EQUALS,
            IntakeQuestionRuleEvaluator::OPERATOR_NOT_EQUALS,
            IntakeQuestionRuleEvaluator::OPERATOR_IN,
            IntakeQuestionRuleEvaluator::OPERATOR_NOT_IN,
            IntakeQuestionRuleEvaluator::OPERATOR_EXISTS,
            IntakeQuestionRuleEvaluator::OPERATOR_MISSING,
        ] as $operator) {
            if (array_key_exists($operator, $condition)) {
                return $operator;
            }
        }

        return IntakeQuestionRuleEvaluator::OPERATOR_EQUALS;
    }

    private function hasLegacyConditionValue(array $condition): bool
    {
        foreach ([
            IntakeQuestionRuleEvaluator::OPERATOR_EQUALS,
            IntakeQuestionRuleEvaluator::OPERATOR_NOT_EQUALS,
            IntakeQuestionRuleEvaluator::OPERATOR_IN,
            IntakeQuestionRuleEvaluator::OPERATOR_NOT_IN,
        ] as $operator) {
            if (array_key_exists($operator, $condition)) {
                return true;
            }
        }

        return false;
    }

    private function syntheticIntakeContext(array $patient, array $answers): array
    {
        $context = [];

        foreach ($patient as $key => $value) {
            $context["patient.{$key}"] = $value;
            $context[$key] = $value;
        }

        foreach ($answers as $key => $value) {
            $context["answers.{$key}"] = $value;
        }

        return $context;
    }

    private function productScopeCode(array $validated): string
    {
        if (! empty($validated['product_code'])) {
            return $validated['product_code'];
        }

        if (! empty($validated['product_id'])) {
            return Product::query()->whereKey($validated['product_id'])->value('slug')
                ?? NetworkIntakeQuestionSet::ALL_SCOPE;
        }

        return NetworkIntakeQuestionSet::ALL_SCOPE;
    }

    private function contentCoveragePayload($products, array $overrideProductCodes, bool $hasDefaultContent, array $extra = []): array
    {
        $overrideProductCodes = collect($overrideProductCodes)->filter()->unique()->values();

        $productsWithOverride = $products
            ->filter(fn (Product $product): bool => $overrideProductCodes->contains($product->slug))
            ->values();

        $missingOverride = $products
            ->reject(fn (Product $product): bool => $overrideProductCodes->contains($product->slug))
            ->values();

        $usingDefault = $hasDefaultContent ? $missingOverride : collect();
        $withoutContent = $hasDefaultContent ? collect() : $missingOverride;

        return array_merge([
            'products_total' => $products->count(),
            'products_with_override' => $productsWithOverride->count(),
            'products_using_default' => $usingDefault->count(),
            'products_without_content' => $withoutContent->count(),
            'missing' => $missingOverride->map(fn (Product $product): array => $this->coverageProductPayload($product))->values(),
            'using_default' => $usingDefault->map(fn (Product $product): array => $this->coverageProductPayload($product))->values(),
            'without_content' => $withoutContent->map(fn (Product $product): array => $this->coverageProductPayload($product))->values(),
        ], $extra);
    }

    private function coverageProductPayload(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'product_name' => $product->name,
        ];
    }

    private function drNetworkCaseListPayload(Order $order): array
    {
        $patient = $order->patient;
        $patientName = $patient
            ? trim(implode(' ', array_filter([
                $patient->first_name,
                $patient->middle_name,
                $patient->last_name,
            ])))
            : null;

        $totalPaidAmount = $order->payments->reduce(
            fn (string $carry, $payment): string => DecimalMoney::add($carry, $payment->amount ?? 0),
            '0.00'
        );

        return [
            'id' => $order->id,
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'state_code' => $order->state_code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'final_amount' => $order->final_amount,
            'total_paid_amount' => $totalPaidAmount,
            'dr_network_patient_fee_amount' => $order->dr_network_patient_fee_amount,
            'dr_network_fee_amount' => $order->dr_network_fee_amount,
            'created_at' => $order->created_at,
            'patient' => $patient ? [
                'id' => $patient->id,
                'name' => $patientName,
                'email' => $patient->email,
                'phone' => $patient->phone ?: $patient->cell,
            ] : null,
            'product' => $order->product ? [
                'id' => $order->product->id,
                'name' => $order->product->name,
                'slug' => $order->product->slug,
            ] : null,
            'flow' => $order->networkFlow ? [
                'id' => $order->networkFlow->id,
                'flow_key' => $order->networkFlow->flow_key,
                'name' => $order->networkFlow->name,
            ] : null,
            'flow_run' => $order->flowRun ? [
                'id' => $order->flowRun->id,
                'status' => $order->flowRun->status,
                'current_step_key' => $order->flowRun->current_step_key,
                'failure_reason' => $order->flowRun->failure_reason,
                'failure_message' => $this->flowRunFailureMessage($order->flowRun),
                'pause_reason' => $order->flowRun->pause_reason,
                'status_reason' => $this->flowRunStatusMessage($order->flowRun),
                'status_message' => $this->flowRunStatusMessage($order->flowRun),
            ] : null,
            'network_case_id' => $order->consultationRecord?->network_case_id,
            'consultation_status' => $order->consultationRecord?->internal_status,
            'finance_transaction_status' => $order->drNetworkTransaction?->status,
        ];
    }

    private function flowRunPayload(DrNetworkFlowRun $flowRun, bool $includeSteps = false): array
    {
        $payload = [
            'id' => $flowRun->id,
            'order_id' => $flowRun->order_id,
            'dr_network_id' => $flowRun->dr_network_id,
            'flow_id' => $flowRun->flow_id,
            'status' => $flowRun->status,
            'current_step_key' => $flowRun->current_step_key,
            'pause_reason' => $flowRun->pause_reason,
            'failure_reason' => $flowRun->failure_reason,
            'failure_message' => $this->flowRunFailureMessage($flowRun),
            'status_reason' => $this->flowRunStatusMessage($flowRun),
            'status_message' => $this->flowRunStatusMessage($flowRun),
            'started_at' => $flowRun->started_at,
            'paused_at' => $flowRun->paused_at,
            'completed_at' => $flowRun->completed_at,
            'failed_at' => $flowRun->failed_at,
            'updated_at' => $flowRun->updated_at,
            'context' => $flowRun->context,
            'order' => $flowRun->relationLoaded('order') && $flowRun->order ? [
                'id' => $flowRun->order->id,
                'order_uuid' => $flowRun->order->order_uuid,
                'state_code' => $flowRun->order->state_code,
                'product_id' => $flowRun->order->product_id,
                'network_flow_key' => $flowRun->order->network_flow_key,
            ] : null,
            'flow' => $flowRun->relationLoaded('flowDefinition') && $flowRun->flowDefinition ? [
                'id' => $flowRun->flowDefinition->id,
                'flow_key' => $flowRun->flowDefinition->flow_key,
                'name' => $flowRun->flowDefinition->name,
            ] : null,
        ];

        if ($includeSteps && $flowRun->relationLoaded('steps')) {
            $payload['steps'] = $flowRun->steps->map(fn (DrNetworkFlowRunStep $step): array => [
                'id' => $step->id,
                'step_key' => $step->step_key,
                'status' => $step->status,
                'output' => $step->output,
                'error_message' => $this->flowRunStepErrorMessage($step),
                'error_code' => $this->flowRunStepErrorCode($step),
                'started_at' => $step->started_at,
                'completed_at' => $step->completed_at,
            ])->values()->all();
        }

        return $payload;
    }

    private function drNetworkCasePayload(DrNetwork $network, Order $order): array
    {
        $patient = $order->patient;
        $patientName = $patient
            ? trim(implode(' ', array_filter([
                $patient->first_name,
                $patient->middle_name,
                $patient->last_name,
            ])))
            : null;

        $flowRun = $order->flowRun;
        $consultationRecord = $order->consultationRecord;
        $transaction = $order->drNetworkTransaction;
        $totalPaidAmount = $order->payments->reduce(
            fn (string $carry, $payment): string => DecimalMoney::add($carry, $payment->amount ?? 0),
            '0.00'
        );

        return [
            'id' => $order->id,
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'state_code' => $order->state_code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'purchase_type' => $order->purchase_type,
            'pricing_type' => $order->pricing_type,
            'currency' => $order->currency,
            'price' => $order->price,
            'base_amount' => $order->base_amount,
            'coupon_discount_amount' => $order->coupon_discount_amount,
            'final_amount' => $order->final_amount,
            'total_paid_amount' => $totalPaidAmount,
            'dr_network_patient_fee_amount' => $order->dr_network_patient_fee_amount,
            'dr_network_fee_amount' => $order->dr_network_fee_amount,
            'network_product_identifier' => $order->network_product_identifier,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'patient' => $patient ? [
                'id' => $patient->id,
                'name' => $patientName,
                'first_name' => $patient->first_name,
                'middle_name' => $patient->middle_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'phone' => $patient->phone ?: $patient->cell,
                'city' => $patient->city,
                'state' => $patient->state,
                'zip' => $patient->zip,
            ] : null,
            'product' => $order->product ? [
                'id' => $order->product->id,
                'name' => $order->product->name,
                'slug' => $order->product->slug,
            ] : null,
            'dr_network' => [
                'id' => $network->id,
                'name' => $network->name,
                'slug' => $network->slug,
                'adapter_key' => $network->adapter_key,
                'status' => $network->status,
            ],
            'flow' => $order->networkFlow ? [
                'id' => $order->networkFlow->id,
                'flow_key' => $order->networkFlow->flow_key,
                'name' => $order->networkFlow->name,
                'steps' => $this->caseFlowStepsPayload($order),
                'current_step' => $this->caseCurrentFlowStepPayload($order),
            ] : null,
            'flow_run' => $flowRun ? [
                'id' => $flowRun->id,
                'status' => $flowRun->status,
                'current_step_key' => $flowRun->current_step_key,
                'pause_reason' => $flowRun->pause_reason,
                'failure_reason' => $flowRun->failure_reason,
                'failure_message' => $this->flowRunFailureMessage($flowRun),
                'status_reason' => $this->flowRunStatusMessage($flowRun),
                'status_message' => $this->flowRunStatusMessage($flowRun),
                'context' => $flowRun->context,
                'started_at' => $flowRun->started_at,
                'paused_at' => $flowRun->paused_at,
                'completed_at' => $flowRun->completed_at,
                'failed_at' => $flowRun->failed_at,
                'updated_at' => $flowRun->updated_at,
                'steps' => $flowRun->steps->map(fn ($step): array => [
                    'id' => $step->id,
                    'step_key' => $step->step_key,
                    'status' => $step->status,
                    'error_message' => $this->flowRunStepErrorMessage($step),
                    'error_code' => $this->flowRunStepErrorCode($step),
                    'started_at' => $step->started_at,
                    'completed_at' => $step->completed_at,
                ])->values(),
            ] : null,
            'blocking_intake_answer' => $this->caseBlockingIntakeAnswerPayload($order),
            'consultation_record' => $consultationRecord ? [
                'id' => $consultationRecord->id,
                'network_case_id' => $consultationRecord->network_case_id,
                'network_status' => $consultationRecord->network_status,
                'internal_status' => $consultationRecord->internal_status,
                'payable_amount' => $consultationRecord->payable_amount,
                'currency' => $consultationRecord->currency,
                'submitted_at' => $consultationRecord->submitted_at,
                'resolved_at' => $consultationRecord->resolved_at,
            ] : null,
            'finance_transaction' => $transaction ? [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'patient_paid_amount' => $transaction->patient_paid_amount,
                'network_owed_amount' => $transaction->network_owed_amount,
                'profit_amount' => $transaction->profit_amount,
                'currency' => $transaction->currency,
                'void_reason' => $transaction->void_reason,
                'voided_at' => $transaction->voided_at,
                'occurred_at' => $transaction->occurred_at,
            ] : null,
            'payments' => $order->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'created_at' => $payment->created_at,
            ])->values(),
            'intake_answers' => $order->intakeAnswers->map(fn ($answer): array => [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'question_key' => $answer->question?->question_key,
                'question_text' => $answer->question?->question_text,
                'input_type' => $answer->question?->input_type,
                'answer_value' => $answer->decodedAnswerValue(),
                'created_at' => $answer->created_at,
            ])->values(),
            'documents' => $order->documents->map(fn ($document): array => [
                'id' => $document->id,
                'document_type_id' => $document->document_type_id,
                'document_type' => $document->documentType ? [
                    'id' => $document->documentType->id,
                    'key' => $document->documentType->key,
                    'name' => $document->documentType->name,
                    'category' => $document->documentType->category,
                ] : null,
                'original_filename' => $document->original_filename,
                'download_filename' => $this->caseDocumentFilename($document),
                'file_path' => $document->file_path,
                'preview_url' => route('admin.dr-networks.cases.documents.preview', [
                    'network' => $network->id,
                    'order' => $order->id,
                    'document' => $document->id,
                ]),
                'download_url' => route('admin.dr-networks.cases.documents.download', [
                    'network' => $network->id,
                    'order' => $order->id,
                    'document' => $document->id,
                ]),
                'can_preview' => true,
                'can_download' => true,
                'mime_type' => $document->mime_type,
                'status' => $document->status,
                'metadata' => $document->metadata,
                'verified_at' => $document->verified_at,
                'created_at' => $document->created_at,
            ])->values(),
        ];
    }

    private function caseFlowStepsPayload(Order $order): array
    {
        $definitionSteps = collect($order->networkFlow?->steps ?? [])
            ->sortBy(fn (array $step, int $index): int => (int) ($step['order'] ?? $index + 1))
            ->values();

        $runSteps = $order->flowRun?->steps?->keyBy('step_key') ?? collect();
        $currentStepKey = $order->flowRun?->current_step_key;
        $flowRun = $order->flowRun;
        $failedRunStep = $flowRun?->steps?->first(
            fn (DrNetworkFlowRunStep $step): bool => $step->status === DrNetworkFlowRunStep::STATUS_FAILED
        );
        $failedStepKey = $failedRunStep?->step_key
            ?? ($flowRun?->status === DrNetworkFlowRun::STATUS_FAILED ? $currentStepKey : null);
        $failedStepOrder = $failedStepKey
            ? $definitionSteps
                ->mapWithKeys(fn (array $step, int $index): array => [
                    (string) ($step['step_key'] ?? '') => (int) ($step['order'] ?? $index + 1),
                ])
                ->get($failedStepKey)
            : null;

        return $definitionSteps
            ->map(function (array $step, int $index) use ($runSteps, $currentStepKey, $flowRun, $failedStepOrder): array {
                $stepKey = (string) ($step['step_key'] ?? '');
                $runStep = $runSteps->get($stepKey);
                $stepOrder = (int) ($step['order'] ?? $index + 1);
                $status = $runStep?->status ?? ($stepKey === $currentStepKey ? 'in_progress' : 'pending');

                if (
                    $flowRun?->status === DrNetworkFlowRun::STATUS_FAILED
                    && $failedStepOrder !== null
                    && $stepOrder > $failedStepOrder
                ) {
                    $status = DrNetworkFlowRunStep::STATUS_FAILED;
                }

                return [
                    'step_key' => $stepKey,
                    'name' => $step['name'] ?? $this->humanizeStepKey($stepKey),
                    'description' => $step['description'] ?? null,
                    'required' => (bool) ($step['required'] ?? true),
                    'order' => $stepOrder,
                    'is_current' => $stepKey !== '' && $stepKey === $currentStepKey,
                    'run_step_id' => $runStep?->id,
                    'status' => $status,
                    'error_message' => $runStep ? $this->flowRunStepErrorMessage($runStep) : null,
                    'error_code' => $runStep ? $this->flowRunStepErrorCode($runStep) : null,
                    'started_at' => $runStep?->started_at,
                    'completed_at' => $runStep?->completed_at,
                ];
            })
            ->values()
            ->all();
    }

    private function caseCurrentFlowStepPayload(Order $order): ?array
    {
        $currentStepKey = $order->flowRun?->current_step_key;

        if (! $currentStepKey) {
            return null;
        }

        return collect($this->caseFlowStepsPayload($order))
            ->firstWhere('step_key', $currentStepKey);
    }

    private function caseBlockingIntakeAnswerPayload(Order $order): ?array
    {
        $flowRun = $order->flowRun;

        if (! $flowRun) {
            return null;
        }

        $context = $flowRun?->context ?? [];

        if (! is_array($context)) {
            return null;
        }

        $blockingQuestionKey = $context['blocking_question_key'] ?? null;
        $blockingQuestionId = $context['blocking_question_id'] ?? null;

        if (! is_string($blockingQuestionKey) && ! is_numeric($blockingQuestionId)) {
            return null;
        }

        $answer = $order->intakeAnswers
            ->first(function ($answer) use ($blockingQuestionKey, $blockingQuestionId): bool {
                if (is_numeric($blockingQuestionId) && (int) $answer->question_id === (int) $blockingQuestionId) {
                    return true;
                }

                return is_string($blockingQuestionKey)
                    && $answer->question?->question_key === $blockingQuestionKey;
            });

        $blockingRuleKey = $context['blocking_rule_key'] ?? null;
        $triggeredRule = collect($context['triggered_rules'] ?? [])
            ->first(fn ($rule): bool => is_array($rule) && is_string($blockingRuleKey) && ($rule['rule_key'] ?? null) === $blockingRuleKey);

        return [
            'question_id' => $answer?->question_id ?? (is_numeric($blockingQuestionId) ? (int) $blockingQuestionId : null),
            'question_key' => $answer?->question?->question_key ?? (is_string($blockingQuestionKey) ? $blockingQuestionKey : null),
            'question_text' => $answer?->question?->question_text,
            'input_type' => $answer?->question?->input_type,
            'answer_value' => $answer?->decodedAnswerValue() ?? ($context['blocking_answer'] ?? null),
            'answer_id' => $answer?->id,
            'answer_created_at' => $answer?->created_at,
            'rule_key' => is_string($blockingRuleKey) ? $blockingRuleKey : ($triggeredRule['rule_key'] ?? null),
            'failure_reason' => $context['failure_reason'] ?? $flowRun?->failure_reason,
            'failure_message' => $this->flowRunFailureMessage($flowRun),
            'hard_stop_type' => $context['hard_stop_type'] ?? ($triggeredRule['hard_stop_type'] ?? null),
            'conditions' => $context['conditions'] ?? ($triggeredRule['conditions'] ?? null),
            'triggered_rule' => $triggeredRule ?: null,
        ];
    }

    private function assertCaseDocumentBelongsToNetworkOrder(DrNetwork $network, Order $order, OrderDocument $document): void
    {
        if (
            (int) $order->dr_network_id !== (int) $network->id
            || (int) $document->order_id !== (int) $order->id
        ) {
            abort(404);
        }
    }

    private function streamCaseDocument(OrderDocument $document, string $disposition)
    {
        $document->loadMissing(['order.patient', 'documentType']);

        if (! Storage::exists($document->file_path)) {
            Log::warning('Dr Network case document stream failed: file missing.', [
                'document_id' => $document->id,
                'order_id' => $document->order_id,
                'file_path' => $document->file_path,
                'disposition' => $disposition,
            ]);

            abort(response()->json(['message' => 'Document file not found.'], 404));
        }

        $filename = $this->caseDocumentFilename($document);
        $headers = array_filter([
            'Content-Type' => $document->mime_type,
        ]);

        Log::info('Dr Network case document stream requested.', [
            'document_id' => $document->id,
            'order_id' => $document->order_id,
            'patient_email' => $document->order?->patient?->email,
            'document_type_key' => $document->documentType?->key,
            'original_filename' => $document->original_filename,
            'resolved_filename' => $filename,
            'file_path' => $document->file_path,
            'mime_type' => $document->mime_type,
            'disposition' => $disposition,
        ]);

        if ($disposition === 'attachment') {
            return Storage::download($document->file_path, $filename, $headers);
        }

        return Storage::response($document->file_path, $filename, $headers, 'inline');
    }

    private function caseDocumentFilename(OrderDocument $document): string
    {
        $document->loadMissing(['order.patient', 'documentType']);

        $patientEmail = $this->safeFilenamePart($document->order?->patient?->email ?? 'patient');
        $documentType = $this->safeFilenamePart(
            $document->documentType?->key
                ?? $document->documentType?->name
                ?? 'document'
        );
        $extension = $this->caseDocumentExtension($document);

        return "{$patientEmail}_{$documentType}.{$extension}";
    }

    private function caseDocumentExtension(OrderDocument $document): string
    {
        $originalExtension = pathinfo((string) $document->original_filename, PATHINFO_EXTENSION);
        $storedExtension = pathinfo((string) $document->file_path, PATHINFO_EXTENSION);
        $extension = strtolower($originalExtension ?: $storedExtension);

        if ($extension !== '') {
            return $extension;
        }

        return match ($document->mime_type) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    private function safeFilenamePart(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9@._-]+/', '-', $value) ?: '';
        $value = trim($value, '-_.');

        return $value !== '' ? $value : 'document';
    }

    private function humanizeStepKey(string $stepKey): string
    {
        return str($stepKey)->replace('_', ' ')->title()->toString();
    }

    private function flowRunStatusMessage(DrNetworkFlowRun $flowRun): ?string
    {
        return $this->flowRunFailureMessage($flowRun) ?: $flowRun->pause_reason;
    }

    private function flowRunFailureMessage(DrNetworkFlowRun $flowRun): ?string
    {
        $message = $flowRun->context['failure_message'] ?? $flowRun->context['error_message'] ?? null;

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return $this->humanizeReason($flowRun->failure_reason);
    }

    private function flowRunStepErrorMessage(DrNetworkFlowRunStep $step): ?string
    {
        $message = $step->output['failure_message'] ?? $step->output['error_message'] ?? null;

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return $this->humanizeReason($step->error_message);
    }

    private function flowRunStepErrorCode(DrNetworkFlowRunStep $step): ?string
    {
        $reason = $step->output['reason'] ?? $step->output['failure_reason'] ?? $step->error_message;

        return is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
    }

    private function humanizeReason(?string $reason): ?string
    {
        if (! is_string($reason) || trim($reason) === '') {
            return null;
        }

        $reason = trim($reason);

        if (! str_contains($reason, '_')) {
            return $reason;
        }

        return str($reason)->replace('_', ' ')->ucfirst()->toString();
    }

    private function assertFlowBelongsToNetwork(int $flowId, int $networkId): void
    {
        $belongs = NetworkFlowDefinition::query()
            ->whereKey($flowId)
            ->where('dr_network_id', $networkId)
            ->exists();

        if (! $belongs) {
            abort(response()->json(['message' => 'Selected flow does not belong to the selected network.'], 422));
        }
    }

    private function upsertConfigValue(
        DrNetwork $network,
        string $key,
        mixed $value,
        string $valueType,
        bool $isSecret,
        string $displayName
    ): void {
        DrNetworkConfigValue::query()->updateOrCreate([
            'dr_network_id' => $network->id,
            'key' => $key,
        ], [
            'value' => $value,
            'lookup_hash' => filled($value) ? DrNetworkConfigValue::lookupHash((string) $value) : null,
            'value_type' => $valueType,
            'is_secret' => $isSecret,
            'display_name' => $displayName,
        ]);
    }

    private function maskedConfigValues(DrNetwork $network): array
    {
        $network->loadMissing('configValues');

        return $network->configValues
            ->map(fn (DrNetworkConfigValue $value): array => [
                'key' => $value->key,
                'display_name' => $value->display_name,
                'value_type' => $value->value_type,
                'is_secret' => $value->is_secret,
                'configured' => filled($value->typedValue()),
                'fingerprint' => filled($value->typedValue()) ? substr(hash('sha256', (string) $value->typedValue()), 0, 12) : null,
                'value' => $value->is_secret ? null : $value->typedValue(),
            ])
            ->values()
            ->all();
    }

    private function authorizeRead(Request $request): void
    {
        $this->authorizeRole($request, self::READ_ROLES);
    }

    private function authorizeWrite(Request $request): void
    {
        $this->authorizeRole($request, self::CONFIG_WRITE_ROLES);
    }

    private function authorizeCredentialWrite(Request $request): void
    {
        $this->authorizeRole($request, self::CREDENTIAL_WRITE_ROLES);
    }

    private function authorizeSupportAction(Request $request): void
    {
        $this->authorizeRole($request, ['admin', 'super_admin', 'support']);
    }

    private function authorizeRole(Request $request, array $roles): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (in_array($user->role, $roles, true) || ((bool) ($user->isCompany ?? false) && in_array('admin', $roles, true))) {
            return;
        }

        abort(response()->json(['message' => 'You do not have permission to manage Dr Network configuration.'], 403));
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 25), 1), 100);
    }
}
