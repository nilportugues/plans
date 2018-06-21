<?php

return [

    /**
     * The model which handles the plans tables.
     */

    'models' => [

        'plan' => \Rennokki\Plans\Models\PlanModel::class,
        'subscription' => \Rennokki\Plans\Models\SubscriptionModel::class,
        'features' => \Rennokki\Plans\Models\FeatureModel::class,

    ],

];