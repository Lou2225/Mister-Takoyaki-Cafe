<?php

namespace App\Http\Controllers\Api;

use App\Helpers\QrCodeHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ConfigurationService;
use Illuminate\Http\JsonResponse;

class PosReceiptController extends Controller
{
    /**
     * Plain-JSON receipt payload consumed by the browser-side Web Bluetooth
     * thermal printer client. It includes the full kitchen/barista/customer
     * receipt layout and review QR target so the browser can print the same
     * structure as the local thermal printer template.
     */
    public function data(Order $order): JsonResponse
    {
        $user = auth()->user();

        if ($user->role_id !== 1 && $order->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to access this order.');
        }

        // CRITICAL: Eager load all relationships needed for receipt building
        $order->load([
            'items.product.category',  // Category needed for production_station
            'items.options.option',
            'items.modifiers.modifier',
            'user',
        ]);

        $business = ConfigurationService::getBusinessConfig();
        $financial = ConfigurationService::getFinancialConfig();

        $posConfig = ConfigurationService::getPosConfig();
        $businessLogo = $business['logo'] ?? null;
        $logoDataUri = null;
        $receiptLogoEnabled = (bool) ($posConfig['logo_enabled'] ?? true);

        if ($receiptLogoEnabled) {
            $candidatePaths = [];
            if (!empty($businessLogo)) {
                $candidatePaths[] = storage_path('app/public/' . $businessLogo);
                $candidatePaths[] = public_path('storage/' . $businessLogo);
                $candidatePaths[] = public_path($businessLogo);
            }
            // Fallback default store logos
            $candidatePaths[] = public_path('images/mtc-logo-only.png');
            $candidatePaths[] = public_path('images/mistertakoyaki.png');

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

        $qrUrl = \App\Models\SystemSetting::get('receipt_qr_url', '');
        if (empty($qrUrl)) {
            $qrUrl = route('customer.review', ['branch' => $order->branch_id]);
        } else {
            $qrUrl = str_replace('{order_id}', $order->id, $qrUrl);
            $separator = str_contains($qrUrl, '?') ? '&' : '?';
            $qrUrl .= $separator . 'branch=' . $order->branch_id;
        }

        // Rewrite localhost / 127.0.0.1 to LAN IP so phone cameras can scan the QR code
        if (str_contains($qrUrl, 'localhost') || str_contains($qrUrl, '127.0.0.1')) {
            $localIp = gethostbyname(gethostname());
            $qrUrl = str_replace(['localhost', '127.0.0.1'], $localIp, $qrUrl);
        }

        $qrCode = null;
        try {
            $qrCode = QrCodeHelper::generateReviewQrCode($qrUrl);
        } catch (\Throwable $e) {
            $qrCode = null;
        }

        $kitchenItems = $order->items->filter(fn ($item) => ($item->product->category->production_station ?? 'kitchen') === 'kitchen');
        $baristaItems = $order->items->filter(fn ($item) => ($item->product->category->production_station ?? '') === 'barista');

        $normalizeItem = function ($item) {
            return [
                'qty'     => (float) $item->quantity,
                'name'    => $item->product->name ?? 'Item',
                'subtotal'=> number_format((float) $item->subtotal, 2, '.', ''),
                'notes'   => $item->special_instructions ?? '',
                'options' => $item->options->map(fn ($o) => [
                    'name' => $o->option->name ?? '',
                ])->values()->all(),
                'modifiers' => $item->modifiers->map(fn ($m) => [
                    'name' => $m->modifier->name ?? '',
                ])->values()->all(),
            ];
        };

        $receipts = [];
        if ($kitchenItems->isNotEmpty()) {
            $receipts[] = [
                'type'  => 'kitchen',
                'title' => 'KITCHEN SLIP',
                'items' => $kitchenItems->map($normalizeItem)->values()->all(),
            ];
        }

        if ($baristaItems->isNotEmpty()) {
            $receipts[] = [
                'type'  => 'barista',
                'title' => 'BARISTA SLIP',
                'items' => $baristaItems->map($normalizeItem)->values()->all(),
            ];
        }

        $receipts[] = [
            'type'  => 'customer',
            'title' => $business['name'] ?? 'Mister Takoyaki Cafe',
            'items' => $order->items->map($normalizeItem)->values()->all(),
        ];

        return response()->json([
            'order' => [
                'reference'        => $order->reference_no,
                'date'             => $order->created_at->format('d/m/y H:i'),
                'type'             => $order->order_type,
                'table_number'     => $order->table_number ?? null,
                'customer_name'    => $order->customer_name ?? 'Walk-in',
                'customer_phone'   => $order->customer_phone ?? '',
                'payment_method'   => $order->payment_method ?? 'Cash',
                'amount_tendered'  => $order->amount_tendered !== null ? number_format((float) $order->amount_tendered, 2, '.', '') : null,
                'change_amount'    => $order->change_amount !== null ? number_format((float) $order->change_amount, 2, '.', '') : null,
                'discount_amount'  => number_format((float) $order->discount_amount, 2, '.', ''),
                'service_charge'   => number_format((float) $order->service_charge, 2, '.', ''),
                'delivery_fee'     => number_format((float) $order->delivery_fee, 2, '.', ''),
                'delivery_address' => $order->delivery_address ?? '',
                'delivery_notes'   => $order->delivery_notes ?? '',
                'notes'            => $order->notes ?? '',
                'items'            => $order->items->map($normalizeItem)->values()->all(),
                'subtotal'         => number_format((float) ($order->total_amount + $order->discount_amount), 2, '.', ''),
                'total'            => number_format((float) $order->total_amount, 2, '.', ''),
            ],
            'settings' => [
                'business_name'          => $business['name'] ?? 'Mister Takoyaki Cafe',
                'business_address'       => $business['address'] ?? '',
                'business_phone'         => $business['phone'] ?? '',
                'business_email'         => $business['email'] ?? '',
                'currency_symbol'        => $financial['currency_symbol'] ?? '₱',
                'receipt_logo_enabled'   => $receiptLogoEnabled,
                'logo_data_uri'          => $logoDataUri,
                'receipt_footer_message' => \App\Models\SystemSetting::get('receipt_footer_message', 'Thank you for your visit!'),
                'receipt_return_policy'  => \App\Models\SystemSetting::get('receipt_return_policy', ''),
                'receipt_copies'         => (int) \App\Models\SystemSetting::get('receipt_copies', 1),
                'qr_code'                => $qrCode,
                'qr_url'                 => $qrUrl,
                'kitchen_slip_title'     => \App\Models\SystemSetting::get('kitchen_slip_title', '🍳 KITCHEN SLIP'),
                'kitchen_slip_subtitle'  => \App\Models\SystemSetting::get('kitchen_slip_subtitle', 'Food Preparation Order'),
                'barista_slip_title'     => \App\Models\SystemSetting::get('barista_slip_title', '☕ BARISTA SLIP'),
                'barista_slip_subtitle'  => \App\Models\SystemSetting::get('barista_slip_subtitle', 'Beverage Preparation Order'),
                'customer_receipt_title' => \App\Models\SystemSetting::get('customer_receipt_title', 'Customer Receipt & Invoice'),
                'show_receipt_qr_code'   => (bool) \App\Models\SystemSetting::get('show_receipt_qr_code', true),
                'show_receipt_footer'    => (bool) \App\Models\SystemSetting::get('show_receipt_footer', true),
                'show_receipt_tendered'  => (bool) \App\Models\SystemSetting::get('show_receipt_tendered', true),
                'show_receipt_change'    => (bool) \App\Models\SystemSetting::get('show_receipt_change', true),
            ],
            'receipts' => $receipts,
        ]);
    }
}