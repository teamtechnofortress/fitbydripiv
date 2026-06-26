<?php

namespace App\Services\DrNetwork\DocumentManagement;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\DrNetwork\Flow\FlowRunner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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

        $document = OrderDocument::query()->create([
            'order_id' => $order->id,
            'document_type_id' => $documentTypeId,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'status' => OrderDocument::STATUS_PENDING_VERIFICATION,
        ]);

        $document->update([
            'status' => OrderDocument::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);

        $validation = $this->validator->validate(
            $order->id,
            $order->dr_network_id,
            $order->network_flow_key,
            $order->state_code,
            $order->product?->slug
        );

        if ($validation['all_satisfied'] && $order->flowRun?->current_step_key === 'document_upload') {
            $this->flowRunner->advance($order->flowRun, 'document_upload', [
                'satisfied_rules' => $validation['satisfied'],
            ]);
        }

        return $validation;
    }
}
