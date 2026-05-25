<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Преобразовать messenger_config из плоского массива ['telegram','vk'] в объектный {telegram: {enabled: true}, vk: {enabled: true}}.
     */
    public function up(): void
    {
        DB::table('bots')->orderBy('id')->each(function ($row) {
            $config = json_decode($row->messenger_config ?? 'null', true);

            if (! is_array($config) || ! array_is_list($config)) {
                return;
            }

            $newConfig = [];
            foreach ($config as $driverName) {
                if (is_string($driverName)) {
                    $newConfig[$driverName] = ['enabled' => true];
                }
            }

            DB::table('bots')->where('id', $row->id)->update([
                'messenger_config' => json_encode($newConfig),
            ]);
        });
    }

    /** Откатить миграцию: свернуть {telegram: {enabled: true}, ...} в ['telegram', ...]. Поле profile теряется.
     */
    public function down(): void
    {
        DB::table('bots')->orderBy('id')->each(function ($row) {
            $config = json_decode($row->messenger_config ?? 'null', true);

            if (! is_array($config) || array_is_list($config)) {
                return;
            }

            $flat = [];
            foreach ($config as $driverName => $settings) {
                if (is_array($settings) && ($settings['enabled'] ?? false) === true) {
                    $flat[] = $driverName;
                }
            }

            DB::table('bots')->where('id', $row->id)->update([
                'messenger_config' => json_encode($flat),
            ]);
        });
    }
};
