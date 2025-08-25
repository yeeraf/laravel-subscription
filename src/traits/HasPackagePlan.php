<?php

namespace  Yeeraf\LaravelSubscription\Traits;

use Yeeraf\LaravelSubscription\Models\ModelPackagePlan;
use Yeeraf\LaravelSubscription\Models\PackagePlanPrice;
use Illuminate\Support\Carbon;

trait HasPackagePlan
{
    public function subscriptions()
    {
        return $this->morphMany(ModelPackagePlan::class, 'model');
    }

    /**
     * Get the current active package plan for a given date and time.
     *
     * @param \DateTimeInterface|null $currentDateTime The current date and time.
     * @return \Yeeraf\LaravelSubscription\Models\ModelPackagePlan

     */
    public function currentSubscription(?\DateTimeInterface $currentDateTime = null): ModelPackagePlan | null
    {
        $currentDateTime = $currentDateTime ?? now();

        return $this->subscriptions()
            ->where(function ($q) use ($currentDateTime) {
                $q->where('status', ModelPackagePlan::STATUS_ACTIVE);
                $q->where('start_date', '<=', $currentDateTime);
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $currentDateTime);
        })->latest('start_date')
        ->first();
    }

    public function getBenefitValue(string $benefitName, ?Carbon $currentDateTime = null): mixed
    {
        $currentDateTime = $currentDateTime ?? Carbon::now();

        // Find current active subscription for this model
        $subscription = $this->currentSubscription($currentDateTime);
        if (!$subscription) {
            return null;
        }

        // Resolve the package plan via the subscription's price
        $packagePlan = $subscription->packagePlan;
        if (!$packagePlan || !$packagePlan->exists) {
            return null;
        }

        // Query the package plan's benefits by name and active date window
        $benefit = $packagePlan->benefits()
            ->where('name', $benefitName)
            ->where(function ($q) use ($currentDateTime) {
                $q->whereNull('benefit_package_plan.end_date')
                  ->orWhere('benefit_package_plan.end_date', '>=', $currentDateTime);
            })
            ->where(function ($q) use ($currentDateTime) {
                $q->whereNull('benefit_package_plan.start_date')
                  ->orWhere('benefit_package_plan.start_date', '<=', $currentDateTime);
            })
            ->orderBy('benefit_package_plan.created_at', 'desc')
            ->first();

        if (!$benefit) {
            return null;
        }

        return $this->castBenefitValue($benefit->pivot->value, $benefit->type);
    }

    public function subscribeToPackagePlan(PackagePlanPrice $packagePlanPrice, ?Carbon $currentDateTime = null, ?int $createdBy = null): ModelPackagePlan 
    {
        $currentDateTime = $currentDateTime ?? Carbon::now();
        $isStartValid = !$packagePlanPrice->start_date || Carbon::parse($packagePlanPrice->start_date)->lte($currentDateTime);
        $isEndValid = !$packagePlanPrice->end_date || Carbon::parse($packagePlanPrice->end_date)->gte($currentDateTime);
        $createdBy = $createdBy ?? auth()->id();
        if (!$isStartValid || !$isEndValid) {
            throw new \InvalidArgumentException('This package plan price is not valid for the given subscribe date.');
        }

        return ModelPackagePlan::create([
            'model_id' => $this->id,
            'model_type' => get_class($this),
            'package_plan_price_id' => $packagePlanPrice->id,
            'package_plan_id' => $packagePlanPrice->package_plan_id,
            'status' => ModelPackagePlan::STATUS_PENDING,
            'created_by' => $createdBy
        ]);
    }

    private function castBenefitValue(string $value, string $type): mixed 
    {
        if ($value === null) {
            return null;
        }
        
        return match ($type) {
            'string' => $value,
            'float' => (float) $value,
            'int' => (int) $value,
            'bool' => (bool) $value,
            default => $value,
        };    
    }
}