<?php

namespace App\Models;

use App\Enums\ConnectionAuthType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Переиспользуемое подключение бота к внешнему API.
 *
 * @property int $id
 * @property int $bot_id
 * @property string $name
 * @property string $slug
 * @property string $base_url
 * @property ConnectionAuthType $auth_type
 * @property array<string, mixed>|null $auth_config
 * @property array<int, array<string, string>>|null $default_headers
 */
class BotConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'name',
        'slug',
        'base_url',
        'auth_type',
        'auth_config',
        'default_headers',
    ];

    protected $casts = [
        'auth_type' => ConnectionAuthType::class,
        'auth_config' => 'encrypted:array',
        'default_headers' => 'array',
    ];

    /** Бот-владелец подключения. */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
