<?php

namespace Yeeraf\LaravelSubscription\Tests\Unit;

use Yeeraf\LaravelSubscription\Tests\TestCase;
use Yeeraf\LaravelSubscription\Tests\Support\TestUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Yeeraf\LaravelSubscription\Models\ModelPackagePlan;
use Yeeraf\LaravelSubscription\Models\PackagePlan;
use Yeeraf\LaravelSubscription\Models\PackagePlanPrice;

class ModelPackagePlanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();
    }

    protected function newPlan(string $name = 'Std'): PackagePlan
    {
        return PackagePlan::create(['name' => $name]);
    }

    protected function newPrice(PackagePlan $plan, array $overrides = []): PackagePlanPrice
    {
        $now = Carbon::now();
        $data = array_merge([
            'package_plan_id' => $plan->id,
            'price' => 19.99,
            'currency' => 'USD',
            'day_duration' => 10,
            'start_date' => $now->copy()->subDay(),
            'end_date' => $now->copy()->addDay(),
        ], $overrides);

        return PackagePlanPrice::create($data);
    }

    public function test_cancel_from_pending_status_updates_status_end_date_logs_and_remark(): void
    {
        $user = TestUser::create(['name' => 'U']);
        $plan = $this->newPlan();
        $price = $this->newPrice($plan);

        $sub = ModelPackagePlan::create([
            'model_id' => $user->id,
            'model_type' => TestUser::class,
            'package_plan_price_id' => $price->id,
            'status' => ModelPackagePlan::STATUS_PENDING,
            'start_date' => null,
        ]);

        $at = Carbon::now()->addMinutes(5);
        $sub->cancel($at, 11, 'duplicate');

        $sub->refresh();
        $this->assertSame(ModelPackagePlan::STATUS_CANCELLED, $sub->status);
        $this->assertTrue($at->isSameSecond(Carbon::parse($sub->end_date)));
        $this->assertSame('duplicate', $sub->remark);
        $this->assertCount(1, $sub->logs);
        $this->assertSame('cancel', $sub->logs()->first()->action);
    }

    public function test_cancel_ignored_when_already_cancelled(): void
    {
        $user = TestUser::create(['name' => 'U']);
        $plan = $this->newPlan();
        $price = $this->newPrice($plan);

        $sub = ModelPackagePlan::create([
            'model_id' => $user->id,
            'model_type' => TestUser::class,
            'package_plan_price_id' => $price->id,
            'status' => ModelPackagePlan::STATUS_CANCELLED,
        ]);

        $before = $sub->replicate();
        $sub->cancel(Carbon::now(), 1, 'noop');

        $after = $sub->fresh();
        $this->assertSame(ModelPackagePlan::STATUS_CANCELLED, $after->status);
        $this->assertEquals($before->end_date, $after->end_date);
        $this->assertEquals(0, $after->logs()->count(), 'No new logs should be written');
    }

    public function test_activate(): void
    {
        $user = TestUser::create(['name' => 'U']);
        $plan = $this->newPlan();
        $price = $this->newPrice($plan, ['day_duration' => 10]);
        $sub = ModelPackagePlan::create([
            'model_id' => $user->id,
            'model_type' => TestUser::class,
            'package_plan_price_id' => $price->id,
            'package_plan_id' => $plan->id,
            'status' => ModelPackagePlan::STATUS_PENDING,
        ]);
        $now = Carbon::now();
        $sub->activate($now, 9);
        $this->assertSame(ModelPackagePlan::STATUS_ACTIVE, $sub->fresh()->status);
        $this->assertTrue($now->isSameSecond(Carbon::parse($sub->fresh()->start_date)));
        $this->assertTrue($now->copy()->addDays(10)->isSameSecond(Carbon::parse($sub->fresh()->end_date)));
        $this->assertCount(1, $sub->logs);
    }
}