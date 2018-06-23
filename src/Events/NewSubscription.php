<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class NewSubscription
{
    use SerializesModels;

    public $subscription;
    public $duration;

    public function __construct($subscription, $duration)
    {
        $this->subscription = $subscription;
        $this->duration = $duration;
    }
}
