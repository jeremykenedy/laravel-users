<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateCleanupRequest;
use jeremykenedy\laravelusers\Support\UserSettings;

class CleanupSettingsController extends PackageSettingsController
{
    public function save(UpdateCleanupRequest $request, UserSettings $settings): RedirectResponse
    {
        abort_unless($settings->available(), 409, trans('laravelusers::ui.settings_migration_required'));
        $data = $request->validated();
        $settings->save(['enabled' => $request->boolean('enabled'), 'amount' => $data['unit'] === 'immediately' ? 0 : (int) $data['amount'], 'unit' => $data['unit']], 'cleanup');

        return redirect()->to(route('users.settings').'#cleanup')->with('success', trans('laravelusers::ui.cleanup_saved'));
    }
}
