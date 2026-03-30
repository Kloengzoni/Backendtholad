<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PeexitService — Intégration Collect API Peexit
 * Doc : https://peex-api-docs.peexit.com/collect/collections
 *
 * FIX: Le constructeur ne throw plus si la clé manque.
 *       On retourne une erreur métier depuis les méthodes,
 *       ce qui évite le "Erreur serveur" générique côté Flutter.
 */
class PeexitService
{
    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.peexit.base_url', 'https://dev-backend.peexit.com/api/v1'), '/');
        $this->secretKey = config('services.peexit.secret_key', '');

        if (empty($this->secretKey)) {
            Log::critical('[Peexit] PEEX_SECRET_KEY non configurée dans les variables Railway.');
            // FIX: On ne throw plus ici — la vérification se fait dans les méthodes
            // pour permettre au PaymentController de retourner un 503 propre.
        }
    }

    /**
     * Vérifie que la clé est configurée avant tout appel réseau.
     * Lance une RuntimeException métier (non critique) si elle manque.
     */
    private function assertConfigured(): void
    {
        if (empty($this->secretKey)) {
            throw new \RuntimeException(
                'Le système de paiement mobile est temporairement indisponible. ' .
                'Veuillez contacter le support ou réessayer plus tard.'
            );
        }
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
     */
    public function requestCollection(array $data): array
    {
        $this->assertConfigured();

        Log::info('[Peexit] Initiating collection', [
            'track_id' => $data['track_id'],
            'amount'   => $data['amount'],
            'phone'    => $data['phone'],
            'country'  => $data['country'] ?? 'N/A',
        ]);

        $response = Http::withHeaders($this->headers())
            ->timeout(30)
            ->post("{$this->baseUrl}/collection/request_payment", $data);

        Log::info('[Peexit] Collection response', [
            'http_status' => $response->status(),
            'body'        => $response->body(),
        ]);

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
        Log::info('[Peexit] Collection initiated successfully', ['result' => $result]);

        return $result;
    }

    /**
     * Vérifier le statut d'une transaction par son track_id
     */
    public function getTransactionStatus(string $trackId): array
    {
        $this->assertConfigured();

        Log::info('[Peexit] Checking transaction status', ['track_id' => $trackId]);

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
     * Convertir un statut Peexit en statut interne TholadImmo
     */
    public function mapStatus(string $peexStatus): string
    {
        return match ($peexStatus) {
            'paid'                               => 'succès',
            'pending', 'new'                     => 'en_attente',
            'failed', 'canceled', 'rejected'     => 'échoué',
            default                              => 'en_attente',
        };
    }

    /**
     * Indique si le service est correctement configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }
}
