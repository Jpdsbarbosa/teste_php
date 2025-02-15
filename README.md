## Installing

```bash
    composer require teste456789/sdk
```

## Checkout API

### Setup


To use the SDK, you will need an API token generated by the Nox system.

```php
use Nox\Sdk\CheckoutApi;

$token = 'your_api_token';

$checkoutApi = new CheckoutApi($token);
```

### Using Checkout API methods

#### Listing Checkouts

Gets a list of checkouts based on the given query parameters.

`getCheckouts(array $queryParams = [])`

Usage Example:

```php
$response = $checkoutApi->getCheckouts([
    'page' => 1,
    'created_at_start' => '2024-04-08',
    'created_at_end' => '2024-05-08',
]);
print_r($response);
```

Response:

```php
[
    'checkouts' => [
        [
            'id' => 1,
            'image' => 'base64image',
            'image_type' => 'image/png',
            'description' => 'Teste',
            'price' => '50.00',
            'redirect_url' => 'https://example.com',
            'is_enabled' => true,
            'theme_color' => 'custom',
            'payment_method' => 'all',
            'colors' => [
                'primary' => '#F2F2F2',
                'secondary' => '#FFFFFF',
                'text' => '#334155',
                'button' => '#3F438C',
                'textButton' => '#FFFFFF'
            ],
            'created_at' => '2024-06-13T18:41:20.329Z',
            'url_id' => 'f25f51c9-0a90-41c9-9571-035813ae0000'
        ]
    ],
    'current_page' => 1,
    'total_pages' => 1
]
```

#### Retrieving Checkout

Retrieves a checkout using its `urlId` identifier.

`getCheckout(string $urlId)`

Usage Example:

```php
$checkout = $checkoutApi->getCheckout('123');
print_r($checkout);
```

Response:

```php
[
    'image' => 'base64image',
    'description' => 'Produto teste',
    'price' => '10.00',
    'redirect_url' => 'https://example.com',
    'is_enabled' => true,
    'payment_method' => 'all',
    'url_id' => '0989d6cc-b02c-493b-b953-dab39dbc1111',
    'colors' => [
        'primary' => '#F2F2F2',
        'secondary' => '#FFFFFF',
        'text' => '#334155',
        'button' => '#3F438C',
        'textButton' => '#FFFFFF'
    ],
    'code' => 'string',
    'txid' => 'string'
]
```

#### Creating Checkout

Create a new checkout with the provided data.
`createCheckout(array $data, $file = null)`

Usage Example:

```php
$data = [
    'colors' => [
        'primary' => '#000000',
        'secondary' => '#FFFFFF',
        'text' => '#333333',
        'button' => '#FF0000',
        'textButton' => '#FFFFFF'
    ],
    'price' => 1000,
    'description' => 'Produto teste',
    'redirect_url' => 'https://example.com',
    'payment_method' => 'credit_card',
    'is_enabled' => true,
    'theme_color' => '#FF00FF'
];
$fileContent = fopen('path\to\file', 'r');
$response = $checkoutApi->createCheckout($data, $fileContent);
print_r($response);
```

Response:

```php
[
    'id' => 1,
    'url_id' => '87ff1499-dab3-44e6-9538-3cbb05b66666'
]
```

#### Updating Checkout

Updates data from an existing checkout.

`updateCheckout(int $id, array $data, $file = null)`

Usage Example:

```php
$updateData = [
    'price' => 1200,
    'change_image' => true
];
$fileContent = fopen('path\to\file', 'r');
$response = $checkoutApi->updateCheckout(123, $updateData, $fileContent);
print_r($response);
```

Response:

```php
[
    'detail' => 'Checkout updated successfully.'
]
```

#### Error Handling

In case of errors, the methods will throw an exception with a detailed message

```php
try {
    $checkout = $checkoutApi->getCheckout('123');
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

## V2 API

### Setup

To use the SDK, you will need an API token and a merchant resgistration. To achieve them, you must get in contact with Nox, so we can provide them to you.

```php
use Nox\Sdk\V2Api;

$token = 'your_api_token';

