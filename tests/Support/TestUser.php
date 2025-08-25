<?php

namespace Yeeraf\LaravelSubscription\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Yeeraf\LaravelSubscription\Traits\HasPackagePlan;

class TestUser extends Model
{
    use HasPackagePlan;

    protected $table = 'users';
    protected $guarded = [];
}