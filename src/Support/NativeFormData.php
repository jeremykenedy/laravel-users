<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ViewErrorBag;

class NativeFormData
{
    public function field(string $key, string $label, string $type = 'text', mixed $value = '', array $attributes = []): array
    {
        $parts = explode('.', $key);
        $name = array_shift($parts).implode('', array_map(fn ($part) => '['.$part.']', $parts));

        return $attributes + ['key' => $key, 'name' => $name.(!empty($attributes['multiple']) ? '[]' : ''), 'label' => $label, 'type' => $type, 'value' => $type === 'password' ? '' : $value, 'section' => 'profile', 'required' => false, 'disabled' => false];
    }

    public function form(string $id, string $title, ?string $action, string $method, array $fields, Request $request): array
    {
        $values = [];
        foreach ($fields as $field) {
            $value = $field['type'] === 'password' ? '' : ($request->hasSession() ? $request->session()->getOldInput($field['key'], $field['value']) : $field['value']);
            if ($field['type'] === 'checkbox') {
                $value = in_array($value, [true, 1, '1', 'true', 'on'], true);
            }
            Arr::set($values, $field['key'], $value);
        }
        $errors = $request->hasSession() ? $request->session()->get('errors') : null;

        return ['id' => $id, 'title' => $title, 'action' => $action, 'method' => $method, 'fields' => $fields, 'values' => $values, 'errors' => $errors instanceof ViewErrorBag ? $errors->getBag('default')->messages() : [], 'submit' => __('laravelusers::ui.save')];
    }

    public function choices(iterable $models): array
    {
        return collect($models)->map(fn ($model) => $this->choice($model))->values()->all();
    }

    public function roles(iterable $models): array
    {
        return collect($models)->map(function ($model) {
            $choice = $this->choice($model);
            if (config('laravelusers.showRoleLevels', true) && isset($model->getAttributes()['level'])) {
                $choice['label'] .= ' ('.__('laravelusers::ui.role_level', ['level' => $model->getAttribute('level')]).')';
            }

            return $choice;
        })->values()->all();
    }

    private function choice($model): array
    {
        return ['value' => (string) $model->getKey(), 'label' => (string) $model->name];
    }

    public function options(array $values, string $prefix): array
    {
        return array_map(fn ($value) => ['value' => $value, 'label' => __('laravelusers::ui.'.$prefix.$value)], $values);
    }
}
