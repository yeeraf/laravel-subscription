<?php

namespace Yeeraf\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    protected $guarded = [];

    private static $types = ['string', 'float', 'int', 'bool', 'datetime'];

    public function benefitPackagePlan()
    {
        return $this->hasMany(BenefitPackagePlan::class);
    }

    public function packagePlans()
    {
        return $this->belongsToMany(
            PackagePlan::class,
            'benefit_package_plan',
            'benefit_id',
            'package_plan_id'
        )->withPivot(['value', 'start_date', 'end_date'])
        ->withTimestamps();
    }
}