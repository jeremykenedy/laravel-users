<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserSettings;

class UserAccessMiddleware
{
    public function __construct(private readonly UserSettings $settings)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $request->attributes->remove('laravelusers.access');
        $this->settings->load();
        $method = $request->route()->getActionMethod();
        if ($method === 'destroyWithEmail') {
            $method = 'destroy';
        }
        abort_if(in_array($method, ['settings', 'updateSettings'], true) && !config('laravelusers.settings.enabled', false), 404);
        $action = ['index' => 'view_users', 'search' => 'view_users', 'show' => 'view_users', 'create' => 'create_users', 'store' => 'create_users', 'edit' => 'edit_users', 'update' => 'edit_users', 'destroy' => 'delete_users', 'deleted' => 'view_deleted', 'editDeleted' => 'edit_deleted', 'updateDeleted' => 'edit_deleted', 'restore' => 'restore_users', 'forceDestroy' => 'force_delete', 'settings' => 'edit_settings', 'updateSettings' => 'edit_settings'][$method] ?? null;
        if ($method === 'bulk') {
            $bulkAction = $request->input('action');
            $action = is_string($bulkAction) ? (['delete' => 'delete_users', 'restore' => 'restore_users', 'force_delete' => 'force_delete'][$bulkAction] ?? null) : null;
        }
        abort_if($action && !UserAccess::allows($action), 403);
        if (in_array($method, ['email', 'previewEmail'], true)) {
            $emailAction = $request->input('action');
            abort_if(is_string($emailAction) && in_array($emailAction, ['message', 'reset', 'welcome'], true) && !UserAccess::email($emailAction, $request->boolean('deleted')), 403);
            abort_if($request->boolean('include_restore') && !UserAccess::allows('restore_users'), 403);
            abort_if($request->boolean('include_force_delete') && !UserAccess::allows('force_delete'), 403);
        }
        $request->attributes->set('laravelusers.access', array_combine(UserAccess::ACTIONS, array_map(fn ($action) => UserAccess::allows($action), UserAccess::ACTIONS)));

        return $next($request);
    }
}
