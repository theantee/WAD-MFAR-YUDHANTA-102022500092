@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="page-wrap auth-wrap">
    <div class="auth-card">
        <p class="section-eyebrow">AKUN SHOPKU</p>
        <h1>Masuk</h1>
        <p class="auth-sub">Masuk untuk checkout, menghitung ongkir, dan melihat riwayat pesananmu.</p>
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if (session('url.intended') && ! $errors->any() && ! session('status'))<div class="notice">Silakan masuk atau daftar dulu untuk melanjutkan.</div>@endif
        @if ($errors->any())<div class="error-notice">@foreach ($errors->all() as $pesan){{ $pesan }}@if (! $loop->last)<br>@endif @endforeach</div>@endif
        <form class="checkout-form auth-form" method="post" action="{{ url('/masuk') }}">
            @csrf
            <label>Email<input type="email" name="email" required autocomplete="email" value="{{ old('email') }}"></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="button button-primary button-full" type="submit">Masuk</button>
        </form>
        <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
    </div>
</div>
@endsection
