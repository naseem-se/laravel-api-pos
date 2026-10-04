<?php

namespace Tests\Feature;

use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;
use Tests\TestCase;

class PlanManagementRequestTest extends TestCase
{
    public function test_plan_requests_accept_zero_order_and_status_feature_flags(): void
    {
        $storeRules = (new StorePlanRequest())->rules();
        $updateRules = (new UpdatePlanRequest())->rules();

        $this->assertContains('min:0', $storeRules['display_order']);
        $this->assertArrayHasKey('is_active', $storeRules);
        $this->assertArrayHasKey('kds_enabled', $storeRules);
        $this->assertArrayHasKey('online_ordering_enabled', $storeRules);
        $this->assertArrayHasKey('is_active', $updateRules);
        $this->assertArrayHasKey('kds_enabled', $updateRules);
        $this->assertArrayHasKey('online_ordering_enabled', $updateRules);
    }

    public function test_plan_resource_includes_editable_status_order_and_features(): void
    {
        $plan = new Plan([
            'slug' => 'starter',
            'name' => 'Starter',
            'display_order' => 0,
            'is_active' => false,
            'kds_enabled' => true,
            'online_ordering_enabled' => false,
        ]);
        $data = (new PlanResource($plan))->toArray(Request::create('/'));

        $this->assertSame(0, $data['display_order']);
        $this->assertFalse($data['is_active']);
        $this->assertTrue($data['kds_enabled']);
        $this->assertFalse($data['online_ordering_enabled']);
    }
}