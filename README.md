# emailalias-php

Official PHP client for the [EmailAlias.io](https://emailalias.io) REST API.

API access is a **Premium** feature. Generate a key from **Settings → API Keys** in the web dashboard.

## Install

```bash
composer require emailalias/emailalias
```

Requires PHP **8.1+** and the `curl` + `json` extensions.

## Quick start

```php
<?php
require 'vendor/autoload.php';

use EmailAlias\Client;

$client = new Client(getenv('EMAILALIAS_API_KEY'));

// Create an alias
$alias = $client->createAlias([
    'alias_type' => 'random',
    'label' => 'Shopping',
]);
echo $alias['alias_email'] . PHP_EOL; // e.g. "x7k9m@email91.com"

// List aliases
foreach ($client->listAliases() as $a) {
    echo $a['alias_email'] . ' → ' . $a['destination_email'] . PHP_EOL;
}

// Forward to a verified additional destination
$workAlias = $client->createAlias([
    'alias_type' => 'custom',
    'custom_code' => 'work-signup',
    'label' => 'Work',
    'destination_email' => 'work@mycompany.com', // must be verified on your account
]);

// Send email from an alias
$client->sendEmail(
    aliasId: $alias['id'],
    toEmail: 'recipient@example.com',
    subject: 'Hello',
    body: 'Sent from my alias.'
);

// Disable an alias
$client->updateAlias($alias['id'], ['active' => false]);
```

## Error handling

```php
use EmailAlias\Client;
use EmailAlias\AuthenticationException;
use EmailAlias\RateLimitException;
use EmailAlias\EmailAliasException;

try {
    $client->listAliases();
} catch (AuthenticationException $e) {
    // Invalid key, or account no longer Premium
} catch (RateLimitException $e) {
    // Respect X-RateLimit-Reset and retry
} catch (EmailAliasException $e) {
    error_log($e->status . ': ' . $e->getMessage());
}
```

## Configuration

```php
$client = new Client(
    apiKey: 'ea_live_xxx',
    baseUrl: 'https://api.emailalias.io', // override for staging / self-host
    timeoutSeconds: 30
);
```

## Available methods

| Method | Endpoint |
|---|---|
| `listAliases()` | `GET /api/aliases` |
| `createAlias($options)` | `POST /api/aliases` |
| `updateAlias($id, $options)` | `PATCH /api/aliases/{id}` |
| `deleteAlias($id)` | `DELETE /api/aliases/{id}` |
| `listAvailableDomains()` | `GET /api/aliases/domains` |
| `listDestinations()` | `GET /api/destinations` |
| `addDestination($email)` | `POST /api/destinations` |
| `resendDestinationVerification($id)` | `POST /api/destinations/{id}/resend` |
| `deleteDestination($id)` | `DELETE /api/destinations/{id}` |
| `sendEmail($aliasId, $toEmail, $subject, $body, $htmlBody=null)` | `POST /api/send-email` |
| `listDomains()` | `GET /api/domains` |
| `addDomain($name)` | `POST /api/domains` |
| `verifyDomain($id)` | `POST /api/domains/{id}/verify` |
| `deleteDomain($id)` | `DELETE /api/domains/{id}` |
| `getDashboardStats()` | `GET /api/analytics/dashboard` |
| `listLogs($page, $perPage)` | `GET /api/analytics/logs` |
| `listExposureEvents($page, $perPage)` | `GET /api/analytics/exposure` |

Full API reference: <https://emailalias.io/documentation>

## License

MIT
