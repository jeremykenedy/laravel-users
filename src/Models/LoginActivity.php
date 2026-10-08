<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    protected $table = 'laravelusers_login_activity';

    protected $primaryKey = 'user_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_key', 'last_login_at', 'ip_address', 'device', 'os', 'browser'];

    protected $casts = ['last_login_at' => 'datetime', 'ip_address' => 'encrypted'];
}
