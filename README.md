1.composer create-project "laravel/laravel:^10.0" myapp
2.php artisan key:generat

3.APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:xxxxxxxxxxxxxxxx
APP_DEBUG=true
APP_URL=http://laravel10-app.test
4.Create MySQL database and rename it to env
5.composer require barryvdh/laravel-debugbar --dev


# Laravel Sanctum vs JWT

## Sanctum vs JWT Comparison

| Feature                             | Sanctum                            | JWT                                |
| ----------------------------------- | ---------------------------------- | ---------------------------------- |
| Laravel integration                 | ✅ Excellent                        | Good                               |
| Setup                               | ✅ Easy                             | More complex                       |
| Security                            | ✅ Strong when configured correctly | ✅ Strong when configured correctly |
| Token revocation                    | ✅ Easy                             | More difficult                     |
| SPA authentication                  | ✅ Excellent                        | Usually unnecessary                |
| Mobile/API authentication           | ✅ Yes                              | ✅ Yes                              |
| **Stateful authentication**         | ✅ Yes — SPA/session-cookie         | ❌ No                               |
| **Stateless authentication**        | ✅ Yes — Personal Access Tokens     | ✅ Yes — JWT                        |
| Token stored server-side            | ✅ Yes                              | ❌ Normally no                      |
| Access + refresh token architecture | ⚠️ Not JWT-based                   | ✅ Native pattern                   |
| Laravel ecosystem                   | ✅ First-party                      | Third-party                        |
| Recommended default                 | **✅ Yes**                          | Depends                            |

---

# 1. What is Laravel Sanctum?

Laravel Sanctum is Laravel's first-party authentication system for:

* SPA authentication
* Mobile applications
* REST APIs
* Personal access tokens

Sanctum can support **both stateful and stateless authentication**, depending on how it is used.

---

# 2. Sanctum Stateful Authentication

When Sanctum is used for SPA authentication, Laravel normally uses:

```text
Browser
   │
   │ Session Cookie
   ▼
Laravel
   │
   ▼
Session
   │
   ▼
Authenticated User
```

Example:

```http
Cookie: laravel_session=xxxxxxxx
```

The server maintains the authentication state through the Laravel session.

Therefore:

> **Sanctum SPA authentication = Stateful**

---

# 3. Sanctum Stateless Authentication

Sanctum can also be used with personal access tokens.

Create a token:

```php
$token = $user->createToken('mobile-app')->plainTextToken;
```

The client sends:

```http
Authorization: Bearer YOUR_TOKEN
```

Architecture:

```text
Mobile App
    │
    │ Bearer Token
    ▼
Laravel API
    │
    ▼
Sanctum Token
    │
    ▼
Database
    │
    ▼
User
```

No Laravel session is required.

Therefore:

> **Sanctum Personal Access Tokens = Stateless from the HTTP-session perspective**

However, Sanctum stores its tokens server-side in the database.

This makes token revocation straightforward.

---

# 4. JWT Authentication

JWT means:

> **JSON Web Token**

JWT is normally used as a **stateless authentication mechanism**.

Architecture:

```text
Mobile App
     │
     │ JWT
     ▼
Laravel API
     │
     ├── Verify Signature
     ├── Check Expiration
     └── Read User ID
```

A JWT can contain claims such as:

```json
{
    "sub": 123,
    "iat": 1727000000,
    "exp": 1727003600
}
```

The server verifies the JWT signature and expiration.

The token itself contains information that the server can validate.

---

# 5. Stateful vs Stateless

## Stateful

In stateful authentication, the server maintains authentication state.

Example:

```text
Browser
   │
   │ Session Cookie
   ▼
Laravel
   │
   ▼
Session Store
   │
   ▼
User
```

The session contains authentication state.

### Example

```http
Cookie: laravel_session=abc123
```

Laravel uses the session to identify the authenticated user.

---

## Stateless

In stateless authentication, each request contains the information required to authenticate the request.

Example:

```text
Mobile App
    │
    │ Bearer Token
    ▼
Laravel API
    │
    ▼
Authenticate Request
```

There is no PHP session required.

Example:

```http
Authorization: Bearer TOKEN
```

---

# 6. Important Difference

Do not confuse:

> **Stateless**

with:

> **No database**

Stateless authentication does **not** necessarily mean the application never uses a database.

For example, Sanctum Personal Access Tokens are stateless from the HTTP session perspective, but Sanctum stores tokens in the database.

```text
Sanctum

Request
   │
   ▼
Bearer Token
   │
   ▼
Database
   │
   ▼
Token/User
```

JWT normally works differently:

```text
JWT

Request
   │
   ▼
JWT
   │
   ▼
Verify Signature
   │
   ▼
Read Claims
```

The JWT itself carries claims and can be verified without storing the token in a database.

---

# 7. Sanctum Installation — Laravel 10

Go to your Laravel project:

```bash
cd C:\laragon\www\laravel10-app
```

Install Sanctum:

```bash
composer require laravel/sanctum
```

Publish Sanctum files:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Run migrations:

```bash
php artisan migrate
```

Clear Laravel cache:

```bash
php artisan optimize:clear
```

---

# 8. Configure User Model

Open:

```text
app/Models/User.php
```

Add:

