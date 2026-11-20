@extends('layouts.app')

@section('title', 'Not found')

@section('content')
    <div class="card">
        <h1>Not found</h1>
        <p class="error">{{ $message ?? \App\Support\AssignmentRules::NOT_PUBLIC }}</p>
    </div>
@endsection
