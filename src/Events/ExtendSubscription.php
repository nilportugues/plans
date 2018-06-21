<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class ExtendSubscription
{
    use SerializesModels;

    public $subscription;
    public $duration;
    public $startFromNow;

    public function __construct($subscription, $duration, $startFromNow)
    {
        $this->subscription = $subscription;
        $this->duration = $duration;
        $this->startFromNow = $startFromNow;
    }
}