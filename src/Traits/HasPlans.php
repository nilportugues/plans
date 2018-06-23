<?php

namespace Rennokki\Plans\Traits;

use Carbon\Carbon;

trait HasPlans
{
    public function subscriptions()
    {
        return $this->morphMany(config('plans.models.subscription'), 'model');
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
                    ->where('starts_on', '<', Carbon::now())
                    ->where('expires_on', '>', Carbon::now())
                    ->first();
    }

    public function lastActiveSubscription()
    {
        if (! $this->hasSubscriptions()) {
            return;
        }

        if ($this->hasActiveSubscription()) {
            return $this->activeSubscription();
        }

        return $this->subscriptions()->orderBy('expires_on', 'desc');
    }

    public function plan()
    {
        return ($this->hasActiveSubscription()) ? $this->activeSubscription()->plan() : null;
    }

    public function hasSubscriptions()
    {
        return (bool) ($this->subscriptions()->count() > 0);
    }

    public function hasActiveSubscription()
    {
        return (bool) $this->activeSubscription();
    }

    public function subscribeTo($plan, $duration = 30)
    {
        $subscriptionModel = config('plans.models.subscription');

        if ($duration < 1 || $this->hasActiveSubscription()) {
            return false;
        }

        $subscription = $this->subscriptions()->save(new $subscriptionModel([
            'plan_id' => $plan->id,
            'starts_on' => Carbon::now(),
            'expires_on' => Carbon::now()->addDays($duration),
            'cancelled_on' => null,
        ]));

        event(new \Rennokki\Plans\Events\NewSubscription($subscription, $duration));

        return $subscription;
    }

    public function upgradeTo($newPlan, $duration = 30, $startFromNow = true)
    {
        if (! $this->hasActiveSubscription()) {
            return $this->subscribeTo($newPlan, $duration, $startFromNow);
        }

        return $this->activeSubscription()->upgradeTo($newPlan, $duration, $startFromNow);
    }

    public function extendCurrentSubscriptionWith($duration = 30, $startFromNow = true)
    {
        if (! $this->hasActiveSubscription()) {
            return $this->subscribeTo(($this->hasSubscriptions()) ? $this->lastActiveSubscription()->plan()->first() : config('plans.models.plan')::first(), $duration);
        }

        return $this->activeSubscription()->extendWith($duration, $startFromNow);
    }

    public function cancelCurrentSubscription()
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        return $this->activeSubscription()->cancel();
    }
}
