@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <p>Form login host.</p>
@endsection

@section('nav')
    <a href="{{ route('host.dashboard') }}">Masuk &rarr; Dashboard</a> |
    <a href="{{ route('auth.register') }}">Daftar</a> |
    <a href="{{ route('landing') }}">Kembali</a>
@endsection
