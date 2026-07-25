<?php

namespace App\Services\DrNetwork\DocumentManagement;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\DrNetwork\Flow\FlowRunner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DocumentUploadService
{
    public function __construct(
        private DocumentRequirementValidator $validator,
        private FlowRunner $flowRunner,
    ) {}

    public function store(Order $order, UploadedFile $file, int $documentTypeId): array
    {
        $order->loadMissing(['flowRun', 'product']);

        if (! $order->dr_network_id || ! $order->network_flow_key) {
            throw new RuntimeException('Order is not assigned to a doctor network flow.');
        }

        $path = Storage::putFile("orders/{$order->id}/documents", $file);

        try {
            $oldPaths = DB::transaction(function () use ($order, $documentTypeId, $file, $path): array {
                $existingDocuments = OrderDocument::query()
                    ->where('order_id', $order->id)
                    ->where('document_type_id', $documentTypeId)
                    ->latest('id')
                    ->lockForUpdate()
                    ->get();

                $oldPaths = $existingDocuments
                    ->pluck('file_path')
                    ->filter()
                    ->values()
                    ->all();

                $document = $existingDocuments->first();

                $attributes = [
                    'order_id' => $order->id,
                    'document_type_id' => $documentTypeId,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'status' => OrderDocument::STATUS_VERIFIED,
                    'metadata' => null,
                    'verified_at' => now(),
                ];

                if ($document) {
                    $document->update($attributes);

                    $existingDocuments
                        ->skip(1)
                        ->each(fn (OrderDocument $duplicate): bool => $duplicate->delete());
                } else {
                    OrderDocument::query()->create($attributes);
                }

                return $oldPaths;
            });
        } catch (Throwable $exception) {
            Storage::delete($path);

            throw $exception;
        }

        collect($oldPaths)
            ->filter(fn (string $oldPath): bool => $oldPath !== $path)
            ->each(fn (string $oldPath): bool => Storage::delete($oldPath));

        $validation = $this->validator->validate(
            $order->id,
            $order->dr_network_id,
            $order->network_flow_key,
            $order->state_code,
            $order->product?->slug
        );

        return $validation;
    }

    public function completeDocumentUpload(Order $order): array
    {
        $order->loadMissing(['flowRun', 'product']);

        if (! $order->dr_network_id || ! $order->network_flow_key) {
            throw new RuntimeException('Order is not assigned to a doctor network flow.');
        }

        $validation = $this->validator->validate(
            $order->id,
            $order->dr_network_id,
            $order->network_flow_key,
            $order->state_code,
            $order->product?->slug
        );

        if (! $validation['all_satisfied']) {
            return $validation;
        }

        if ($order->flowRun?->current_step_key === 'document_upload') {
            $this->flowRunner->advance($order->flowRun, 'document_upload', [
                'satisfied_rules' => $validation['satisfied'],
            ]);
        }

        return $validation;
    }
}
