<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Estima o volume de dados ocupado pelo sistema (banco + arquivos) pra
 * ajudar a planejar upgrade de hospedagem. Tanto local quanto producao
 * rodam SQLite (nao MySQL) - o tamanho por tabela vem da virtual table
 * nativa `dbstat`; se ela nao estiver disponivel (build do SQLite sem
 * SQLITE_ENABLE_DBSTAT_VTAB) ou o driver for outro, a quebra por tenant
 * retorna null e a tela mostra "-", mas o total do sistema (arquivo do
 * banco inteiro) sempre funciona.
 */
class DataVolumeCalculator
{
    public static function totalDatabaseBytes(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'mysql') {
            $row = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS bytes FROM information_schema.tables WHERE table_schema = DATABASE()'
            );

            return (int) ($row->bytes ?? 0);
        }

        $path = $connection->getDatabaseName();

        return is_string($path) && is_file($path) ? filesize($path) : 0;
    }

    public static function totalStorageBytes(): int
    {
        try {
            return collect(Storage::disk('public')->allFiles())
                ->sum(fn (string $file) => Storage::disk('public')->size($file));
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Fatia proporcional do tamanho fisico da tabela que pertence a esse
     * tenant, baseada na proporcao de linhas sobre o total da tabela.
     */
    public static function estimateTenantTableBytes(string $table, int $tenantRows): ?int
    {
        if ($tenantRows === 0) {
            return 0;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT data_length + index_length AS bytes, table_rows FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            );

            if (!$row || (int) $row->table_rows === 0) {
                return 0;
            }

            return (int) round(((int) $row->bytes) * ($tenantRows / (int) $row->table_rows));
        }

        if ($driver === 'sqlite') {
            try {
                $tableBytes = (int) (DB::selectOne('SELECT SUM(pgsize) AS bytes FROM dbstat WHERE name = ?', [$table])->bytes ?? 0);
            } catch (\Throwable) {
                return null;
            }

            $totalRows = DB::table($table)->count();

            return $totalRows === 0 ? 0 : (int) round($tableBytes * ($tenantRows / $totalRows));
        }

        return null;
    }

    public static function humanize(?int $bytes): string
    {
        if ($bytes === null) {
            return '-';
        }

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 ** 2) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        if ($bytes < 1024 ** 3) {
            return number_format($bytes / (1024 ** 2), 1, ',', '.') . ' MB';
        }

        return number_format($bytes / (1024 ** 3), 2, ',', '.') . ' GB';
    }
}
