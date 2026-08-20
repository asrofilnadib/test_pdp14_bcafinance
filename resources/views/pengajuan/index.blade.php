@extends('layouts.app')

@section('title', 'Pengajuan Kredit')
@section('subtitle', 'Daftar pengajuan dengan pencarian server-side')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:p-6">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <label class="text-sm text-slate-600">Status</label>
            <select id="filter-status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua</option>
                <option value="draft">Draft</option>
                <option value="submitted">Menunggu Approval</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
                <option value="printed">Dokumen Dicetak</option>
                <option value="signed">Sudah TTD</option>
                <option value="disbursed">Dana Dicairkan</option>
            </select>
        </div>
        @if(auth()->user()->canCreatePengajuan())
            <a href="/pengajuan/create" class="inline-flex items-center justify-center rounded-xl bg-navy-900 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-800">Pengajuan Baru</a>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table id="table-pengajuan" class="min-w-full display nowrap">
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Konsumen</th>
                    <th>NIK</th>
                    <th>Kendaraan</th>
                    <th>Dealer</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function statusBadge(status, label) {
    const map = {
        draft: 'bg-slate-100 text-slate-700',
        submitted: 'bg-amber-100 text-amber-800',
        approved: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-red-100 text-red-700',
        printed: 'bg-sky-100 text-sky-800',
        signed: 'bg-indigo-100 text-indigo-800',
        disbursed: 'bg-navy-900 text-white'
    };
    const cls = map[status] || 'bg-slate-100 text-slate-700';
    return '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ' + cls + '">' + label + '</span>';
}

const table = $('#table-pengajuan').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    pageLength: 10,
    order: [[6, 'desc']],
    ajax: {
        url: '/pengajuan/datatable',
        type: 'GET',
        data: function (d) {
            d.status = $('#filter-status').val();
        },
        error: function (xhr) {
            ajaxError(xhr);
        }
    },
    columns: [
        { data: 'nomor' },
        { data: 'konsumen' },
        { data: 'nik' },
        { data: 'kendaraan' },
        { data: 'dealer', orderable: false },
        {
            data: 'status',
            render: function (data, type, row) {
                return statusBadge(row.status, row.status_label);
            }
        },
        { data: 'tanggal' },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function (id, type, row) {
                let html = '<div class="flex gap-2"><a class="text-navy-700 font-semibold text-sm" href="/pengajuan/' + id + '">Detail</a>';
                if (row.can_edit) {
                    html += '<a class="text-gold-600 font-semibold text-sm" href="/pengajuan/' + id + '/edit">Ubah</a>';
                }
                html += '</div>';
                return html;
            }
        }
    ],
    language: {
        processing: 'Memuat data...',
        search: 'Cari:',
        lengthMenu: 'Tampil _MENU_',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ pengajuan',
        infoEmpty: 'Tidak ada data',
        zeroRecords: 'Pengajuan tidak ditemukan',
        paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
    }
});

$('#filter-status').on('change', function () {
    table.ajax.reload();
});
</script>
@endpush
