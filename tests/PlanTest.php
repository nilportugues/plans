<?php

namespace Rennokki\Plans\Test;

use Rennokki\Plans\Test\Models\User;

class PlanTest extends TestCase {

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

}