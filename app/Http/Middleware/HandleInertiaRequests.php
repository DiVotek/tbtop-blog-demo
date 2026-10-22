<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Shares the validation errors bag with Inertia admin responses.
 */
class HandleInertiaRequests extends Middleware
{
    /** @var string */
    protected $rootView = 'admin';
}
