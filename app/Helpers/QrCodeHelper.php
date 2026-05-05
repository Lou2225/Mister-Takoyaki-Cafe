<?php

namespace App\Helpers;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QrCodeHelper
{
    /**
     * Generate a QR code as a data URI (base64 encoded PNG)
     * 
     * @param string $data The data to encode in the QR code
     * @param int $size The size of the QR code in pixels (default: 200)
     * @return string Data URI for embedding in HTML
     */
    public static function generateDataUri(string $data, int $size = 200): string
    {
        try {
            $qrCode = new QrCode($data);
            $qrCode->setSize($size);
            $qrCode->setMargin(0);
            
            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);
            
            $svgData = $result->getString();
            $base64 = base64_encode($svgData);
            
            return 'data:image/svg+xml;base64,' . $base64;
        } catch (\Exception $e) {
            Log::error('QR Code generation failed: ' . $e->getMessage());
            return ''; // Return empty string on error
        }
    }

    /**
     * Generate a QR code for a customer review URL
     * 
     * @param string $transactionId The transaction/order ID
     * @param string|null $customBaseUrl Override the default base URL
     * @return string Data URI for the QR code
     */
    public static function generateReviewQrCode(string $transactionId, ?string $customBaseUrl = null): string
    {
        $reviewUrl = route('customer.review');
        
        // Ensure that if accessed via localhost locally, the QR code uses the LAN IP so phones can scan it
        if (str_contains($reviewUrl, 'localhost') || str_contains($reviewUrl, '127.0.0.1')) {
            $localIp = gethostbyname(gethostname());
            $reviewUrl = str_replace(['localhost', '127.0.0.1'], $localIp, $reviewUrl);
        }
        
        return self::generateDataUri($reviewUrl);
    }

    /**
     * Generate a QR code image file and return its storage path
     * 
     * @param string $data The data to encode
     * @param string $filename The filename to save as (without extension)
     * @return string The storage path relative to public disk
     */
    public static function generateAndStore(string $data, string $filename): string
    {
        try {
            $qrCode = new QrCode($data);
            $qrCode->setSize(200);
            $qrCode->setMargin(0);
            
            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);
            
            $path = 'qr-codes/' . $filename . '.svg';
            Storage::disk('public')->put($path, $result->getString());
            
            return $path;
        } catch (\Exception $e) {
            Log::error('QR Code file generation failed: ' . $e->getMessage());
            return '';
        }
    }
}
