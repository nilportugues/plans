<?php

namespace Rennokki\Plans\Models;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class PlanSubscriptionModel extends Model
{
    protected $table = 'plans_subscriptions';
    protected $fillable = [
        'plan_id', 'model_id', 'model_type',
        'starts_on', 'cancelled_on', 'expires_on',
    ];
    protected $dates = [
        'starts_on',
        'expires_on',
        'cancelled_on',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function plan()
    {
        return $this->belongsTo(config('plans.models.plan'), 'plan_id');
    }

    public function cancel()
    {
        if($this->isCancelled() || $this->isPendingCancellation())
            return false;

        return $this->update([
            'cancelled_on' => Carbon::now(),
        ]);
    }

    public function extendWith($duration = 30, $startFromNow = true)
    {
        if($duration < 1)
            return false;

        if($startFromNow)
        {
            $this->update([
                'expires_on' => Carbon::parse($this->expires_on)->addDays($duration),
            ]);

            return $this;
        }

        return Self::create([
            'plan_id' => $this->id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'starts_on' => Carbon::parse($this->expires_on),
            'expires_on' => Carbon::parse($this->expires_on)->addDays($duration),
            'cancelled_on' => null,
        ]);
    }

    public function upgradeTo($newPlan, $duration = 30, $startFromNow = true)
    {
        $subscription = $this->extendWith($duration, $startFromNow);

        if($subscription->plan_id != $newPlan->id)
        {
            $subscription->update([
                'plan_id' => $newPlan->id,
            ]);

            $subscription = $this;
        }

        return $subscription;
    }

    public function hasStarted()
    {
        return (bool) Carbon::now()->lessThanOrEqualTo(Carbon::parse($this->starts_on));
    }

    public function hasExpired()
    {
        return (bool) Carbon::now()->greaterThan(Carbon::parse($this->expires_on));
    }

    public function isActive()
    {
        return (bool) ($this->hasStarted() && !$this->hasExpired());
    }

    public function remainingDays()
    {
        if($this->hasExpired())
            return (int) 0;

        return (int) Carbon::now()->diffInDays(Carbon::parse($this->expires_on));
    }

    public function isCancelled()
    {
        return (bool) $this->cancel_on != null;
    }

    public function isPendingCancellation()
    {
        return (bool) ($this->isCancelled() && $this->isActive());
    }
}
