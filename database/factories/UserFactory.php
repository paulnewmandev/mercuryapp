<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'id' => (string) Str::uuid(),
            'company_id' => Company::factory(),
            'role_id' => Role::factory(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make(Str::password(12, true, true, true)),
            'document_number' => fake()->optional()->numerify('############'),
            'phone_number' => fake()->optional()->phoneNumber(),
            'email_verified_at' => now(),
            'status' => 'A',
        ];
    }

    /**
     * Indica que la dirección de correo debe quedar sin verificar.
     *
     * @return static
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
