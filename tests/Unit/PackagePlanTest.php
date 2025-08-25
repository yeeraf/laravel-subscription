<?php

namespace Yeeraf\LaravelSubscription\Tests\Unit;

use Yeeraf\LaravelSubscription\Tests\TestCase;
use Illuminate\Support\Carbon;
use Yeeraf\LaravelSubscription\Models\PackagePlan;
use Yeeraf\LaravelSubscription\Models\Benefit;
use Yeeraf\LaravelSubscription\Models\BenefitPackagePlan;
use Yeeraf\LaravelSubscription\Models\BenefitPackagePlanLog;

class PackagePlanTest extends TestCase
{
    public function test_find_active_benefit_returns_correct_benefit(): void
    {
        $plan = PackagePlan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
        ]);

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

        $active = $plan->findActiveBenefit('max_projects', $now);

        $this->assertNotNull($active);
        $this->assertEquals('max_projects', $active->name);
        $this->assertEquals('10', $active->pivot->value);
    }

    public function test_find_active_benefit_returns_null_when_out_of_range(): void
    {
        $plan = PackagePlan::create([
            'name' => 'Pro',
        ]);

        $benefit = Benefit::create([
            'name' => 'priority_support',
            'type' => 'bool',
        ]);

        $now = Carbon::now();
        $plan->benefits()->attach($benefit->id, [
            'value' => '1',
            'start_date' => $now->copy()->addDay(), // not started yet
            'end_date' => $now->copy()->addDays(10),
        ]);

        $active = $plan->findActiveBenefit('priority_support', $now);

        $this->assertNull($active);
    }

    public function test_benefits_pivot_contains_expected_columns(): void
    {
        $plan = PackagePlan::create(['name' => 'Pro']);
        $benefit = Benefit::create(['name' => 'storage_gb', 'type' => 'int']);
        $plan->benefits()->attach($benefit->id, [
            'value' => '100',
            'start_date' => null,
            'end_date' => null,
        ]);

        $attached = $plan->benefits()->first();
        $this->assertArrayHasKey('value', $attached->pivot->getAttributes());
        $this->assertArrayHasKey('start_date', $attached->pivot->getAttributes());
        $this->assertArrayHasKey('end_date', $attached->pivot->getAttributes());
        $this->assertNotNull($attached->pivot->created_at);
        $this->assertNotNull($attached->pivot->updated_at);
    }

    public function test_assign_update_end_benefit_behaviors_create_logs_and_update_pivot(): void
    {
        $plan = PackagePlan::create(['name' => 'Pro']);
        $benefit = Benefit::create(['name' => 'max_projects', 'type' => 'int']);

        $start = Carbon::now()->subDay();
        $end = Carbon::now()->addDay();

        // assign
        $plan->assignBenefit('max_projects', '10', $start, $end);

        $pivot = BenefitPackagePlan::query()
            ->where('package_plan_id', $plan->id)
            ->where('benefit_id', $benefit->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($pivot, 'Pivot row should be created on assignBenefit');
        $this->assertSame('10', $pivot->value);
        $this->assertTrue($start->isSameSecond(Carbon::parse($pivot->start_date)));
        $this->assertTrue($end->isSameSecond(Carbon::parse($pivot->end_date)));

        $this->assertSame(1, BenefitPackagePlanLog::where('benefit_package_plan_id', $pivot->id)->count());
        $this->assertSame('create', BenefitPackagePlanLog::where('benefit_package_plan_id', $pivot->id)->latest('id')->first()->action);

        // update
        $plan->updateBenefit('max_projects', '15', $start, $end);

        $pivot = BenefitPackagePlan::query()
            ->where('package_plan_id', $plan->id)
            ->where('benefit_id', $benefit->id)
            ->latest('created_at')
            ->first();

        $this->assertSame('15', $pivot->value);
        $this->assertSame(2, BenefitPackagePlanLog::where('benefit_package_plan_id', $pivot->id)->count());
        $this->assertSame('update', BenefitPackagePlanLog::where('benefit_package_plan_id', $pivot->id)->latest('id')->first()->action);

        // end
        $plan->endBenefit('max_projects');

        $pivot = BenefitPackagePlan::query()
            ->where('package_plan_id', $plan->id)
            ->where('benefit_id', $benefit->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($pivot->end_date, 'Pivot end_date should be set by endBenefit');
        $this->assertSame(3, BenefitPackagePlanLog::where('benefit_package_plan_id', $pivot->id)->count());
        $this->assertSame('update', BenefitPackagePlanLog::where('benefit_package_plan_id', $pivot->id)->latest('id')->first()->action);
    }
}