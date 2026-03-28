<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey    = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
    }

    /**
     * Upload un fichier image sur Cloudinary et retourne l'URL sécurisée.
     */
    public function upload(UploadedFile $file, string $folder = 'immostay/properties'): string
    {
        $url = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";

        $timestamp = time();
        $params = [
            'folder'    => $folder,
            'timestamp' => $timestamp,
        ];

        // Signature Cloudinary
        ksort($params);
        $paramStr = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $signature = hash('sha256', $paramStr . $this->apiSecret);

        $multipart = [
            ['name' => 'file',      'contents' => fopen($file->getRealPath(), 'r'), 'filename' => $file->getClientOriginalName()],
            ['name' => 'api_key',   'contents' => $this->apiKey],
            ['name' => 'timestamp', 'contents' => $timestamp],
            ['name' => 'folder',    'contents' => $folder],
            ['name' => 'signature', 'contents' => $signature],
        ];

        $client   = new \GuzzleHttp\Client();
        $response = $client->post($url, ['multipart' => $multipart]);
        $data     = json_decode($response->getBody()->getContents(), true);

        if (empty($data['secure_url'])) {
            throw new \RuntimeException('Cloudinary upload failed: ' . json_encode($data));
        }

        return $data['secure_url'];
    }

    /**
     * Supprime une image Cloudinary à partir de son URL.
     */
    public function delete(string $url): void
    {
        // Extraire le public_id depuis l'URL
        // Ex: https://res.cloudinary.com/xxx/image/upload/v1234/immostay/properties/abc.jpg
        if (!str_contains($url, 'cloudinary.com')) {
            return;
        }

        preg_match('/\/upload\/(?:v\d+\/)?(.+)\.[a-z]+$/i', $url, $matches);
        if (empty($matches[1])) {
            return;
        }

        $publicId  = $matches[1];
        $timestamp = time();
        $signature = hash('sha256', "public_id={$publicId}&timestamp={$timestamp}" . $this->apiSecret);

        $client = new \GuzzleHttp\Client();
        $client->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy", [
            'form_params' => [
                'public_id' => $publicId,
                'api_key'   => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ],
        ]);
    }
}
