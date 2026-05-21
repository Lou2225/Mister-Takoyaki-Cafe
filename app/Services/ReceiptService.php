<?php

namespace App\Services;

use App\Helpers\QrCodeHelper;
use App\Models\Order;
use App\Models\SystemSetting;

class ReceiptService
{
    /**
     * Get formatted receipt data for an order
     */
    public static function getReceiptData(Order $order)
    {
        // Load relationships
        $order->load([
            'items.product',
            'items.options.option',
            'items.modifiers.modifier',
        ]);

        $address = SystemSetting::get('business_address', '');
        if (is_array($address)) {
            $address = $address['formatted'] ?? '';
        }

        // Get all receipt configuration settings
        $settings = [
            'business_name'           => SystemSetting::get('business_name', 'Mister Takoyaki Cafe'),
            'business_email'          => SystemSetting::get('business_email', ''),
            'business_phone'          => SystemSetting::get('business_phone', ''),
            'business_address'        => $address,
            'currency_symbol'         => '₱',
            'currency'                => 'PHP',
            'vat_rate'                => 0,
            'receipt_logo_enabled'    => (bool) SystemSetting::get('receipt_logo_enabled', true),
            'receipt_show_vat'        => (bool) SystemSetting::get('receipt_show_vat', true),
            'receipt_footer_message'  => SystemSetting::get('receipt_footer_message', 'Thank you for your visit!'),
            'receipt_return_policy'   => SystemSetting::get('receipt_return_policy', 'No return, no exchange.'),
            'receipt_copies'          => (int) SystemSetting::get('receipt_copies', 1),
            'receipt_qr_url'          => SystemSetting::get('receipt_qr_url', ''),
        ];

        // Generate QR code for review
        $qrCode = null;
        try {
            $qrUrl = $settings['receipt_qr_url'];
            if (empty($qrUrl)) {
                $qrUrl = route('customer.review', ['branch' => $order->branch_id]);
            } else {
                $qrUrl = str_replace('{order_id}', $order->id, $qrUrl);
                // Append branch context to custom URLs so reviews are attributed correctly
                $separator = str_contains($qrUrl, '?') ? '&' : '?';
                $qrUrl .= $separator . 'branch=' . $order->branch_id;
            }

            // Rewrite localhost to local LAN IP so phones can scan it
            if (str_contains($qrUrl, 'localhost') || str_contains($qrUrl, '127.0.0.1')) {
                $localIp = gethostbyname(gethostname());
                $qrUrl = str_replace(['localhost', '127.0.0.1'], $localIp, $qrUrl);
            }

            $qrCode = QrCodeHelper::generateReviewQrCode($qrUrl);
        } catch (\Exception $e) {
            // Silently fail QR generation - receipt will still work without it
            $qrCode = null;
        }

        // Get business logo if configured
        $businessLogo = $settings['receipt_logo_enabled'] 
            ? SystemSetting::get('business_logo', null) 
            : null;

        return [
            'order'         => $order,
            'settings'      => $settings,
            'businessLogo'  => $businessLogo,
            'qrCode'        => $qrCode,
            'copies'        => $settings['receipt_copies'],
        ];
    }

    /**
     * Calculate subtotal (before tax and discount)
     */
    public static function calculateSubtotal(Order $order): float
    {
        return $order->total_amount + $order->discount_amount;
    }
}
