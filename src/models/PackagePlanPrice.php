<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePlanPrice extends Model
{
    protected $gaurded = [];

    public function packagePlan()
    {
        return $this->belongsTo(PackagePlan::class);
    }
}
