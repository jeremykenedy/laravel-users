<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Support\ImpersonationSession;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserSettings;

class VerifyImpersonationState
{
    public function __construct(private ImpersonationSession $sessions, private UserSettings $settings)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->hasSession() || !$request->session()->has(ImpersonationSession::KEY)) {
            return $next($request);
        }
        $state = $this->sessions->read($request);
        $target = $state ? $this->sessions->target($state) : null;
        $actor = $state ? $this->sessions->actor($state) : null;
        if (!$target || !$actor) {
            $this->sessions->invalidate($request);
            abort(403);
        }
        $this->settings->load();
        if (!$request->routeIs('users.impersonation.stop') && ($state['expires_at'] <= now()->getTimestamp() || !UserAccess::canImpersonate($target, $actor))) {
            $returnTo = $this->sessions->restore($request, $state, $actor);

            return redirect()->to($returnTo)->with('warning', trans('laravelusers::ui.impersonation_expired'));
        }

        return $next($request);
    }
}
