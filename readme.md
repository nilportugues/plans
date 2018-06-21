[![Build Status](https://travis-ci.org/rennokki/plans.svg?branch=master)](https://travis-ci.org/rennokki/plans)
[![codecov](https://codecov.io/gh/rennokki/plans/branch/master/graph/badge.svg)](https://codecov.io/gh/rennokki/plans/branch/master)
[![Latest Stable Version](https://poser.pugx.org/rennokki/plans/v/stable)](https://packagist.org/packages/rennokki/plans)
[![Total Downloads](https://poser.pugx.org/rennokki/plans/downloads)](https://packagist.org/packages/rennokki/plans)
[![Monthly Downloads](https://poser.pugx.org/rennokki/plans/d/monthly)](https://packagist.org/packages/rennokki/plans)
[![License](https://poser.pugx.org/rennokki/plans/license)](https://packagist.org/packages/rennokki/plans)

# The app is WIP.

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

