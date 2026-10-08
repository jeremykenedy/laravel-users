<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Models;

use Illuminate\Database\Eloquent\Model;

class AccountPreference extends Model
{
    protected $table = 'laravelusers_account_preferences';

    protected $primaryKey = 'user_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_key', 'user_type', 'enabled', 'settings_enabled', 'full_name'];

    protected $casts = ['enabled' => 'boolean', 'settings_enabled' => 'boolean'];
}
