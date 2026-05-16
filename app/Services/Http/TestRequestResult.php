<?php

namespace App\Services\Http;

/** Результат тестового запроса (для возврата в UI). */
final class TestRequestResult
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly ?int $status,
        public readonly int $durationMs,
        public readonly array $headers,
        public readonly string $bodyRaw,
        public readonly mixed $bodyJson,
        public readonly bool $truncated,
        public readonly ?string $error,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'duration_ms' => $this->durationMs,
            'headers' => $this->headers,
            'body_raw' => $this->bodyRaw,
            'body_json' => $this->bodyJson,
            'truncated' => $this->truncated,
            'error' => $this->error,
        ];
    }
}
