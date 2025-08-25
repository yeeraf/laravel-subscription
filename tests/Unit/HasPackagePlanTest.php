<?php

namespace Yeeraf\LaravelSubscription\Tests\Unit;

use Yeeraf\LaravelSubscription\Tests\TestCase;
use Yeeraf\LaravelSubscription\Tests\Support\TestUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Yeeraf\LaravelSubscription\Models\PackagePlan;
use Yeeraf\LaravelSubscription\Models\PackagePlanPrice;
use Yeeraf\LaravelSubscription\Models\ModelPackagePlan;
use Yeeraf\LaravelSubscription\Models\Benefit;
use Yeeraf\LaravelSubscription\Models\BenefitPackagePlan;

class HasPackagePlanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();
    }

    protected function newPlan(string $name = 'Basic'): PackagePlan
    {
        return PackagePlan::create(['name' => $name]);
    }

    protected function newPrice(PackagePlan $plan, array $overrides = []): PackagePlanPrice
    {
        $now = Carbon::now();
        $data = array_merge([
            'package_plan_id' => $plan->id,
            'price' => 9.99,
            'currency' => 'USD',
            'day_duration' => 30,
            'start_date' => $now->copy()->subDay(),
            'end_date' => $now->copy()->addDays(30),
        ], $overrides);

        return PackagePlanPrice::create($data);
    }

    public function test_current_subscription_returns_latest_active_one_by_start_date(): void
    {
        $user = TestUser::create(['name' => 'Alice']);
        $plan = $this->newPlan('Gold');

        $price1 = $this->newPrice($plan);
        $price2 = $this->newPrice($plan, [
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addDays(60),
        ]);

        $sub1 = ModelPackagePlan::create([
            'model_id' => $user->id,
            'model_type' => TestUser::class,
            'package_plan_price_id' => $price1->id,
            'status' => ModelPackagePlan::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDays(2),
        ]);

        $sub2 = ModelPackagePlan::create([
            'model_id' => $user->id,
            'model_type' => TestUser::class,
            'package_plan_price_id' => $price2->id,
            'status' => ModelPackagePlan::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);

        $current = $user->currentSubscription();

        $this->assertNotNull($current);
        $this->assertEquals($sub2->id, $current->id, 'Latest active subscription (by start_date) should be returned');
    }

    public function test_get_benefit_value_returns_casted_value(): void
    {
        $user = TestUser::create(['name' => 'Bob']);
        $plan = $this->newPlan('Pro');
        $price = $this->newPrice($plan);

        // Active subscription covering now()
        ModelPackagePlan::create([
            'model_id' => $user->id,
            'model_type' => TestUser::class,
            'package_plan_price_id' => $price->id,
            'package_plan_id' => $plan->id,
            'status' => ModelPackagePlan::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDay(),
        ]);

        // Benefit active covering now()
        $benefit = Benefit::create(['name' => 'max_projects', 'type' => 'int']);
        $plan->benefits()->attach($benefit->id, [
            'value' => '5',
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->addDays(2),
        ]);

        $this->assertSame(5, $user->getBenefitValue('max_projects'));
    }
}