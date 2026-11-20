@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="card">
        <h1>Log in</h1>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <label>
                <input class="checkbox" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                Remember me
            </label>
            <button type="submit">Log in</button>
        </form>
        @env(['local', 'testing'])
            <p class="muted">Seeded accounts use the password <strong>password</strong>.</p>
            <ul class="muted">
                <li>superadmin@example.com</li>
                <li>sales@example.com</li>
                <li>manager@example.com</li>
            </ul>
        @endenv
    </div>
@endsection
