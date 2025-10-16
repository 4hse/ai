<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorizedUser extends Model
{
    protected $table = 'authorized_users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
    ];
}
