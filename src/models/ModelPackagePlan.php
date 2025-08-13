<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPackagePlan extends Model
{
    protected $gaurded = [];

    public function model()
    {
        return $this->morphTo();
    }

    public function packagePlans()
    {
        return $this->belongsTo(PackagePlan::class)
            ->withDefault();
    }
}
