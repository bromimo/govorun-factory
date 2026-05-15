<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Медиафайл из библиотеки бота.
 *
 * @property int $id
 * @property int $bot_id
 * @property string $type
 * @property string $original_name
 * @property string $filename
 * @property string $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 */
class BotMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'type',
        'original_name',
        'filename',
        'mime_type',
        'size',
        'width',
        'height',
    ];

    /** Бот, которому принадлежит медиафайл. */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

}