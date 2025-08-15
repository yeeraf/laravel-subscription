<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;


class BenefitPackagePlanLog extends Model
{
    protected $gaurded = [];

    protected $casts = [
        'changes' => 'array',
        'logged_at' => 'datetime',
    ];

    public function benefitPackagePlan()
    {
        return $this->belongsTo(BenefitPackagePlan::class, 'benefit_package_plan_id');
    }

    public function logAction(BenefitPackagePlan $benefitPackagePlan, string $action, ?string $description = null, array $changes = [])
    {
        return $this->create([
            'benefit_package_plan_id'=> $benefitPackagePlan->id,
            'action'               => $action,
            'description'          => $description,
            'changes'              => $changes ?: null,
            'logged_at'            => now(),
        ]);
    }
}