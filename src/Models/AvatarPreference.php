<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Models;

use Illuminate\Database\Eloquent\Model;

class AvatarPreference extends Model
{
    protected $table = 'laravelusers_avatar_preferences';

    protected $primaryKey = 'user_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_key', 'source'];
}
