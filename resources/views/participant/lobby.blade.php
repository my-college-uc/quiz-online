@extends('layouts.app')

@section('title', 'Participant Lobby')

@section('content')
    <p>"Kamu sudah masuk!" — menunggu host memulai.</p>
@endsection

@section('nav')
    <a href="{{ route('participant.question') }}">(Host mulai) &rarr; Soal</a>
@endsection
