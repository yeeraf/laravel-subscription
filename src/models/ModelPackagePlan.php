<?php

namespace Yeeraf\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ModelPackagePlan extends Model
{
    protected $table = 'model_package_plan';
    protected $guarded = [];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    public function model()
    {
        return $this->morphTo();
    }

    public function packagePlan()
    {
        return $this->belongsTo(PackagePlan::class)
            ->withDefault();
    }

    public function packagePlanPrice()
    {
        return $this->belongsTo(PackagePlanPrice::class)
            ->withDefault();
    }

    public function logs()
    {
        return $this->hasMany(ModelPackagePlanLog::class);
    }

    public function activate(?Carbon $currentDateTime = null, int $updatedBy) 
    {
        if ($this->status !== self::STATUS_PENDING) {
            return $this;
        }

        $currentDateTime = $currentDateTime ?? Carbon::now();
        $endDate = $currentDateTime->copy()->addDays($this->packagePlanPrice->day_duration);
        
        $existedPackagePlans = ModelPackagePlan::query()
            ->where('model_id', $this->model_id)
            ->where('model_type', $this->model_type)
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
        try {
            \DB::beginTransaction();
                $this->status = self::STATUS_ACTIVE;
                $this->start_date = $currentDateTime;
                $this->end_date = $endDate;
                $this->updated_by = $updatedBy;
                $this->save();
                $this->logAction('update', 'change status to active', [
                    'status' => self::STATUS_ACTIVE,
                    'start_date' => $currentDateTime,
                    'end_date' => $endDate,
                    'updated_by' => $updatedBy
                ]);
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

        return $this;
    }
    
    public function cancel(?Carbon $cancelDateTime = null, int $updatedBy, ?string $remark = null) 
    {
        $cancelDateTime = $cancelDateTime ?? Carbon::now();

        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PENDING])) {
            return $this;
        }
        try {
            \DB::beginTransaction();
                $this->status = self::STATUS_CANCELLED;
                $this->end_date = $cancelDateTime;
                $this->remark = $remark;
                $this->updated_by = $updatedBy;
                $this->save();
                $this->logAction('cancel', null, [
                    'status' => self::STATUS_CANCELLED,
                    'end_date' => $cancelDateTime,
                    'remark' => $remark,
                    'updated_by' => $updatedBy
                ]);
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        
        return $this;
    }

    public function logAction(string $action, ?string $description = null, array $changes = [])
    {
        return $this->logs()->create([
            'model'                => get_class($this->model),
            'model_package_plan_id'=> $this->id,
            'action'               => $action,
            'description'          => $description,
            'changes'              => $changes ?: null,
            'logged_at'            => now(),
        ]);
    }
}
