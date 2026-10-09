<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLink extends Model
{
    protected $table = 'laravelusers_account_links';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'user_type', 'user_id', 'action', 'token_hash', 'deleted_fingerprint', 'expires_at', 'consumed_at'];

    protected $casts = ['expires_at' => 'integer', 'consumed_at' => 'integer'];
}
