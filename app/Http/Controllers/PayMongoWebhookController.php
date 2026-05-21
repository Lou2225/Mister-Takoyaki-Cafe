<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function __construct(private PayMongoService $payMongo) {}

    /**
     * Handle PayMongo webhook events (payment.paid, payment.failed).
     */
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Paymongo-Signature', '');

        // Verify signature to ensure request is from PayMongo
        if (!$this->payMongo->verifyWebhookSignature($payload, $sigHeader)) {
            Log::warning('PayMongo: Invalid webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        $eventType = $event['data']['attributes']['type'] ?? null;

        Log::info('PayMongo webhook received', ['type' => $eventType]);

        if ($eventType === 'payment.paid') {
            $this->handlePaymentPaid($event);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle the return URL redirect after GCash payment.
     * This is where GCash sends the user after completing (or cancelling) payment.
     */
    public function returnCallback(Request $request)
    {
        $ref = $request->query('ref');

        // Redirect back to POS with a query param; the Livewire component will poll or react
        return redirect()->route('pos.index', ['gcash_paid' => $ref]);
    }

    private function handlePaymentPaid(array $event): void
    {
        // Navigate to the payment details to get metadata
        $paymentData = $event['data']['attributes']['data'] ?? [];
        $metadata    = $paymentData['attributes']['metadata'] ?? [];
        $referenceNo = $metadata['reference_no'] ?? null;

        if (!$referenceNo) {
            Log::warning('PayMongo webhook: payment.paid missing reference_no in metadata');
            return;
        }

        $order = Order::where('reference_no', $referenceNo)
            ->whereIn('status', [Order::STATUS_DRAFTED, Order::STATUS_PENDING, 'gcash_pending'])
            ->first();

        if (!$order) {
            Log::warning("PayMongo webhook: No order found for reference_no={$referenceNo}");
            return;
        }

        // Mark as paid — the POS will detect this via polling
        $order->update([
            'status'             => Order::STATUS_COMPLETED,
            'payment_method'     => 'GCash',
            'payment_reference'  => $event['data']['attributes']['data']['id'] ?? $referenceNo,
        ]);

        Log::info("PayMongo: Order #{$referenceNo} marked as paid via GCash webhook.");
    }
}
