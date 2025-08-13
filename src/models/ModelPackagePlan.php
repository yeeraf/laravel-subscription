<?php

namespace DerFlohwalzer\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPackagePlan extends Model
{
    protected $gaurded = [];

    private const STATUS_PENDING = 'pending';
    private const STATUS_ACTIVE = 'active';
    private const STATUS_CANCELLED = 'cancelled';

    private const 

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
