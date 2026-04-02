<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'block_schema',
        'vue_component', 'php_stub', 'active',
    ];

    protected function casts(): array
    {
        return [
            'block_schema' => 'array',
            'active' => 'boolean',
        ];
    }
}
