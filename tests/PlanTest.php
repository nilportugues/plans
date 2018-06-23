<?php

namespace Rennokki\Plans\Test;

class PlanTest extends TestCase
{
    protected $user;
    protected $plan;
    protected $newPlan;

    public function setUp()
    {
        parent::setUp();

        $this->user = factory(\Rennokki\Plans\Test\Models\User::class)->create();
        $this->plan = factory(\Rennokki\Plans\Models\PlanModel::class)->create();
        $this->newPlan = factory(\Rennokki\Plans\Models\PlanModel::class)->create();
    }

    public function testNoSubscriptions()
    {
        $this->assertNull($this->user->subscriptions()->first());
        $this->assertNull($this->user->activeSubscription());
        $this->assertNull($this->user->lastActiveSubscription());
        $this->assertNull($this->user->plan());
        $this->assertFalse($this->user->hasActiveSubscription());
    }

    public function testSubscribeToWithInvalidDuration()
    {
        $this->assertFalse($this->user->subscribeTo($this->plan, 0));
        $this->assertFalse($this->user->subscribeTo($this->plan, -1));
    }

    public function testSubscribeTo()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertNotNull($this->user->subscriptions()->first());
        $this->assertNotNull($this->user->activeSubscription());
        $this->assertNotNull($this->user->lastActiveSubscription());
        $this->assertNotNull($this->user->plan());
        $this->assertTrue($this->user->hasActiveSubscription());
        $this->assertEquals($subscription->remainingDays(), 14);
    }

    public function testUpgradeToNow()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $subscription = $subscription->upgradeTo($this->newPlan, 30, true);

        $this->assertEquals($subscription->plan_id, $this->newPlan->id);
        $this->assertEquals($subscription->remainingDays(), 44);
    }

    public function testUpgradeToAnotherCycle()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $subscription->upgradeTo($this->newPlan, 30, false);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
        $this->assertEquals($this->user->subscriptions->count(), 2);
    }

    public function testExtendWithWrongDuration()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $this->assertFalse($subscription->extendWith(-1));
        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
    }

    public function testExtendNow()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $subscription = $subscription->extendWith(30, true);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 44);
    }

    public function testExtendToAnotherCycle()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $subscription->extendWith(30, false);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
        $this->assertEquals($this->user->subscriptions->count(), 2);
    }

    public function testUpgradeFromUserWithoutActiveSubscription()
    {
        $subscription = $this->user->upgradeTo($this->newPlan, 15, true);
        sleep(1);

        $this->assertEquals($subscription->plan_id, $this->newPlan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
    }

    public function testUpgradeToFromUserNow()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $subscription = $this->user->upgradeTo($this->newPlan, 15, true);

        $this->assertEquals($subscription->plan_id, $this->newPlan->id);
        $this->assertEquals($subscription->remainingDays(), 29);
    }

    public function testUpgradeToFromUserToAnotherCycle()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $this->user->upgradeTo($this->newPlan, 30, false);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
        $this->assertEquals($this->user->subscriptions->count(), 2);
    }

    public function testExtendFromUserWithoutActiveSubscription()
    {
        $subscription = $this->user->extendCurrentSubscriptionWith(15, true);
        sleep(1);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
    }

    public function testExtendFromUserNow()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $subscription = $this->user->extendCurrentSubscriptionWith(15, true);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 29);
    }

    public function testExtendFromUserToAnotherCycle()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $this->user->extendCurrentSubscriptionWith(15, false);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);
        $this->assertEquals($this->user->subscriptions->count(), 2);
    }

    public function testCancelSubscription()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);

        $subscription = $subscription->cancel();

        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->isCancelled());
        $this->assertTrue($subscription->isPendingCancellation());
        $this->assertFalse($subscription->cancel());
    }

    public function testCancelSubscriptionFromUser()
    {
        $subscription = $this->user->subscribeTo($this->plan, 15);
        sleep(1);

        $this->assertEquals($subscription->plan_id, $this->plan->id);
        $this->assertEquals($subscription->remainingDays(), 14);

        $subscription = $this->user->cancelCurrentSubscription();

        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->isCancelled());
        $this->assertTrue($subscription->isPendingCancellation());
        $this->assertFalse($subscription->cancel());
    }

    public function testCancelSubscriptionWithoutSubscription()
    {
        $this->assertFalse($this->user->cancelCurrentSubscription());
    }
}
