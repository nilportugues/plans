<?php

namespace Rennokki\Plans\Models;

use Illuminate\Database\Eloquent\Model;

class PlanModel extends Model
{
    protected $table = 'plans';
    protected $fillable = [
        'name', 'description', 'price', 'duration',
    ];

    public function features()
    {
        return $this->hasMany(config('plans.models.feature'), 'plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(config('plans.models.subscription'), 'plan_id');
    }
}
