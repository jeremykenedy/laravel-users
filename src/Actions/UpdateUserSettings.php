<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserSettings;

class UpdateUserSettings
{
    public function __construct(private readonly UserSettings $settings)
    {
    }

    public function handle(array $data): void
    {
        abort_unless($this->settings->available(), 409, trans('laravelusers::ui.settings_migration_required'));
        $access = $data['access'] ?? config('laravelusers.access', []);
        if (!UserAccess::allows('edit_settings', $access)) {
            throw ValidationException::withMessages(['access' => trans('laravelusers::ui.settings_lockout')]);
        }
        $settings = ['access' => $access];
        foreach (['avatar_source' => 'avatar.source', 'profile_color' => 'profileCardColor', 'edit_color' => 'editCardColor', 'profile_gradient' => 'profileCardGradient', 'profile_gradient_strength' => 'profileCardGradientStrength', 'edit_gradient' => 'editCardGradient', 'edit_gradient_strength' => 'editCardGradientStrength', 'profile_dark_color' => 'profileCardDarkColor', 'profile_dark_gradient' => 'profileCardDarkGradient', 'profile_dark_gradient_strength' => 'profileCardDarkGradientStrength', 'edit_dark_color' => 'editCardDarkColor', 'edit_dark_gradient' => 'editCardDarkGradient', 'edit_dark_gradient_strength' => 'editCardDarkGradientStrength', 'notifications_driver' => 'notifications.driver', 'notifications_dismissible' => 'notifications.dismissible'] as $input => $key) {
            $value = $data[$input] ?? config('laravelusers.'.$key);
            $settings[$key] = $value !== null && in_array($input, ['profile_gradient', 'edit_gradient', 'profile_dark_gradient', 'edit_dark_gradient', 'notifications_dismissible'], true) ? (bool) $value : $value;
        }
        $this->settings->save($settings);
    }
}
