<?php

namespace App\Services\Inventory;

class WacCalculationService
{
    /**
     * Calculate Moving Weighted Average Cost (WAC) when new stock arrives.
     *
     * Formula:
     * WAC_new = ((Qty_current * WAC_current) + (Qty_incoming * Cost_incoming)) / (Qty_current + Qty_incoming)
     *
     * In case current quantity is zero or negative, the incoming cost directly becomes the new WAC.
     */
    public function calculateIncoming(float $currentQty, float $currentWac, float $incomingQty, float $incomingCost): float
    {
        if ($incomingQty <= 0) {
            return round($currentWac, 4);
        }

        if ($currentQty <= 0) {
            return round($incomingCost, 4);
        }

        $totalQty = $currentQty + $incomingQty;
        if ($totalQty <= 0) {
            return round($incomingCost, 4);
        }

        $currentValue = $currentQty * $currentWac;
        $incomingValue = $incomingQty * $incomingCost;
        $newWac = ($currentValue + $incomingValue) / $totalQty;

        return round($newWac, 4);
    }
}
