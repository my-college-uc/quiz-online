@extends('layouts.app')

@section('title', 'Host Final Results')

@section('content')
    <p>Podium + papan peringkat akhir.</p>
@endsection

@section('nav')
    <a href="{{ route('host.dashboard') }}">Main Lagi / Selesai</a>
@endsection
