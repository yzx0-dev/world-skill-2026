<?php

namespace App\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithApiJson
{
    protected function success(mixed $data = [], array $meta = [], int $status = 200): JsonResponse
    {
        // Standard response shape required by the test project.
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
