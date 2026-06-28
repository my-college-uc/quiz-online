@extends('layouts.app')

@section('title', 'Per-Question Result')

@section('content')
    <p>Benar/Salah + poin + peringkat saat ini.</p>
@endsection

@section('nav')
    <a href="{{ route('participant.question') }}">Soal berikutnya</a> |
    <a href="{{ route('participant.final') }}">Selesai</a>
@endsection
