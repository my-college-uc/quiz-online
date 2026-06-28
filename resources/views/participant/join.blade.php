@extends('layouts.app')

@section('title', 'Join')

@section('content')
    <p>Masukkan PIN, lalu nama panggilan.</p>
@endsection

@section('nav')
    <a href="{{ route('participant.lobby') }}">Gabung</a> |
    <a href="{{ route('landing') }}">Kembali</a>
@endsection
