<?php

declare(strict_types=1);

namespace EmailAlias;

class Client
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeoutSeconds;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://emailalias.io',
        int $timeoutSeconds = 30
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('apiKey is required');
        }
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSeconds = $timeoutSeconds;
    }

    // ── Low-level transport ──────────────────────────────────────────────
    /**
     * @param array<string, mixed>|null $body
     * @return mixed
     */
    private function request(string $method, string $path, ?array $body = null)
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) {
            throw new EmailAliasException('Failed to initialise cURL');
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new EmailAliasException('Network error: ' . $err);
        }

        if ($status === 204) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $decoded = ['detail' => (string) $raw];
        }

        if ($status >= 200 && $status < 300) {
            return $decoded;
        }

        $detail = $decoded['detail'] ?? json_encode($decoded);
        $message = is_string($detail) ? $detail : json_encode($detail);

        switch ($status) {
            case 401:
                throw new AuthenticationException($message, $status);
            case 404:
                throw new NotFoundException($message, $status);
            case 429:
                throw new RateLimitException($message, $status);
            default:
                throw new EmailAliasException($message, $status);
        }
    }

    // ── Aliases ───────────────────────────────────────────────────────────
    /** @return array<int, array<string, mixed>> */
    public function listAliases(): array
    {
        return $this->request('GET', '/api/aliases');
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createAlias(array $options = []): array
    {
        $body = array_merge(['alias_type' => 'random'], $options);
        return $this->request('POST', '/api/aliases', $body);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function updateAlias(string $aliasId, array $options): array
    {
        return $this->request('PATCH', '/api/aliases/' . $aliasId, $options);
    }

    public function deleteAlias(string $aliasId): void
    {
        $this->request('DELETE', '/api/aliases/' . $aliasId);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAvailableDomains(): array
    {
        return $this->request('GET', '/api/aliases/domains');
    }

    // ── Destinations ──────────────────────────────────────────────────────
    /** @return array<int, array<string, mixed>> */
    public function listDestinations(): array
    {
        return $this->request('GET', '/api/destinations');
    }

    /** @return array<string, mixed> */
    public function addDestination(string $email): array
    {
        return $this->request('POST', '/api/destinations', ['email' => $email]);
    }

    /** @return array<string, mixed> */
    public function resendDestinationVerification(string $destinationId): array
    {
        return $this->request('POST', '/api/destinations/' . $destinationId . '/resend');
    }

    public function deleteDestination(string $destinationId): void
    {
        $this->request('DELETE', '/api/destinations/' . $destinationId);
    }

    // ── Send email ────────────────────────────────────────────────────────
    /**
     * @return array<string, mixed>
     */
    public function sendEmail(
        string $aliasId,
        string $toEmail,
        string $subject,
        string $body,
        ?string $htmlBody = null
    ): array {
        $payload = [
            'alias_id' => $aliasId,
            'to_email' => $toEmail,
            'subject' => $subject,
            'body' => $body,
        ];
        if ($htmlBody !== null) {
            $payload['html_body'] = $htmlBody;
        }
        return $this->request('POST', '/api/send-email', $payload);
    }

    // ── Custom Domains ────────────────────────────────────────────────────
    /** @return array<int, array<string, mixed>> */
    public function listDomains(): array
    {
        return $this->request('GET', '/api/domains');
    }

    /** @return array<string, mixed> */
    public function addDomain(string $domainName): array
    {
        return $this->request('POST', '/api/domains', ['domain_name' => $domainName]);
    }

    /** @return array<string, mixed> */
    public function verifyDomain(string $domainId): array
    {
        return $this->request('POST', '/api/domains/' . $domainId . '/verify');
    }

    public function deleteDomain(string $domainId): void
    {
        $this->request('DELETE', '/api/domains/' . $domainId);
    }

    // ── Analytics ─────────────────────────────────────────────────────────
    /** @return array<string, mixed> */
    public function getDashboardStats(): array
    {
        return $this->request('GET', '/api/analytics/dashboard');
    }

    /** @return array<string, mixed> */
    public function listLogs(int $page = 1, int $perPage = 25): array
    {
        return $this->request('GET', '/api/analytics/logs?page=' . $page . '&per_page=' . $perPage);
    }

    /** @return array<string, mixed> */
    public function listExposureEvents(int $page = 1, int $perPage = 25): array
    {
        return $this->request('GET', '/api/analytics/exposure?page=' . $page . '&per_page=' . $perPage);
    }
}
