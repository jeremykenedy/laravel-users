<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use jeremykenedy\laravelusers\Actions\DeleteAccount;
use jeremykenedy\laravelusers\Actions\UpdateAccount;
use jeremykenedy\laravelusers\App\Http\Requests\DeleteAccountRequest;
use jeremykenedy\laravelusers\App\Http\Requests\UpdateAccountRequest;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\EmailChanges;

class AccountController extends Controller
{
    public function show(Request $request, EmailChanges $emails)
    {
        $user = $request->user();
        $data = ['user' => $user, 'accountPage' => true, 'accountEditable' => AccountPreferences::editable($user), 'fullName' => AccountPreferences::fullName($user), 'pendingEmail' => $emails->pending($user)];
        $data['avatarSourceEnabled'] = (bool) config('laravelusers.account.avatar', true);
        $data['appearanceEnabled'] = (bool) config('laravelusers.account.appearance', true);

        return response()->view('laravelusers::account.page', $data + AvatarPreferences::formData($user) + AppearancePreferences::formData($user))->header('Cache-Control', 'no-store, private');
    }

    public function update(UpdateAccountRequest $request, UpdateAccount $update): RedirectResponse
    {
        $data = $request->validated();
        $update->handle($request->user(), $data);

        return back()->with('success', trans('laravelusers::ui.'.($data['section'] === 'email' ? 'account_email_pending' : 'account_saved')));
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccount $delete): RedirectResponse
    {
        $delete->handle($request->user(), $request->validated());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('laravelusers.account.redirect', '/'));
    }

    public function confirmEmail(Request $request, string $token, EmailChanges $emails): Response
    {
        $change = $emails->inspect($request->user(), $token);

        return $this->confirmation(['change' => $change, 'token' => $token], $change ? 200 : 410);
    }

    public function acceptEmail(Request $request, string $token, EmailChanges $emails): Response
    {
        $status = $emails->confirm($request->user(), $token);

        return $this->confirmation(['completed' => $status], $status ? 200 : 410);
    }

    private function confirmation(array $data, int $status): Response
    {
        return response()->view('laravelusers::account.confirm-email', $data + ['accountPage' => true], $status)->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow')->header('X-Frame-Options', 'DENY');
    }
}
