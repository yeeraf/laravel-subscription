<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePlanPrice extends Model
{
    protected $guarded = [];

    public function packagePlan()
    {
        return $this->belongsTo(PackagePlan::class);
    }
}
