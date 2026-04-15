<?php

use App\Models\Bot;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Bot::query()->chunkById(100, function ($bots) {
            foreach ($bots as $bot) {
                $config = $bot->config ?? [];
                $messages = $config['validation_messages'] ?? null;

                if (! is_array($messages)) {
                    continue;
                }

                $changed = false;
                foreach (['min', 'max', 'minNumeric', 'maxNumeric'] as $key) {
                    if (isset($messages[$key]) && is_string($messages[$key]) && str_contains($messages[$key], '{0}')) {
                        $messages[$key] = str_replace('{0}', '{value}', $messages[$key]);
                        $changed = true;
                    }
                }

                if (isset($messages['between']) && is_string($messages['between'])) {
                    $new = str_replace(['{0}', '{1}'], ['{min}', '{max}'], $messages['between']);
                    if ($new !== $messages['between']) {
                        $messages['between'] = $new;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $config['validation_messages'] = $messages;
                    $bot->config = $config;
                    $bot->save();
                }
            }
        });
    }

    public function down(): void
    {
        Bot::query()->chunkById(100, function ($bots) {
            foreach ($bots as $bot) {
                $config = $bot->config ?? [];
                $messages = $config['validation_messages'] ?? null;

                if (! is_array($messages)) {
                    continue;
                }

                $changed = false;
                foreach (['min', 'max', 'minNumeric', 'maxNumeric'] as $key) {
                    if (isset($messages[$key]) && is_string($messages[$key]) && str_contains($messages[$key], '{value}')) {
                        $messages[$key] = str_replace('{value}', '{0}', $messages[$key]);
                        $changed = true;
                    }
                }

                if (isset($messages['between']) && is_string($messages['between'])) {
                    $new = str_replace(['{min}', '{max}'], ['{0}', '{1}'], $messages['between']);
                    if ($new !== $messages['between']) {
                        $messages['between'] = $new;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $config['validation_messages'] = $messages;
                    $bot->config = $config;
                    $bot->save();
                }
            }
        });
    }
};
