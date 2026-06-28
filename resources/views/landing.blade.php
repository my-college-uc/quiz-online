@extends('layouts.app')

@section('title', 'Landing')

@section('content')
    <p>Halaman awal. Masukkan PIN untuk bergabung, atau masuk/daftar sebagai host.</p>
@endsection

@section('nav')
    <a href="{{ route('auth.login') }}">Masuk</a> |
    <a href="{{ route('auth.register') }}">Daftar</a> |
    <a href="{{ route('participant.join') }}">Gabung (PIN)</a>
@endsection
