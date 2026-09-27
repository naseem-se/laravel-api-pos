<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Support\Tenant;

class RestaurantSettingsService
{
    public function get(): Restaurant
    {
        return Restaurant::allRestaurants()->findOrFail(Tenant::id());
    }

    public function update(array $data): Restaurant
    {
        $restaurant = $this->get();

        // Top-level restaurant columns update normally — these are
        // real columns, not part of the settings JSON blob.
        $restaurant->fill(array_filter([
            'name' => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'logo_url' => array_key_exists('logo_url', $data) ? $data['logo_url'] : null,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : null,
        ], fn ($v) => $v !== null));

        $currentSettings = $restaurant->settings ?? [];
        $incomingSettings = $this->extractSettingsFromRequest($data);

        $restaurant->settings = array_replace_recursive($currentSettings, $incomingSettings);
        $restaurant->save();

        return $restaurant->fresh();
    }
    
    protected function extractSettingsFromRequest(array $data): array
    {
        $settings = [];

        if (array_key_exists('tax_rate_percent', $data)) $settings['taxRatePercent'] = $data['tax_rate_percent'];
        if (array_key_exists('currency', $data)) $settings['currency'] = $data['currency'];
        if (array_key_exists('accepting_online_orders', $data)) $settings['acceptingOnlineOrders'] = $data['accepting_online_orders'];
        if (array_key_exists('receipt_paper_width', $data)) $settings['receiptPaperWidth'] = $data['receipt_paper_width'];

        if (array_key_exists('loyalty', $data)) {
            $loyalty = [];
            $l = $data['loyalty'];
            if (array_key_exists('enabled', $l)) $loyalty['enabled'] = $l['enabled'];
            if (array_key_exists('earn_rate_per_100_spent', $l)) $loyalty['earnRatePer100Spent'] = $l['earn_rate_per_100_spent'];
            if (array_key_exists('redemption_value_per_point', $l)) $loyalty['redemptionValuePerPoint'] = $l['redemption_value_per_point'];
            if (array_key_exists('min_points_to_redeem', $l)) $loyalty['minPointsToRedeem'] = $l['min_points_to_redeem'];

            $settings['loyalty'] = $loyalty; // still shallow-merges correctly since array_replace_recursive descends into this nested key too
        }

        return $settings;
    }

    public function toggleStatus(): Restaurant
    {
        $restaurant = $this->get();
        $restaurant->is_active = ! $restaurant->is_active;
        $restaurant->save();

        return $restaurant->fresh();
    }
}
