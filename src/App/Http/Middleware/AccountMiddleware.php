<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\UserSettings;

class AccountMiddleware
{
    public function __construct(private readonly UserSettings $settings)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->settings->load();
        abort_unless($request->user() instanceof Model && AccountPreferences::enabled($request->user()), 404);

        return $next($request);
    }
}
