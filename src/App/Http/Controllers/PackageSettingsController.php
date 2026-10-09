<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use jeremykenedy\laravelusers\Actions\QueuePackageChange;
use jeremykenedy\laravelusers\App\Http\Middleware\UserAccessMiddleware;
use jeremykenedy\laravelusers\App\Http\Requests\ManagePackageRequest;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageRequirements;

class PackageSettingsController extends Controller
{
    public function __construct()
    {
        $middleware = config('laravelusers.middleware', []);
        if ($middleware) {
            $this->middleware($middleware);
        }
        if (config('laravelusers.rolesEnabled', false) && config('laravelusers.rolesMiddlwareEnabled', true)) {
            $this->middleware(config('laravelusers.rolesMiddlware', 'role:admin'));
        }
        $this->middleware(UserAccessMiddleware::class);
    }

    public function store(ManagePackageRequest $request, QueuePackageChange $change, PackageRequirements $requirements, ManagedPackages $packages): JsonResponse
    {
        if ($request->validated()['operation'] === 'verify') {
            $verified = $requirements->verify($packages);

            return response()->json([
                'status'      => $verified ? 'completed' : 'not_ready',
                'queue_ready' => $verified,
                'message'     => trans($verified ? 'laravelusers::ui.package_requirements_verified' : 'laravelusers::ui.package_requirements_not_verified'),
            ]);
        }

        if ($request->validated()['operation'] === 'setup') {
            $requirements->configure();

            return response()->json(['status' => 'completed', 'queue_ready' => $packages->queueReady(), 'message' => trans('laravelusers::ui.package_requirements_ready')]);
        }
        $id = $change->handle($request->user(), $request->validated());

        return response()->json(['id' => $id, 'status_url' => route('users.settings.packages.status', $id)], 202);
    }

    public function verify(ManagePackageRequest $request, PackageRequirements $requirements, ManagedPackages $packages): JsonResponse
    {
        abort_unless($request->validated()['operation'] === 'verify', 422);
        $verified = $requirements->verify($packages);

        return response()->json([
            'status'      => $verified ? 'completed' : 'not_ready',
            'queue_ready' => $verified,
            'message'     => trans($verified ? 'laravelusers::ui.package_requirements_verified' : 'laravelusers::ui.package_requirements_not_verified'),
        ]);
    }

    public function status(string $id, ManagedPackages $packages): JsonResponse
    {
        abort_unless($packages->allowed(request()->user()), 403);
        $record = ManagedPackages::cache()->get('laravelusers.package.'.$id);
        abort_unless($record && ($record['actor'] ?? null) === (string) request()->user()->getKey(), 404);

        return response()->json(array_diff_key($record, ['actor' => true]))->header('Cache-Control', 'no-store, private');
    }
}
