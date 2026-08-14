<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

trait ApiResponse
{
    /**
     * Return a standardized success JSON response.
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function errorResponse(
        string $message,
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a standardized validation error JSON response from a ValidationException.
     */
    protected function validationErrorResponse(
        ValidationException $exception,
        ?string $defaultMessage = null,
        ?string $errorKey = null
    ): JsonResponse {
        $errors = $exception->errors();
        $message = $defaultMessage;

        if ($errorKey && isset($errors[$errorKey][0])) {
            $message = $errors[$errorKey][0];
        }

        return $this->errorResponse(
            message: $message ?? 'Validation failed.',
            errors: $errors,
            statusCode: 422
        );
    }
}
