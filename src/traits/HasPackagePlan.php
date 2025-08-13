<?php

namespace  DerFlohwalzer\LaravelSubscription\Traits;

use DerFlohwalzer\LaravelSubscription\Models\ModelPackagePlan;
trait HasPackagePlan
{
    public function modelPackagePlan()
    {
        return $this->mrorphMany(ModelPackagePlan::class, 'model');
    }

    /**
     * Get the current active package plan for a given date and time.
     *
     * @param \DateTimeInterface|null $currentDateTime The current date and time.
     * @return \DerFlohwalzer\LaravelSubscription\Models\ModelPackagePlan

     */
    public function currentPackagePlan(?\DateTimeInterface $currentDateTime = null): ModelPackagePlan
    {
        $currentDateTime = $currentDateTime ?? now();

        return $this->modelPackagePlan()
            ->where(function ($q) use ($currentDateTime) {
                $q->where('status', 'active');
                $q->where('start_date', '<=', $currentDateTime);
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $currentDateTime);
        })->latest('start_date')
        ->first();
    }

    public function getBenefitValue(string $benefitname) {
        $packagePlan = $this->currentPackagePlan();
        $benefit = $packagePlan->benefits()->where('name', $benefitname)->first();

        return $this->castBenefitValue($benefit->pivot->value, $benefit->type);
    }

    private function castBenefitValue(string $value, string $type) {
        if ($value === null) {
            return null;
        }
        
        return match ($type) {
            'string' => $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'datetime' => \DateTime::createFromFormat('Y-m-d H:i:s', $value),
            default => $value,
        };
    }
}