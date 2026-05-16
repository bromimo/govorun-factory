<?php

namespace App\Services\Http;

/** DTO с черновиком HTTP-запроса для отправки через ConnectionRequestService. */
final class RequestDraft
{
    /**
     * @param  array<int, array{key: string, value: string}>  $headers
     * @param  array<int, array{key: string, value: string}>  $query
     * @param  array<string, mixed>  $stateSample
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly string $bodyMode = 'none',
        public readonly mixed $body = null,
        public readonly array $stateSample = [],
    ) {}
}
