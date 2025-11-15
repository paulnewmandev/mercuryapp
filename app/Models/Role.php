<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo que describe los roles del sistema y sus permisos asignados.
 *
 * @package App\Models
 *
 * @property-read string $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string $status
 */
class Role extends Model
{
    use HasFactory;
    use BelongsToCompany;

    /**
     * Indica que la llave primaria es un UUID.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Define el tipo de llave primaria.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'description',
        'status',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Role $role): void {
            if (! $role->getAttribute('id')) {
                $role->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    /**
     * Relación con los usuarios que poseen este rol.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relación muchos a muchos con permisos.
     *
     * @return BelongsToMany<Permission>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'A' => gettext('Activo'),
            'I' => gettext('Inactivo'),
            'T' => gettext('En papelera'),
            default => $this->status,
        };
    }
}


