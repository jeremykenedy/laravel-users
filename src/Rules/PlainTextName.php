<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Rules;

use Illuminate\Contracts\Validation\Rule;

class PlainTextName implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && !str_contains($value, '<') && !str_contains($value, '>');
    }

    public function message(): string
    {
        return trans('laravelusers::ui.plain_name');
    }
}
