<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ModelPackagePlan extends Model
{
    protected $gaurded = [];

    public
     const STATUS_PENDING = 'pending';
    public
     const STATUS_ACTIVE = 'active';
    public
     const STATUS_CANCELLED = 'cancelled';

    public function model()
    {
        return $this->morphTo();
    }

    public function packagePlans()
    {
        return $this->belongsTo(PackagePlan::class)
            ->withDefault();
    }

    public function packagePlanPrice()
    {
        return $this->belongsTo(PackagePlanPrice::class)
            ->withDefault();
    }

    public function activate(?Carbon $currentDateTime = null, ?int $updatedBy = null) 
    {
        $updatedBy = $updatedBy ?? auth()->id();
        $currentDateTime = $currentDateTime ?? Carbon::now();
        
        $existedPackagePlans = $this->packagePlans()
            ->where('status', ModelPackagePlan::STATUS_ACTIVE)
            ->where(function ($q) use ($currentDateTime) {
                $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $currentDateTime);
            })
            ->get();

        $existedPackagePlans->each(function (ModelPackagePlan $existedPackagePlan) use ($currentDateTime, $updatedBy) {
            $existedPackagePlan->cancel(
                $currentDateTime->copy()->subSecond(),
                $updatedBy
            );
        });

        $this->status = self::STATUS_ACTIVE;
        $this->start_date = $currentDateTime;
        $this->end_date = $currentDateTime->addDays($this->packagePlanPrice->day_duration);
        $this->save();
    }

    private function cancel(?Carbon $cancelDateTime = null, ?int $updatedBy = null, ?string $remark = null) 
    {
        $cancelDateTime = $cancelDateTime ?? Carbon::now();
        $updatedBy = $updatedBy ?? auth()->id();

        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PENDING])) {
            throw new \InvalidArgumentException('You can only cancel an active or pending package plan.');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->end_date = $cancelDateTime;
        $this->remark = $remark;
        $this->updated_by = $updatedBy;
        $this->save();
    }
}
