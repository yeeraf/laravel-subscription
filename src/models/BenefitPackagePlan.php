<?php

namespace Yeeraf\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitPackagePlan extends Model
{
    protected $table = 'benefit_package_plan';

    protected $guarded = [];

    public function packagePlan()
    {
        return $this->belongsTo(PackagePlan::class);
    }

    public function benefit()
    {
        return $this->belongsTo(Benefit::class);
    }
}