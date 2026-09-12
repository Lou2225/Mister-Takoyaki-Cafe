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
            $qrUrl = self::buildReviewQrUrl($order, $settings['receipt_qr_url'] ?? null);
            $qrCode = QrCodeHelper::generateReviewQrCode($qrUrl);
        } catch (\Throwable $e) {
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

    /**
     * Build the fully-qualified customer review QR code URL for an order.
     */
    public static function buildReviewQrUrl(Order $order, ?string $configuredUrl = null): string
    {
        if (empty($order->review_token)) {
            $order->updateQuietly(['review_token' => (string) \Illuminate\Support\Str::random(32)]);
        }

        $qrUrl = $configuredUrl ?? SystemSetting::get('receipt_qr_url', '', $order->branch_id);
        if (empty($qrUrl)) {
            $qrUrl = route('customer.review', [
                'token'  => $order->review_token,
                'branch' => $order->branch_id,
            ]);
        } else {
            $qrUrl = str_replace(['{order_id}', '{token}'], [$order->id, $order->review_token], $qrUrl);
            if (!str_contains($qrUrl, 'token=')) {
                $separator = str_contains($qrUrl, '?') ? '&' : '?';
                $qrUrl .= $separator . http_build_query([
                    'token'  => $order->review_token,
                    'branch' => $order->branch_id,
                ]);
            }
        }

        // Rewrite localhost / 127.0.0.1 to LAN IP so phone cameras can scan the QR code on local development
        if (app()->environment('local') || str_contains($qrUrl, 'localhost') || str_contains($qrUrl, '127.0.0.1')) {
            $lanIp = gethostbyname(gethostname());
            if (!empty($lanIp) && $lanIp !== '127.0.0.1') {
                $qrUrl = preg_replace('#^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?#', "http://{$lanIp}:8000", $qrUrl);
            }
        }

        return $qrUrl;
    }
}
