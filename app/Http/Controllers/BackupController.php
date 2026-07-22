<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Native\Mobile\Share;
use PDO;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    private const MAX_BACKUP_BYTES = 100 * 1024 * 1024;

    /**
     * Stream the on-device SQLite database as a downloadable backup — the
     * web/webview-safe path.
     */
    public function download(): BinaryFileResponse|Response
    {
        $path = $this->databasePath();

        if ($path === null) {
            return response('No hay base de datos para respaldar.', 404);
        }

        return response()->download($path, $this->backupFilename());
    }

    /**
     * Open the native share sheet with a copy of the backup (device only;
     * Share::file no-ops outside the native runtime).
     */
    public function share(Share $share): RedirectResponse
    {
        $path = $this->databasePath();

        if ($path === null) {
            return back()->with('error', 'No hay base de datos para respaldar.');
        }

        $copy = storage_path('app/'.$this->backupFilename());
        File::copy($path, $copy);

        $share->file('Respaldo Mi Economía', 'Copia de seguridad de tus datos.', $copy);

        return back()->with('success', 'Elegí dónde guardar o enviar el respaldo.');
    }

    /**
     * Replace the current database with an uploaded backup. The previous
     * database is kept next to the live one as a safety copy, and pending
     * migrations run afterwards in case the backup predates the app version.
     */
    public function restore(Request $request): RedirectResponse
    {
        $upload = $request->file('backup');
        $temporary = null;

        if ($upload instanceof UploadedFile && $upload->isValid()) {
            if ($upload->getSize() > self::MAX_BACKUP_BYTES) {
                return back()->with('error', 'El archivo es demasiado grande para ser un respaldo.');
            }

            $source = $upload->getRealPath();
        } else {
            // The Android webview bridge flattens multipart bodies to plain
            // strings, so on device the app sends the bytes as base64 instead.
            $payload = (string) $request->input('backup_base64');

            if ($payload === '') {
                return back()->with('error', 'Selecciona un archivo de respaldo.');
            }

            if (strlen($payload) > intdiv(self::MAX_BACKUP_BYTES, 3) * 4 + 4) {
                return back()->with('error', 'El archivo es demasiado grande para ser un respaldo.');
            }

            $temporary = $this->decodeBase64Backup($payload);

            if ($temporary === null) {
                return back()->with('error', 'El archivo no es un respaldo válido de Mi Economía.');
            }

            $source = $temporary;
        }

        try {
            if (! $this->isValidBackup($source)) {
                return back()->with('error', 'El archivo no es un respaldo válido de Mi Economía.');
            }

            $target = config('database.connections.sqlite.database');

            if (! is_string($target) || $target === ':memory:') {
                return back()->with('error', 'No se puede restaurar en esta instalación.');
            }

            if (file_exists($target)) {
                File::copy($target, $target.'.pre-restore');
            }

            // Close the live connection and drop its WAL leftovers so the copy
            // below can't be mixed with journal pages of the old database.
            DB::purge();
            File::delete([$target.'-wal', $target.'-shm']);

            File::copy($source, $target);

            Artisan::call('migrate', ['--force' => true]);

            return back()->with('success', 'Respaldo restaurado: tus datos fueron reemplazados.');
        } finally {
            if ($temporary !== null) {
                File::delete($temporary);
            }
        }
    }

    /**
     * Writes a base64 (or data URL) payload to a temporary file and returns
     * its path, or null when the payload isn't decodable.
     */
    private function decodeBase64Backup(string $payload): ?string
    {
        if (str_starts_with($payload, 'data:') && str_contains($payload, ',')) {
            $payload = substr($payload, (int) strpos($payload, ',') + 1);
        }

        $bytes = base64_decode(trim($payload), true);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'restore-');

        if ($path === false) {
            return null;
        }

        File::put($path, $bytes);

        return $path;
    }

    /**
     * A restorable backup is a healthy SQLite file that contains the core
     * tables of this app (any backup produced by the download button does).
     */
    private function isValidBackup(string $path): bool
    {
        $header = (string) file_get_contents($path, false, null, 0, 16);

        if ($header !== "SQLite format 3\0") {
            return false;
        }

        try {
            $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            if ($pdo->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                return false;
            }

            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
                ->fetchAll(PDO::FETCH_COLUMN);

            return array_diff(['accounts', 'categories', 'transactions', 'settings', 'migrations'], $tables) === [];
        } catch (Throwable) {
            return false;
        }
    }

    private function databasePath(): ?string
    {
        $path = config('database.connections.sqlite.database');

        if (! is_string($path) || ! file_exists($path)) {
            return null;
        }

        try {
            // Fold WAL pages into the main file so the backup is complete.
            DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (Throwable) {
            // Without WAL there is nothing to consolidate.
        }

        return $path;
    }

    private function backupFilename(): string
    {
        return 'mi-economia-'.now()->format('Y-m-d').'.sqlite';
    }
}
