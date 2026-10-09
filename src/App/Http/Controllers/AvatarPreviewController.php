<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use jeremykenedy\laravelusers\Actions\AvatarPreview;
use jeremykenedy\laravelusers\App\Http\Requests\AvatarPreviewRequest;

class AvatarPreviewController extends PackageSettingsController
{
    public function preview(AvatarPreviewRequest $request, AvatarPreview $preview): JsonResponse
    {
        return response()->json(['avatars' => $preview->handle($request->validated()['avatar_source'])])
            ->header('Cache-Control', 'no-store, private');
    }

    public function image(AvatarPreviewRequest $request, AvatarPreview $preview): Response
    {
        return response()->view('laravelusers::avatars.initials', $preview->image($request->validated()['sample']))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Content-Security-Policy', "default-src 'none'; sandbox");
    }
}
