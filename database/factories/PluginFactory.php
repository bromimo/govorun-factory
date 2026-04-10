<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Фабрика для модели Plugin. */
class PluginFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->word().'_action';

        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'block_schema' => ['param1' => 'string'],
            'vue_component' => 'Custom'.ucfirst($name).'Form',
            'php_stub' => '        // '.$name.' stub',
            'active' => true,
        ];
    }
}
