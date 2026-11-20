@extends('layouts.app')

@section('title', 'Invite a user')

@section('content')
    <div class="card">
        <h1>Invite a user</h1>
        <p class="muted">Sales and Manager can join the existing company. Admin and Member cannot be assigned. A new company cannot be created.</p>
        <form method="POST" action="{{ route('invitations.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            <label for="role">Role</label>
            <select id="role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <label>
                <input class="checkbox" type="checkbox" name="new_company" value="1" @checked(old('new_company'))>
                Invite into a new company
            </label>
            <label for="company_name">New company name</label>
            <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}">
            <button type="submit">Send invitation</button>
        </form>
    </div>
@endsection
