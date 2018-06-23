<?php

namespace Rennokki\Plans\Test;

use Rennokki\Plans\Models\PlanFeatureModel;

class FeatureTest extends TestCase
{
    protected $user;
    protected $plan;

    public function setUp()
    {
        parent::setUp();

        $this->user = factory(\Rennokki\Plans\Test\Models\User::class)->create();
        $this->plan = factory(\Rennokki\Plans\Models\PlanModel::class)->create();
    }

    public function testConsumeFeature()
    {
        $subscription = $this->user->subscribeTo($this->plan, 30);

        $subscription->features()->saveMany([
            new PlanFeatureModel([
                'name' => 'Build minutes',
                'code' => 'build.minutes',
                'description' => 'Build minutes used for CI/CD.',
                'type' => 'limit',
                'limit' => 2000,
            ]),
            new PlanFeatureModel([
                'name' => 'Vault access',
                'code' => 'vault.access',
                'description' => 'Access to the precious vault.',
                'type' => 'feature',
            ]),
        ]);

        $this->assertEquals($subscription->features()->count(), 2);
        $this->assertEquals($subscription->usages()->count(), 0);

        $this->assertFalse($subscription->consumeFeature('build.minutes', 2001));
        $this->assertEquals($subscription->usages()->count(), 1);
        $this->assertFalse($subscription->consumeFeature('build.hours', 1));
        $this->assertTrue($subscription->consumeFeature('build.minutes', 10));
        $this->assertTrue($subscription->consumeFeature('build.minutes', 20));
        $this->assertEquals($subscription->usages()->where('code', 'build.minutes')->first()->used, 30);

        $this->assertFalse($subscription->consumeFeature('vault.access', 2001));
    }
}
