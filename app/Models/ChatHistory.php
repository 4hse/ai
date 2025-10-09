<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatHistory extends Model
{
    protected $table = 'chat_history';

    protected $primaryKey = 'thread_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'thread_id',
        'user_id',
        'messages',
    ];

    protected $casts = [
        'messages' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
