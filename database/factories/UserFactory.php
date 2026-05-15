<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    /** Текущий пароль, используемый фабрикой. */
    protected static ?string $password;

    /** Определение состояния модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Viewer->value,
        ];
    }

    /** Указать, что email пользователя не подтверждён.
     *
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Создать пользователя с ролью администратора.
     *
     */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin->value]);
    }

    /** Создать пользователя с ролью редактора.
     *
     */
    public function editor(): static
    {
        return $this->state(fn () => ['role' => UserRole::Editor->value]);
    }

    /** Создать пользователя с ролью наблюдателя.
     *
     */
    public function viewer(): static
    {
        return $this->state(fn () => ['role' => UserRole::Viewer->value]);
    }
}
