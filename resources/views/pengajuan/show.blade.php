@extends('layouts.app')

@section('title', 'Detail Pengajuan')
@section('subtitle', 'Ringkasan data, dokumen, dan aksi sesuai peran')

@section('content')
<div id="react-root" data-page="pengajuan-detail" data-public-id="{{ $pengajuanPublicId }}"></div>
@endsection
