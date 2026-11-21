@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<div class="page-wrap auth-wrap">
    <div class="auth-card">
        <p class="section-eyebrow">AKUN SHOPKU</p>
        <h1>Buat akun</h1>
        <p class="auth-sub">Daftar untuk berbelanja dan menyimpan alamat pengirimanmu.</p>
        @if ($errors->any())<div class="error-notice">@foreach ($errors->all() as $pesan){{ $pesan }}@if (! $loop->last)<br>@endif @endforeach</div>@endif
        <form class="checkout-form auth-form" method="post" action="{{ url('/daftar') }}">
            @csrf
            <label>Nama lengkap<input type="text" name="name" required maxlength="100" autocomplete="name" value="{{ old('name') }}"></label>
            <label>Email<input type="email" name="email" required maxlength="255" autocomplete="email" value="{{ old('email') }}"></label>
            <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"><span class="small-muted">Minimal 8 karakter.</span></label>
            <button class="button button-primary button-full" type="submit">Daftar sekarang</button>
        </form>
        <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </div>
</div>
@endsection
