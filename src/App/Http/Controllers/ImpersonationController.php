<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateImpersonationSettingsRequest;
use jeremykenedy\laravelusers\Support\RoleAccess;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserSettings;

class ImpersonationController extends Controller
{
    public function __construct()
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
        abort_unless(UserAccess::canImpersonate(), 404);
        abort_if($request->session()->has('laravelusers.impersonation'), 409);
        $model = config('laravelusers.defaultUserModel');
        $target = $model::query()->findOrFail($id);
        abort_unless(UserAccess::canImpersonate($target), 403);

        $actor = Auth::user();
        $guard = Auth::getDefaultDriver();
        $request->session()->put('laravelusers.impersonation', [
            'actor_id'   => (string) $actor->getKey(),
            'actor_name' => (string) $actor->getAttribute('name'),
            'guard'      => $guard,
            'return_to'  => $this->returnPath($request),
        ]);
        Auth::guard($guard)->login($target);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->to(url('/'))->with('success', trans('laravelusers::ui.impersonation_started'));
    }

    public function stop(Request $request): RedirectResponse
    {
        $state = $request->session()->get('laravelusers.impersonation');
        abort_unless(is_array($state) && isset($state['actor_id'], $state['guard']), 404);
        $model = config('laravelusers.defaultUserModel');
        $actor = $model::query()->find($state['actor_id']);
        if (!$actor instanceof Model) {
            Auth::guard($state['guard'])->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, trans('laravelusers::ui.impersonation_actor_missing'));
        }

        Auth::guard($state['guard'])->login($actor);
        $request->session()->forget('laravelusers.impersonation');
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $returnTo = $this->safeReturnPath($state['return_to'] ?? null);

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

    private function returnPath(Request $request): string
    {
        $referer = (string) $request->headers->get('referer');
        $path = parse_url($referer, PHP_URL_PATH);
        $query = parse_url($referer, PHP_URL_QUERY);
        $returnTo = is_string($path) && is_string($query) ? $path.'?'.mb_substr($query, 0, 2048) : $path;

        return $this->safeReturnPath($returnTo);
    }

    private function safeReturnPath(mixed $path): string
    {
        return is_string($path)
            && str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && preg_match('/[\r\n\\\\]/', $path) !== 1
            ? $path
            : route('users');
    }
}
