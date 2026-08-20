import { useEffect, useRef, useState } from 'react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Select } from '@/components/ui/select';
import PengajuanForm from '@/pages/PengajuanForm';

function statusBadge(status, label) {
    const map = {
        draft: 'bg-slate-100 text-slate-700',
        submitted: 'bg-amber-100 text-amber-800',
        approved: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-red-100 text-red-700',
        printed: 'bg-sky-100 text-sky-800',
        signed: 'bg-indigo-100 text-indigo-800',
        disbursed: 'bg-navy-900 text-white',
    };
    const cls = map[status] || 'bg-slate-100 text-slate-700';
    return `<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${cls}">${label}</span>`;
}

export default function PengajuanList({ canCreate }) {
    const tableRef = useRef(null);
    const dtRef = useRef(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [formId, setFormId] = useState('');
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);

    const reloadTable = () => {
        if (dtRef.current) {
            dtRef.current.ajax.reload(null, false);
        }
    };

    const openCreate = () => {
        setFormMode('create');
        setFormId('');
        setFormOpen(true);
    };

    const openEdit = (id) => {
        setFormMode('edit');
        setFormId(String(id));
        setFormOpen(true);
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        window.showLoader();
        window.$.ajax({
            url: `/pengajuan/${deleteTarget.id}`,
            type: 'DELETE',
            success: (res) => {
                window.hideLoader();
                window.toastr.success(res.message);
                setDeleteOpen(false);
                setDeleteTarget(null);
                reloadTable();
            },
            error: (xhr) => {
                window.hideLoader();
                window.ajaxError(xhr);
            },
        });
    };

    useEffect(() => {
        const $table = window.$(tableRef.current);
        const dt = $table.DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            pageLength: 10,
            order: [[6, 'desc']],
            ajax: {
                url: '/pengajuan/datatable',
                type: 'GET',
                data: (d) => {
                    d.status = window.$('#filter-status').val();
                },
                error: window.ajaxError,
            },
            columns: [
                { data: 'nomor' },
                { data: 'konsumen' },
                { data: 'nik' },
                { data: 'kendaraan' },
                { data: 'dealer', orderable: false },
                {
                    data: 'status',
                    render: (data, type, row) => statusBadge(row.status, row.status_label),
                },
                { data: 'tanggal' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: (id, type, row) => {
                        let html = `<div class="flex flex-wrap gap-2"><a class="text-sm font-semibold text-primary" href="/pengajuan/${id}">Detail</a>`;
                        if (row.can_edit) {
                            html += `<button type="button" class="js-edit text-sm font-semibold text-gold-600" data-id="${id}">Ubah</button>`;
                        }
                        if (row.can_delete) {
                            html += `<button type="button" class="js-delete text-sm font-semibold text-red-600" data-id="${id}" data-nama="${row.konsumen}">Hapus</button>`;
                        }
                        html += '</div>';
                        return html;
                    },
                },
            ],
            language: {
                processing: 'Memuat data...',
                search: 'Cari:',
                lengthMenu: 'Tampil _MENU_',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ pengajuan',
                infoEmpty: 'Tidak ada data',
                zeroRecords: 'Pengajuan tidak ditemukan',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
            },
        });

        dtRef.current = dt;

        $table.on('click', '.js-edit', function () {
            openEdit(window.$(this).data('id'));
        });

        $table.on('click', '.js-delete', function () {
            setDeleteTarget({
                id: window.$(this).data('id'),
                nama: window.$(this).data('nama'),
            });
            setDeleteOpen(true);
        });

        window.$('#filter-status').on('change.pengajuanList', () => dt.ajax.reload());

        return () => {
            window.$('#filter-status').off('change.pengajuanList');
            $table.off('click', '.js-edit');
            $table.off('click', '.js-delete');
            dt.destroy();
            dtRef.current = null;
        };
    }, []);

    return (
        <>
            <Card>
                <CardContent className="p-4 lg:p-6">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-wrap items-center gap-2">
                            <label className="text-sm text-muted-foreground" htmlFor="filter-status">Status</label>
                            <Select id="filter-status" className="w-[220px]">
                                <option value="">Semua</option>
                                <option value="draft">Draft</option>
                                <option value="submitted">Menunggu Approval</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                                <option value="printed">Dokumen Dicetak</option>
                                <option value="signed">Sudah TTD</option>
                                <option value="disbursed">Dana Dicairkan</option>
                            </Select>
                        </div>
                        {canCreate && (
                            <Button type="button" onClick={openCreate}>
                                <Plus /> Pengajuan Baru
                            </Button>
                        )}
                    </div>
                    <div className="overflow-x-auto">
                        <table ref={tableRef} className="display nowrap min-w-full">
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
                </CardContent>
            </Card>

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{formMode === 'edit' ? 'Ubah Pengajuan' : 'Pengajuan Baru'}</DialogTitle>
                        <DialogDescription>Isi data konsumen, kendaraan, pinjaman, lalu unggah dokumen.</DialogDescription>
                    </DialogHeader>
                    {formOpen && (
                        <PengajuanForm
                            key={`${formMode}-${formId || 'new'}`}
                            mode={formMode}
                            pengajuanId={formId}
                            onSaved={() => reloadTable()}
                            onSubmitted={() => {
                                setFormOpen(false);
                                reloadTable();
                            }}
                        />
                    )}
                </DialogContent>
            </Dialog>

            <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus draft pengajuan?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Data {deleteTarget?.nama || 'pengajuan ini'} akan dihapus. Tindakan ini tidak bisa dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-white hover:bg-destructive/90"
                            onClick={(e) => {
                                e.preventDefault();
                                confirmDelete();
                            }}
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
