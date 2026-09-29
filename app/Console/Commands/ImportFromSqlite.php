<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Copies a locally built SQLite database into the current connection.
 *
 * This exists because PortfolioSeeder cannot be used to populate a real
 * deployment. The seeder deletes storage/app/public/portfolio before it runs and
 * never creates ProjectImage or LatestWorkItem image rows, so seeding a live
 * site both destroys the committed media and leaves 15 of the 22 linked images
 * missing. Copying the rows preserves every one of them.
 *
 * Primary keys are carried across verbatim. The tables are full of integer
 * foreign keys (project_images.project_id, social_links.sort_order and so on),
 * and re-issuing new ids would silently break every relationship on the site.
 * The Postgres sequences are moved to match afterwards, so the next insert does
 * not collide with an id that was written by hand.
 */
class ImportFromSqlite extends Command
{
    /**
     * @var string
     */
    protected $signature = 'db:import-sqlite
        {--source=database/database.sqlite : Path to the SQLite file to read from}
        {--force : Skip the confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Copy rows from a local SQLite database into the current connection, preserving ids';

    /**
     * Tables that are not content.
     *
     * `migrations` is excluded because Neon has already run them, and the
     * framework tables below hold nothing worth carrying to production: local
     * dev sessions would let a stale cookie authenticate against the live site,
     * and cached or queued rows describe the machine they were made on.
     *
     * @var list<string>
     */
    private const SKIPPED = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function handle(): int
    {
        $source = base_path($this->option('source'));

        if (! is_file($source)) {
            $this->components->error("No SQLite file at {$source}.");

            return self::FAILURE;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->components->error('The default connection is SQLite, so there would be nothing to import into.');

            return self::FAILURE;
        }

        try {
            $origin = $this->openSource($source);
            $tables = $this->importableTables($origin);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($tables === []) {
            $this->components->warn('The source has no tables to copy.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Copying %d table(s) from %s', count($tables), $source));
        $this->components->twoColumnDetail('Into', DB::connection()->getDatabaseName().' ('.DB::connection()->getDriverName().')');
        $this->newLine();

        $total = 0;

        foreach ($tables as $table) {
            $rows = $origin->query('SELECT * FROM "'.$table.'"')->fetchAll(PDO::FETCH_ASSOC);
            $copied = $this->copyRows($table, $rows);
            $total += $copied;

            $this->components->twoColumnDetail($table, $copied === 0 && $rows !== []
                ? 'skipped (target not empty)'
                : "{$copied} row(s)");
        }

        $this->newLine();
        $this->syncSequences();
        $this->components->info("Copied {$total} row(s) in total.");
        $this->newLine();
        $this->components->twoColumnDetail('Next', 'Run the site and check /work, /latest-work and the CV download.');

        return self::SUCCESS;
    }

    /**
     * Open the SQLite file on its own connection, so nothing here can be
     * confused with the connection being written to.
     */
    private function openSource(string $path): PDO
    {
        return new PDO('sqlite:'.$path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * @return list<string>
     */
    private function importableTables(PDO $origin): array
    {
        $found = $origin
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll(PDO::FETCH_COLUMN);

        /*
         * getTableListing() returns schema-qualified names ("public.projects"),
         * while SQLite reports them bare. The two only meet after the prefix is
         * dropped, and only tables present on both sides are worth copying.
         */
        $existing = array_map($this->stripSchema(...), DB::getSchemaBuilder()->getTableListing());

        return $this->parentsFirst(array_values(array_filter(
            $found,
            fn (string $name): bool => ! in_array($name, self::SKIPPED, true)
                && in_array($name, $existing, true),
        )));
    }

    /**
     * "public.projects" and "projects" are the same table; SQLite only ever
     * calls it the second one.
     */
    private function stripSchema(string $name): string
    {
        return str_contains($name, '.') ? substr($name, strrpos($name, '.') + 1) : $name;
    }

    /**
     * Reorder tables so a row never lands before the row it points at.
     *
     * This schema is full of single-table foreign keys, and Postgres enforces
     * them immediately. Copying alphabetically inserts profile_highlights
     * before profiles and the whole import dies on the first constraint.
     *
     * @param  list<string>  $tables
     * @return list<string>
     */
    private function parentsFirst(array $tables): array
    {
        $references = [];

        /*
         * Read from pg_constraint rather than information_schema: on this
         * server constraint_column_usage reports no rows for foreign keys at
         * all, which silently produced an alphabetical order and a mid-import
         * constraint failure.
         */
        foreach (DB::select(
            'SELECT conrelid::regclass::text AS child, confrelid::regclass::text AS parent
             FROM pg_constraint
             WHERE contype = ?
               AND connamespace = current_schema()::regnamespace',
            ['f'],
        ) as $row) {
            $child = $this->stripSchema((string) $row->child);
            $parent = $this->stripSchema((string) $row->parent);

            $references[$child][] = $parent;
        }

        $remaining = $tables;
        $ordered = [];

        while ($remaining !== []) {
            /*
             * Ready means every parent has already been written, so the parents
             * left to do must be empty. array_intersect, not array_diff: the
             * diff is the set of parents still outstanding, which is the
             * opposite of the test.
             */
            $ready = array_values(array_filter(
                $remaining,
                fn (string $table): bool => array_intersect(
                    $references[$table] ?? [],
                    $remaining,
                ) === [],
            ));

            if ($ready === []) {
                $this->components->warn('Circular foreign keys detected; copying the remaining tables as listed.');

                return [...$ordered, ...$remaining];
            }

            foreach ($ready as $table) {
                $ordered[] = $table;
            }

            $remaining = array_values(array_diff($remaining, $ready));
        }

        return $ordered;
    }

    /**
     * Insert rows verbatim, coercing values to whatever the target column is
     * actually typed as.
     *
     * SQLite has no boolean type, so booleans arrive as 0/1. Postgres refuses
     * an integer where a boolean belongs, which is why the column types are
     * read from the target rather than assumed.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function copyRows(string $table, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $pdo = DB::connection()->getPdo();

        /*
         * Refuse to append. The ids in the source are meaningful foreign keys
         * elsewhere, so writing rows 1-5 over rows 6-10 would corrupt every
         * relationship pointing at them. Bail out and let a human decide.
         */
        if (DB::table($table)->exists()) {
            $this->components->warn("Table {$table} already has rows; skipped to avoid clashing ids.");

            return 0;
        }

        $columns = array_keys($rows[0]);
        $types = $this->columnTypes($table);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quote($table),
            implode(', ', array_map(fn (string $c): string => $this->quote($c), $columns)),
            implode(', ', array_fill(0, count($columns), '?')),
        );

        $statement = $pdo->prepare($sql);
        $copied = 0;

        foreach ($rows as $row) {
            $values = [];

            foreach ($columns as $column) {
                $value = $row[$column] ?? null;

                if ($value !== null && ($types[$column] ?? null) === 'bool') {
                    $value = (bool) $value ? 'true' : 'false';
                }

                $values[] = $value;
            }

            $statement->execute($values);
            $copied++;
        }

        return $copied;
    }

    /**
     * Postgres sequences do not move when rows are inserted with explicit ids,
     * so the next insert from the app would pick id 1 again and hit a duplicate
     * key. Each sequence is pushed to the current maximum.
     */
    private function syncSequences(): void
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $sequences = $pdo
            ->query("SELECT sequencename FROM pg_sequences WHERE schemaname = 'public'")
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($sequences as $sequence) {
            $table = $this->tableForSequence((string) $sequence);

            if ($table === null) {
                continue;
            }

            $key = $this->primaryKeyOf($table);

            if ($key === null || ! str_contains($key['type'], 'int')) {
                continue;
            }

            $max = (int) DB::table($table)->max($key['column']);
            $quoted = $connection->getQueryGrammar()->wrapTable($sequence);

            $pdo->exec("SELECT setval('{$quoted}', GREATEST({$max}, 1), ".($max > 0 ? 'true' : 'false').')');
        }
    }

    private function tableForSequence(string $sequence): ?string
    {
        if (! str_ends_with($sequence, '_id_seq')) {
            return null;
        }

        $table = substr($sequence, 0, -strlen('_id_seq'));

        return DB::getSchemaBuilder()->hasTable($table) ? $table : null;
    }

    /**
     * The single-column primary key for a table, if it has one.
     *
     * Only single-column keys are handled, which is all this schema uses.
     *
     * @return array{column: string, type: string}|null
     */
    private function primaryKeyOf(string $table): ?array
    {
        if (isset($this->primaryKeys[$table])) {
            $column = $this->primaryKeys[$table];

            return $column === null
                ? null
                : ['column' => $column, 'type' => $this->columnTypes($table)[$column] ?? ''];
        }

        $column = DB::selectOne(
            'SELECT kcu.column_name
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON kcu.constraint_name = tc.constraint_name
              AND kcu.table_schema = tc.table_schema
             WHERE tc.table_schema = current_schema()
               AND tc.table_name = ?
               AND tc.constraint_type = ?
               AND kcu.ordinal_position = 1',
            [$table, 'PRIMARY KEY'],
        );

        $name = $column?->column_name;

        return $this->primaryKeys[$table] = is_string($name)
            ? ['column' => $name, 'type' => $this->columnTypes($table)[$name] ?? '']
            : null;
    }

    /**
     * Target column types, keyed by column name.
     *
     * @return array<string, string>
     */
    private array $columnTypes = [];

    /**
     * @var array<string, array{column: string, type: string}|null>
     */
    private array $primaryKeys = [];

    /**
     * @return array<string, string>
     */
    private function columnTypes(string $table): array
    {
        if (isset($this->columnTypes[$table])) {
            return $this->columnTypes[$table];
        }

        $rows = DB::select(
            'SELECT column_name, data_type
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = ?',
            [$table],
        );

        $types = [];

        foreach ($rows as $row) {
            $types[(string) $row->column_name] = match ((string) $row->data_type) {
                'boolean' => 'bool',
                'character varying', 'text', 'character' => 'string',
                default => (string) $row->data_type,
            };
        }

        return $this->columnTypes[$table] = $types;
    }

    private function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
