<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        $this->renderable(function (Throwable $e, $request) {
            // Handle Spatie UnauthorizedException or 403 HTTP Exception
            if ($e instanceof UnauthorizedException || ($e instanceof HttpException && $e->getStatusCode() === 403)) {
                if ($request->expectsJson() || $request->is('livewire/*')) {
                    return response()->json([
                        'message' => __('Access denied'),
                        'error' => $e->getMessage() ?: __('You do not have permission to access this resource.')
                    ], 403);
                }
                return response()->view('errors.403', [
                    'message' => $e->getMessage() ?: __('You do not have permission to access this resource.'),
                    'code' => 403,
                    'exception' => $e,
                    'isAuthenticated' => auth()->check()
                ], 403);
            }

            // Handle 404 Not Found
            if ($e instanceof HttpException && $e->getStatusCode() === 404) {
                if ($request->expectsJson() || $request->is('livewire/*')) {
                    return response()->json([
                        'message' => __('Page not found'),
                        'error' => $e->getMessage() ?: __('The requested page does not exist.')
                    ], 404);
                }
                return response()->view('errors.404', [
                    'message' => $e->getMessage() ?: __('The requested page does not exist.'),
                    'code' => 404,
                    'exception' => $e,
                    'isAuthenticated' => auth()->check()
                ], 404);
            }

            // Handle other HTTP exceptions (e.g., 500, 429, etc.)
            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
                if ($request->expectsJson() || $request->is('livewire/*')) {
                    return response()->json([
                        'message' => __('An error occurred'),
                        'error' => $e->getMessage() ?: __('An unexpected error occurred.')
                    ], $status);
                }
                return response()->view("errors.generic", [
                    'message' => $e->getMessage() ?: __('An unexpected error occurred.'),
                    'code' => $status,
                    'exception' => $e,
                    'isAuthenticated' => auth()->check()
                ], $status);
            }

            // Let Laravel handle other exceptions
            return null;
        });
    }
}