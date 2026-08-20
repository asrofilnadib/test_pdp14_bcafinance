@extends('layouts.app')

@section('title', $mode === 'edit' ? 'Ubah Pengajuan' : 'Pengajuan Baru')
@section('subtitle', 'Isi data konsumen, kendaraan, dan pinjaman')

@section('content')
<div id="react-root" data-page="pengajuan-form" data-mode="{{ $mode }}" data-id="{{ $pengajuanId }}"></div>
@endsection
