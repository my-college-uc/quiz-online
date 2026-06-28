@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <p>Form daftar akun host.</p>
@endsection

@section('nav')
    <a href="{{ route('host.dashboard') }}">Daftar &rarr; Dashboard</a> |
    <a href="{{ route('auth.login') }}">Masuk</a>
@endsection
