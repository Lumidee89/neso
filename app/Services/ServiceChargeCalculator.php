<?php

namespace App\Services;

use App\Models\ServiceCharge;
use Illuminate\Support\Collection;

class ServiceChargeCalculator
{
    /**
     * Return the active charges that apply to a given amount for a given context,
     * each paired with the computed charge amount.
     *
     * @return Collection<int, array{charge: ServiceCharge, amount: float}>
     */
    public function calculate(string $appliesTo, float $amount): Collection
    {
        return ServiceCharge::where('active', true)
            ->whereIn('applies_to', ['all', $appliesTo])
            ->get()
            ->filter(function (ServiceCharge $charge) use ($amount) {
                if ($charge->min_amount !== null && $amount < (float) $charge->min_amount) {
                    return false;
                }

                if ($charge->max_amount !== null && $amount > (float) $charge->max_amount) {
                    return false;
                }

                return true;
            })
            ->map(function (ServiceCharge $charge) use ($amount) {
                $chargeAmount = $charge->type === 'flat'
                    ? (float) $charge->value
                    : round($amount * ((float) $charge->value / 100), 2);

                return ['charge' => $charge, 'amount' => $chargeAmount];
            })
            ->values();
    }
}
