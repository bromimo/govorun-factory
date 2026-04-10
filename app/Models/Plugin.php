<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Модель плагина с пользовательскими блоками. */
class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'block_schema',
        'vue_component', 'php_stub', 'active',
    ];

    /** Приведение атрибутов модели.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'block_schema' => 'array',
            'active' => 'boolean',
        ];
    }
}
