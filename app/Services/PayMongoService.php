<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    private const API_BASE = 'https://api.paymongo.com/v1';

    private string $secretKey;
    private string $publicKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey     = config('services.paymongo.secret_key', '');
        $this->publicKey     = config('services.paymongo.public_key', '');
        $this->webhookSecret = config('services.paymongo.webhook_secret', '');
    }

    /**
     * Create a GCash payment link via the Payment Intent + Payment Method workflow.
     * Returns ['checkout_url' => '...', 'payment_intent_id' => '...'] on success.
     */
    public function createGCashPaymentLink(
        float $amount,
        string $referenceNo,
        string $description = 'Mister Takoyaki Cafe Order'
    ): array {
        // PayMongo amounts are in centavos (PHP * 100)
        $amountCentavos = (int) round($amount * 100);

        // Step 1: Create Payment Intent
        $intentResponse = Http::withBasicAuth($this->secretKey, '')
            ->post(self::API_BASE . '/payment_intents', [
                'data' => [
                    'attributes' => [
                        'amount'                  => $amountCentavos,
                        'currency'                => 'PHP',
                        'payment_method_allowed'  => ['gcash'],
                        'description'             => $description,
                        'statement_descriptor'    => 'MSTR TAKOYAKI',
                        'metadata'                => [
                            'reference_no' => $referenceNo,
                        ],
                    ],
                ],
            ]);

        if ($intentResponse->failed()) {
            $error = $intentResponse->json('errors.0.detail') ?? 'Failed to create payment intent.';
            Log::error('PayMongo: Create Payment Intent failed', [
                'status'   => $intentResponse->status(),
                'response' => $intentResponse->json(),
            ]);
            throw new \RuntimeException($error);
        }

        $intent = $intentResponse->json('data');
        $intentId = $intent['id'];
        $clientKey = $intent['attributes']['client_key'];

        // Step 2: Create Payment Method (GCash) - Using Secret Key for better reliability
        $methodResponse = Http::withBasicAuth($this->secretKey, '')
            ->post(self::API_BASE . '/payment_methods', [
                'data' => [
                    'attributes' => [
                        'type' => 'gcash',
                    ],
                ],
            ]);

        if ($methodResponse->failed()) {
            $error = $methodResponse->json('errors.0.detail') ?? 'Failed to create payment method.';
            // Specifically handle the 'not allowed' error for better user feedback
            if (str_contains($error, 'not allowed')) {
                $error = "GCash is not yet enabled on your PayMongo account. Please activate it in your PayMongo Dashboard settings.";
            }

            Log::error('PayMongo: Create Payment Method failed', [
                'status'   => $methodResponse->status(),
                'response' => $methodResponse->json(),
            ]);
            throw new \RuntimeException($error);
        }

        $paymentMethodId = $methodResponse->json('data.id');

        // Step 3: Attach Payment Method to Intent
        $returnUrl = route('paymongo.return', ['ref' => $referenceNo]);
        $attachResponse = Http::withBasicAuth($this->secretKey, '')
            ->post(self::API_BASE . "/payment_intents/{$intentId}/attach", [
                'data' => [
                    'attributes' => [
                        'payment_method' => $paymentMethodId,
                        'client_key'     => $clientKey,
                        'return_url'     => $returnUrl,
                    ],
                ],
            ]);

        if ($attachResponse->failed()) {
            Log::error('PayMongo: Attach Payment Method failed', [
                'status'   => $attachResponse->status(),
                'response' => $attachResponse->json(),
            ]);
            throw new \RuntimeException('Failed to attach GCash payment method.');
        }

        $attachData = $attachResponse->json('data');
        $nextAction = $attachData['attributes']['next_action'] ?? null;
        $checkoutUrl = $nextAction['redirect']['url'] ?? null;

        if (!$checkoutUrl) {
            throw new \RuntimeException('PayMongo did not return a GCash redirect URL.');
        }

        return [
            'checkout_url'      => $checkoutUrl,
            'payment_intent_id' => $intentId,
        ];
    }

    /**
     * Verify the PayMongo webhook signature.
     * Returns true if the payload is authentic.
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): bool
    {
        if (empty($this->webhookSecret) || empty($sigHeader)) {
            return false;
        }

        // Parse "t=timestamp,li=hash,te=hash" format
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = explode('=', $part, 2) + [null, null];
            if ($k && $v) $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? null;
        $liveHash  = $parts['li'] ?? null;
        $testHash  = $parts['te'] ?? null;

        if (!$timestamp) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedHash  = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return hash_equals($expectedHash, $liveHash ?? $testHash ?? '');
    }
}
