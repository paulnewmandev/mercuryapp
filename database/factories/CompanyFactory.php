<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();

        return [
            'id' => (string) Str::uuid(),
            'name' => $companyName,
            'legal_name' => $companyName . ' S.A.',
            'type_tax' => fake()->randomElement(Company::TAX_REGIME_TYPES),
            'number_tax' => fake()->numerify('##########'),
            'address' => fake()->address(),
            'website' => fake()->optional()->url(),
            'email' => fake()->companyEmail(),
            'phone_number' => fake()->phoneNumber(),
            'theme_color' => fake()->hexColor(),
            'logo_url' => '/theme-images/logo/icon-256x256.png',
            'digital_url' => fake()->optional()->url(),
            'digital_signature' => base64_encode(Str::random(40)),
            'status' => 'A',
            'status_detail' => fake()->randomElement(['trial', 'active', 'suspended']),
        ];
    }
}


