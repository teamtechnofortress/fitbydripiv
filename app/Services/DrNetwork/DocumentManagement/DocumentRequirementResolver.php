<?php

namespace App\Services\DrNetwork\DocumentManagement;

use App\Models\DocumentType;
use App\Models\NetworkDocumentRule;
use Illuminate\Support\Collection;

class DocumentRequirementResolver
{
    private const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

    private const ACCEPTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    private const ACCEPTED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'pdf',
    ];

    public function resolve(int $drNetworkId, string $flowKey, ?string $stateCode = null, ?string $productCode = null): array
    {
        $rules = NetworkDocumentRule::query()
            ->forNetwork($drNetworkId)
            ->forFlow($flowKey)
            ->forState($stateCode)
            ->forProduct($productCode)
            ->active()
            ->ordered()
            ->get();

        $documentTypes = $this->documentTypesForRules($rules);

        return $rules
            ->map(fn (NetworkDocumentRule $rule): array => $this->formatRule($rule, $documentTypes))
            ->values()
            ->all();
    }

    private function documentTypesForRules(Collection $rules): Collection
    {
        $documentIds = $rules
            ->flatMap(fn (NetworkDocumentRule $rule): array => $rule->document_ids ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($documentIds->isEmpty()) {
            return collect();
        }

        return DocumentType::query()
            ->whereIn('id', $documentIds)
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    private function formatRule(NetworkDocumentRule $rule, Collection $documentTypes): array
    {
        $documentTypeOptions = collect($rule->document_ids ?? [])
            ->map(fn (int|string $id) => $documentTypes->get((int) $id))
            ->filter()
            ->map(fn (DocumentType $documentType): array => [
                'id' => $documentType->id,
                'key' => $documentType->key,
                'name' => $documentType->name,
                'category' => $documentType->category,
                'description' => $documentType->description,
                'metadata' => $documentType->metadata ?? [],
            ])
            ->values()
            ->all();

        return array_merge($rule->toArray(), [
            'document_type_options' => $documentTypeOptions,
            'upload' => [
                'field_name' => 'document',
                'document_type_field_name' => 'document_type_id',
                'max_size_bytes' => self::MAX_UPLOAD_BYTES,
                'max_size_mb' => 20,
                'accepted_mime_types' => self::ACCEPTED_MIME_TYPES,
                'accepted_extensions' => self::ACCEPTED_EXTENSIONS,
            ],
            'satisfaction' => [
                'operator' => $rule->operator,
                'required_count' => $this->requiredCount($rule, count($documentTypeOptions)),
                'description' => $this->operatorDescription($rule),
            ],
        ]);
    }

    private function requiredCount(NetworkDocumentRule $rule, int $documentTypeCount): int
    {
        return match ($rule->operator) {
            NetworkDocumentRule::OPERATOR_ANY => min(1, $documentTypeCount),
            NetworkDocumentRule::OPERATOR_ALL, NetworkDocumentRule::OPERATOR_EXACT => $documentTypeCount,
            default => 0,
        };
    }

    private function operatorDescription(NetworkDocumentRule $rule): string
    {
        return match ($rule->operator) {
            NetworkDocumentRule::OPERATOR_ANY => 'Upload any one of the accepted document types.',
            NetworkDocumentRule::OPERATOR_ALL => 'Upload all accepted document types.',
            NetworkDocumentRule::OPERATOR_EXACT => 'Upload exactly the accepted document types.',
            default => 'Upload the required document.',
        };
    }
}
