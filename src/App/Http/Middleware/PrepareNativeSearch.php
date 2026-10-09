<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\App\Http\Requests\NativeSearchRequest;

class PrepareNativeSearch
{
    public function __construct(private NativeSearchRequest $search)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $request->attributes->set('laravelusers.search', $this->search->validated()['user_search_box'] ?? null);

        return $next($request);
    }
}
