@extends('layouts.app')

@section('title', 'Quiz Editor')

@section('content')
    <p>Form judul/deskripsi kuis + daftar soal + tambah soal.</p>
    <p>Tiap soal: field upload gambar (image_path, opsional).</p>
@endsection

@section('nav')
    <a href="{{ route('host.dashboard') }}">Simpan / Kembali ke Dashboard</a>
@endsection
