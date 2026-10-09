<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateImpersonationSettingsRequest;
use jeremykenedy\laravelusers\Support\ImpersonationSession;
use jeremykenedy\laravelusers\Support\RoleAccess;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserSettings;

class ImpersonationController extends Controller
{
    public function __construct(private ImpersonationSession $sessions)
    {
        $this->middleware('auth');
        $middleware = config('laravelusers.middleware', []);
        if ($middleware) {
            $this->middleware($middleware)->only('start', 'update');
        }
        if (config('laravelusers.rolesEnabled', false) && config('laravelusers.rolesMiddlwareEnabled', true)) {
            $this->middleware(config('laravelusers.rolesMiddlware', 'role:admin'))->only('start', 'update');
        }
        $middleware = config('laravelusers.impersonation.middleware', []);
        if ($middleware) {
            $this->middleware($middleware)->only('start');
        }
    }

    public function start(int $id, Request $request, UserSettings $settings): RedirectResponse
    {
        $settings->load();
        abort_unless(UserAccess::impersonationAvailable(), 404);
        abort_if($request->session()->has('laravelusers.impersonation'), 409);
        $model = config('laravelusers.defaultUserModel');
        $target = $model::query()->findOrFail($id);
        abort_unless(UserAccess::canImpersonate($target), 403);

        $this->sessions->begin($request, Auth::user(), $target);

        return redirect()->to(url('/'))->with('success', trans('laravelusers::ui.impersonation_started'));
    }

    public function stop(Request $request): RedirectResponse
    {
        $state = $this->sessions->read($request);
        abort_unless($state, 404);
        $actor = $this->sessions->actor($state);
        abort_unless($actor, 403);
        $returnTo = $this->sessions->restore($request, $state, $actor);

        return redirect()->to($returnTo)->with('success', trans('laravelusers::ui.impersonation_stopped'));
    }

    public function update(UpdateImpersonationSettingsRequest $request, UserSettings $settings): RedirectResponse
    {
        abort_unless($settings->available(), 409, trans('laravelusers::ui.settings_migration_required'));
        $settings->load();
        $enabled = $request->boolean('enabled');
        abort_if($enabled && !RoleAccess::available($request->user()), 422);
        $model = $settings->model();
        $global = $model->newQuery()->find('global');
        $values = $global?->value ?? [];
        $values['impersonation.enabled'] = $enabled;
        $settings->save($values);
        config(['laravelusers.impersonation.enabled' => $enabled]);

        return redirect()->to(route('users.settings').'#packages')->with('success', trans($enabled ? 'laravelusers::ui.impersonation_enabled' : 'laravelusers::ui.impersonation_disabled'));
    }
}
