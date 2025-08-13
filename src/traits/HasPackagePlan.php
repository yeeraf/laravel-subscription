<?php

namespace  DerFlohwalzer\LaravelSubscription\Traits;

use DerFlohwalzer\LaravelSubscription\Models\ModelPackagePlan;
use DerFlohwalzer\LaravelSubscription\Models\PackagePlanPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Mix;
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
     * @return \DerFlohwalzer\LaravelSubscription\Models\ModelPackagePlan

     */
    public function currentSubscription(?\DateTimeInterface $currentDateTime = null): ModelPackagePlan
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

    public function getBenefitValue(string $benefitName, ?\DateTimeInterface $currentDateTime = null): mixed 
    {
        $currentDateTime = $currentDateTime ?? now();
        $packagePlan = $this->currentSubscription();
        
        $benefitPackagePlan = $packagePlan
            ->packagePlans
            ->whereHas('benefits', function ($q) use ($benefitName) {
                $q->where('name', $benefitName);
            })
            ->where(function ($q) use ($currentDateTime) {
                $q->where('start_date', '<=', $currentDateTime);
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $currentDateTime);
            })
            ->latest('created_at')
            ->first();


        return $this->castBenefitValue($benefitPackagePlan->pivot->value, $benefitPackagePlan->type);
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
            'datetime' => \DateTime::createFromFormat('Y-m-d H:i:s', $value),
            default => $value,
        };    
    }
}