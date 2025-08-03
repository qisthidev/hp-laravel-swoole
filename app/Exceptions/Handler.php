<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle API exceptions
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Handle API exceptions
     */
    private function handleApiException(Throwable $e, $request): JsonResponse
    {
        $requestId = uniqid('req_');

        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => $requestId,
                ],
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'The requested resource was not found.',
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => $requestId,
                ],
            ], 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'error' => [
                    'code' => 'ENDPOINT_NOT_FOUND',
                    'message' => 'The requested endpoint was not found.',
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => $requestId,
                ],
            ], 404);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required.',
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => $requestId,
                ],
            ], 401);
        }

        if ($e instanceof InvalidSortQuery || $e instanceof InvalidFilterQuery) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_QUERY',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => $requestId,
                ],
            ], 400);
        }

        // Handle other exceptions
        if (config('app.debug')) {
            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'request_id' => $requestId,
                ],
            ], 500);
        }

        return response()->json([
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'An internal server error occurred.',
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => $requestId,
            ],
        ], 500);
    }
}