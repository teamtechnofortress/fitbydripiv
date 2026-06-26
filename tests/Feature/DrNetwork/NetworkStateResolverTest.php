<?php

namespace Tests\Feature\DrNetwork;

use App\Models\DrNetwork;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkStateMapping;
use App\Models\State;
use App\Services\DrNetwork\Resolvers\NetworkStateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkStateResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_next_active_network_when_higher_priority_network_is_paused(): void
    {
        $state = State::query()->create([
            'country_code' => 'US',
            'state_code' => 'CA',
            'state_name' => 'California',
            'is_active' => true,
        ]);
        $flow = $this->createFlow('async_review');
        $pausedNetwork = $this->createNetwork('Ola Health', 'ola-health', DrNetwork::STATUS_PAUSED);
        $activeNetwork = $this->createNetwork('Network B', 'network-b', DrNetwork::STATUS_ACTIVE);

        NetworkStateMapping::query()->create([
            'state_id' => $state->id,
            'dr_network_id' => $pausedNetwork->id,
            'flow_id' => $flow->id,
            'priority' => 1,
            'is_active' => true,
        ]);
        NetworkStateMapping::query()->create([
            'state_id' => $state->id,
            'dr_network_id' => $activeNetwork->id,
            'flow_id' => $flow->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        $resolved = app(NetworkStateResolver::class)->resolve('CA');

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved['network']->is($activeNetwork));
        $this->assertTrue($resolved['flow']->is($flow));
    }

    public function test_it_falls_back_to_next_active_flow_when_higher_priority_flow_is_inactive(): void
    {
        $state = State::query()->create([
            'country_code' => 'US',
            'state_code' => 'CA',
            'state_name' => 'California',
            'is_active' => true,
        ]);
        $network = $this->createNetwork('Ola Health', 'ola-health', DrNetwork::STATUS_ACTIVE);
        $inactiveFlow = $this->createFlow('inactive_async_review', false);
        $activeFlow = $this->createFlow('active_async_review');

        NetworkStateMapping::query()->create([
            'state_id' => $state->id,
            'dr_network_id' => $network->id,
            'flow_id' => $inactiveFlow->id,
            'priority' => 1,
            'is_active' => true,
        ]);
        NetworkStateMapping::query()->create([
            'state_id' => $state->id,
            'dr_network_id' => $network->id,
            'flow_id' => $activeFlow->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        $resolved = app(NetworkStateResolver::class)->resolve('CA');

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved['network']->is($network));
        $this->assertTrue($resolved['flow']->is($activeFlow));
    }

    private function createNetwork(string $name, string $slug, string $status): DrNetwork
    {
        return DrNetwork::query()->create([
            'name' => $name,
            'slug' => $slug,
            'adapter_key' => $slug,
            'integration_mode' => DrNetwork::INTEGRATION_MODE_API,
            'status' => $status,
            'is_default' => false,
        ]);
    }

    private function createFlow(string $flowKey, bool $isActive = true): NetworkFlowDefinition
    {
        return NetworkFlowDefinition::query()->create([
            'flow_key' => $flowKey,
            'name' => str_replace('_', ' ', $flowKey),
            'steps' => ['intake', 'review'],
            'is_active' => $isActive,
        ]);
    }
}
