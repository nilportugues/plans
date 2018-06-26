<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class ExtendSubscription
{
    use SerializesModels;

    public $subscription;
    public $duration;
    public $startFromNow;
    public $newSubscription;

    public function __construct($subscription, $duration, $startFromNow, $newSubscription)
    {
        $this->subscription = $subscription;
        $this->duration = $duration;
        $this->startFromNow = $startFromNow;
        $this->newSubscription = $newSubscription;
    }
}
