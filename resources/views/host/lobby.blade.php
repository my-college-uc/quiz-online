@extends('layouts.app')

@section('title', 'Host Lobby')

@section('content')
    <p>Tampilkan Game PIN + daftar peserta yang bergabung.</p>
@endsection

@section('nav')
    <a href="{{ route('host.game') }}">Mulai</a> |
    <a href="{{ route('host.dashboard') }}">Keluar</a>
@endsection
