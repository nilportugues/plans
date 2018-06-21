<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class UpgradeSubscription
{
    use SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}