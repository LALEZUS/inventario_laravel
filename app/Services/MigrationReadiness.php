<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;

class MigrationReadiness
{
    private const TRANSFORMED_COLUMNS = [
        'backup_runs' => ['filename'],
        'gallery' => ['filename'],
        'tutorials' => ['content_url'],
    ];

    public const TABLES = [
        'account_management', 'asset_files', 'assignments', 'backup_runs',
        'calendar_reminders', 'cellphones', 'correos_outlook', 'email_backups',
        'employees', 'enterprise_networks', 'ftp_catalog', 'gallery',
        'hardware_assets', 'inks', 'licenses', 'maintenance_logs',
        'microsoft_emails', 'network_devices', 'notes', 'office_emails',
        'peripherals', 'printer_supplies', 'printers', 'toner', 'tutorials',
        'users', 'watchguard_users',
    ];

    public function audit(
        ConnectionInterface $source,
        ConnectionInterface $target,
        ?array $tables = null,
    ): array {
        $results = [];

        foreach ($tables ?? self::TABLES as $table) {
            $results[] = $this->compareTable($source, $target, $table);
        }

        return [
            'ready' => collect($results)->every(fn (array $result) => $result['ready']),
            'tables' => $results,
        ];
    }

    private function compareTable(
        ConnectionInterface $source,
        ConnectionInterface $target,
        string $table,
    ): array {
        $sourceExists = $source->getSchemaBuilder()->hasTable($table);
        $targetExists = $target->getSchemaBuilder()->hasTable($table);

        if (! $sourceExists || ! $targetExists) {
            return [
                'table' => $table,
                'ready' => false,
                'source_count' => $sourceExists ? $source->table($table)->count() : null,
                'target_count' => $targetExists ? $target->table($table)->count() : null,
                'source_only' => [],
                'target_only' => [],
                'changed' => [],
                'source_only_columns' => [],
                'target_only_columns' => [],
                'missing' => ! $sourceExists ? 'source' : 'target',
            ];
        }

        $sourceColumns = $source->getSchemaBuilder()->getColumnListing($table);
        $targetColumns = $target->getSchemaBuilder()->getColumnListing($table);
        $sourceOnlyColumns = array_values(array_diff($sourceColumns, $targetColumns));
        $targetOnlyColumns = array_values(array_diff($targetColumns, $sourceColumns));
        $commonColumns = array_values(array_diff(
            array_intersect($sourceColumns, $targetColumns),
            ['created_at', 'updated_at'],
            self::TRANSFORMED_COLUMNS[$table] ?? [],
        ));

        if (! in_array('id', $commonColumns, true)) {
            return [
                'table' => $table,
                'ready' => false,
                'source_count' => $source->table($table)->count(),
                'target_count' => $target->table($table)->count(),
                'source_only' => [],
                'target_only' => [],
                'changed' => [],
                'source_only_columns' => $sourceOnlyColumns,
                'target_only_columns' => $targetOnlyColumns,
                'missing' => 'id',
            ];
        }

        $sourceRows = $this->rowsById($source, $table, $commonColumns);
        $targetRows = $this->rowsById($target, $table, $commonColumns);
        $sourceIds = array_keys($sourceRows);
        $targetIds = array_keys($targetRows);
        $sourceOnly = array_values(array_diff($sourceIds, $targetIds));
        $targetOnly = array_values(array_diff($targetIds, $sourceIds));
        $changed = [];

        foreach (array_intersect($sourceIds, $targetIds) as $id) {
            if ($sourceRows[$id] !== $targetRows[$id]) {
                $changed[] = $id;
            }
        }

        return [
            'table' => $table,
            'ready' => $sourceOnly === [] && $changed === [] && $sourceOnlyColumns === [],
            'source_count' => count($sourceRows),
            'target_count' => count($targetRows),
            'source_only' => $sourceOnly,
            'target_only' => $targetOnly,
            'changed' => $changed,
            'source_only_columns' => $sourceOnlyColumns,
            'target_only_columns' => $targetOnlyColumns,
            'missing' => null,
        ];
    }

    private function rowsById(ConnectionInterface $connection, string $table, array $columns): array
    {
        return $connection->table($table)
            ->select($columns)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $values = (array) $row;
                $id = (string) $values['id'];
                ksort($values);

                return [$id => json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
            })
            ->all();
    }
}
