<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo responsable de administrar los tokens efímeros asociados a usuarios.
 */
class UserToken extends Model
{
    use HasFactory;

    protected $table = 'user_tokens';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'token',
        'expires_at',
        'created_at',
    ];
}


