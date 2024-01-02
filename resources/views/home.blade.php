@extends('layouts.template')

<div class="jumbotron py-4 px-5">
    @section('content')
    @if(Session::get ('couldntAccess'))
        <div class="alert alert-danger">{{ Session::get('couldntAccess') }}</div>
    @endif
    <h1 class="display-4">
        Selamat Datang ! {{ Auth::user()->name }}!
    </h1>
    <hr class="my-4">
    <p>Aplikasi ini digunakan hanya oleh pegawai administrator APOTEK. Digunakan untuk mengelola data obat,
        penyetokan, juga pembelian (kasir).</p>
</div>
@endsection