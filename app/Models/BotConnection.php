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

    /** Сериализация для фронта: секреты замаскированы.
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $auth = $this->auth_config ?? [];

        foreach (['token', 'password', 'value'] as $key) {
            if (! empty($auth[$key])) {
                $auth[$key] = '••••'.mb_substr($auth[$key], -4);
            }
        }

        $secret = ($this->auth_config ?? [])['token']
            ?? ($this->auth_config ?? [])['value']
            ?? ($this->auth_config ?? [])['password']
            ?? null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'base_url' => $this->base_url,
            'auth_type' => $this->auth_type->value,
            'auth_config' => $auth,
            'auth_config_preview' => $secret ? '••••'.mb_substr($secret, -4) : null,
            'default_headers' => $this->default_headers ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
