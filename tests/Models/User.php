<?php

namespace Rennokki\Plans\Test\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Rennokki\Plans\Traits\HasPlans;

class User extends Authenticatable
{
    use HasPlans;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}
