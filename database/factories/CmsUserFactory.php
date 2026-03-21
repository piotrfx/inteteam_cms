<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<CmsUser>
 */
final class CmsUserFactory extends Factory
{
    protected $model = CmsUser::class;

    private static ?string $password = null;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => self::$password ??= Hash::make('password'),
            'role' => 'editor',
            'email_verified_at' => now(),
            'remember_token' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function editor(): static
    {
        return $this->state(['role' => 'editor']);
    }

    public function viewer(): static
    {
        return $this->state(['role' => 'viewer']);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
