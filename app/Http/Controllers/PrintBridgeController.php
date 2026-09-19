<?php

namespace App\Http\Controllers;

class PrintBridgeController extends Controller
{
    public function download(\Illuminate\Http\Request $request)
    {
        $format = strtolower($request->query('format', 'exe'));

        if ($format === 'zip') {
            $path = public_path('downloads/mtc-print-bridge.zip');
            $filename = 'mtc-print-bridge.zip';
        } else {
            $path = public_path('downloads/MTC-PrintBridge-Setup.exe');
            $filename = 'MTC-PrintBridge-Setup.exe';
        }

        if (!file_exists($path)) {
            abort(404, 'Print bridge installer not found.');
        }

        return response()->download($path, $filename);
    }
}