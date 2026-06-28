@extends('layouts.app')

@section('title', 'Host Dashboard')

@section('content')
    <p>Daftar kuis milik host (Kuis Saya).</p>
@endsection

@section('nav')
    <a href="{{ route('host.quiz-editor') }}">Buat / Edit Kuis</a> |
    <a href="{{ route('host.lobby') }}">Mulai Host</a> |
    <a href="{{ route('auth.login') }}">Logout</a>
@endsection
