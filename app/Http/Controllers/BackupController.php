<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Native\Mobile\Share;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
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

    private function databasePath(): ?string
    {
        $path = config('database.connections.sqlite.database');

        return is_string($path) && file_exists($path) ? $path : null;
    }

    private function backupFilename(): string
    {
        return 'mi-economia-'.now()->format('Y-m-d').'.sqlite';
    }
}
