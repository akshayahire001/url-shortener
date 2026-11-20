@extends('layouts.app')

@section('title', 'Create short URL')

@section('content')
    <div class="card">
        <h1>Create short URL</h1>
        @if ($blockedReason)
            <p class="error">{{ $blockedReason }}</p>
        @else
            <form method="POST" action="{{ route('short-urls.store') }}">
                @csrf
                <label for="original_url">Original URL</label>
                <input id="original_url" type="url" name="original_url" value="{{ old('original_url') }}" placeholder="https://example.com/page" required>
                <button type="submit">Save</button>
            </form>
        @endif
        <p><a href="{{ route('short-urls.index') }}">Back to the list</a></p>
    </div>
@endsection
