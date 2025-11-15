<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo que representa la entidad empresa dentro de MercuryApp.
 *
 * @package App\Models
 *
 * @property-read string $id
 * @property string $name
 * @property string $legal_name
 * @property string $type_tax
 * @property string|null $number_tax
 * @property string|null $address
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone_number
 * @property string $theme_color
 * @property string|null $logo_url
 * @property string|null $digital_url
 * @property string|null $digital_signature
 * @property string $status
 * @property string $status_detail
 */
class Company extends Model
{
    use HasFactory;

    public const TAX_REGIME_TYPES = [
        'Regimen General',
        'Contribuyente Negocio Popular - Régimen Rimpe',
        'Contribuyente Regimen Rimpe',
        'Contribuyente Régimen Microempresas',
    ];

    /**
     * Llave primaria incremental deshabilitada al utilizar UUIDs.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Tipo de llave primaria.
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
        'name',
        'legal_name',
        'type_tax',
        'number_tax',
        'address',
        'website',
        'email',
        'phone_number',
        'theme_color',
        'logo_url',
        'digital_url',
        'digital_signature',
        'digital_signature_password',
        'sri_environment',
        'status',
        'status_detail',
    ];

    /**
     * Relación con usuarios de la empresa.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Sucursales pertenecientes a la empresa.
     *
     * @return HasMany<Branch>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}


