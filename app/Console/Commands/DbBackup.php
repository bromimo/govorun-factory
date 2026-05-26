<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/** Создаёт SQL-дамп текущей БД в папку .backups/, удаляя предыдущий бекап. */
class DbBackup extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Создать SQL-дамп БД в .backups/ (хранится один актуальный файл)';

    public function handle(): int
    {
        $dir = base_path('.backups');
        File::ensureDirectoryExists($dir);

        foreach (File::glob("{$dir}/*.sql") as $old) {
            File::delete($old);
        }

        $filename = "{$dir}/".now()->format('Y_m_d_His').'_govorun.sql';
        $pdo = DB::getPdo();
        $dbName = DB::getDatabaseName();

        $sql = "-- Backup: {$dbName} ".now()->toDateTimeString()."\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= array_values($create)[1].";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            $cols = '`'.implode('`, `', array_keys($rows[0])).'`';
            $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
            $chunks = array_chunk($rows, 500);

            foreach ($chunks as $ci => $chunk) {
                $values = [];
                foreach ($chunk as $row) {
                    $escaped = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
                    $values[] = '('.implode(', ', $escaped).')';
                }
                $sql .= implode(",\n", $values);
                $sql .= $ci < count($chunks) - 1 ? ",\n" : ";\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($filename, $sql);

        $size = round(filesize($filename) / 1024, 1);
        $this->info('Backup saved: '.basename($filename)." ({$size} KB)");

        return self::SUCCESS;
    }
}
