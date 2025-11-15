<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo que representa las notificaciones internas registradas en la plataforma.
 */
class Notification extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToCompany;

    protected $table = 'notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'user_id',
        'title',
        'description',
        'category',
        'meta',
        'status',
        'read_at',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Casting de atributos de marca temporal.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Usuario asociado a la notificación.
     *
     * @return BelongsTo<User, Notification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


