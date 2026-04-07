# XPortal API Integration Guide

## Overview

The eTranzact Downtime Tracker authenticates users against the **XPortal Authentication API** hosted at eTranzact Ghana. This document covers how the API is called, how responses are processed, user provisioning, session management, and security considerations.

---

## API Endpoint

**URL:** `https://webpay.etranzactgh.com/XPortal/api/Authenticator`

**Method:** `POST`

**Content-Type:** `application/json`

---

## Request Format

### Encryption & Payload

Credentials are **RSA-encrypted** before transmission to prevent plaintext exposure over the network.

1. Build a JSON object with username and password:
   ```json
   {"username": "", "password": ""}
   ```

2. Encrypt using **RSA PKCS#1 padding** with eTranzact's public key
3. Base64-encode the encrypted bytes
4. Wrap in a JSON payload object:
   ```json
   {"payload": "P13BwM8EiYlynpuvRSM4zRWGr7dwe6Eyw01fIZmLfjq5enre4sF+PRcdp/..."}
   ```

### Public Key

The RSA public key (stored in `config/config.php` as `EXTERNAL_AUTH_PUBLIC_KEY`) is:

```
-----BEGIN PUBLIC KEY-----
****************************************************************
*****************************************************************
8******************************************************************
**********************************************************************8
-----END PUBLIC KEY-----
```

---

## Response Format

On successful authentication, the API returns HTTP 200 with:

```json
{
  "userExists": true,
  "userData": {
    "user_id": "10500000000002346",
    "email": "user.name@etranzact.com.gh",
    "firstname": "user",
    "lastname": "name",
    "username": "user.name",
    "type_id": "[0],[5],[31],...",
    "mobile": "",
    "status_id": "ACTIVE",
    "user_code": "[22],[2500000000000049]|2,...",
    "branchCode": "ALL",
    "first_logon": "1",
    "admin": "NONE",
    "temp_password": "false",
    "firstRoute": "/mobileMoney",
    "bankCode": "00000000",
    "requires2FA": false,
    "isLoggedIn": true,
    "company": "Etranzact Ghana Ltd.",
    "lastLogin": "2026-04-06 15:29:40.0"
  },
  "code": "00",
  "extra_info": "",
  "message": "Successful Login",
  "userId": "10500000000002346"
}
```

On invalid credentials, `userExists` is `false`:

```json
{
  "userExists": false,
  "userData": {},
  "code": "06",
  "message": "Invalid Payload"
}
```

---

## Implementation in Code

### Configuration (`config/config.php`)

Constants define the API endpoint, public key, and response field mappings:

```php
define('EXTERNAL_AUTH_API_URL', 'https://webpay.etranzactgh.com/XPortal/api/Authenticator');
define('EXTERNAL_AUTH_API_TIMEOUT', 10); // seconds
define('EXTERNAL_AUTH_PUBLIC_KEY', '...'); // RSA public key
define('EXTERNAL_AUTH_RES_SUCCESS', 'userExists');
define('EXTERNAL_AUTH_RES_USER', 'userData');
define('EXTERNAL_AUTH_RES_FIRSTNAME', 'firstname');
define('EXTERNAL_AUTH_RES_LASTNAME', 'lastname');
define('EXTERNAL_AUTH_RES_EMAIL', 'email');
define('EXTERNAL_AUTH_RES_ADMIN', 'admin');
define('EXTERNAL_AUTH_ROLE_NONE', 'NONE');
```

### API Call (`src/includes/auth.php` → `callExternalAuthApi()`)

```php
function callExternalAuthApi($username, $password)
{
    // 1. Build credentials JSON
    $credentials = json_encode([
        'username' => $username,
        'password' => $password,
    ]);

    // 2. RSA encrypt with public key
    $encrypted = '';
    if (!openssl_public_encrypt($credentials, $encrypted, EXTERNAL_AUTH_PUBLIC_KEY, OPENSSL_PKCS1_PADDING)) {
        error_log("External auth API: RSA encryption failed");
        return null;
    }

    // 3. Base64 encode and wrap
    $body = json_encode(['payload' => base64_encode($encrypted)]);

    // 4. POST to API
    $ch = curl_init(EXTERNAL_AUTH_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json, text/plain, */*'],
        CURLOPT_TIMEOUT => EXTERNAL_AUTH_API_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => (APP_ENV !== 'development'),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 5. Error handling
    if ($curlError) {
        error_log("External auth API curl error: {$curlError}");
        return null;
    }

    if ($httpCode !== 200) {
        error_log("External auth API returned HTTP {$httpCode}");
        return null;
    }

    // 6. Decode and return
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("External auth API returned invalid JSON");
        return null;
    }

    return $decoded;
}
```

---

## User Provisioning & Session Management

### Login Flow

When a user submits the login form (`public/login.php`):

1. **API call**: `callExternalAuthApi($username, $password)` → encrypted request
2. **On success** (`userExists == true`):
   - Extract `userData` from response
   - Call `findOrProvisionUser($apiUsername, $userData)`
   - Set session variables
   - Log successful login
3. **On failure** (`userExists == false` or network error):
   - Log failed attempt
   - Redirect with error message

