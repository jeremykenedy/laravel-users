<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

class NativeAppearanceData
{
    public function __construct(private NativeFormData $forms)
    {
    }

    public function appearanceFields(array $data, array $enabled = []): array
    {
        $fields = [];
        if (($enabled['avatar'] ?? true) && ($data['avatarSourceEnabled'] ?? false)) {
            $fields[] = $this->forms->field('avatar_source', __('laravelusers::ui.avatar_source'), 'select', $data['avatarSource'] ?? 'inherit', ['options' => $this->forms->options(array_merge(['inherit'], Avatar::SOURCES), 'avatar_source_'), 'disabled' => !($data['avatarSourceAvailable'] ?? false), 'section' => 'appearance']);
        }
        if (!($enabled['appearance'] ?? true) || !($data['appearanceEnabled'] ?? false)) {
            return $fields;
        }
        foreach (['' => $data['appearanceAvailable'] ?? false, '_dark' => $data['appearanceDarkAvailable'] ?? false] as $mode => $available) {
            if ($mode !== '' && !$available) {
                continue;
            }
            $fields = array_merge($fields, $this->colorFields($data, $mode, (bool) $available));
        }

        return $fields;
    }

    public function appearanceDefaults(): array
    {
        $colors = [];
        foreach (['profile', 'edit', 'profile_dark', 'edit_dark'] as $kind) {
            $editing = str_starts_with($kind, 'edit');
            $dark = str_ends_with($kind, '_dark');
            $prefix = $editing ? 'editCard' : 'profileCard';
            $colors[$kind] = Frontend::profileColors($prefix.'Color', $editing ? '#705000' : '#2458b7', $dark) + ['gradient' => (bool) (($dark ? config('laravelusers.'.$prefix.'DarkGradient') : null) ?? config('laravelusers.'.$prefix.'Gradient', true)), 'highlight_color' => ($dark ? config('laravelusers.'.$prefix.'DarkGradientHighlightColor') : null) ?? config('laravelusers.'.$prefix.'GradientHighlightColor', '#ffffff'), 'highlight_inherits_light' => $dark && config('laravelusers.'.$prefix.'DarkGradientHighlightColor') === null];
        }

        return $colors;
    }

    private function colorFields(array $data, string $mode, bool $available): array
    {
        $fields = [];
        $preference = $data['appearancePreference'] ?? [];
        $key = $mode === '' ? '' : 'dark_';
        $fields[] = $this->forms->field('user_card'.$mode.'_color', __('laravelusers::ui.'.($mode === '' ? 'settings_profile_color' : 'settings_profile_dark_color')), 'color', $preference[$key.'color'] ?? null, ['nullable' => true, 'fallback' => Frontend::profileColors(dark: $mode !== '')['base'], 'disabled' => !$available, 'section' => 'appearance']);
        $gradient = $preference[$key.'gradient'] ?? null;
        $fields[] = $this->forms->field('user_card'.$mode.'_gradient', __('laravelusers::ui.appearance_gradient'), 'select', $gradient === null ? 'inherit' : ($gradient ? 'on' : 'off'), ['options' => $this->forms->options(['inherit', 'on', 'off'], 'appearance_'), 'disabled' => !$available, 'section' => 'appearance']);
        if ($mode !== '' || ($data['appearanceStrengthAvailable'] ?? false)) {
            $fields[] = $this->forms->field('user_card'.$mode.'_gradient_strength', __('laravelusers::ui.gradient_strength'), 'range', $preference[$key.'strength'] ?? null, ['nullable' => true, 'fallback' => Frontend::profileColors(dark: $mode !== '')['strength'], 'min' => 0, 'max' => 100, 'disabled' => !$available, 'section' => 'appearance']);
        }
        $fields = array_merge($fields, $this->highlightField($data, $mode, $available));

        return $fields;
    }

    private function highlightField(array $data, string $mode, bool $available): array
    {
        $fields = [];
        $preference = $data['appearancePreference'] ?? [];
        $key = $mode === '' ? '' : 'dark_';
        if ($data['appearanceHighlightAvailable'] ?? false) {
            $prefix = $mode === '' ? 'profileCard' : 'profileCardDark';
            $fields[] = $this->forms->field('user_card'.$mode.'_gradient_highlight_color', __('laravelusers::ui.gradient_highlight_color'), 'color', $preference[$key.'highlight_color'] ?? null, ['nullable' => true, 'fallback' => config('laravelusers.'.$prefix.'GradientHighlightColor') ?? config('laravelusers.profileCardGradientHighlightColor', '#ffffff'), 'inherit_from' => $mode !== '' && config('laravelusers.profileCardDarkGradientHighlightColor') === null ? 'user_card_gradient_highlight_color' : null, 'inherit_label' => __('laravelusers::ui.appearance_inherit_highlight_color'), 'disabled' => !$available, 'section' => 'appearance']);
        }

        return $fields;
    }
}
