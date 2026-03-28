<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PeexitService — Intégration Collect API Peexit
 * Doc : https://peex-api-docs.peexit.com/collect/collections
 *
 * Usage :
 *   $peex = new PeexitService();
 *   $result = $peex->requestCollection([...]);
 *   $status = $peex->getTransactionStatus('TRACK_ID_123');
 */
class PeexitService
{
    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.peexit.base_url'), '/');
        $this->secretKey = config('services.peexit.secret_key');
    }

    // ─── Headers communs ──────────────────────────────────────────────────
    private function headers(): array
    {
        return [
            'SECRETKEY'    => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    /**
     * Initier une collecte Mobile Money (MTN, Airtel, Orange…)
     *
     * @param  array{
     *   track_id: string,       // Référence unique de votre côté (ex: "PAY-2025-ABCD1234")
     *   phone: string,          // Format international : +242068XXXXXXX
     *   amount: float,          // Montant en FCFA
     *   currency: string,       // "XAF"
     *   customer_name: string,  // Nom complet du client
     *   country: string,        // Code ISO2 : "CG" pour Congo Brazzaville
     *   description: string,    // Motif du paiement
     * } $data
     *
     * @return array  Réponse Peexit avec : id, status, track_id, method, payment_proof, message
     * @throws \RuntimeException en cas d'échec HTTP
     */
    public function requestCollection(array $data): array
    {
        Log::info('[Peexit] Initiating collection', [
            'track_id' => $data['track_id'],
            'amount'   => $data['amount'],
            'phone'    => $data['phone'],
        ]);

        $response = Http::withHeaders($this->headers())
            ->timeout(30)
            ->post("{$this->baseUrl}/collection/request_payment", $data);

        if ($response->failed()) {
            Log::error('[Peexit] Collection request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException(
                'Peexit collection failed: ' . ($response->json('message') ?? $response->body())
            );
        }

        $result = $response->json();
        Log::info('[Peexit] Collection initiated', ['result' => $result]);

        return $result;
    }

    /**
     * Vérifier le statut d'une transaction par son track_id
     *
     * Statuts possibles : new | pending | paid | failed | canceled | rejected
     *
     * @param  string $trackId  Votre référence unique (track_id envoyé lors de la création)
     * @return array            Objet transaction complet avec status
     */
    public function getTransactionStatus(string $trackId): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(15)
            ->get("{$this->baseUrl}/collection/all_requests", [
                'track_id' => $trackId,
            ]);

        if ($response->failed()) {
            Log::error('[Peexit] Status check failed', [
                'track_id' => $trackId,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new \RuntimeException('Peexit status check failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Convertir un statut Peexit en statut interne ImmoStay
     */
    public function mapStatus(string $peexStatus): string
    {
        return match ($peexStatus) {
            'paid'               => 'succès',
            'pending', 'new'     => 'en_attente',
            'failed', 'canceled', 'rejected' => 'échoué',
            default              => 'en_attente',
        };
    }
}