### User Auto-Provisioning (`findOrProvisionUser()`)

If the user **does not exist** in the local database, they are automatically created:

```php
INSERT INTO users (
    username,
    email,
    full_name,           // firstname + lastname concatenated
    role,                // 'admin' if userData['admin'] != 'NONE', else 'user'
    is_active,           // 1 (always active on first login)
    changed_password,    // 1 (skips first-login password change for API users)
    password_hash,       // '' (empty — no local password stored)
    created_at,
    updated_at
)
VALUES (?, ?, ?, ?, 1, 1, '', NOW(), NOW())
```

**Why `password_hash = ''`?**
API-authenticated users have no local password. The `password_hash` field is marked `NOT NULL` in the schema, so an empty string is used as a placeholder. The login flow never checks this field for API users — it only calls the external API.

### Session Variables

On successful login, these are set:

```php
$_SESSION['user_id']          = $user['user_id'];          // local DB ID (auto-incremented)
$_SESSION['username']         = $user['username'];         // from API: "user.Opata"
$_SESSION['full_name']        = $user['full_name'];        // from API: "user Opata"
$_SESSION['role']             = $user['role'];             // 'admin' or 'user'
$_SESSION['changed_password'] = $user['changed_password']; // always 1 for API users
$_SESSION['login_time']       = time();                    // for timeout tracking
```

**Session timeout**: 1 hour of inactivity (configurable via `SESSION_TIMEOUT` in `config.php`).

---

## User Data Syncing

If a user already exists in the local database and logs in again, their profile is optionally synced:

```php
// If API returned updated name or email
if ($apiFullName !== $user['full_name'] || ($apiEmail && $apiEmail !== $user['email'])) {
    UPDATE users SET
        full_name = ?,
        email = ?,
        updated_at = NOW()
    WHERE user_id = ?
}
```

This ensures the local database stays in sync with XPortal if user profiles change.

---

## Role Mapping

The API returns `userData['admin']` as either:
- `"NONE"` — regular user → local `role = 'user'`
- Any other value (e.g., `"ADMIN"`) → local `role = 'admin'`

Admin users in the system can access:
- User management (`users.php`)
- Role and permission configuration
- System settings and audit logs

---

## Error Handling & Logging

### Scenarios

| Scenario | Behavior | Log Entry |
|----------|----------|-----------|
| Invalid credentials | Return `userExists: false` | `login_failed` with API message |
| Network timeout | cURL error, return `null` | `login_failed` with curl error |
| API HTTP error | Non-200 response, return `null` | `login_failed` with HTTP code |
| JSON decode fail | Invalid response, return `null` | `login_failed` with JSON error |
| DB insert fail | User creation fails, return `false` | `login_failed` with reason |
| RSA encryption fail | Exception, return `null` | `login_failed` with OpenSSL error |

All failed attempts are logged to `activity_logs` table with IP address and user agent for audit.

---

## Security Considerations

### Encryption

- **RSA PKCS#1**: Used with a 2048-bit public key from eTranzact
- **Base64 encoding**: Converts binary encrypted bytes to safely transportable ASCII
- **HTTPS only**: API endpoint is HTTPS; credentials are never transmitted in plaintext

### Session Security

- **HTTPOnly cookies**: Session cookie cannot be accessed by JavaScript (prevents XSS token theft)
- **SameSite=Strict**: Cookie is only sent on same-origin requests (prevents CSRF)
- **Session regeneration**: `session_regenerate_id(true)` on login prevents session fixation
- **Activity timeout**: Session expires after 1 hour of inactivity

### Password Storage

- API users have **no local password** — `password_hash = ''`
- Password verification happens entirely on the XPortal side
- The local system cannot reset or change XPortal user passwords

---

## Testing the Integration

A temporary debug endpoint (`public/auth_debug.php`) was used during development to verify:
1. RSA encryption is correct
2. API is reachable and responding
3. User provisioning works

**This file should be deleted in production.** It contains test credentials and exposes internal logic.

---

## Future Enhancements

Potential improvements to consider:

1. **Token-based caching**: Cache user data for X minutes to reduce API calls on each login
2. **Two-factor authentication**: XPortal returns `requires2FA: false/true`; implement 2FA flow if API supports it
3. **Role sync**: Periodically sync role changes from XPortal to local DB
4. **Logout notification**: Optionally notify XPortal when user logs out of this system
5. **API versioning**: If XPortal changes response structure, handle graceful degradation

---

## Configuration Checklist

Before deploying to production:

- [ ] Verify `EXTERNAL_AUTH_API_URL` is correct (should be production XPortal URL, not dev)
- [ ] Confirm `APP_ENV` is set to `'production'` so SSL verification is enabled
- [ ] Test login with a real XPortal user account
- [ ] Verify user is created in local `users` table with correct name and email
- [ ] Check `activity_logs` table records login attempt
- [ ] Confirm `password_hash = ''` for new API-authenticated users
- [ ] Delete `public/auth_debug.php`
- [ ] Test session timeout (wait 1 hour + 1 minute, verify redirect to login)
- [ ] Verify admin role mapping (ask XPortal what value indicates admin)
