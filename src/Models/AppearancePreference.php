<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Models;

use Illuminate\Database\Eloquent\Model;

class AppearancePreference extends Model
{
    protected $table = 'laravelusers_appearance_preferences';

    protected $primaryKey = 'user_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_key', 'color', 'gradient', 'gradient_strength', 'dark_color', 'dark_gradient', 'dark_gradient_strength'];

    protected $casts = ['gradient' => 'boolean', 'gradient_strength' => 'integer', 'dark_gradient' => 'boolean', 'dark_gradient_strength' => 'integer'];
}
