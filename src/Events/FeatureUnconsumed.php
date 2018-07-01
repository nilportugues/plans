<?php

namespace Rennokki\Plans\Events;

use Illuminate\Queue\SerializesModels;

class FeatureUnconsumed
{
    use SerializesModels;

    public $subscription;
    public $feature;
    public $used;
    public $remaining;

    public function __construct($subscription, $feature, $used, $remaining)
    {
        $this->subscription = $subscription;
        $this->feature = $feature;
        $this->used = $used;
        $this->remaining = $remaining;
    }
}
