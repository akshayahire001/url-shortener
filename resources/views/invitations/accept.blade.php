@extends('layouts.app')

@section('title', 'Accept invitation')

@section('content')
    <div class="card">
        <h1>Accept invitation</h1>
        <p class="muted">{{ $invitation->email }} · {{ $invitation->role->name }} · {{ $invitation->company->name }}</p>
        <form method="POST" action="{{ route('invitations.accept.store', $invitation->token) }}">
            @csrf
            <label for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required>
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            <button type="submit">Create account</button>
        </form>
    </div>
@endsection
