<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PeexitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymentController — Intégration Peexit Collect API
 *
 * FIX: Vérification que PeexitService est configuré AVANT de créer le paiement.
 *      Retourne un 503 clair si PEEX_SECRET_KEY manque, au lieu d'un crash 500.
 */
class PaymentController extends Controller
{
    public function __construct(private PeexitService $peex) {}

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/payments/initiate
    // ────────────────────────────────────────────────────────────────────────
    public function initiate(Request $request)
    {
        $request->validate([
            'booking_ref' => 'required|string',
            'method'      => 'required|in:mtn_momo,airtel_money,orange_money,wave,carte,virement',
            'phone'       => 'required_if:method,mtn_momo,airtel_money,orange_money,wave|string',
        ]);

        $mobileMoneyMethods = ['mtn_momo', 'airtel_money', 'orange_money', 'wave'];

        // FIX: Vérifier la configuration AVANT de créer quoi que ce soit
        if (in_array($request->method, $mobileMoneyMethods) && !$this->peex->isConfigured()) {
            Log::critical('[PaymentController] PEEX_SECRET_KEY manquante — paiement mobile impossible');
            return response()->json([
                'success' => false,
                'message' => 'Le paiement mobile est temporairement indisponible. Veuillez contacter le support.',
                'code'    => 'PAYMENT_SERVICE_UNAVAILABLE',
            ], 503);
        }

        // ── Récupérer la réservation ──────────────────────────────────────
        $booking = Booking::where('reference', $request->booking_ref)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->payment?->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation est déjà payée.',
            ], 409);
        }

        // ── Créer l'enregistrement paiement ──────────────────────────────
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id'    => $request->user()->id,
            'method'     => $request->method,
            'phone'      => $request->phone,
            'amount'     => $booking->total_amount,
            'currency'   => $booking->currency ?? 'XAF',
            'status'     => 'en_attente',
        ]);

        // ── Méthodes Mobile Money → appel Peexit ─────────────────────────
        if (in_array($request->method, $mobileMoneyMethods)) {
            try {
                $user    = $request->user();
                $country = $this->resolveCountryCode($user->country ?? 'Congo (Brazzaville)');
                $phone   = $this->formatPhone($request->phone, $country);

                $peexResult = $this->peex->requestCollection([
                    'track_id'      => $payment->reference,
                    'phone'         => $phone,
                    'amount'        => (float) $booking->total_amount,
                    'currency'      => $booking->currency ?? 'XAF',
                    'customer_name' => $user->name,
                    'country'       => $country,
                    'description'   => "Réservation ImmoStay #{$booking->reference}",
                ]);

                $payment->update([
                    'provider_ref'    => (string) ($peexResult['id'] ?? ''),
                    'gateway_response'=> $peexResult,
                    'status'          => $this->peex->mapStatus($peexResult['status'] ?? 'pending'),
                ]);

            } catch (\Throwable $e) {
                Log::error('[PaymentController] Peexit error', ['error' => $e->getMessage()]);
                $payment->update(['status' => 'échoué']);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'initiation du paiement : ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié. Validez sur votre téléphone.',
            'data'    => [
                'payment_reference' => $payment->reference,
                'status'            => $payment->status,
                'amount'            => $payment->amount,
                'currency'          => $payment->currency,
                'method'            => $payment->method,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  GET /api/v1/payments/{ref}/status
    // ────────────────────────────────────────────────────────────────────────
    public function status(string $ref)
    {
        $payment = Payment::where('reference', $ref)->firstOrFail();

        if ($payment->isPending() && $payment->provider_ref && $this->peex->isConfigured()) {
            try {
                $peexResult = $this->peex->getTransactionStatus($payment->reference);
                $newStatus  = $this->peex->mapStatus($peexResult['status'] ?? 'pending');

                if ($newStatus !== $payment->status) {
                    $payment->update([
                        'status'          => $newStatus,
                        'gateway_response'=> $peexResult,
                        'paid_at'         => $newStatus === 'succès' ? now() : null,
                    ]);

                    if ($newStatus === 'succès') {
                        $payment->booking?->update(['status' => 'confirmé']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[PaymentController] Status refresh failed', [
                    'ref'   => $ref,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payment->refresh();

        return response()->json([
            'success' => true,
            'data'    => [
                'reference' => $payment->reference,
                'status'    => $payment->status,
                'amount'    => $payment->amount,
                'currency'  => $payment->currency,
                'method'    => $payment->method,
                'paid_at'   => $payment->paid_at?->toIso8601String(),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/payments/peex/callback
    // ────────────────────────────────────────────────────────────────────────
    public function peexCallback(Request $request)
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        $expectedUser = config('services.peexit.callback_user', 'peex');
        $expectedPass = config('services.peexit.callback_password', 'peex_callback');

        if ($username !== $expectedUser || $password !== $expectedPass) {
            Log::warning('[Peexit Callback] Unauthorized callback attempt');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('[Peexit Callback] Received', ['payload' => $payload]);

        $transactions = isset($payload[0]) ? $payload : [$payload];

        foreach ($transactions as $tx) {
            $trackId    = $tx['track_id'] ?? null;
            $peexStatus = $tx['status']   ?? null;

            if (!$trackId || !$peexStatus) continue;

            $payment = Payment::where('reference', $trackId)->first();
            if (!$payment) {
                Log::warning('[Peexit Callback] Payment not found', ['track_id' => $trackId]);
                continue;
            }

            $newStatus = $this->peex->mapStatus($peexStatus);
            $payment->update([
                'status'          => $newStatus,
                'gateway_response'=> $tx,
                'paid_at'         => $newStatus === 'succès' ? now() : null,
            ]);

            if ($newStatus === 'succès') {
                $payment->booking?->update(['status' => 'confirmé']);
                Log::info('[Peexit Callback] Booking confirmed', [
                    'payment' => $trackId,
                    'booking' => $payment->booking?->reference,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  Helpers privés
    // ────────────────────────────────────────────────────────────────────────

    private function resolveCountryCode(string $country): string
    {
        $map = [
            'Congo Brazzaville'    => 'CG',
            'Congo (Brazzaville)'  => 'CG',
            'Congo RDC'            => 'CD',
            'Gabon'                => 'GA',
            'Cameroun'             => 'CM',
            'Côte d\'Ivoire'       => 'CI',
            'Sénégal'              => 'SN',
            'Mali'                 => 'ML',
            'Guinée'               => 'GN',
            'Tchad'                => 'TD',
            'Centrafrique'         => 'CF',
            'Angola'               => 'AO',
            'France'               => 'FR',
            'Belgique'             => 'BE',
            'Togo'                 => 'TG',
            'Bénin'                => 'BJ',
        ];

        if (preg_match('/^[A-Z]{2}$/', $country)) return $country;

        return $map[$country] ?? 'CG';
    }

    private function formatPhone(string $phone, string $countryCode): string
    {
        if (str_starts_with($phone, '+')) {
            return preg_replace('/\s+/', '', $phone);
        }

        $dialCodes = [
            'CG' => '+242', 'CD' => '+243', 'GA' => '+241',
            'CM' => '+237', 'CI' => '+225', 'SN' => '+221',
            'ML' => '+223', 'GN' => '+224', 'TD' => '+235',
            'CF' => '+236', 'AO' => '+244', 'FR' => '+33',
            'BE' => '+32',  'TG' => '+228', 'BJ' => '+229',
        ];

        $dialCode = $dialCodes[$countryCode] ?? '+242';
        $phone    = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return $dialCode . $phone;
    }
}
