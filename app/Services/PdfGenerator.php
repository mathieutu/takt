<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PdfGenerator
{
    private readonly string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.pdf.api_url');
    }

    public function fromView(string $view, array $data = [], array $pdfOptions = []): string
    {
        $payload = ['html' => view($view, $data)->render()];

        if ($pdfOptions !== []) {
            $payload['pdfOptions'] = $pdfOptions;
        }

        $response = Http::asJson()->timeout(65)->post($this->apiUrl, $payload);

        if ($response->failed()) {
            throw new RuntimeException("Failed to generate PDF: {$response->body()}");
        }

        return $response->body();
    }
}
