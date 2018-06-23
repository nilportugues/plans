<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class FeatureConsumed
{
    use SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}
