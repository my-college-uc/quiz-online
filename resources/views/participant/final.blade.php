@extends('layouts.app')

@section('title', 'Participant Final')

@section('content')
    <p>Peringkat akhir + skor.</p>
@endsection

@section('nav')
    <a href="{{ route('participant.join') }}">Main Lagi</a> |
    <a href="{{ route('landing') }}">Keluar</a>
@endsection
