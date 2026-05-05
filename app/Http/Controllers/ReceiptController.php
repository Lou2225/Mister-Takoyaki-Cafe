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
            'items.product', 
            'items.options.option', 
            'items.modifiers.modifier', 
            'branch', 
            'user'
        ]);

        $address = SystemSetting::get('business_address', 'Main Branch, Manila');
        if (is_array($address)) {
            $address = !empty($address['formatted']) ? $address['formatted'] : 'Main Branch, Manila';
        }

        $settings = [
            'business_name' => SystemSetting::get('business_name', 'Mister Takoyaki Cafe'),
            'business_address' => $address,
            'business_phone' => SystemSetting::get('business_phone', '0912-345-6789'),
            'receipt_footer_message' => SystemSetting::get('receipt_footer_message', 'Thank you for your visit!'),
            'receipt_logo_enabled' => SystemSetting::get('receipt_logo_enabled', true),
            'receipt_show_vat' => SystemSetting::get('receipt_show_vat', true),
            'qr_code' => null
        ];

        $qrUrl = SystemSetting::get('receipt_qr_url');
        if (empty($qrUrl)) {
            $qrUrl = route('customer.review', ['branch' => $order->branch_id]);
        }
        
        // Rewrite localhost to local LAN IP so phones can connect
        if (str_contains($qrUrl, 'localhost') || str_contains($qrUrl, '127.0.0.1')) {
            $localIp = gethostbyname(gethostname());
            $qrUrl = str_replace(['localhost', '127.0.0.1'], $localIp, $qrUrl);
        }

        if ($qrUrl) {
            $settings['qr_code'] = \App\Helpers\QrCodeHelper::generateDataUri($qrUrl, 100);
        }

        return view('receipts.thermal', compact('order', 'settings'));
    }
}
