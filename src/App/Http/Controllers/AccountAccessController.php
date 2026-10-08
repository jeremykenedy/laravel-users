<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use jeremykenedy\laravelusers\Actions\UpdateAccountAccess;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateAccountAccessRequest;

class AccountAccessController extends PackageSettingsController
{
    public function save(UpdateAccountAccessRequest $request, UpdateAccountAccess $update): RedirectResponse
    {
        $update->handle($request->user(), $request->validated());

        return redirect()->to(route('users.settings').'#accounts')->with('success', trans('laravelusers::ui.account_access_saved'));
    }
}