```php
use Laravel\Sanctum\HasApiTokens;
```

Then:

```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ...
}
```

---

# 9. Create Sanctum Token

After authenticating a user:

```php
$token = $user->createToken('mobile-app')->plainTextToken;
```

Return it:

```php
return response()->json([
    'user' => $user,
    'token' => $token,
]);
```

Example response:

```json
{
    "user": {
        "id": 1,
        "name": "Nahid",
        "email": "nahid@example.com"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

# 10. Send Sanctum Token

The client sends:

```http
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxx
```

For example:

```bash
curl http://laravel10-app.test/api/user \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

# 11. Protect API Routes

Open:

```text
routes/api.php
```

Example:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

});
```

Only authenticated users can access these routes.

---

# 12. Logout / Revoke Sanctum Token

To revoke the current token:

```php
$request->user()->currentAccessToken()->delete();
```

To revoke all tokens:

```php
$request->user()->tokens()->delete();
```

This is one of the major advantages of Sanctum.

---

# 13. JWT Installation — Laravel 10

A commonly used Laravel JWT package is:

```text
php-open-source-saver/jwt-auth
```

Install:

```bash
composer require php-open-source-saver/jwt-auth
```

Publish configuration:

```bash
php artisan vendor:publish \
    --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

Generate JWT secret:

```bash
php artisan jwt:secret
```

This adds a JWT secret to your `.env`.

Example:

```env
JWT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxx
```

---

# 14. JWT Architecture

Typical JWT authentication:

```text
             LOGIN
               │
               ▼
        Check Email/Password
               │
               ▼
          Generate JWT
               │
               ▼
        Return Access Token
               │
               ▼
          Mobile/Web App
               │
               │
               ▼
     Authorization: Bearer JWT
               │
               ▼
         Laravel API
               │
               ▼
       Verify JWT Signature
               │
               ▼
        Authenticated User
```

---

# 15. Access Token and Refresh Token

A common JWT architecture uses two tokens:

```text
Access Token
    │
    ├── Short lifetime
    └── Used for API requests

Refresh Token
    │
    ├── Longer lifetime
    └── Used to obtain a new access token
```

Example:

```text
Login
  │
  ├── Access Token → 15 minutes
  │
  └── Refresh Token → 30 days
```

When the access token expires:

```text
Client
   │
   │ Refresh Token
   ▼
Laravel
   │
   ▼
New Access Token
```

The exact expiration times should be chosen according to the application's security requirements.

---

# 16. Sanctum vs JWT Architecture

## Sanctum

```text
                    Laravel
                       │
             ┌─────────┴─────────┐
             │                   │
          SPA Mode          API Token Mode
             │                   │
          Session              Token
          Cookie                │
             │                  ▼
             ▼              Token DB
          Stateful           Stateless
```

## JWT

```text
                 Laravel API
                      │
                      ▼
                    JWT
                      │
              ┌───────┴────────┐
              │                │
          Signature         Expiration
              │                │
              └───────┬────────┘
                      ▼
                Authenticated
                    User
```

---

# 17. Which One Should You Use?

For a normal Laravel application:

```text
Laravel
   │
   └── Sanctum
```

Sanctum is usually the simpler choice because it integrates directly with Laravel.

For example:

```text
Laravel ERP
Laravel E-commerce
Laravel Admin Panel
Laravel SPA
Laravel Mobile API
```

Sanctum is a strong default.

JWT becomes particularly relevant when you specifically need a JWT-based architecture, such as systems designed around self-contained signed tokens and access/refresh-token flows.

---

# 18. Interview Answer

### Question:

> Is Sanctum stateful or stateless?

### Answer:

> Laravel Sanctum supports both. When used for SPA authentication, Sanctum uses Laravel's session and cookies, so that authentication flow is stateful. When using Sanctum personal access tokens, requests are authenticated with bearer tokens rather than a Laravel session, making that flow stateless from the HTTP-session perspective. However, Sanctum stores personal access tokens server-side.

### Question:

> Is JWT stateful or stateless?

### Answer:

> JWT authentication is normally stateless. The client sends the JWT with each request, and the server verifies the token's signature and claims instead of maintaining a traditional server-side session.

---

# 19. Quick Memory Trick

```text
SANCTUM
│
├── SPA
│    └── Session/Cookie
│         └── STATEFUL
│
└── Personal Access Token
     └── Bearer Token
          └── STATELESS
```

```text
JWT
│
└── Signed Token
     │
     ├── Access Token
     └── Refresh Token
          │
          └── STATELESS
```

## Final Summary

| Authentication                | Stateful? | Token          | Server-side session | Token stored server-side |
| ----------------------------- | --------: | -------------- | ------------------: | -----------------------: |
| Sanctum SPA                   |     ✅ Yes | Session/Cookie |               ✅ Yes |          Session storage |
| Sanctum Personal Access Token |     ❌ No* | Bearer Token   |                ❌ No |                    ✅ Yes |
| JWT                           |      ❌ No | JWT            |                ❌ No |            ❌ Normally no |

> *** "Stateless" here means no server-side Laravel authentication session is used. Sanctum still stores and manages personal access tokens server-side.**



https://jwt-auth.readthedocs.io/en/develop/laravel-installation/