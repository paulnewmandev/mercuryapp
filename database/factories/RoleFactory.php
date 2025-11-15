<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleSlug = Str::slug(fake()->jobTitle(), '_');

        return [
            'id' => (string) Str::uuid(),
            'name' => $roleSlug,
            'display_name' => ucwords(str_replace('_', ' ', $roleSlug)),
            'description' => fake()->optional()->sentence(),
            'status' => 'A',
        ];
    }
}


