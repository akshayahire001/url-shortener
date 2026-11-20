<?php

namespace App\Http\Controllers;

use App\Contracts\ShortUrlServiceInterface;
use App\Http\Requests\StoreShortUrlRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShortUrlController extends Controller
{
    public function __construct(private readonly ShortUrlServiceInterface $shortUrls) {}

    public function index(Request $request): View
    {
        $error = null;

        try {
            $shortUrls = $this->shortUrls->listFor($request->user());
        } catch (AuthorizationException $exception) {
            $shortUrls = collect();
            $error = $exception->getMessage();
        }

        return view('short-urls.index', [
            'shortUrls' => $shortUrls,
            'error' => $error,
        ]);
    }

    public function create(Request $request): View
    {
        return view('short-urls.create', [
            'blockedReason' => $request->user()->creationBlockedReason(),
        ]);
    }

    public function store(StoreShortUrlRequest $request): RedirectResponse
    {
        try {
            $shortUrl = $this->shortUrls->create(
                $request->user(),
                $request->validated('original_url')
            );
        } catch (AuthorizationException $exception) {
            return redirect()
                ->route('short-urls.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('short-urls.index')
            ->with('status', 'Short URL saved. Code: '.$shortUrl->code.'. It is not publicly resolvable.');
    }
}
