<?php

namespace App\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

trait HandlesExports
{
    /**
     * Stream a PDF response using DomPDF.
     * Named differently from Livewire public methods to avoid conflicts.
     */
    protected function generatePdfReport(string $view, array $data, string $filename = 'report.pdf')
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultPaperSize'     => 'a4',
                'chroot'               => public_path(),
            ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Stream a CSV response.
     * Named differently from Livewire public methods to avoid conflicts.
     */
    protected function generateCsvReport(string $filename, array $data)
    {
        if (empty($data)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No data available for export.']);
            return;
        }

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($file, "\xEF\xBB\xBF");
            // Headers row
            if (isset($data[0])) {
                fputcsv($file, array_keys((array) $data[0]));
            }
            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Stream an Excel-compatible HTML table response.
     * Named differently from Livewire public methods to avoid conflicts.
     */
    protected function generateExcelReport(string $filename, array $data)
    {
        // Reuse CSV — Excel opens CSV fine. Use .xls extension for direct open.
        return $this->generateCsvReport($filename, $data);
    }
}

