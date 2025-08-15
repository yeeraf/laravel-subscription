<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;


class BenefitPackagePlanLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
        'logged_at' => 'datetime',
    ];

    public function benefitPackagePlan()
    {
        return $this->belongsTo(BenefitPackagePlan::class, 'benefit_package_plan_id');
    }

    public static function logAction(BenefitPackagePlan $benefitPackagePlan, string $action, ?string $description = null, array $changes = [])
    {
        return self::create([
            'benefit_package_plan_id'=> $benefitPackagePlan->id,
            'action'               => $action,
            'description'          => $description,
            'changes'              => $changes ?: null,
            'logged_at'            => now(),
        ]);
    }
}