<?php

namespace Rennokki\Plans\Traits;

use Carbon\Carbon;

trait HasPlans
{
    /**
     * Get Subscriptions relatinship.
     *
     * @return morphMany Relatinship.
     */
    public function subscriptions()
    {
        return $this->morphMany(config('plans.models.subscription'), 'model');
    }

    /**
     * Return the current subscription relatinship.
     *
     * @return morphMany Relatinship.
     */
    public function currentSubscription()
    {
        return $this->subscriptions()
                    ->where('starts_on', '<', Carbon::now())
                    ->where('expires_on', '>', Carbon::now());
    }

    /**
     * Return the current active subscription.
     *
     * @return PlanSubscriptionModel The PlanSubscription model instance.
     */
    public function activeSubscription()
    {
        return $this->currentSubscription()->first();
    }

    /**
     * Get the last active subscription.
     *
     * @return null|PlanSubscriptionModel The PlanSubscription model instance.
     */
    public function lastActiveSubscription()
    {
        if (! $this->hasSubscriptions()) {
            return;
        }

        if ($this->hasActiveSubscription()) {
            return $this->activeSubscription();
        }

        return $this->subscriptions()->orderBy('expires_on', 'desc')->first();
    }

    /**
     * Check if the model has subscriptions.
     *
     * @return bool Wether the binded model has subscriptions or not.
     */
    public function hasSubscriptions()
    {
        return (bool) ($this->subscriptions()->count() > 0);
    }

    /**
     * Check if the model has an active subscription right now.
     *
     * @return bool Wether the binded model has an active subscription or not.
     */
    public function hasActiveSubscription()
    {
        return (bool) $this->activeSubscription();
    }

    /**
     * Subscribe the binded model to a plan. Returns false if it has an active subscription already.
     *
     * @param PlanModel $plan The Plan model instance.
     * @param int $duration The duration, in days, for the subscription.
     * @return PlanSubscription The PlanSubscription model instance.
     */
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

    /**
     * Upgrade the binded model's plan. If it is the same plan, it just extends it.
     *
     * @param PlanModel $newPlan The new Plan model instance.
     * @param int $duration The duration, in days, for the new subscription.
     * @param bool $startFromNow Wether the subscription will start from now, extending the current plan, or a new subscription will be created to extend the current one.
     * @return PlanSubscription The PlanSubscription model instance with the new plan or the current one, extended.
     */
    public function upgradeTo($newPlan, $duration = 30, $startFromNow = true)
    {
        if (! $this->hasActiveSubscription()) {
            return $this->subscribeTo($newPlan, $duration, $startFromNow);
        }

        return $this->activeSubscription()->upgradeTo($newPlan, $duration, $startFromNow);
    }

    /**
     * Extend the current subscription with an amount of days.
     *
     * @param int $duration The duration, in days, for the extension.
     * @param bool $startFromNow Wether the subscription will be extended from now, extending to the current plan, or a new subscription will be created to extend the current one.
     * @return PlanSubscription The PlanSubscription model instance of the extended subscription.
     */
    public function extendCurrentSubscriptionWith($duration = 30, $startFromNow = true)
    {
        if (! $this->hasActiveSubscription()) {
            return $this->subscribeTo(($this->hasSubscriptions()) ? $this->lastActiveSubscription()->plan()->first() : config('plans.models.plan')::first(), $duration);
        }

        return $this->activeSubscription()->extendWith($duration, $startFromNow);
    }

    /**
     * Cancel the current subscription.
     *
     * @return bool Wether the subscription was cancelled or not.
     */
    public function cancelCurrentSubscription()
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        return $this->activeSubscription()->cancel();
    }
}
