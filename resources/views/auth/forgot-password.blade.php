@extends('auth.layout')

@section('title', 'Forgot Password')
@section('heading', 'Forgot password')
@section('helper', 'Pag-enter sa imong username aron makadawat og verification code.')

@section('content')
<form action="{{ route('password.forgot.submit') }}" method="POST" id="forgot-form">
    @csrf

    @if (session('status'))
        <div class="mb-4 p-3 text-sm border-l-4 bg-teal-soft" style="border-left-color: var(--primary); color: var(--primary);" data-auto-dismiss data-auto-dismiss-delay="10000">
            <p class="font-medium text-sm">{{ session('status') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);" data-auto-dismiss data-auto-dismiss-delay="10000">
            <p class="font-medium text-sm">{{ $errors->first() }}</p>
        </div>
    @endif

    <div class="mb-4">
        <label for="username" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username') }}"
               class="auth-input" placeholder="Username" required autocomplete="username" autofocus>
    </div>

    <button type="submit" class="auth-btn">Send verification code</button>
</form>

<p class="text-center text-xs mt-5" style="color: var(--ink-muted);">
    <a href="{{ route('login') }}" class="font-medium" style="color: var(--primary); text-decoration: underline;">Back to sign in</a>
</p>
@endsection
