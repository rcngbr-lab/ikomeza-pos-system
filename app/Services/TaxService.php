<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Schema;

class TaxService
{
    public function saleTotals(float $subtotal, float $discount = 0): array
    {
        $discount = max(0, min($discount, $subtotal));
        $netAmount = max($subtotal - $discount, 0);
        $enabled = $this->bool('vat_enabled', true);
        $rate = $enabled ? max((float) $this->setting('vat_rate', 18), 0) : 0;
        $pricesIncludeVat = $this->bool('prices_include_vat', true);

        if (!$enabled || $rate <= 0 || $netAmount <= 0) {
            return [
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'taxable_amount' => round($netAmount, 2),
                'tax' => 0.0,
                'vat_rate' => 0.0,
                'grand_total' => round($netAmount, 2),
                'prices_include_vat' => $pricesIncludeVat,
                'fiscal_payload' => $this->fiscalPayload($netAmount, 0, $rate),
            ];
        }

        if ($pricesIncludeVat) {
            $taxable = $netAmount / (1 + ($rate / 100));
            $tax = $netAmount - $taxable;
            $grandTotal = $netAmount;
        } else {
            $taxable = $netAmount;
            $tax = $netAmount * ($rate / 100);
            $grandTotal = $netAmount + $tax;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'taxable_amount' => round($taxable, 2),
            'tax' => round($tax, 2),
            'vat_rate' => round($rate, 3),
            'grand_total' => round($grandTotal, 2),
            'prices_include_vat' => $pricesIncludeVat,
            'fiscal_payload' => $this->fiscalPayload($grandTotal, $tax, $rate),
        ];
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        if (!Schema::hasTable('business_settings')) {
            return $default;
        }

        return BusinessSetting::query()->where('key', $key)->value('value') ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->setting($key, $default ? '1' : '0');

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function fiscalPayload(float $total, float $tax, float $rate): array
    {
        return [
            'business_name' => $this->setting('business_name', config('app.name')),
            'tin' => $this->setting('business_tin'),
            'ebm_mode' => $this->setting('fiscal_ebm_mode', 'MANUAL'),
            'vat_rate' => $rate,
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}

