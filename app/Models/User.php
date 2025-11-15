<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\UserToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Modelo Eloquent que representa a los usuarios internos de MercuryApp.
 *
 * @package App\Models
 *
 * @property-read string $id
 * @property string $company_id
 * @property string $role_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password_hash
 * @property string|null $document_number
 * @property string|null $phone_number
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $avatar_url
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $status
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use BelongsToCompany;

    /**
     * Indica que la llave primaria utiliza UUIDs.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Define el tipo de dato de la llave primaria.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'role_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'remember_token',
        'document_number',
        'phone_number',
        'date_of_birth',
        'gender',
        'avatar_url',
        'status',
    ];

    protected $appends = [
        'status_label',
        'display_name',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (User $user): void {
            if (! $user->getAttribute('id')) {
                $user->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    /**
     * Atributos ocultos para serialización.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Atributos casteados a tipos nativos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Indica el nombre de la columna que almacena la contraseña cifrada.
     *
     * @return string
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * Obtiene la compañía a la que pertenece el usuario.
     *
     * @return BelongsTo<Company, User>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Obtiene el rol asignado al usuario.
     *
     * @return BelongsTo<Role, User>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Tokens efímeros asociados al usuario.
     *
     * @return HasMany<UserToken>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
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

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->display_name;
    }
}
