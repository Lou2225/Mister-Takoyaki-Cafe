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

        $posConfig = \App\Services\ConfigurationService::getPosConfig();
        $businessConfig = \App\Services\ConfigurationService::getBusinessConfig();
        $financialConfig = \App\Services\ConfigurationService::getFinancialConfig();

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
            'qr_code' => null
        ];

        $businessLogo = $businessConfig['logo'] ?? null;

        $qrUrl = $posConfig['qr_url'] ?? '';
        if (empty($qrUrl)) {
            $qrUrl = route('customer.review', ['branch' => $order->branch_id]);
        } else {
            // Append branch context to custom URLs so reviews are attributed correctly
            $separator = str_contains($qrUrl, '?') ? '&' : '?';
            $qrUrl .= $separator . 'branch=' . $order->branch_id;
        }
        
        // Rewrite localhost to local LAN IP so phones can connect
        if (str_contains($qrUrl, 'localhost') || str_contains($qrUrl, '127.0.0.1')) {
            $localIp = gethostbyname(gethostname());
            $qrUrl = str_replace(['localhost', '127.0.0.1'], $localIp, $qrUrl);
        }

        if ($qrUrl) {
            $settings['qr_code'] = \App\Helpers\QrCodeHelper::generateDataUri($qrUrl, 100);
        }

        return view('receipts.thermal', compact('order', 'settings', 'businessLogo'));
    }
}
