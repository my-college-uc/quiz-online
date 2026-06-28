@extends('layouts.app')

@section('title', 'Participant Question')

@section('content')
    <p>Teks soal + 4 tile jawaban + countdown.</p>
    <p>Tampilkan gambar soal (image_path) jika ada.</p>
@endsection

@section('nav')
    <a href="{{ route('participant.result') }}">Jawab</a>
@endsection
