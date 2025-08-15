<?php

namespace DerFlohwalzer\LaravelSubscription\Tests\Unit;

use DerFlohwalzer\LaravelSubscription\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use DerFlohwalzer\LaravelSubscription\Traits\HasPackagePlan;
use DerFlohwalzer\LaravelSubscription\Models\PackagePlan;
use DerFlohwalzer\LaravelSubscription\Models\PackagePlanPrice;
use DerFlohwalzer\LaravelSubscription\Models\Benefit;
use DerFlohwalzer\LaravelSubscription\Models\ModelPackagePlan;

class SubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Allow mass assignment in tests to bypass model $guarded typos in package
        Model::unguard();
    }

    protected function newUser(array $attributes = []): TestUser
    {
        return TestUser::create(array_merge(['name' => 'Tester'], $attributes));
    }

    protected function newPlan(string $name = 'Pro', ?string $description = null): PackagePlan
    {
        return PackagePlan::create(compact('name', 'description'));
    }

    protected function newPrice(PackagePlan $plan, array $overrides = []): PackagePlanPrice
    {
        $data = array_merge([
            'package_plan_id' => $plan->id,
            'price' => 100.00,
            'currency' => 'USD',
            'day_duration' => 30,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDay(),
        ], $overrides);

        return PackagePlanPrice::create($data);
    }

    public function test_package_plan_has_prices_relationship(): void
    {
        $plan = $this->newPlan();
        $price = $this->newPrice($plan);

        $this->assertEquals($plan->id, $price->packagePlan->id, 'Price belongs to its package plan');
        $this->assertCount(1, $plan->prices, 'Package plan has one price attached');
    }

    public function test_benefit_belongs_to_many_package_plans_through_pivot(): void
    {
        $plan = $this->newPlan();
        $benefit = Benefit::create([
            'name' => 'max_projects',
            'type' => 'int',
            'description' => 'Maximum projects allowed',
        ]);

        $now = Carbon::now();
        $plan->benefits()->attach($benefit->id, [
            'value' => '10',
            'start_date' => $now->copy()->subDay(),
            'end_date' => $now->copy()->addDay(),
        ]);

        $benefits = $plan->benefits()->get();
        $this->assertCount(1, $benefits);
        $this->assertEquals('max_projects', $benefits->first()->name);
        $this->assertEquals('10', $benefits->first()->pivot->value);
    }

    public function test_subscribe_to_package_plan_rejects_out_of_range_price(): void
    {
        $user = $this->newUser();
        $plan = $this->newPlan();
        // Create a price that is not yet valid
        $price = $this->newPrice($plan, [
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(10),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $user->subscribeToPackagePlan($price, Carbon::now(), 1);
    }

    public function test_subscribe_to_package_plan_creates_pending_record_with_created_by(): void
    {
        $user = $this->newUser();
        $plan = $this->newPlan();
        $price = $this->newPrice($plan);

        $subscription = $user->subscribeToPackagePlan($price, Carbon::now(), 5);

        $this->assertInstanceOf(ModelPackagePlan::class, $subscription);
        $this->assertEquals(ModelPackagePlan::STATUS_PENDING, $subscription->status);
        $this->assertEquals($user->id, $subscription->model_id);
        $this->assertEquals(5, $subscription->created_by);
        $this->assertEquals($price->id, $subscription->package_plan_price_id);
    }

    public function test_cancel_creates_log_and_sets_status_and_end_date(): void
    {
        $user = $this->newUser();
        $plan = $this->newPlan();
        $price = $this->newPrice($plan);

        $subscription = $user->subscribeToPackagePlan($price, Carbon::now(), 2);
        
        $cancelAt = Carbon::now()->addHour();
        $subscription->cancel($cancelAt, 7, 'user requested');

        $subscription->refresh();
        $this->assertEquals(ModelPackagePlan::STATUS_CANCELLED, $subscription->status);
        $this->assertNotNull($subscription->end_date);
        $this->assertTrue($cancelAt->isSameSecond(Carbon::parse($subscription->end_date)));

        $this->assertCount(1, $subscription->logs, 'A cancel log should be created');
        $log = $subscription->logs()->first();
        $this->assertEquals('cancel', $log->action);
        $this->assertNotNull($log->logged_at);
        $this->assertIsArray($log->changes);
        $this->assertEquals('user requested', $subscription->remark);
    }
}

/**
 * Simple test model that uses HasPackagePlan trait to receive subscriptions.
 */
class TestUser extends Model
{
    use HasPackagePlan;

    protected $table = 'users';
    protected $guarded = [];
}