$v2Api = new V2Api($token);
```

### Using V2 API methods

#### Get Account.

Retrieves the account information associated with the API token.
`getAccount()`

Usage Example:

```php
$account = $v2Api->getAccount();
print_r($account);
```

Response:

```php
[
    'name' => 'John Doe',
    'balance' => 1500.50
]
```

#### Create Payment Method

Creates a new payment using the specified method and details.
`createPayment(array $data)`

Usage Example:

```php
$paymentResponse = $v2Api->createPayment([
    'method' => 'PIX',
    'code' => '123456',
    'amount' => 1000
]);
print_r($paymentResponse);
```

Response:

```php
[
    'method' => 'PIX',
    'code' => '123456',
    'amount' => 1000,
    'qrcode' => 'https://api.example.com/qrcode',
    'qrcodebase64' => 'iVBORw0KGgoAAAANSUhEUgAA...',
    'copypaste' => '1234567890',
    'txid' => 'abc123',
    'Status' => 'WAITING_PAYMENT'
]
```

#### Create Cash-out Payment

Creates a new cash-out payment.
`createPaymentCashOut(array $data)`

Usage Example:

```php
$paymentResponse = $v2Api->createPaymentCashOut([
    'method' => 'PIX',
    'code' => '123456',
    'amount' => 1000,
    'type' => 'PIX_KEY',
    'pixkey' => 'your-pix-key',
]);
print_r($paymentResponse);
```

Response:

```php
[
    "Method" => "PIXOUT",
    "Status" => "SENT",
    "Code" => "123456",
    "TxID" => "abc123",
    "Amount" => 1000,
]
```

#### Retrieve Payment's Info

Fetches the details of a specific payment by its `code` or `txid`.
`getPayment(string $identifier)`

Usage Example:

```php
$payment = $v2Api->getPayment('payment-identifier');
print_r($paymentResponse);
```

Response:

```php
[
    "Method" => "PIX",
    "Status" => "PAID",
    "Code" => "123456",
    "TxID" => "abc123",
    "Amount" => 1000,
    "end2end" => "e2e123",
    "receipt" => "https://api.example.com/receipt"
]
```

#### Resend Webhook

Resends the webhook for a specific transaction.
`resendWebhook(string $txid)`

Usage Example:

```php
$v2Api->resendWebhook('txid');
```

#### Send Transactions Report

Generates and sends a transaction report based on the specified
filters to merchant email associated with the token in csv format.
`sendTransactionsReport(array $filters = [])`

Usage Example:

```php
$v2Api->sendTransactionsReport([
    'begin_date' => '2024-01-01'
    'end_data' => '2024-01-31',
    'method' => 'PIX',
    'status' => 'PAID'
]);
```

#### Create Credit Card Payment

Creates a new payment using a credit card.
`createCreditCardPayment(array $data)`

Usage Example:

```php
$creditCardPaymentResponse = $v2Api->createCreditCardPayment([
    'amount' => 1000,
    'email' => 'user@example.com',
    'code' => '123456',
    'name' => 'User Name',
    'cpf_cnpj' => '12345678910',
    'expired_url' => 'https://example.com/expired',
    'return_url' => 'https://example.com/return',
    'max_installments_value' => 200,
    'soft_descriptor_light' => 'Company name'
]);
print_r($creditCardPaymentResponse);
```

Response:

```php
[
    "id" => "cc123",
    "due_date" => "2024-12-31",
    "currency" => "BRL",
    "email" => "user@example.com",
    "status" => "pending",
    "total_cents" => 1000,
    "order_id" => "order123",
    "secure_id" => "secure123",
    "secure_url" => "https://api.example.com/secure",
    "total" => "10.00",
    "created_at_iso" => "2024-09-01T12:34:56Z"
]
```

#### Retrieve Credit Card Payment

Retrieves details of a specific credit card payment by its identifier `order_id`.
`getCreditCardPayment(string $identifier)`

Usage Example:

```php
$payment = $v2Api->getCreditCardPayment('credit-card-identifier');
print_r($payment);
```

Response:

```php
[
    "id" => 123,
    "status" => "PAID",
    "code" => "cc123",
    "txid" => "tx123",
    "amount" => 1000,
    "created_at" => "2024-09-01T12:34:56Z",
    "paid_at" => "2024-09-01T13:00:00Z",
    "canceled_at" => null,
    "customer_name" => "John Doe",
    "customer_email" => "john.doe@example.com",
    "customer_document" => "123.456.789-00",
    "merchant_id" => 456,
    "id_from_bank" => "bank123"
]
```

#### Error Handling

In case of errors, the methods will throw an exception with a detailed message

```php
try {
    $creditCardPayment = $v2Api->getCreditCardPayment('identifier');
} catch (\Exception $e) {
    echo $e->getMessage();
}
```
