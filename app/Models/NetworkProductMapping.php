<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkProductMapping extends Model
{
    use HasFactory;

    protected $table = 'network_product_mappings';

    protected $fillable = [
        'dr_network_id',
        'product_id',
        'flow_id',
        'external_service_id',
        'external_service_key',
        'external_config',
        'is_active',
    ];

    protected $casts = [
        'dr_network_id' => 'integer',
        'flow_id' => 'integer',
        'external_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function drNetwork(): BelongsTo
    {
        return $this->belongsTo(DrNetwork::class, 'dr_network_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function flowDefinition(): BelongsTo
    {
        return $this->belongsTo(NetworkFlowDefinition::class, 'flow_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForNetwork(Builder $query, int $drNetworkId): Builder
    {
        return $query->where('dr_network_id', $drNetworkId);
    }

    public function scopeForProduct(Builder $query, string $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForFlow(Builder $query, int $flowId): Builder
    {
        return $query->where('flow_id', $flowId);
    }
}
