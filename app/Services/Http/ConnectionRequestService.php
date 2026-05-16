<?php

namespace App\Services\Http;

use App\Models\BotConnection;
use App\Enums\ConnectionAuthType;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;

/** Выполняет тестовый HTTP-запрос от лица фабрики через BotConnection. */
class ConnectionRequestService
{
    private const BODY_LIMIT_BYTES = 1024 * 1024;

    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly SsrfGuard $ssrf,
        private readonly TemplatePlaceholderRenderer $tpl,
    ) {}

    public function execute(BotConnection $connection, RequestDraft $draft): TestRequestResult
    {
        $url = $this->buildUrl($connection, $draft);

        if (! $this->ssrf->isAllowed($url)) {
            return $this->errorResult('URL запрещён политикой безопасности (приватная сеть)');
        }

        $client = Http::timeout(self::TIMEOUT_SECONDS);
        $client = $this->applyAuth($client, $connection);
        $client = $this->applyHeaders($client, $connection, $draft);

        $started = microtime(true);

        try {
            $response = $this->dispatch($client, $draft, $url);
        } catch (ConnectionException $e) {
            return $this->errorResult('Ошибка соединения: '.$e->getMessage());
        }

        $duration = (int) ((microtime(true) - $started) * 1000);

        return $this->buildResult($response, $duration);
    }

    private function buildUrl(BotConnection $connection, RequestDraft $draft): string
    {
        $path = $this->tpl->render($draft->path, $draft->stateSample);
        $base = rtrim($connection->base_url, '/');
        $path = '/'.ltrim($path, '/');
        $url = $base.$path;

        $query = [];

        foreach ($draft->query as $item) {
            $key = $this->tpl->render($item['key'] ?? '', $draft->stateSample);
            $value = $this->tpl->render($item['value'] ?? '', $draft->stateSample);
            if ($key !== '') {
                $query[$key] = $value;
            }
        }

        if ($connection->auth_type === ConnectionAuthType::ApiKey) {
            $cfg = $connection->auth_config ?? [];
            if (($cfg['in'] ?? 'header') === 'query' && ! empty($cfg['key'])) {
                $query[$cfg['key']] = $cfg['value'] ?? '';
            }
        }

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    private function applyAuth(PendingRequest $client, BotConnection $connection): PendingRequest
    {
        $cfg = $connection->auth_config ?? [];

        return match ($connection->auth_type) {
            ConnectionAuthType::Bearer => $client->withToken($cfg['token'] ?? ''),
            ConnectionAuthType::Basic => $client->withBasicAuth($cfg['login'] ?? '', $cfg['password'] ?? ''),
            ConnectionAuthType::ApiKey => ($cfg['in'] ?? 'header') === 'header' && ! empty($cfg['key'])
                ? $client->withHeaders([$cfg['key'] => $cfg['value'] ?? ''])
                : $client,
            ConnectionAuthType::None => $client,
        };
    }

    private function applyHeaders(PendingRequest $client, BotConnection $connection, RequestDraft $draft): PendingRequest
    {
        $headers = [];

        foreach ($connection->default_headers ?? [] as $h) {
            if (! empty($h['key'])) {
                $headers[$h['key']] = $h['value'] ?? '';
            }
        }

        foreach ($draft->headers as $h) {
            $key = $this->tpl->render($h['key'] ?? '', $draft->stateSample);
            $value = $this->tpl->render($h['value'] ?? '', $draft->stateSample);
            if ($key !== '') {
                $headers[$key] = $value;
            }
        }

        return $headers === [] ? $client : $client->withHeaders($headers);
    }

    private function dispatch(PendingRequest $client, RequestDraft $draft, string $url): Response
    {
        if ($draft->bodyMode === 'json' && is_string($draft->body)) {
            $rendered = $this->tpl->render($draft->body, $draft->stateSample);
            $decoded = json_decode($rendered, true);

            return $this->callMethod($client, $draft->method, $url, $decoded ?? []);
        }

        if ($draft->bodyMode === 'form' && is_array($draft->body)) {
            $form = [];
            foreach ($draft->body as $item) {
                $form[$this->tpl->render($item['key'] ?? '', $draft->stateSample)]
                    = $this->tpl->render($item['value'] ?? '', $draft->stateSample);
            }

            return $this->callMethod($client->asForm(), $draft->method, $url, $form);
        }

        return $this->callMethod($client, $draft->method, $url);
    }

    private function callMethod(PendingRequest $client, string $method, string $url, array $data = []): Response
    {
        return match (strtoupper($method)) {
            'GET' => $client->get($url, $data),
            'POST' => $client->post($url, $data),
            'PUT' => $client->put($url, $data),
            'PATCH' => $client->patch($url, $data),
            'DELETE' => $client->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    private function buildResult(Response $response, int $duration): TestRequestResult
    {
        $raw = $response->body();
        $truncated = false;

        if (strlen($raw) > self::BODY_LIMIT_BYTES) {
            $raw = substr($raw, 0, self::BODY_LIMIT_BYTES);
            $truncated = true;
        }

        $json = null;

        if (! $truncated) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        return new TestRequestResult(
            status: $response->status(),
            durationMs: $duration,
            headers: collect($response->headers())->mapWithKeys(fn ($v, $k) => [$k => is_array($v) ? implode(', ', $v) : $v])->all(),
            bodyRaw: $raw,
            bodyJson: $json,
            truncated: $truncated,
            error: null,
        );
    }

    private function errorResult(string $message): TestRequestResult
    {
        return new TestRequestResult(
            status: null,
            durationMs: 0,
            headers: [],
            bodyRaw: '',
            bodyJson: null,
            truncated: false,
            error: $message,
        );
    }
}
