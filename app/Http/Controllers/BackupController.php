<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /**
     * Stream the on-device SQLite database as a downloadable backup. This is the
     * web/webview-safe foundation for the native File/Share plugins used to save
     * or send the backup once running inside the Android app.
     */
    public function __invoke(): BinaryFileResponse|Response
    {
        $path = config('database.connections.sqlite.database');

        if (! is_string($path) || ! file_exists($path)) {
            return response('No hay base de datos para respaldar.', 404);
        }

        $filename = 'mi-economia-'.now()->format('Y-m-d').'.sqlite';

        return response()->download($path, $filename);
    }
}
