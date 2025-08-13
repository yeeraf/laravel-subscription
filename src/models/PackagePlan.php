<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePlan extends Model
{
    protected $fillable = ['name', 'description'];

    public function price()
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
}