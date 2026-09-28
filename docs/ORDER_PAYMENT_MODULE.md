# Order & Payment Module

A demo order API with multiple payment gateways (Stripe, Razorpay, PayPal), built with the **Repository pattern**, **SOLID** principles, **DTOs**, **Form Requests**, and **idempotency keys**.

> **Demo scope:** no database tables. Orders, payments and idempotency keys are stored in Laravel's cache (`CACHE_DRIVER=file`). Payment gateways are fake and do not call the real provider APIs; each one has a comment showing where the real SDK call goes.

---

## Table of contents

1. [Folder structure](#folder-structure)
2. [Request flow](#request-flow)
3. [Layers and responsibilities](#layers-and-responsibilities)
4. [SOLID in this module](#solid-in-this-module)
5. [Idempotency](#idempotency)
6. [API reference](#api-reference)
7. [Testing with Bruno](#testing-with-bruno)
8. [How to extend](#how-to-extend)

---

## Folder structure

```
app/
├── DTOs/
│   ├── Idempotency/
│   │   └── IdempotentResponseDTO.php        # Stored response + request fingerprint
│   ├── Order/
│   │   ├── CreateOrderDTO.php               # Input to OrderService
│   │   ├── OrderDTO.php                     # Output of OrderRepository
│   │   └── OrderItemDTO.php
│   └── Payment/
│       ├── PaymentRequestDTO.php            # Input to a gateway (incl. idempotency key)
│       └── PaymentResultDTO.php             # Output of a gateway
├── Enums/
│   └── OrderStatus.php                      # pending | paid | failed
├── Exceptions/
│   └── UnsupportedPaymentMethodException.php
├── Http/
│   ├── Controllers/Api/
│   │   └── OrderController.php              # Thin: request in, JSON out
│   ├── Middleware/
│   │   └── EnsureIdempotency.php            # Idempotency-Key handling (alias: idempotent)
│   └── Requests/Order/
│       └── StoreOrderRequest.php            # Validation + toDTO()
├── Providers/
│   └── RepositoryServiceProvider.php        # Interface → implementation bindings
├── Repositories/
│   ├── Order/
│   │   ├── OrderRepository.php
│   │   └── OrderInterfaces/
│   │       ├── OrderRepositoryInterface.php
│   │       └── IdempotencyKeyRepositoryInterface.php
│   └── Payment/
│       ├── PaymentRepository.php
│       └── PaymentInterfaces/
│           ├── PaymentGatewayInterface.php
│           └── PaymentRepositoryInterface.php
└── Services/
    ├── Order/
    │   └── OrderService.php                 # Business logic
    └── Payment/
        ├── PaymentGatewayFactory.php        # Resolves a gateway by name
        └── Gateways/
            ├── StripePaymentGateway.php
            ├── RazorpayPaymentGateway.php
            └── PaypalPaymentGateway.php

config/payment.php                           # Registered gateways + default currency
routes/api.php                               # POST /api/orders, GET /api/orders/{id}
```

---

## Request flow

```
POST /api/orders  (Authorization: Bearer <jwt>, Idempotency-Key: <uuid>)
        │
        ▼
auth:api middleware ─────────────── 401 if no/invalid token
        │
        ▼
EnsureIdempotency middleware
        │  key seen + same body?  ──► replay stored response (Idempotent-Replayed: true)
        │  key seen + other body? ──► 422
        │  key in progress?       ──► 409
        ▼
StoreOrderRequest ───────────────── 422 on invalid input
        │  toDTO()
        ▼
OrderController::store(CreateOrderDTO)
        │
        ▼
OrderService::placeOrder()
        ├─► OrderRepositoryInterface::create()            → order (pending)
        └─► OrderService::pay()
              ├─► PaymentRepositoryInterface::findByIdempotencyKey()   (already paid? reuse)
              ├─► PaymentGatewayFactory::make('stripe')->charge()     (only if not found)
              ├─► PaymentRepositoryInterface::save()
              └─► OrderRepositoryInterface::updatePaymentStatus()     → paid | failed
        │
        ▼
JSON response: 201 (paid) or 402 (payment failed)
```

---

## Layers and responsibilities

| Layer | Class | Knows about | Does **not** know about |
|---|---|---|---|
| Validation | `StoreOrderRequest` | HTTP input rules | Storage, payments |
| HTTP | `OrderController` | Request / response | Business rules, storage |
| Business logic | `OrderService` | Interfaces only | HTTP, cache/DB, Stripe/Razorpay SDKs |
| Storage | `OrderRepository`, `PaymentRepository` | Cache | HTTP, payment providers |
| Payment provider | `*PaymentGateway` | One provider's API | Orders, storage |
| Wiring | `RepositoryServiceProvider` | Which class implements which interface | — |

### Where the repository is used

The repository is **only** used from the service layer (and the idempotency middleware), never from the controller:

```php
// app/Services/Order/OrderService.php
public function __construct(
    private readonly OrderRepositoryInterface $orders,      // interface, not OrderRepository
    private readonly PaymentRepositoryInterface $payments,
    private readonly PaymentGatewayFactory $gateways,
) {}
```

Laravel injects the concrete class based on the bindings:

```php
// app/Providers/RepositoryServiceProvider.php
public array $bindings = [
    OrderRepositoryInterface::class          => OrderRepository::class,
    IdempotencyKeyRepositoryInterface::class => OrderRepository::class,
    PaymentRepositoryInterface::class        => PaymentRepository::class,
];
```

### Why DTOs

- `CreateOrderDTO` carries validated input into the service, so the service never touches `Request`.
- `OrderDTO` is what every order repository returns. Swapping the cache for Eloquent does not change what callers receive.
- `PaymentRequestDTO` / `PaymentResultDTO` give every gateway the same input and output shape.

---

## SOLID in this module

| Principle | Where | How |
|---|---|---|
| **S** — Single Responsibility | Every class | Request validates, controller handles HTTP, service holds business logic, repository stores, each gateway talks to one provider. |
| **O** — Open/Closed | `config/payment.php`, `PaymentGatewayFactory` | Adding a gateway = new class + one config line. Validation, factory and service are not edited. |
| **L** — Liskov Substitution | Gateways, repositories | Any `PaymentGatewayInterface` works in `OrderService`; any `OrderRepositoryInterface` implementation returns the same `OrderDTO`. |
| **I** — Interface Segregation | `OrderInterfaces/` | `OrderRepository` implements two small interfaces. `OrderService` depends only on `OrderRepositoryInterface`; the middleware only on `IdempotencyKeyRepositoryInterface`. |
| **D** — Dependency Inversion | `OrderService`, `EnsureIdempotency` | High-level code depends on interfaces; `RepositoryServiceProvider` picks the implementation. |

---

## Idempotency

Two independent layers make it safe for clients to retry.

### 1. Order level — `Idempotency-Key` header

Handled by `EnsureIdempotency` middleware on `POST /api/orders`.

| Situation | Response |
|---|---|
| Header missing or longer than 255 chars | `400` |
| First request with a key | Runs normally; response stored for 24 h |
| Same key, same body | Stored response replayed with header `Idempotent-Replayed: true` — no new order, no new charge |
| Same key, different body | `422` — key already used with a different request |
| Same key while the first request is still running | `409` — already being processed |
| Response was `422` (validation) or `5xx` | Not stored, so the client can fix the body or retry with the same key |

Details:

- Keys are scoped per **user + method + path**, so two users can send the same key without collisions.
- The body is fingerprinted with SHA-256 to detect key reuse with a different payload.
- A cache lock (30 s) prevents two concurrent requests from both creating an order.

### 2. Payment level — per-order payment key

Handled by `OrderService::pay()`.

- Each order's payment key is `order-{orderId}-payment`.
- `PaymentRepository` is checked first. If a result is already recorded for that key, the gateway is **not** called again.
- The same key is passed to the gateway in `PaymentRequestDTO::$idempotencyKey`, so the provider also deduplicates:

| Gateway | Real-world mechanism |
|---|---|
| Stripe | `['idempotency_key' => $key]` request option |
| PayPal | `PayPal-Request-Id: <key>` header |
| Razorpay | `receipt => $key`, look up existing order by receipt before creating |

- `pay()` also returns immediately if the order is already `paid`.

In the demo, gateways derive the transaction id from the key, so the same key always yields the same transaction id.

---

## API reference

All endpoints require `Authorization: Bearer <access_token>` from `POST /api/auth/login`.

### `POST /api/orders`

**Headers**

| Header | Required | Example |
|---|---|---|
| `Authorization` | yes | `Bearer eyJ0eXAi...` |
| `Idempotency-Key` | yes | `3f2b8c1e-5d6a-4e7f-9a0b-1c2d3e4f5a6b` |
| `Accept` | recommended | `application/json` |

**Body**

```json
{
  "payment_method": "stripe",
  "currency": "USD",
  "items": [
    { "product_id": 1, "name": "Keyboard", "quantity": 2, "unit_price": 49.99 },
    { "product_id": 2, "name": "Mouse", "quantity": 1, "unit_price": 19.5 }
  ]
}
```

| Field | Rules |
|---|---|
| `payment_method` | required, one of the keys in `config/payment.php` (`stripe`, `razorpay`, `paypal`) |
| `currency` | optional, 3 letters, defaults to `PAYMENT_DEFAULT_CURRENCY` (`USD`) |
| `items` | required, array, at least 1 |
| `items.*.product_id` | required, integer ≥ 1 |
| `items.*.name` | required, string ≤ 255 |
| `items.*.quantity` | required, integer ≥ 1 |
| `items.*.unit_price` | required, numeric ≥ 0.01 |

**Response `201`**

```json
{
  "message": "Order placed successfully.",
  "data": {
    "id": "79a38310-fd23-424a-aa15-fb10b6de5e17",
    "user_id": 1,
    "status": "paid",
    "payment_method": "stripe",
    "currency": "USD",
    "total_amount": 119.48,
    "items": [
      { "product_id": 1, "name": "Keyboard", "quantity": 2, "unit_price": 49.99, "subtotal": 99.98 },
      { "product_id": 2, "name": "Mouse", "quantity": 1, "unit_price": 19.5, "subtotal": 19.5 }
    ],
    "transaction_id": "pi_22a58bfb10b2149abd8db1cc",
    "created_at": "2026-09-28T06:15:06+00:00"
  }
}
```

**Other responses:** `400` missing key · `401` unauthenticated · `402` payment failed · `409` key in progress · `422` validation error or key reused with different body.

### `GET /api/orders/{id}`

Returns `200` with `{ "data": { ...order } }`, or `404` if the order does not exist or belongs to another user.

---

## Testing with Bruno

Collection: `bruno/api-project`, environment: `local`.

1. Run the seeder once: `php artisan migrate --seed` (creates `nahid@gmail.com` / `12345678`).
2. **Auth → login** — saves the JWT to `accessToken`.
3. **Orders → create-order** — generates `orderIdempotencyKey` if empty, creates the order, saves `orderId`.
4. Send **create-order** again — you get the same order back with `Idempotent-Replayed: true`.
5. **Orders → get-order** — fetches the order by `orderId`.
6. To create a **new** order, clear `orderIdempotencyKey` in the environment first.

---

## How to extend

### Add a new payment gateway (e.g. bKash)

1. Create the class:

   ```php
   // app/Services/Payment/Gateways/BkashPaymentGateway.php
   class BkashPaymentGateway implements PaymentGatewayInterface
   {
       public function charge(PaymentRequestDTO $payment): PaymentResultDTO
       {
           // call bKash API, pass $payment->idempotencyKey as the merchant invoice number
       }
   }
   ```

2. Register it in `config/payment.php`:

   ```php
   'gateways' => [
       // ...
       'bkash' => BkashPaymentGateway::class,
   ],
   ```

Nothing else changes — validation, the factory and `OrderService` pick it up automatically.

### Move orders to a real database

1. Create an `orders` migration and an `Order` model.
2. Create `app/Repositories/Order/EloquentOrderRepository.php` implementing `OrderRepositoryInterface`, returning `OrderDTO`.
3. Change one line in `RepositoryServiceProvider`:

   ```php
   OrderRepositoryInterface::class => EloquentOrderRepository::class,
   ```

The controller, service and middleware stay untouched.
