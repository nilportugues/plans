[![Build Status](https://travis-ci.org/rennokki/plans.svg?branch=master)](https://travis-ci.org/rennokki/plans)
[![codecov](https://codecov.io/gh/rennokki/plans/branch/master/graph/badge.svg)](https://codecov.io/gh/rennokki/plans/branch/master)
[![StyleCI](https://github.styleci.io/repos/138162161/shield?branch=master)](https://github.styleci.io/repos/138162161)
[![Latest Stable Version](https://poser.pugx.org/rennokki/plans/v/stable)](https://packagist.org/packages/rennokki/plans)
[![Total Downloads](https://poser.pugx.org/rennokki/plans/downloads)](https://packagist.org/packages/rennokki/plans)
[![Monthly Downloads](https://poser.pugx.org/rennokki/plans/d/monthly)](https://packagist.org/packages/rennokki/plans)
[![License](https://poser.pugx.org/rennokki/plans/license)](https://packagist.org/packages/rennokki/plans)

# Laravel Plans
Laravel Plans is a package for SaaS-like apps that need easy management over plans, features and event-driven updates on plans.

# Installation
Install the package:
```bash
$ composer require rennokki/plans
```

If your Laravel version does not support package discovery, add this line in the `providers` array in your `config/app.php` file:
```php
Rennokki\Plans\PlansServiceProvider::class,
```

Publish the config file & migration files:
```bash
$ php artisan vendor:publish
```

Migrate the database:
```bash
$ php artisan migrate
```

Add the `HasPlans` trait to your Eloquent model:
```php
use Rennokki\Plans\Traits\HasPlans;

class User extends Model {
    use HasPlans;
    ...
}
```

In case you have modified `plans.php`'s models, make sure you use them, and do not forget to extend your models from them.

# What can you do?
You can create a Plan instance.
```php
$plan = PlanModel::create([
    'name' => 'My awesome plan',
    'description' => 'One of the best plans out here.',
    'price' => 9.99,
    'duration' => 30, // in days
]);
```

You can use the plan to assign Features to it. Features come in two types: `limit` or `feature`.

Feature is a single string, that do not needs counting. For example, you can store permissions.

Limit is a usually an number. For this one, `limit` and `used` field will be used. It is good to measure how many of that feature the holder has consumed. For example, how many build minutes has consumed during the month (or during the Cycle, which is 30 days in this example)
```php
$plan->features()->saveMany([
    new PlanFeatureModel([
        'name' => 'Vault access',
        'code' => 'vault.access',
        'description' => 'Offering access to the vault.',
        'type' => 'feature',
    ]),
    new PlanFeatureModel([
        'name' => 'Build minutes',
        'code' => 'build.minutes',
        'description' => 'Build minutes used for CI/CD.',
        'type' => 'limit',
        'limit' => 2000,
    ]),
    ...
]);
```

# Limits
To consume the `limit` type feature, you can call it from `activeSubscription()` method. Make sure that before you call it, you check for `hasActiveSubscription()` like:
```php
if($user->hasActiveSubscription()) {
    $user->activeSubscription()->consumeFeature('build.minutes', 10); // consumed 10 minutes.
}
```

This will return a `null` if the feature does not exist, or `false` if the feature is not a `limit` or the amount is exceeding the current feature allowance. For example:
```php
if($user->hasActiveSubscription()) {
    $user->activeSubscription()->consumeFeature('build.minutes', 2001); // false
    $user->activeSubscription()->consumeFeature('build.hours', 1); // null
}
```

# Relationships & Getters
Relationships can hold a lot of information.

Relationships can be used on the User:
```php
$user->subscriptions(); // All subscriptions; Relationship
$user->plan(); // Current plan; Relationship

// null if does not have an active subscription
$user->activeSubscription();

// If it does not have subscriptions, it returns null.
// If has one active, it returns it. If does not have an active one, it returns the last one.
$user->lastActiveSubscription();

// Returns true if has an active subscription.
$user->hasActiveSubscription();
```

Relationships from the Subscription:
```php
$subscription = $user->activeSubscription();

$subscription->plan(); // Relationship
$subscription->features(); // Relationship.

// Cancel the current subscription.
// If the subscription did not end, it is in a pending cancellation state.
$subscription->cancel();

// It can be checked out later.
$subscription->isCancelled(); // true
$subscription->isPendingCancellation(); // true; if it expires, it will be false
$subscription->isActive(); // still true; if it expires, it will be false
```

# Subscribing to plans
You can subscribe your models, you can extend the period and you can cancel subscriptions. To do so, you will be guided to:
```php
// Subscribe an user to a plan.
$user->subscribeTo($plan, 30, true);
$user->activeSubscription()->remainingDays(); // 29 days; and 23 hours... you know.
```

The first parameter represents the Plan instance the model will be subscribed to. The second one is the period, in days. The third one is optional, meaning the that subscription will start now, and not after the current subscription is finished.

A false response is returned if the model has an active subscription. Thus, you can use `upgradeTo` to change this status.

# Upgrading to another plans
```php
$user->upgradeTo($anotherPlan, 60, false);
$user->activeSubscription()->upgradeTo($anotherPlan, 60, false); // alias
```

Again, the first parameter is the plan we wish to change to. The second is the period in days. The third parameter, if false, indicates that the subscription will start after the previous subscription ends. So, in this case, the `$anotherPlan` won't come into place until the `$plan` finished that 30 days period.

Alternatively, if the user is not subscribed to any plan, it will be automatically subscribed if using this method.

# Extending subscriptions' durations.
Same as the upgrading methods, this accepts a duration and a parameter to know if the change should be made now, or in another cycle (another, future subscription), after the current subscription ends.
```php
$user->extendCurrentSubscriptionWith(60, true); // 60 days, starts now
$user->activeSubscription()->extendWith(60, true); // alias
```

# Cancelling
You can cancel subscriptions. If a subscription is not finished yet (it is not expired), it will be marked as `pending cancelling`. It will be fully cancelled when the expiration date comes in.
```php
$user->cancelCurrentSubscription(); // false if there is not active subscription
$user->activeSubscription()->cancel();
```

# Events
When using subscription plans, you want to trigger events to automatically run code that might do changes. For example, if an user automatically extends their period before the subscription ends, you can give him free bonus days for loyality.

Events are easy to use. If you are not familiar, you can check [Laravel's docs on Events](https://laravel.com/docs/5.6/events).

All you have to do is to implement the following Events in your `EventServiceProvider.php` file:
```php
$listen = [
    ...
    \Rennokki\Plans\Events\CancelSubscription::class => [
        // $event->subscription = The subscription that was cancelled.
    ],
    \Rennokki\Plans\Events\NewSubscription::class => [
        // $event->subscription = The subscription that was created.
        // $event->duration = The duration, in days, of the subscription.
    ],
    \Rennokki\Plans\Events\ExtendSubscription::class => [
        // $event->subscription = The subscription that was extended.
        // $event->duration = The duration, in days, of the subscription.
        // $event->startFromNow = If the subscription is exteded now or is created a new subscription, in the future.
        // $event->newSubscription = If the startFromNow is false, here will be sent the new subscription that starts after the current one ends.
    ],
    \Rennokki\Plans\Events\UpgradeSubscription::class => [
        // $event->subscription = The current subscription.
        // $event->duration = The duration, in days, of the upgraded subscription.
        // $event->startFromNow = If the subscription is upgraded now or is created a new subscription, in the future.
        // $event->oldPlan = Here lies the current (which is now old) plan.
        // $event->newPlan = Here lies the new plan. If it's the same plan, it will match with the $event->oldPlan
    ],
    \Rennokki\Plans\Events\FeatureConsumed::class => [
        // $event->subscription = The current subscription.
        // $event->feature = The feature that was used.
        // $event->used = The amount used.
        // $event->remaining = The total amount remaining.
    ],
];
```
