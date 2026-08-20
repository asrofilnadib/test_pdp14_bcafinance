import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import PengajuanForm from '@/pages/PengajuanForm';

const STATUS_VARIANT = {
    draft: 'secondary',
    submitted: 'warning',
    approved: 'success',
    rejected: 'destructive',
    printed: 'info',
    signed: 'indigo',
    disbursed: 'default',
};

function rupiah(value) {
    if (value === null || value === undefined || value === '') return '-';
    return `Rp ${Number(value).toLocaleString('id-ID')}`;
}

export default function PengajuanDetail({ pengajuanId }) {
    const [data, setData] = useState(null);
    const [formOpen, setFormOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const user = window.authUser || {};

    const load = () => {
        window.showLoader();
        window.$.ajax({
            url: `/pengajuan/${pengajuanId}/json`,
            type: 'GET',
            success: (res) => setData(res),
            error: window.ajaxError,
            complete: window.hideLoader,
        });
    };

    useEffect(() => {
        load();
    }, [pengajuanId]);

    useEffect(() => {
        if (!data || !window.PhotoSwipeLightbox || !window.PhotoSwipe) return undefined;
        const lightbox = new window.PhotoSwipeLightbox({
            gallery: '#dokumen-gallery',
            children: 'a[data-pswp]',
            pswpModule: window.PhotoSwipe,
        });
        lightbox.init();
        return () => lightbox.destroy();
    }, [data]);

    if (!data) {
        return <Card><CardContent className="p-6 text-sm text-muted-foreground">Memuat detail...</CardContent></Card>;
    }

    const canApprove = ['atasan_marketing', 'super_user'].includes(user.role) && data.status === 'submitted';
    const canPrint = ['admin_backoffice', 'super_user'].includes(user.role) && ['approved', 'printed'].includes(data.status);
    const canUploadTtd = ['dealer', 'marketing', 'super_user'].includes(user.role) && data.status === 'printed';
    const canDisburse = ['admin_backoffice', 'super_user'].includes(user.role) && data.status === 'signed';
    const canDelete = data.can_edit && data.status === 'draft';

    const postAction = (url, payload, successText) => {
        window.showLoader();
        window.$.ajax({
            url,
            type: 'POST',
            data: payload || {},
            success: (res) => {
                window.toastr.success(res.message || successText);
                load();
            },
            error: (xhr) => {
                window.hideLoader();
                window.ajaxError(xhr);
            },
        });
    };

    const approve = () => {
        window.Swal.fire({
            title: 'Setujui pengajuan?',
            input: 'textarea',
            inputPlaceholder: 'Catatan opsional',
            showCancelButton: true,
            confirmButtonColor: '#102844',
            confirmButtonText: 'Setujui',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) postAction(`/pengajuan/${data.id}/approve`, { catatan: result.value || '' });
        });
    };

    const reject = () => {
        window.Swal.fire({
            title: 'Tolak pengajuan?',
            input: 'textarea',
            inputPlaceholder: 'Alasan penolakan wajib diisi',
            inputValidator: (value) => (!value ? 'Catatan wajib diisi' : undefined),
            showCancelButton: true,
            confirmButtonColor: '#b42318',
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) postAction(`/pengajuan/${data.id}/reject`, { catatan: result.value });
        });
    };

    const printDoc = (path) => {
        window.$.ajax({
            url: `/pengajuan/${data.id}/print`,
            type: 'POST',
            success: () => {
                window.open(path, '_blank');
                load();
            },
            error: window.ajaxError,
        });
    };

    const uploadTtd = (tipe, file) => {
        if (!file) return;
        const formData = new FormData();
        formData.append('tipe', tipe);
        formData.append('file', file);
        window.showLoader();
        window.$.ajax({
            url: `/pengajuan/${data.id}/dokumen`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (res) => {
                window.toastr.success(res.message);
                load();
            },
            error: (xhr) => {
                window.hideLoader();
                window.ajaxError(xhr);
            },
        });
    };

    const disburse = () => {
        window.Swal.fire({
            title: 'Catat pencairan dana?',
            text: 'Status akan menjadi Dana Dicairkan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#102844',
            confirmButtonText: 'Cairkan',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) postAction(`/pengajuan/${data.id}/disburse`);
        });
    };

    const removeDraft = () => {
        window.showLoader();
        window.$.ajax({
            url: `/pengajuan/${data.id}`,
            type: 'DELETE',
            success: (res) => {
                window.toastr.success(res.message);
                window.location.href = '/pengajuan';
            },
            error: (xhr) => {
                window.hideLoader();
                window.ajaxError(xhr);
            },
        });
    };

    return (
        <div className="space-y-4">
            <Card>
                <CardContent className="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">{data.nomor}</p>
                        <h2 className="text-xl font-semibold">{data.konsumen_nama}</h2>
                        <Badge className="mt-2" variant={STATUS_VARIANT[data.status] || 'secondary'}>{data.status_label}</Badge>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {data.can_edit && <Button type="button" variant="outline" onClick={() => setFormOpen(true)}>Ubah</Button>}
                        {canDelete && <Button type="button" variant="destructive" onClick={() => setDeleteOpen(true)}>Hapus</Button>}
                        {canApprove && (
                            <>
                                <Button type="button" className="bg-emerald-700 hover:bg-emerald-700/90" onClick={approve}>Setujui</Button>
                                <Button type="button" variant="destructive" onClick={reject}>Tolak</Button>
                            </>
                        )}
                        {canPrint && (
                            <>
                                <Button type="button" onClick={() => printDoc(`/pengajuan/${data.id}/cetak-kontrak`)}>Cetak Kontrak</Button>
                                <Button type="button" variant="gold" onClick={() => printDoc(`/pengajuan/${data.id}/cetak-po`)}>Cetak PO</Button>
                            </>
                        )}
                        {canDisburse && <Button type="button" onClick={disburse}>Pencairan Dana</Button>}
                    </div>
                </CardContent>
            </Card>

            {data.catatan_approval && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Catatan approval: {data.catatan_approval}
                </div>
            )}

            <section className="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader><CardTitle>Konsumen</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div><p className="text-muted-foreground">NIK</p><p>{data.konsumen_nik}</p></div>
                        <div><p className="text-muted-foreground">Tanggal lahir</p><p>{data.konsumen_tgl_lahir || '-'}</p></div>
                        <div><p className="text-muted-foreground">Status perkawinan</p><p>{data.status_perkawinan || '-'}</p></div>
                        <div><p className="text-muted-foreground">Pasangan</p><p>{data.data_pasangan || '-'}</p></div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Kendaraan</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div><p className="text-muted-foreground">Dealer</p><p>{data.dealer?.nama || '-'}</p></div>
                        <div><p className="text-muted-foreground">Unit</p><p>{data.merk_kendaraan} {data.model_kendaraan}</p></div>
                        <div><p className="text-muted-foreground">Tipe / Warna</p><p>{data.tipe_kendaraan} / {data.warna_kendaraan}</p></div>
                        <div><p className="text-muted-foreground">Harga</p><p>{rupiah(data.harga_kendaraan)}</p></div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Pinjaman</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div><p className="text-muted-foreground">Asuransi</p><p>{data.asuransi || '-'}</p></div>
                        <div><p className="text-muted-foreground">DP</p><p>{rupiah(data.down_payment)}</p></div>
                        <div><p className="text-muted-foreground">Tenor</p><p>{data.lama_kredit ? `${data.lama_kredit} bulan` : '-'}</p></div>
                        <div><p className="text-muted-foreground">Angsuran</p><p>{rupiah(data.angsuran)}</p></div>
                    </CardContent>
                </Card>
            </section>

            <Card>
                <CardHeader><CardTitle>Dokumen</CardTitle></CardHeader>
                <CardContent>
                    <div id="dokumen-gallery" className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {(data.dokumens || []).map((doc) => (
                            <div key={doc.id} className="rounded-xl border p-3">
                                <p className="text-sm font-medium">{doc.tipe_label}</p>
                                {doc.is_image ? (
                                    <a href={doc.url} data-pswp data-pswp-width="1600" data-pswp-height="1200" className="mt-2 block overflow-hidden rounded-lg bg-muted">
                                        <img src={doc.url} alt={doc.tipe_label} className="h-32 w-full object-cover" />
                                    </a>
                                ) : (
                                    <a href={doc.url} target="_blank" rel="noreferrer" className="mt-2 inline-block text-sm text-primary">Buka PDF</a>
                                )}
                                <p className="mt-1 truncate text-xs text-muted-foreground">{doc.nama_asli}</p>
                            </div>
                        ))}
                        {(data.dokumens || []).length === 0 && <p className="text-sm text-muted-foreground">Belum ada dokumen.</p>}
                    </div>

                    {canUploadTtd && (
                        <div className="mt-5 grid gap-3 md:grid-cols-2">
                            <label className="rounded-xl border border-dashed p-4 text-sm">
                                Kontrak sudah TTD
                                <Input type="file" accept=".jpg,.jpeg,.png,.pdf" className="mt-2 h-auto py-1.5" onChange={(e) => uploadTtd('signed_kontrak', e.target.files[0])} />
                            </label>
                            <label className="rounded-xl border border-dashed p-4 text-sm">
                                PO sudah TTD
                                <Input type="file" accept=".jpg,.jpeg,.png,.pdf" className="mt-2 h-auto py-1.5" onChange={(e) => uploadTtd('signed_po', e.target.files[0])} />
                            </label>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Ubah Pengajuan</DialogTitle>
                        <DialogDescription>Perbarui data lalu simpan draft atau kirim ulang.</DialogDescription>
                    </DialogHeader>
                    {formOpen && (
                        <PengajuanForm
                            key={`edit-${data.id}`}
                            mode="edit"
                            pengajuanId={String(data.id)}
                            onSaved={() => load()}
                            onSubmitted={() => {
                                setFormOpen(false);
                                load();
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
                            Data {data.konsumen_nama} akan dihapus. Tindakan ini tidak bisa dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-white hover:bg-destructive/90"
                            onClick={(e) => {
                                e.preventDefault();
                                removeDraft();
                            }}
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
