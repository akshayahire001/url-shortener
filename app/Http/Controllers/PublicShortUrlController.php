<?php

namespace App\Http\Controllers;

use App\Contracts\ShortUrlServiceInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicShortUrlController extends Controller
{
    public function __construct(private readonly ShortUrlServiceInterface $shortUrls) {}

    public function show(Request $request, string $code): Response
    {
        try {
            $this->shortUrls->denyPublicAccess($code);
        } catch (NotFoundHttpException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->view('short-urls.unavailable', [
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
