<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class NewSubscription
{
    use SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}