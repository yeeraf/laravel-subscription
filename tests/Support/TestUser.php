<?php

namespace DerFlohwalzer\LaravelSubscription\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use DerFlohwalzer\LaravelSubscription\Traits\HasPackagePlan;

class TestUser extends Model
{
    use HasPackagePlan;

    protected $table = 'users';
    protected $guarded = [];
}