<?php

namespace App\Services\DrNetwork\DocumentManagement;

use App\Models\DocumentType;
use App\Models\NetworkDocumentRule;
use App\Models\OrderDocument;
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

    public function resolve(
        int $drNetworkId,
        string $flowKey,
        ?string $stateCode = null,
        ?string $productCode = null,
        ?int $orderId = null
    ): array {
        $rules = NetworkDocumentRule::query()
            ->forNetwork($drNetworkId)
            ->forFlow($flowKey)
            ->forState($stateCode)
            ->forProduct($productCode)
            ->active()
            ->ordered()
            ->get();

        $documentTypes = $this->documentTypesForRules($rules);
        $uploadedDocuments = $this->uploadedDocumentsForRules($orderId, $rules);

        return $rules
            ->map(fn (NetworkDocumentRule $rule): array => $this->formatRule($rule, $documentTypes, $uploadedDocuments))
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

    private function uploadedDocumentsForRules(?int $orderId, Collection $rules): Collection
    {
        if ($orderId === null) {
            return collect();
        }

        $documentIds = $rules
            ->flatMap(fn (NetworkDocumentRule $rule): array => $rule->document_ids ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($documentIds->isEmpty()) {
            return collect();
        }

        return OrderDocument::query()
            ->where('order_id', $orderId)
            ->whereIn('document_type_id', $documentIds)
            ->latest('id')
            ->get();
    }

    private function formatRule(NetworkDocumentRule $rule, Collection $documentTypes, Collection $uploadedDocuments): array
    {
        $ruleDocumentTypeIds = collect($rule->document_ids ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->values();
        $ruleUploadedDocuments = $uploadedDocuments
            ->whereIn('document_type_id', $ruleDocumentTypeIds->all())
            ->values();

        $documentTypeOptions = collect($rule->document_ids ?? [])
            ->map(fn (int|string $id) => $documentTypes->get((int) $id))
            ->filter()
            ->map(function (DocumentType $documentType) use ($uploadedDocuments): array {
                $optionUploads = $uploadedDocuments
                    ->where('document_type_id', $documentType->id)
                    ->values();

                return [
                    'id' => $documentType->id,
                    'key' => $documentType->key,
                    'name' => $documentType->name,
                    'category' => $documentType->category,
                    'description' => $documentType->description,
                    'metadata' => $documentType->metadata ?? [],
                    'uploaded' => $optionUploads->isNotEmpty(),
                    'verified' => $optionUploads->contains(
                        fn (OrderDocument $document): bool => $document->status === OrderDocument::STATUS_VERIFIED
                    ),
                    'uploaded_documents' => $this->formatUploadedDocuments($optionUploads),
                    'latest_upload' => $this->formatUploadedDocument($optionUploads->first()),
                ];
            })
            ->values()
            ->all();

        return array_merge($rule->toArray(), [
            'document_type_options' => $documentTypeOptions,
            'uploaded_document_type_ids' => $ruleUploadedDocuments
                ->pluck('document_type_id')
                ->unique()
                ->values()
                ->all(),
            'verified_document_type_ids' => $ruleUploadedDocuments
                ->where('status', OrderDocument::STATUS_VERIFIED)
                ->pluck('document_type_id')
                ->unique()
                ->values()
                ->all(),
            'uploaded_documents' => $this->formatUploadedDocuments($ruleUploadedDocuments),
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

    private function formatUploadedDocuments(Collection $documents): array
    {
        return $documents
            ->map(fn (OrderDocument $document): array => $this->formatUploadedDocument($document))
            ->filter()
            ->values()
            ->all();
    }

    private function formatUploadedDocument(?OrderDocument $document): ?array
    {
        if (! $document) {
            return null;
        }

        return [
            'id' => $document->id,
            'document_type_id' => $document->document_type_id,
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'status' => $document->status,
            'metadata' => $document->metadata ?? [],
            'verified_at' => $document->verified_at,
            'created_at' => $document->created_at,
        ];
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
