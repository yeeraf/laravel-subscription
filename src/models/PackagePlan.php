<?php

namespace Yeeraf\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yeeraf\LaravelSubscription\Models\BenefitPackagePlanLog;

class PackagePlan extends Model
{
    protected $fillable = ['name', 'description'];

    public function prices()
    {
        return $this->hasMany(PackagePlanPrice::class);
    }

    public function benefitPackagePlan()
    {
        return $this->hasMany(BenefitPackagePlan::class);
    }

    public function benefits()
    {
        return $this->belongsToMany(
            Benefit::class, 
            'benefit_package_plan', 
            'package_plan_id', 
            'benefit_id'
        )->withPivot(['value', 'start_date', 'end_date'])
         ->withTimestamps();
    }

    public function modelPackagePlan()
    {
        return $this->belongsTo(ModelPackagePlan::class);
    }

    /**
     * Find the active benefit by name and current date.
     *
     * @param string $benefitName The name of the benefit to find.
     * @param Carbon|null $currentDateTime The current date and time.
     * @return Benefit|null The active benefit or null if not found.
     */
    public function findActiveBenefit(string $benefitName, ?Carbon $currentDateTime): ?Benefit
    {
        $currentDateTime = $currentDateTime ?? now();

        // Retrieve the first active benefit matching the name and date criteria (filter on pivot columns)
        return $this->benefits()
            ->where('name', $benefitName)
            ->where(function ($query) use ($currentDateTime) {
                $query->whereNull('benefit_package_plan.start_date')
                      ->orWhere('benefit_package_plan.start_date', '<=', $currentDateTime);
            })
            ->where(function ($query) use ($currentDateTime) {
                $query->whereNull('benefit_package_plan.end_date')
                      ->orWhere('benefit_package_plan.end_date', '>=', $currentDateTime);
            })
            ->orderBy('benefit_package_plan.created_at', 'desc')
            ->first();
    }

    /**
     * Assign a benefit to this package plan.
     *
     * @param string $benefitName
     * @param string $value
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return PackagePlan
     */
    public function assignBenefit(string $benefitName, string $value, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $benefit = Benefit::where('name', $benefitName)->firstOrFail();

        try {
            DB::beginTransaction();
                $benefitPackagePlan = BenefitPackagePlan::create([
                    'package_plan_id' => $this->id,
                    'benefit_id' => $benefit->id,
                    'value' => $value,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                BenefitPackagePlanLog::logAction(
                    $benefitPackagePlan,
                    'create',
                    "Assigned benefit {$benefitName} to package plan {$this->name}",
                    $benefitPackagePlan->toArray()
                );

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
       
        return $this;
    }


    public function updateBenefit(string $benefitName, string $value, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        try {
            DB::beginTransaction();
                $benefit = $this->findActiveBenefit($benefitName, now());
                if (!$benefit) {
                    DB::rollBack();
                    return;
                }

                $benefit->pivot->value = $value;
                $benefit->pivot->start_date = $startDate;
                $benefit->pivot->end_date = $endDate;
                $benefit->pivot->save();

                // Retrieve the actual pivot row model for logging
                $benefitPackagePlan = BenefitPackagePlan::query()
                    ->where('package_plan_id', $this->id)
                    ->where('benefit_id', $benefit->id)
                    ->latest('created_at')
                    ->first();

                if ($benefitPackagePlan) {
                    BenefitPackagePlanLog::logAction(
                        $benefitPackagePlan,
                        'update',
                        "Updated benefit {$benefitName} on package plan {$this->name}",
                        $benefit->pivot->toArray()
                    );
                }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        
        
    }
    
    public function endBenefit(string $benefitName)
    {
        try {
            DB::beginTransaction();
                $benefit = $this->findActiveBenefit($benefitName, now());
                if (!$benefit) {
                    DB::rollBack();
                    return;
                }

                $benefit->pivot->end_date = now();
                $benefit->pivot->save();

                $benefitPackagePlan = BenefitPackagePlan::query()
                    ->where('package_plan_id', $this->id)
                    ->where('benefit_id', $benefit->id)
                    ->latest('created_at')
                    ->first();

                if ($benefitPackagePlan) {
                    BenefitPackagePlanLog::logAction(
                        $benefitPackagePlan,
                        'update',
                        "Ended benefit {$benefitName} on package plan {$this->name}",
                        $benefit->pivot->toArray()
                    );
                }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        
    }
}