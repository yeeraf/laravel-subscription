<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;


class ModelPackagePlanLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
        'logged_at' => 'datetime',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function modelPackagePlan()
    {
        return $this->belongsTo(ModelPackagePlan::class, 'model_package_plan_id');
    }
}