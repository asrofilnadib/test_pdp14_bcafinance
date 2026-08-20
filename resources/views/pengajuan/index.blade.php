@extends('layouts.app')

@section('title', 'Pengajuan Kredit')
@section('subtitle', 'Daftar pengajuan dengan pencarian server-side')

@section('content')
<div id="react-root" data-page="pengajuan-list" data-can-create="{{ auth()->user()->canCreatePengajuan() ? '1' : '0' }}"></div>
@endsection
