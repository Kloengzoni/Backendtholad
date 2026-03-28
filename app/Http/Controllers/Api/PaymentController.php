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
 * Flux :
 *   1. App Flutter → POST /api/v1/payments/initiate  (initier le paiement)
 *   2. Peexit envoie une demande USSD au téléphone du client
 *   3. Peexit → POST /api/v1/payments/peex/callback  (webhook confirmation)
 *   4. App Flutter → GET  /api/v1/payments/{ref}/status (polling ou après callback)
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
        $mobileMoneyMethods = ['mtn_momo', 'airtel_money', 'orange_money', 'wave'];

        if (in_array($request->method, $mobileMoneyMethods)) {
            try {
                $user    = $request->user();
                $country = $this->resolveCountryCode($user->country ?? 'Congo (Brazzaville)');

                // Formater le téléphone en international (+242...)
                $phone = $this->formatPhone($request->phone, $country);

                $peexResult = $this->peex->requestCollection([
                    'track_id'      => $payment->reference,       // notre ref unique
                    'phone'         => $phone,
                    'amount'        => (float) $booking->total_amount,
                    'currency'      => $booking->currency ?? 'XAF',
                    'customer_name' => $user->name,
                    'country'       => $country,
                    'description'   => "Réservation ImmoStay #{$booking->reference}",
                ]);

                // Sauvegarder la réponse Peexit
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
                    'message' => 'Erreur lors de l\'initiation du paiement mobile : ' . $e->getMessage(),
                ], 500);
            }
        }

        // ── Autres méthodes (carte, virement) → en attente manuelle ──────
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
    //  GET /api/v1/payments/{ref}/status   (polling depuis l'app Flutter)
    // ────────────────────────────────────────────────────────────────────────
    public function status(string $ref)
    {
        $payment = Payment::where('reference', $ref)->firstOrFail();

        // Si toujours en attente, rafraîchir depuis Peexit
        if ($payment->isPending() && $payment->provider_ref) {
            try {
                $peexResult = $this->peex->getTransactionStatus($payment->reference);
                $newStatus  = $this->peex->mapStatus($peexResult['status'] ?? 'pending');

                if ($newStatus !== $payment->status) {
                    $payment->update([
                        'status'          => $newStatus,
                        'gateway_response'=> $peexResult,
                        'paid_at'         => $newStatus === 'succès' ? now() : null,
                    ]);

                    // Confirmer la réservation si paiement réussi
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
    //  POST /api/v1/payments/peex/callback   (Webhook Peexit → votre serveur)
    //  Sécurisé par Basic Auth : username=peex, password=peex_callback (sandbox)
    // ────────────────────────────────────────────────────────────────────────
    public function peexCallback(Request $request)
    {
        // Vérification Basic Auth Peexit
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

        // Le callback peut être un tableau ou un objet unique
        $transactions = isset($payload[0]) ? $payload : [$payload];

        foreach ($transactions as $tx) {
            $trackId   = $tx['track_id'] ?? null;
            $peexStatus = $tx['status']   ?? null;

            if (!$trackId || !$peexStatus) {
                continue;
            }

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

            // Confirmer la réservation si paiement réussi
            if ($newStatus === 'succès') {
                $payment->booking?->update(['status' => 'confirmé']);
                Log::info('[Peexit Callback] Booking confirmed', [
                    'payment'  => $trackId,
                    'booking'  => $payment->booking?->reference,
                ]);
            }

            if ($newStatus === 'échoué') {
                Log::warning('[Peexit Callback] Payment failed', ['track_id' => $trackId]);
            }
        }

        return response()->json(['success' => true]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  Helpers privés
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Convertir un pays en code ISO2
     */
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

        // Si déjà un code ISO2 (2 lettres majuscules)
        if (preg_match('/^[A-Z]{2}$/', $country)) {
            return $country;
        }

        return $map[$country] ?? 'CG';
    }

    /**
     * Formater le numéro de téléphone au format international Peexit (+242XXXXXXX)
     */
    private function formatPhone(string $phone, string $countryCode): string
    {
        // Déjà au bon format
        if (str_starts_with($phone, '+')) {
            return preg_replace('/\s+/', '', $phone);
        }

        // Indicatifs par pays
        $dialCodes = [
            'CG' => '+242', 'CD' => '+243', 'GA' => '+241',
            'CM' => '+237', 'CI' => '+225', 'SN' => '+221',
            'ML' => '+223', 'GN' => '+224', 'TD' => '+235',
            'CF' => '+236', 'AO' => '+244', 'FR' => '+33',
            'BE' => '+32',  'TG' => '+228', 'BJ' => '+229',
        ];

        $dialCode = $dialCodes[$countryCode] ?? '+242';
        $phone    = preg_replace('/\s+/', '', $phone);

        // Supprimer le 0 local si présent en début
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return $dialCode . $phone;
    }
}
