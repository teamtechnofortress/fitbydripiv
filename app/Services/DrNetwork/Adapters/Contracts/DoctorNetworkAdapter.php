<?php

namespace App\Services\DrNetwork\Adapters\Contracts;

interface DoctorNetworkAdapter
{
    public function submitCase(array $payload): array;

    public function getCaseStatus(string $networkCaseId): array;

    public function getAvailableSlots(array $params = []): array;

    public function bookSlot(string $slotId, array $params = []): array;

    public function translateStatus(string $networkStatus): string;

    public function getAdapterKey(): string;
}
