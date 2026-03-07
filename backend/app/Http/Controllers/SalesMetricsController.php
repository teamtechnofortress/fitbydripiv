<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController;
use App\Models\PatientEncounter;

class SalesMetricsController extends BaseController
{

    public function getSalesMetrics()
    {
        $types = ['IV Therapy', 'Injectables', 'Weight Loss', 'Other'];
        $salesMetrics = [];

        $calculate = function ($collection) {
            $totalAmount = round($collection->sum(function ($item) {
                return $item->inventory ? $item->inventory->price * $item->quantity : 0;
            }), 2);

            return [
                'amount' => $totalAmount,
                'count'  => $collection->count(),
            ];
        };

        // 
        foreach ($types as $type) {
            $encounters = PatientEncounter::where([
                'paid' => 1,
                'deleted' => 0,
                'type' => $type,
            ])->with('inventory')->get();

            $salesMetrics[strtolower($type)] = [
                'total'   => $calculate($encounters),
                'daily'   => $calculate($encounters->filter(fn($item) => $item->created_at >= now()->subDay())),
                'weekly'  => $calculate($encounters->filter(fn($item) => $item->created_at >= now()->subDays(7))),
                'monthly' => $calculate($encounters->filter(fn($item) => $item->created_at >= now()->subDays(30))),
            ];
        }

        // Add-on 
        $addOnEncounters = PatientEncounter::where([
            'paid' => 1,
            'deleted' => 0,
            'is_add_on' => 1,
        ])->with('inventory')->get();

        $salesMetrics['add_on'] = [
            'total'   => $calculate($addOnEncounters),
            'daily'   => $calculate($addOnEncounters->filter(fn($item) => $item->created_at >= now()->subDay())),
            'weekly'  => $calculate($addOnEncounters->filter(fn($item) => $item->created_at >= now()->subDays(7))),
            'monthly' => $calculate($addOnEncounters->filter(fn($item) => $item->created_at >= now()->subDays(30))),
        ];

        return $this->sendResponse($salesMetrics, 'Sales metrics retrieved successfully.');
    }



}
