[![Build Status](https://travis-ci.org/rennokki/plans.svg?branch=master)](https://travis-ci.org/rennokki/plans)
[![codecov](https://codecov.io/gh/rennokki/plans/branch/master/graph/badge.svg)](https://codecov.io/gh/rennokki/plans/branch/master)
[![StyleCI](https://github.styleci.io/repos/138162161/shield?branch=master)](https://github.styleci.io/repos/138162161)
[![Latest Stable Version](https://poser.pugx.org/rennokki/plans/v/stable)](https://packagist.org/packages/rennokki/plans)
[![Total Downloads](https://poser.pugx.org/rennokki/plans/downloads)](https://packagist.org/packages/rennokki/plans)
[![Monthly Downloads](https://poser.pugx.org/rennokki/plans/d/monthly)](https://packagist.org/packages/rennokki/plans)
[![License](https://poser.pugx.org/rennokki/plans/license)](https://packagist.org/packages/rennokki/plans)

[![PayPal](https://img.shields.io/badge/PayPal-donate-blue.svg)](https://paypal.me/rennokki)

# Laravel Plans
Laravel Plans is a package for SaaS-like apps that need easy management over plans, features and event-driven updates on plans. If you plan selling your service with subscription, you're in the right place!

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

**Note: In case you have modified `plans.php`'s models, make sure you use them, and do not forget to extend your models from them.**

# What can you do?
The basic unit of the subscription-like system is a plan. You can create it using `Rennokki\Plans\Models\PlanModel`.

If you have extended it, use your own model class to create one.
```php
$plan = PlanModel::create([
    'name' => 'My awesome plan',
    'description' => 'One of the best plans out here.',
    'price' => 9.99,
    'duration' => 30, // in days
]);
```

With the plan, you can assign features (or limits, i'll explain shortly) to it and you can subscribe your model which has `HasPlans` trait to it.

Features come in two types: `limit` and `feature`:
* `feature` is a single string, that do not needs counting. For example, you can store permissions.
* `limit` is a number. For this one, `limit` and `used` field will be used. It is meant to measure how many of that feature the holder has consumed. For example, how many build minutes has consumed during the month (or during the Cycle, which is 30 days in this example)
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

# Limits and Features
To consume the `limit` type feature, you can call `consumeFeature()` from a subscription instance. `activeSubscription()` method.

If you have subscribed your model to a plan, you can use `activeSubscription()` method, but make sure you check it before:
```php
if($user->hasActiveSubscription()) {
    $user->activeSubscription()->consumeFeature('build.minutes', 10); // consumed 10 minutes.
}
```

The `consumeFeature()` method will return a `null` if the feature does not exist, `false` if the feature is not a `limit` or the amount is exceeding the current feature allowance and it will return `true` if the consumption is done. 
```php
if($user->hasActiveSubscription()) {
    $user->activeSubscription()->consumeFeature('build.minutes', 2001); // false
    $user->activeSubscription()->consumeFeature('build.hours', 1); // false
    $user->activeSubscription()->consumeFeature('build.minutes', 30); // true
}
```

Alternatively, you can also un-consume features. This is a reverting method:
```php
if($user->hasActiveSubscription()) {
    $user->activeSubscription()->consumeFeature('build.minutes', 30); // true

    $user->activeSubscription()->unconsumeFeature('build.hours', 1); // false
    $user->activeSubscription()->unconsumeFeature('build.minutes', 30); // true

    // Now, the amount used for that feature is 0, the remaining part is 2000.
}
```

# Subscribing to plans
You can subscribe your models to a plan.
```php
// Subscribe an user to a plan.
$user->subscribeTo($plan, 30, true);
$user->activeSubscription()->remainingDays(); // 29; this is because it is 29 days, 23 hours, and so on.
```

Note: if the user is already subscribed, the `subscribeTo()` will return false. To avoid this, use `upgradeTo()` or `extendWith()` methods to either upgrade or extend the subscription period.

In `subscribeTo()` method, the first parameter represents the Plan instance the model will be subscribed to. The second one is the period, in days.

A false response is returned if the model has an active subscription. Thus, you can use `upgradeTo()` method to change the plan.

# Upgrading to another plans
```php
$user->upgradeTo($anotherPlan, 60, true);
$user->activeSubscription()->upgradeTo($anotherPlan, 60, true); // alias
```

The first parameter is the plan we wish to change to. The second is the period, in days

The third parameter is widely used across the package, so watch out: if set true, instead of creating a new subscription that starts at the end of the current subscription, it will extend the current one with a number of days. If set to false, a new subscription will be created in extension to the current one. If the current active subscription is expiring tomorrow, the new subscription will start tomorrow, after the first one ends.

When talking about this third parameter, we refer if the plan, either we're subscribing, extending or upgrading, will start at the `next cycle` or it is `exteding the current subscription`.

For convenience, if the user is not subscribed to any plan, it will be automatically subscribed if using this method.

# Extending subscriptions' durations.
Same as the upgrading methods, this accepts a duration and a parameter to know if the change should be made now, or in another cycle (another, future subscription, in extension to the current one), after the current subscription ends.
```php
$user->extendCurrentSubscriptionWith(60, true); // 60 days, starts now
$user->activeSubscription()->extendWith(60, true); // alias
```

# Cancelling
You can cancel subscriptions. If a subscription is not finished yet (it is not expired), it will be marked as `pending cancellation`. It will be fully cancelled when the expiration date comes in and it is cancelled.
```php
$user->cancelCurrentSubscription(); // false if there is not active subscription
$user->activeSubscription()->cancel();

// It can be checked out later.
$subscription->isCancelled(); // true, only if it has been cancelled
$subscription->isPendingCancellation(); // true; if it expires, it will be false
$subscription->isActive(); // still true; if it expires, it will be false
```

# Relationships
Whenever you want to query data about plans, you can use the relationships built it, either in the `SubscriptionPlan` instance (which is returned in the `activeSubscription()` method) or from your model which uses the trait.

```php
$user = User::find(1);
$user->subscribeTo(PlanModel::find(1), 30); // Subscribe to plan with ID 1, 30 days.

$user->subscriptions(); // All subscriptions; Relationship
$user->currentSubscription(); // Current subscription; Relationship

// null if does not have an active subscription. It returns a PlanSubscription instance.
$user->activeSubscription();

// If it does not have subscriptions, it returns null.
// If has one active, it returns the `PlanSubscription` instance.
// If does not have an active one, it returns the last one, even if it expired.
$user->lastActiveSubscription();

// Returns true if has an active subscription; else, false.
$user->hasActiveSubscription();
```

To avoid confusion, the `PlanSubscription` instance is returned on `activeSubscription()` and `lastActiveSubscription()`. You can call the following methods in chain to them:
```php
$subscription = $user->activeSubscription();

$subscription->plan(); // Returns the plan of this subscription; Relationship
$subscription->features(); // Returns the features of the current subscription; Relationship
$subscription->usages(); // Returns the usage counts for the limit-type features within this subscription's plan.
```

# Events
When using subscription plans, you want to trigger events to automatically run code that might do changes. For example, if an user automatically extends their period before the subscription ends, you can give him free bonus days for loyality.

Events are easy to use. If you are not familiar, you can check [Laravel's Official Documentation on Events](https://laravel.com/docs/5.6/events).

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
     \Rennokki\Plans\Events\FeatureUnconsumed::class => [
        // $event->subscription = The current subscription.
        // $event->feature = The feature that was used.
        // $event->used = The amount reverted.
        // $event->remaining = The total amount remaining.
    ],
];
```
