<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatUpload extends Model
{
    protected $table = 'chat_uploads';

    protected $fillable = [
        'user_id',
        'file_key',
        'original_name',
        'file_size',
        'mime_type',
        'type',
    ];
}
