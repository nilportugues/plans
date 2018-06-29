<?php

namespace Rennokki\Plans\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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

    public function features()
    {
        return $this->plan()->first()->features();
    }

    public function usages()
    {
        return $this->hasMany(config('plans.models.usage'), 'subscription_id');
    }

    /**
     * Cancel the current subscription.
     *
     * @return bool Wether the subscription was cancelled or not.
     */
    public function cancel()
    {
        if ($this->isCancelled() || $this->isPendingCancellation()) {
            return false;
        }

        $this->update([
            'cancelled_on' => Carbon::now(),
        ]);

        event(new \Rennokki\Plans\Events\CancelSubscription($this));

        return $this;
    }

    /**
     * Extend the current subscription with an amount of days.
     *
     * @param int $duration The duration, in days, for the extension.
     * @param bool $startFromNow Wether the subscription will be extended from now, extending to the current plan, or a new subscription will be created to extend the current one.
     * @return PlanSubscription The PlanSubscription model instance of the extended subscription.
     */
    public function extendWith($duration = 30, $startFromNow = true)
    {
        if ($duration < 1) {
            return false;
        }

        if ($startFromNow) {
            $this->update([
                'expires_on' => Carbon::parse($this->expires_on)->addDays($duration),
            ]);

            event(new \Rennokki\Plans\Events\ExtendSubscription($this, $duration, $startFromNow, null));

            return $this;
        }

        $subscription = Self::create([
            'plan_id' => $this->id,
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'starts_on' => Carbon::parse($this->expires_on),
            'expires_on' => Carbon::parse($this->expires_on)->addDays($duration),
            'cancelled_on' => null,
        ]);

        event(new \Rennokki\Plans\Events\ExtendSubscription($this, $duration, $startFromNow, $subscription));

        return $subscription;
    }

    /**
     * Upgrade the subscription's plan. If it is the same plan, it just extends it.
     *
     * @param PlanModel $newPlan The new Plan model instance.
     * @param int $duration The duration, in days, for the new subscription.
     * @param bool $startFromNow Wether the subscription will start from now, extending the current plan, or a new subscription will be created to extend the current one.
     * @return PlanSubscription The PlanSubscription model instance with the new plan or the current one, extended.
     */
    public function upgradeTo($newPlan, $duration = 30, $startFromNow = true)
    {
        $subscription = $this->extendWith($duration, $startFromNow);
        $oldPlan = $this->plan()->first();

        if ($subscription->plan_id != $newPlan->id) {
            $subscription->update([
                'plan_id' => $newPlan->id,
            ]);

            $subscription = $this;
        }

        event(new \Rennokki\Plans\Events\UpgradeSubscription($subscription, $duration, $startFromNow, $oldPlan, $newPlan));

        return $subscription;
    }

    /**
     * Checks if the current subscription has started.
     *
     * @return bool
     */
    public function hasStarted()
    {
        return (bool) Carbon::now()->greaterThanOrEqualTo(Carbon::parse($this->starts_on));
    }

    /**
     * Checks if the current subscription has expired.
     *
     * @return bool
     */
    public function hasExpired()
    {
        return (bool) Carbon::now()->greaterThan(Carbon::parse($this->expires_on));
    }

    /**
     * Checks if the current subscription is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) ($this->hasStarted() && ! $this->hasExpired());
    }

    /**
     * Get the remaining days in this subscription.
     *
     * @return int
     */
    public function remainingDays()
    {
        if ($this->hasExpired()) {
            return (int) 0;
        }

        return (int) Carbon::now()->diffInDays(Carbon::parse($this->expires_on));
    }

    /**
     * Checks if the current subscription is cancelled (expiration date is in the past & the subscription is cancelled).
     *
     * @return bool
     */
    public function isCancelled()
    {
        return (bool) $this->cancelled_on != null;
    }

    /**
     * Checks if the current subscription is pending cancellation.
     *
     * @return bool
     */
    public function isPendingCancellation()
    {
        return (bool) ($this->isCancelled() && $this->isActive());
    }

    /**
     * Consume a feature, if it is 'limit' type.
     *
     * @param string $featureCode The feature code. This feature has to be 'limit' type.
     * @param int $amount The amount consumed.
     * @return bool Wether the feature was consumed successfully or not.
     */
    public function consumeFeature($featureCode, $amount)
    {
        $usageModel = config('plans.models.usage');

        $usage = $this->usages()->where('code', $featureCode)->first();
        $feature = $this->features()->where('code', $featureCode)->first();

        if ($feature && ! $usage) {
            if ($feature->type == 'limit') {
                $newUsage = $this->usages()->save(new $usageModel([
                    'code' => $featureCode,
                    'used' => 0,
                ]));

                if ($newUsage->used + $amount > $feature->limit) {
                    return false;
                }

                event(new \Rennokki\Plans\Events\FeatureConsumed($this, $feature, $amount, ($feature->limit - $newUsage->used)));

                return $newUsage->update([
                    'used' => (int) ($newUsage->used + $amount),
                ]);
            }
        }

        if (! $feature) {
            return false;
        }

        if ($feature->type != 'limit' || $usage->used + $amount > $feature->limit) {
            return false;
        }

        event(new \Rennokki\Plans\Events\FeatureConsumed($this, $feature, $amount, ($feature->limit - $usage->used)));

        return $usage->update([
            'used' => (int) ($usage->used + $amount),
        ]);
    }
}
