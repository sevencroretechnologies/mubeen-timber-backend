<?php

namespace App\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    protected int $statusCode;

    protected ?array $errors;

    public function __construct(
        string $message = 'Authentication failed',
        int $statusCode = 401,
        ?array $errors = null
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'data' => null,
            'message' => $this->getMessage(),
        ];

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }
}
