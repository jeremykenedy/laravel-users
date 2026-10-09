<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Models;

use Illuminate\Database\Eloquent\Model;

class EmailChange extends Model
{
    protected $table = 'laravelusers_email_changes';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id', 'user_key', 'old_email', 'new_email', 'old_token_hash', 'new_token_hash', 'fingerprint', 'old_confirmed_at', 'new_confirmed_at', 'expires_at'];

    protected $casts = ['old_email' => 'encrypted', 'new_email' => 'encrypted', 'old_confirmed_at' => 'integer', 'new_confirmed_at' => 'integer', 'expires_at' => 'integer'];
}
