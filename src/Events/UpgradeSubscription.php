<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class UpgradeSubscription
{
    use SerializesModels;

    public $subscription;
    public $duration;
    public $startFromNow;
    public $oldPlan;
    public $newPlan;

    public function __construct($subscription, $duration, $startFromNow, $oldPlan, $newPlan)
    {
        $this->subscription = $subscription;
        $this->duration = $duration;
        $this->startFromNow = $startFromNow;
        $this->oldPlan = $oldPlan;
        $this->newPlan = $newPlan;
    }
}
