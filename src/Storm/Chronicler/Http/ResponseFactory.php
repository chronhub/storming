<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function is_array;

class ResponseFactory
{
    private null|MessageBag|array $errors = null;

    private ?string $message = null;

    private array $headers = [];

    private array $data = [];

    private int $status = 200;

    /**
     * @return $this
     */
    public function withStatusCode(int $status, ?string $message = null): self
    {
        $this->status = $status;
        $this->message = $message;

        return $this;
    }

    /**
     * @return $this
     */
    public function withHeader(string $header, mixed $value): self
    {
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return $this
     */
    public function withErrors(array|MessageBag $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function toResponse(Request $request): JsonResponse
    {
        $payload = [];

        if ($this->data !== []) {
            $payload = ['data' => $this->data];
        }

        if ($this->message) {
            $payload['message'] = $this->message;
        }

        if ($this->errors) {
            $payload['errors'] = is_array($this->errors) ? $this->errors : $this->errors->toArray();
        }

        return new JsonResponse($payload, $this->status, $this->headers);
    }
}
