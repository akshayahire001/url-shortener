@extends('layouts.app')

@section('title', 'Short URLs')

@section('content')
    <div class="card">
        <h1>Short URLs</h1>
        <p class="muted">{{ auth()->user()->visibilityNote() }}</p>
        @if (auth()->user()->canCreateShortUrls())
            <p><a class="button" href="{{ route('short-urls.create') }}">Create short URL</a></p>
        @else
            <p><a href="{{ route('short-urls.create') }}">Create short URL</a></p>
        @endif

        @if ($error)
            <p class="error">{{ $error }}</p>
        @elseif ($shortUrls->isEmpty())
            <p>No short URLs are visible for this account.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Original URL</th>
                        <th>Company</th>
                        <th>Created by</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shortUrls as $shortUrl)
                        <tr>
                            <td>{{ $shortUrl->code }}</td>
                            <td>{{ $shortUrl->original_url }}</td>
                            <td>{{ $shortUrl->company->name }}</td>
                            <td>{{ $shortUrl->user->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="muted">Opening /s/{code} does not redirect. Short URLs are not publicly resolvable.</p>
        @endif
    </div>
@endsection
