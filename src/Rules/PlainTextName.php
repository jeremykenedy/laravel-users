<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Rules;

use Illuminate\Validation\ClosureValidationRule;

class PlainTextName extends ClosureValidationRule
{
    public function __construct()
    {
        parent::__construct(static function ($attribute, $value, $fail) {
            if (!is_string($value) || str_contains($value, '<') || str_contains($value, '>')) {
                $fail(trans('laravelusers::ui.plain_name'));
            }
        });
    }
}
