<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'URL Shortener')</title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; margin: 0; background: #f4f1ea; color: #221f1b; }
        header, main { max-width: 920px; margin: 0 auto; padding: 24px; }
        header { display: flex; justify-content: space-between; gap: 16px; align-items: center; }
        nav { display: flex; gap: 16px; align-items: center; }
        a { color: #0b4f6c; }
        .card { background: #fff; border: 1px solid #ddd4c6; padding: 20px; }
        label { display: block; margin-top: 14px; font-size: 14px; }
        input, select { width: 100%; max-width: 480px; padding: 8px; box-sizing: border-box; }
        input.checkbox { width: auto; }
        button, .button { display: inline-block; margin-top: 16px; padding: 8px 14px; background: #0b4f6c; color: #fff; border: 0; text-decoration: none; cursor: pointer; }
        button.link { background: transparent; color: #0b4f6c; padding: 0; margin: 0; font: inherit; }
        .error { color: #8a1f11; }
        .status { color: #0d5c2e; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e4dccf; text-align: left; padding: 8px; vertical-align: top; }
        .muted { color: #5e584f; font-size: 14px; }
    </style>
</head>
<body>
    <header>
        <div>
            <strong>URL Shortener</strong>
            @auth
                <div class="muted">{{ auth()->user()->name }} · {{ auth()->user()->roleName() }}</div>
            @endauth
        </div>
        <nav>
            @auth
                <a href="{{ route('short-urls.index') }}">Short URLs</a>
                @if (auth()->user()->canInvite())
                    <a href="{{ route('invitations.create') }}">Invite</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link" type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Log in</a>
            @endauth
        </nav>
    </header>
    <main>
        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="error">{{ session('error') }}</p>
        @endif
        @if (session('invite_url'))
            <p>Invitation link: <a href="{{ session('invite_url') }}">{{ session('invite_url') }}</a></p>
        @endif
        @if ($errors->any())
            <div class="error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
