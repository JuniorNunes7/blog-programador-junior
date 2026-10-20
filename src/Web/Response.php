<?php

declare(strict_types=1);

namespace Blog\Web;

final readonly class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public int $status,
        public string $body,
        public array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {
    }

    public function send(): never
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $this->body;
        exit;
    }
}
