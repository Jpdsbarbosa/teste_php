<?php

declare(strict_types=1);

namespace Nox\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Nox\Sdk\Dto\CreatePaymentData;
use Nox\Sdk\Dto\CreatePaymentCashOutData;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use React\Socket\SecureServer;

class V2Api {
    private Client $client;

    public function __construct(
        private readonly string $api_token,
        private readonly string $secret_key
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api2.noxpay.io',
            'verify' => false,
            'headers' => [
                'api-key' => $this->api_token,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function createPaymentBuilder(
        string $code,
        float $amount,
        ?string $webhook_url = null,
        ?string $client_name = null,
        ?string $client_document = null
    ): CreatePaymentData {
        return new CreatePaymentData(
            code: $code,
            amount: $amount,
            webhook_url: $webhook_url,
            client_name: $client_name,
            client_document: $client_document
        );
    }

    public function createPaymentCashOutBuilder(
        string $code,
        string $pixkey,
        float $amount,
        ?string $client_name = null,
        ?string $client_document = null,
        string $type = 'PIX_KEY'
    ): CreatePaymentCashOutData {
        return new CreatePaymentCashOutData(
            code: $code,
            pixkey: $pixkey,
            amount: $amount,
            client_name: $client_name,
            client_document: $client_document,
            type: $type
        );
    }

    public function getAccount(): array {
        try {
            $response = $this->client->get('/account');
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    public function createPayment(CreatePaymentData $data): array {
        try {
            $payload = array_filter($data->toArray(), fn($value) => $value !== null);
            
            $response = $this->client->post('/payment', [
                'json' => $payload
            ]);
            
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                error_log('Resposta de erro: ' . $e->getResponse()->getBody()->getContents());
            }
            return $this->handleError($e);
        }
    }

    public function createPaymentCashOut(CreatePaymentCashOutData $data): array {
        try {
            $response = $this->client->post('/payment', [
                'json' => $data->toArray(),
            ]);
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    public function getPayment(string $identifier): array {
        try {
            $response = $this->client->get("/payment/{$identifier}");
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    public function resendWebhook(string $txid): void {
        try {
            $this->client->get("/payment/webhook/resend/{$txid}");
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    private function handleResponse(ResponseInterface $response): array {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws \Exception
     */
    private function handleError(RequestException $e): never {
        $response = $e->getResponse();
        if ($response) {
            throw new \Exception('Api Error: ' . $response->getBody()->getContents());
        }
        throw new \Exception('Error: ' . $e->getMessage());
    }

    public function createWebhookServer(string $host, int $port, array $sslOptions = []): void {
        $app = AppFactory::create();

        $app->add(function ($request, $handler) {
            if ($request->getMethod() === 'POST') {
                $payload = (string)$request->getBody();
                $signature = $request->getHeaderLine('X-Signature') ?? $request->getHeaderLine('noxpay-sign');

                error_log($payload);

                if (empty($payload) || empty($signature)) {
                    error_log("[Webhook] Missing payload or signature.");
                    $response = new \Slim\Psr7\Response();
                    $response->getBody()->write(json_encode(['error' => 'Missing payload or signature']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }

                $expectedSignature = base64_encode(hash('sha256', $this->secret_key . $payload, true));

                if (!hash_equals($expectedSignature, $signature)) {
                    $response = new \Slim\Psr7\Response();
                    $response->getBody()->write(json_encode(['error' => 'Invalid signature']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
                }
            }

            return $handler->handle($request);
        });

        $app->map(['POST', 'GET'], '/', function ($request, $response) {
            if ($request->getMethod() === 'GET') {
                error_log("[Webhook] GET request received.");
                $response->getBody()->write(json_encode(['message' => 'Webhook endpoint is active']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }

            $payload = (string)$request->getBody();
            $data = json_decode($payload, true);

            if (!$data) {
                error_log("[Webhook] Invalid JSON payload.");
                $response->getBody()->write(json_encode(['error' => 'Invalid payload']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (isset($data['webhook_url_text'])) {
                unset($data['webhook_url_text']);
            }
            try {
                $eventType = $data['status'] ?? 'unknown';
                //error_log("[Webhook] Event received: $eventType"); POSSIBILIDADE DE CHAMAR O EVENTO

                $result = [
                    'message' => 'Event processed successfully',
                    'event' => $eventType,
                    'data' => $data
                ];

                $response->getBody()->write(json_encode(['success' => true, 'data' => $result]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } catch (\Exception $e) {
                error_log("[Webhook] Error processing event: " . $e->getMessage());
                $response->getBody()->write(json_encode(['error' => 'Error processing webhook']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
        });

        $server = new HttpServer(function (\Psr\Http\Message\ServerRequestInterface $request) use ($app) {
            return $app->handle($request);
        });

        // Configuração para HTTPS
        $socket = new SocketServer("{$host}:{$port}");
        if (!empty($sslOptions['cert']) && !empty($sslOptions['key'])) {
            $socket = new SecureServer($socket, null, [
                'local_cert' => $sslOptions['cert'],
                'local_pk' => $sslOptions['key'],
                'allow_self_signed' => $sslOptions['allow_self_signed'] ?? false,
                'verify_peer' => $sslOptions['verify_peer'] ?? true,
            ]);
        }

        $server->listen($socket);

        echo "[Webhook] Webhook server running at " . ($sslOptions ? "https" : "http") . "://{$host}:{$port}" . PHP_EOL;
    }
    
    /**
     * Validates the webhook signature
     * 
     * @param string $payload Raw request payload
     * @param string $signature Signature from header
     * @return bool Returns true if signature is valid
     * @throws \Exception If signature is invalid or missing
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        if (empty($payload) || empty($signature)) {
            throw new \Exception('Missing payload or signature');
        }

        $expectedSignature = base64_encode(hash('sha256', $this->secret_key . $payload, true));

        if (!hash_equals($expectedSignature, $signature)) {
            error_log("[Webhook] Invalid signature received.");
            throw new \Exception('Invalid signature');
        }

        return true;
    }
}
