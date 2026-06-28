@extends('layouts.app')

@section('title', 'Host Game')

@section('content')
    <p>Soal + opsi + countdown + reveal (distribusi jawaban & papan peringkat).</p>
    <p>Tampilkan gambar soal (image_path) jika ada.</p>
@endsection

@section('nav')
    <a href="{{ route('host.results') }}">Berikutnya / Selesai</a>
@endsection
