<?php

namespace App\Http\Controllers;

use App\Support\LogReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogDownloadController extends Controller
{
    public function show(Request $request, string $name): StreamedResponse
    {
        // Extra guard (route already protected by auth + permission:logs.download)
        $user = Auth::user();
        if (!$user || !$user->can('logs.download')) {
            abort(403);
        }

        // Find the file by basename from the whitelist of files
        $files = LogReader::listFiles();
        $file = collect($files)->firstWhere('basename', $name);
        if (!$file || !is_file($file['path']) || !is_readable($file['path'])) {
            abort(404);
        }

        $path = $file['path'];
        $downloadName = $file['basename'];

        return response()->streamDownload(function () use ($path) {
            $fh = fopen($path, 'rb');
            if ($fh) {
                while (!feof($fh)) {
                    echo fread($fh, 8192);
                }
                fclose($fh);
            }
        }, $downloadName, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
