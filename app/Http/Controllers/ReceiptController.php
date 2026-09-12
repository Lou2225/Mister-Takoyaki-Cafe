<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * Display a printer-friendly thermal receipt view
     */
    public function thermal(Order $order)
    {
        $order->load([
            'items.product.category', 
            'items.options.option', 
            'items.modifiers.modifier', 
            'branch', 
            'user'
        ]);

        $address = SystemSetting::get('business_address', 'Main Branch, Manila');
        if (is_array($address)) {
            $address = !empty($address['formatted']) ? $address['formatted'] : 'Main Branch, Manila';
        }

        $posConfig = \App\Services\ConfigurationService::getPosConfig($order->branch_id);
        $businessConfig = \App\Services\ConfigurationService::getBusinessConfig();
        $financialConfig = \App\Services\ConfigurationService::getFinancialConfig($order->branch_id);

        $settings = [
            'business_name' => $businessConfig['name'] ?? 'Mister Takoyaki Cafe',
            'business_address' => $businessConfig['address'] ?? 'Main Branch, Manila',
            'business_phone' => $businessConfig['phone'] ?? '',
            'business_email' => $businessConfig['email'] ?? '',
            'receipt_footer_message' => $posConfig['footer_message'] ?? 'Thank you for your visit!',
            'receipt_logo_enabled' => $posConfig['logo_enabled'] ?? true,
            'receipt_return_policy' => $posConfig['return_policy'] ?? 'No return, no exchange.',
            'receipt_show_vat' => $posConfig['show_vat'] ?? false,
            'currency_symbol' => $financialConfig['currency_symbol'] ?? '₱',
            'kitchen_slip_title' => $posConfig['kitchen_slip_title'] ?? '🍳 KITCHEN SLIP',
            'kitchen_slip_subtitle' => $posConfig['kitchen_slip_subtitle'] ?? 'Food Preparation Order',
            'barista_slip_title' => $posConfig['barista_slip_title'] ?? '☕ BARISTA SLIP',
            'barista_slip_subtitle' => $posConfig['barista_slip_subtitle'] ?? 'Beverage Preparation Order',
            'customer_receipt_title' => $posConfig['customer_receipt_title'] ?? 'Customer Receipt & Invoice',
            'show_receipt_qr_code' => $posConfig['show_receipt_qr_code'] ?? true,
            'show_receipt_footer' => $posConfig['show_receipt_footer'] ?? true,
            'receipt_copies' => (int) ($posConfig['copies'] ?? 1),
            'qr_code' => null
        ];

        $businessLogo = $businessConfig['logo'] ?? null;
        $logoDataUri = null;

        if ($settings['receipt_logo_enabled']) {
            $candidatePaths = [];
            if (!empty($businessLogo)) {
                $candidatePaths[] = storage_path('app/public/' . $businessLogo);
                $candidatePaths[] = public_path('storage/' . $businessLogo);
                $candidatePaths[] = public_path($businessLogo);
            }
            // Fallback default store logo
            $candidatePaths[] = public_path('images/mtc-logo-only.png');

            foreach ($candidatePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $mime = mime_content_type($path) ?: 'image/png';
                    $data = file_get_contents($path);
                    if ($data !== false) {
                        $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($data);
                        break;
                    }
                }
            }
        }

        $qrUrl = \App\Services\ReceiptService::buildReviewQrUrl($order, $posConfig['qr_url'] ?? null);

        $settings['qr_url'] = $qrUrl;

        if ($qrUrl) {
            $settings['qr_code'] = \App\Helpers\QrCodeHelper::generateDataUri($qrUrl, 120);
        }

        return view('receipts.thermal-receipt', compact('order', 'settings', 'logoDataUri', 'qrUrl', 'businessLogo'));
    }
}
