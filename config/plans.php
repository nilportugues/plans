<?php

return [

    /*
     * The model which handles the plans tables.
     */

    'models' => [

        'plan' => \Rennokki\Plans\Models\PlanModel::class,
        'subscription' => \Rennokki\Plans\Models\SubscriptionModel::class,
        'feature' => \Rennokki\Plans\Models\FeatureModel::class,
        'usage' => \Rennokki\Plans\Models\PlanSubscriptionUsageModel::class,

    ],

];
