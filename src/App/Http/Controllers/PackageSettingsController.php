<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use jeremykenedy\laravelusers\Actions\QueuePackageChange;
use jeremykenedy\laravelusers\App\Http\Middleware\UserAccessMiddleware;
use jeremykenedy\laravelusers\App\Http\Requests\ManagePackageRequest;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageOperations;
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
            return response()->json($requirements->status($packages))->header('Cache-Control', 'no-store, private');
        }

        if ($request->validated()['operation'] === 'setup') {
            $requirements->configure();

            return response()->json($requirements->status($packages))->header('Cache-Control', 'no-store, private');
        }
        $id = $change->handle($request->user(), $request->validated());

        return response()->json(PackageOperations::forActor($id, $request->user()), 202)->header('Cache-Control', 'no-store, private');
    }

    public function verify(ManagePackageRequest $request, PackageRequirements $requirements, ManagedPackages $packages): JsonResponse
    {
        abort_unless($request->validated()['operation'] === 'verify', 422);

        return response()->json($requirements->status($packages))->header('Cache-Control', 'no-store, private');
    }

    public function status(string $id, ManagedPackages $packages): JsonResponse
    {
        abort_unless($packages->allowed(request()->user()), 403);
        $record = PackageOperations::forActor($id, request()->user());
        abort_unless($record, 404);

        return response()->json($record)->header('Cache-Control', 'no-store, private');
    }
}
