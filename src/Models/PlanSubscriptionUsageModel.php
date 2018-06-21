<?php

namespace Rennokki\Plans\Models;

use Illuminate\Database\Eloquent\Model;

class PlanSubscriptionUsageModel extends Model
{
    protected $table = 'plans_usages';
    protected $fillable = [
        'subscription_id', 'code', 'used',
    ];

    public function subscription()
    {
        return $this->belongsTo(config('plans.models.subscription'), 'subscription_id');
    }
}
