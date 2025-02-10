<?php

namespace Nox\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\StreamInterface;

class CheckoutApi {
    private Client $client;

    public function __construct(string $token) {
        $this->client = new Client([
            'verify' => false,
            'base_uri' => 'https://checkoutdev.noxpay.io',
            'headers' => [
                'Content-Type' => 'application/json',
                'token' => $token,
            ],
        ]);
    }

    public function getCheckouts(array $queryParams = []): array {
        try {
            $response = $this->client->get('/api/checkouts/', [
                'query' => $queryParams,
            ]);
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    public function getCheckout(string $urlId): array {
        try {
            $response = $this->client->get("/api/checkout-detail/{$urlId}");
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    public function createCheckout(array $data, $file = null): array {
        try {
            $multipart = [
                [
                    'name' => 'data',
                    'contents' => json_encode($data),
                ],
            ];

            if ($file) {
                $multipart[] = [
                    'name' => 'file',
                    'contents' => $file,
                ];
            }

            $response = $this->client->post('/api/create-checkout/', [
                RequestOptions::MULTIPART => $multipart,
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    public function updateCheckout(int $id, array $data, $file = null): array {
        try {
            $multipart = [
                [
                    'name' => 'data',
                    'contents' => json_encode($data),
                ],
            ];

            if ($file) {
                $multipart[] = [
                    'name' => 'file',
                    'contents' => $file
                ];
            }

            $response = $this->client->put("/api/update-checkout/{$id}", [
                RequestOptions::MULTIPART => $multipart,
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    private function handleResponse(Response $response): array {
        return json_decode($response->getBody(), true);
    }

    private function handleError(RequestException $e): void {
        $response = $e->getResponse();
        if ($response) {
            throw new \Exception('API Error: ' . $response->getBody());
        } else {
            throw new \Exception('Error: ' . $e->getMessage());
        }
    }
}
