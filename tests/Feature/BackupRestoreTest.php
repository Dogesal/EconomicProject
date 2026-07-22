<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

/**
 * These tests use real SQLite files (not the in-memory test database)
 * because restoring is a file-level operation: the controller swaps the
 * database file on disk and re-runs migrations on it.
 */
class BackupRestoreTest extends TestCase
{
    /** @var string[] */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            File::delete([$file, $file.'-wal', $file.'-shm', $file.'.pre-restore']);
        }

        parent::tearDown();
    }

    public function test_restore_is_rejected_on_in_memory_installations(): void
    {
        // Generar el respaldo primero: purga la conexión y una base :memory:
        // migrada antes de eso se perdería.
        $upload = UploadedFile::fake()->createWithContent('backup.sqlite', $this->validBackupContent());

        Artisan::call('migrate', ['--force' => true]);

        $response = $this->from('/settings')->post(route('settings.backup.restore'), ['backup' => $upload]);

        $response->assertRedirect('/settings');
        $response->assertSessionHas('error', 'No se puede restaurar en esta instalación.');
    }

    public function test_restore_requires_a_file(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        $response = $this->from('/settings')->post(route('settings.backup.restore'));

        $response->assertRedirect('/settings');
        $response->assertSessionHas('error', 'Selecciona un archivo de respaldo.');
    }

    public function test_restore_rejects_a_file_that_is_not_sqlite(): void
    {
        $target = $this->useFileDatabase();
        $before = File::get($target);

        $upload = UploadedFile::fake()->createWithContent('backup.sqlite', 'esto no es una base de datos');

        $response = $this->from('/settings')->post(route('settings.backup.restore'), ['backup' => $upload]);

        $response->assertSessionHas('error', 'El archivo no es un respaldo válido de Mi Economía.');
        $this->assertSame($before, File::get($target));
    }

    public function test_restore_rejects_a_sqlite_file_without_the_app_schema(): void
    {
        $this->useFileDatabase();

        $foreign = $this->newTemporaryPath();
        $pdo = new PDO('sqlite:'.$foreign);
        $pdo->exec('CREATE TABLE otra_cosa (id INTEGER PRIMARY KEY)');
        unset($pdo);

        $upload = new UploadedFile($foreign, 'backup.sqlite', null, null, true);

        $response = $this->from('/settings')->post(route('settings.backup.restore'), ['backup' => $upload]);

        $response->assertSessionHas('error', 'El archivo no es un respaldo válido de Mi Economía.');
    }

    public function test_restore_replaces_the_database_and_keeps_a_safety_copy(): void
    {
        $target = $this->useFileDatabase();

        $backup = $this->newTemporaryPath();
        File::put($backup, $this->validBackupContent(withMarker: true));

        $upload = new UploadedFile($backup, 'backup.sqlite', null, null, true);

        $response = $this->from('/settings')->post(route('settings.backup.restore'), ['backup' => $upload]);

        $response->assertRedirect('/settings');
        $response->assertSessionHas('success', 'Respaldo restaurado: tus datos fueron reemplazados.');

        $restored = new PDO('sqlite:'.$target);
        $marker = $restored->query("SELECT value FROM settings WHERE key = 'restore_marker'")->fetchColumn();
        $this->assertSame('si', $marker);

        $this->assertFileExists($target.'.pre-restore');
    }

    public function test_restore_accepts_a_base64_payload_from_the_native_bridge(): void
    {
        $target = $this->useFileDatabase();

        $response = $this->from('/settings')->post(route('settings.backup.restore'), [
            'backup_base64' => base64_encode($this->validBackupContent(withMarker: true)),
            'backup_name' => 'backup.sqlite',
        ]);

        $response->assertSessionHas('success', 'Respaldo restaurado: tus datos fueron reemplazados.');

        $restored = new PDO('sqlite:'.$target);
        $this->assertSame('si', $restored->query("SELECT value FROM settings WHERE key = 'restore_marker'")->fetchColumn());
    }

    public function test_restore_rejects_a_base64_payload_that_is_not_sqlite(): void
    {
        $target = $this->useFileDatabase();
        $before = File::get($target);

        $response = $this->from('/settings')->post(route('settings.backup.restore'), [
            'backup_base64' => base64_encode('esto no es una base de datos'),
        ]);

        $response->assertSessionHas('error', 'El archivo no es un respaldo válido de Mi Economía.');
        $this->assertSame($before, File::get($target));
    }

    /**
     * Points the app at a freshly migrated on-disk SQLite database and
     * returns its path (the restore target).
     */
    private function useFileDatabase(): string
    {
        $target = $this->newTemporaryPath();
        File::put($target, '');

        config(['database.connections.sqlite.database' => $target]);
        DB::purge();

        Artisan::call('migrate', ['--force' => true]);

        return $target;
    }

    /**
     * Builds a real backup file (migrated schema, optionally with a marker
     * setting) and returns its raw bytes.
     */
    private function validBackupContent(bool $withMarker = false): string
    {
        $path = $this->newTemporaryPath();
        File::put($path, '');

        $original = config('database.connections.sqlite.database');

        config(['database.connections.sqlite.database' => $path]);
        DB::purge();
        Artisan::call('migrate', ['--force' => true]);

        if ($withMarker) {
            DB::table('settings')->insert([
                'key' => 'restore_marker',
                'value' => 'si',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::purge();
        config(['database.connections.sqlite.database' => $original]);

        return File::get($path);
    }

    private function newTemporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'backup-test-').'.sqlite';
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
