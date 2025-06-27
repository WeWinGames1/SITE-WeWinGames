<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyzeDatabasePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:analyze-performance 
                            {--table= : Analyze specific table}
                            {--check-indexes : Check for missing indexes}
                            {--slow-queries : Show slow query log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database performance and suggest optimizations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Analyzing database performance...');

        if ($this->option('table')) {
            $this->analyzeTable($this->option('table'));
        } else {
            $this->analyzeAllTables();
        }

        if ($this->option('check-indexes')) {
            $this->checkMissingIndexes();
        }

        if ($this->option('slow-queries')) {
            $this->showSlowQueries();
        }

        return Command::SUCCESS;
    }

    /**
     * Analyze all tables
     */
    private function analyzeAllTables(): void
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        $this->info('Database Table Analysis:');
        $this->newLine();

        $tableData = [];
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $stats = $this->getTableStats($tableName);
            
            $tableData[] = [
                $tableName,
                $this->formatBytes($stats['data_length']),
                $this->formatBytes($stats['index_length']),
                number_format($stats['rows']),
                $stats['avg_row_length'] ? $this->formatBytes($stats['avg_row_length']) : 'N/A',
            ];
        }

        $this->table(
            ['Table', 'Data Size', 'Index Size', 'Rows', 'Avg Row Size'],
            $tableData
        );
    }

    /**
     * Analyze specific table
     */
    private function analyzeTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return;
        }

        $this->info("Analyzing table: {$tableName}");
        $this->newLine();

        // Get table stats
        $stats = $this->getTableStats($tableName);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Rows', number_format($stats['rows'])],
                ['Data Size', $this->formatBytes($stats['data_length'])],
                ['Index Size', $this->formatBytes($stats['index_length'])],
                ['Total Size', $this->formatBytes($stats['data_length'] + $stats['index_length'])],
                ['Average Row Size', $stats['avg_row_length'] ? $this->formatBytes($stats['avg_row_length']) : 'N/A'],
                ['Auto Increment', $stats['auto_increment'] ?? 'N/A'],
            ]
        );

        // Show indexes
        $this->newLine();
        $this->info('Indexes:');
        $indexes = DB::select("SHOW INDEX FROM {$tableName}");
        
        $indexData = [];
        foreach ($indexes as $index) {
            $indexData[] = [
                $index->Key_name,
                $index->Column_name,
                $index->Non_unique ? 'No' : 'Yes',
                $index->Index_type,
                $index->Cardinality ?? 'N/A',
            ];
        }

        $this->table(
            ['Index Name', 'Column', 'Unique', 'Type', 'Cardinality'],
            $indexData
        );
    }

    /**
     * Check for missing indexes
     */
    private function checkMissingIndexes(): void
    {
        $this->newLine();
        $this->info('Checking for potentially missing indexes...');
        $this->newLine();

        $suggestions = [];

        // Check foreign keys without indexes
        $foreignKeys = $this->getForeignKeysWithoutIndexes();
        foreach ($foreignKeys as $fk) {
            $suggestions[] = [
                $fk->table_name,
                $fk->column_name,
                'Foreign Key',
                "CREATE INDEX idx_{$fk->table_name}_{$fk->column_name} ON {$fk->table_name}({$fk->column_name});",
            ];
        }

        // Check commonly filtered columns without indexes
        $commonColumns = ['status', 'created_at', 'updated_at', 'deleted_at', 'user_id', 'slug'];
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $columns = Schema::getColumnListing($tableName);
            $indexes = $this->getTableIndexes($tableName);

            foreach ($commonColumns as $column) {
                if (in_array($column, $columns) && !$this->hasIndexOn($indexes, $column)) {
                    $suggestions[] = [
                        $tableName,
                        $column,
                        'Common Filter',
                        "CREATE INDEX idx_{$tableName}_{$column} ON {$tableName}({$column});",
                    ];
                }
            }
        }

        if (empty($suggestions)) {
            $this->info('No obvious missing indexes detected.');
        } else {
            $this->warn('Potentially missing indexes found:');
            $this->table(
                ['Table', 'Column', 'Reason', 'Suggested SQL'],
                $suggestions
            );
        }
    }

    /**
     * Show slow queries
     */
    private function showSlowQueries(): void
    {
        $this->newLine();
        $this->info('Recent slow queries:');
        $this->newLine();

        // This would normally query the slow query log
        // For now, we'll show a placeholder
        $this->warn('Slow query logging must be enabled in MySQL configuration.');
        $this->info('To enable: SET GLOBAL slow_query_log = ON;');
        $this->info('To set threshold: SET GLOBAL long_query_time = 1;');
    }

    /**
     * Get table statistics
     */
    private function getTableStats(string $tableName): array
    {
        $result = DB::select("
            SELECT 
                table_rows as rows,
                data_length,
                index_length,
                avg_row_length,
                auto_increment
            FROM information_schema.tables 
            WHERE table_schema = ? AND table_name = ?
        ", [DB::getDatabaseName(), $tableName]);

        return $result[0] ? (array) $result[0] : [];
    }

    /**
     * Get foreign keys without indexes
     */
    private function getForeignKeysWithoutIndexes(): array
    {
        return DB::select("
            SELECT 
                kcu.table_name,
                kcu.column_name,
                kcu.referenced_table_name,
                kcu.referenced_column_name
            FROM information_schema.key_column_usage kcu
            WHERE kcu.referenced_table_name IS NOT NULL
                AND kcu.table_schema = ?
                AND NOT EXISTS (
                    SELECT 1 
                    FROM information_schema.statistics s
                    WHERE s.table_schema = kcu.table_schema
                        AND s.table_name = kcu.table_name
                        AND s.column_name = kcu.column_name
                )
        ", [DB::getDatabaseName()]);
    }

    /**
     * Get table indexes
     */
    private function getTableIndexes(string $tableName): array
    {
        return DB::select("SHOW INDEX FROM {$tableName}");
    }

    /**
     * Check if column has index
     */
    private function hasIndexOn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if ($index->Column_name === $column) {
                return true;
            }
        }
        return false;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
