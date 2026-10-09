<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateEmailTemplatesRequest;
use jeremykenedy\laravelusers\Support\UserSettings;

class EmailTemplatesController extends PackageSettingsController
{
    public function save(UpdateEmailTemplatesRequest $request, UserSettings $settings): RedirectResponse
    {
        abort_unless($settings->available(), 409, trans('laravelusers::ui.settings_migration_required'));
        $current = $settings->model()->newQuery()->find('emails')?->value ?? [];
        $settings->save(array_replace_recursive($current, $request->validated()), 'emails');

        return redirect()->to(route('users.settings').'#emails')->with('success', trans('laravelusers::ui.email_templates_saved'));
    }
}
