<?php

namespace Rennokki\Plans\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeatureModel extends Model
{
    protected $table = 'plans_features';
    protected $fillable = [
        'plan_id', 'name', 'code', 'description',
        'type', 'limit', 'used',
    ];

    public function plan()
    {
        return $this->belongsTo(config('plans.models.plan'), 'plan_id');
    }
}
