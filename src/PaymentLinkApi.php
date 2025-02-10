<?php

declare(strict_types=1);

namespace Nox\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PaymentLinkApi
{
    private Client $client;

    public function __construct(
        private readonly string $apiToken
    ) {
        $this->client = new Client([
            'base_uri' => 'https://paglink.noxpay.io',
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key' => $apiToken,
            ],
        ]);
    }

    public function createLink(
        string $description,
        float $amount,
        bool $reusable = false
    ): array {
        try {
            $data = new class($description, $amount, $reusable) {
                public function __construct(
                    private string $description,
                    private float $amount,
                    private bool $reusable
                ) {}

                public function toArray(): array {
                    return [
                        'description' => $this->description,
                        'amount' => $this->amount,
                        'reusable' => $this->reusable
                    ];
                }
            };

            $response = $this->client->post('/link/', [
                'json' => $data->toArray(),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    public function getLink(string $uuid): array
    {
        try {
            $response = $this->client->get("/link/{$uuid}?format=json");
            $linkDetails = json_decode($response->getBody()->getContents(), true);
            $linkDetails['full_link'] = "https://paglink.noxpay.io/link/{$uuid}";
            return $linkDetails;
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    public function updateLink(
        string $uuid,
        ?string $description = null,
        ?float $amount = null,
        ?bool $reusable = null,
        ?bool $disable = null,
        ?string $valid_until = null
    ): void {
        try {
            $data = new class($description, $amount, $reusable, $disable, $valid_until) {
                public function __construct(
                    private ?string $description,
                    private ?float $amount,
                    private ?bool $reusable,
                    private ?bool $disable,
                    private ?string $valid_until
                ) {}

                public function toArray(): array {
                    return array_filter([
                        'description' => $this->description,
                        'amount' => $this->amount,
                        'reusable' => $this->reusable,
                        'disable' => $this->disable,
                        'valid_until' => $this->valid_until
                    ], fn($value) => $value !== null);
                }
            };

            $this->client->put("/link/{$uuid}", [
                'json' => $data->toArray(),
            ]);
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    private function handleError(RequestException $e): never
    {
        $response = $e->getResponse();
        if ($response) {
            throw new \Exception('Api Error: ' . $response->getBody()->getContents());
        }
        throw new \Exception('Error: ' . $e->getMessage());
    }
} 