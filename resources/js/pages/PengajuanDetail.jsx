import { useEffect, useState } from 'react';

const STATUS_CLASS = {
    draft: 'bg-slate-100 text-slate-700',
    submitted: 'bg-amber-100 text-amber-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-700',
    printed: 'bg-sky-100 text-sky-800',
    signed: 'bg-indigo-100 text-indigo-800',
    disbursed: 'bg-navy-900 text-white',
};

function rupiah(value) {
    if (value === null || value === undefined || value === '') return '-';
    return `Rp ${Number(value).toLocaleString('id-ID')}`;
}

export default function PengajuanDetail({ pengajuanId }) {
    const [data, setData] = useState(null);
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
        return <div className="rounded-2xl bg-white p-6 text-sm text-slate-500">Memuat detail...</div>;
    }

    const canApprove = user.role === 'atasan_marketing' && data.status === 'submitted';
    const canPrint = user.role === 'admin_backoffice' && ['approved', 'printed'].includes(data.status);
    const canUploadTtd = ['dealer', 'marketing'].includes(user.role) && data.status === 'printed';
    const canDisburse = user.role === 'admin_backoffice' && data.status === 'signed';
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
        window.Swal.fire({
            title: 'Hapus draft ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b42318',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;
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
        });
    };

    return (
        <div className="space-y-4">
            <section className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p className="text-sm text-slate-500">{data.nomor}</p>
                    <h2 className="text-xl font-semibold text-navy-900">{data.konsumen_nama}</h2>
                    <span className={`mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold ${STATUS_CLASS[data.status]}`}>{data.status_label}</span>
                </div>
                <div className="flex flex-wrap gap-2">
                    {data.can_edit && <a href={`/pengajuan/${data.id}/edit`} className="rounded-xl border px-4 py-2 text-sm font-semibold">Ubah</a>}
                    {canDelete && <button type="button" onClick={removeDraft} className="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Hapus</button>}
                    {canApprove && (
                        <>
                            <button type="button" onClick={approve} className="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Setujui</button>
                            <button type="button" onClick={reject} className="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Tolak</button>
                        </>
                    )}
                    {canPrint && (
                        <>
                            <button type="button" onClick={() => printDoc(`/pengajuan/${data.id}/cetak-kontrak`)} className="rounded-xl bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Cetak Kontrak</button>
                            <button type="button" onClick={() => printDoc(`/pengajuan/${data.id}/cetak-po`)} className="rounded-xl bg-gold-500 px-4 py-2 text-sm font-semibold text-navy-950">Cetak PO</button>
                        </>
                    )}
                    {canDisburse && <button type="button" onClick={disburse} className="rounded-xl bg-navy-900 px-4 py-2 text-sm font-semibold text-white">Pencairan Dana</button>}
                </div>
            </section>

            {data.catatan_approval && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Catatan approval: {data.catatan_approval}
                </div>
            )}

            <section className="grid gap-4 lg:grid-cols-3">
                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="font-semibold text-navy-900">Konsumen</h3>
                    <dl className="mt-3 space-y-2 text-sm">
                        <div><dt className="text-slate-500">NIK</dt><dd>{data.konsumen_nik}</dd></div>
                        <div><dt className="text-slate-500">Tanggal lahir</dt><dd>{data.konsumen_tgl_lahir || '-'}</dd></div>
                        <div><dt className="text-slate-500">Status perkawinan</dt><dd>{data.status_perkawinan || '-'}</dd></div>
                        <div><dt className="text-slate-500">Pasangan</dt><dd>{data.data_pasangan || '-'}</dd></div>
                    </dl>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="font-semibold text-navy-900">Kendaraan</h3>
                    <dl className="mt-3 space-y-2 text-sm">
                        <div><dt className="text-slate-500">Dealer</dt><dd>{data.dealer?.nama || '-'}</dd></div>
                        <div><dt className="text-slate-500">Unit</dt><dd>{data.merk_kendaraan} {data.model_kendaraan}</dd></div>
                        <div><dt className="text-slate-500">Tipe / Warna</dt><dd>{data.tipe_kendaraan} / {data.warna_kendaraan}</dd></div>
                        <div><dt className="text-slate-500">Harga</dt><dd>{rupiah(data.harga_kendaraan)}</dd></div>
                    </dl>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="font-semibold text-navy-900">Pinjaman</h3>
                    <dl className="mt-3 space-y-2 text-sm">
                        <div><dt className="text-slate-500">Asuransi</dt><dd>{data.asuransi || '-'}</dd></div>
                        <div><dt className="text-slate-500">DP</dt><dd>{rupiah(data.down_payment)}</dd></div>
                        <div><dt className="text-slate-500">Tenor</dt><dd>{data.lama_kredit ? `${data.lama_kredit} bulan` : '-'}</dd></div>
                        <div><dt className="text-slate-500">Angsuran</dt><dd>{rupiah(data.angsuran)}</dd></div>
                    </dl>
                </article>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 className="font-semibold text-navy-900">Dokumen</h3>
                <div id="dokumen-gallery" className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {(data.dokumens || []).map((doc) => (
                        <div key={doc.id} className="rounded-xl border border-slate-200 p-3">
                            <p className="text-sm font-medium">{doc.tipe_label}</p>
                            {doc.is_image ? (
                                <a href={doc.url} data-pswp data-pswp-width="1600" data-pswp-height="1200" className="mt-2 block overflow-hidden rounded-lg bg-slate-100">
                                    <img src={doc.url} alt={doc.tipe_label} className="h-32 w-full object-cover" />
                                </a>
                            ) : (
                                <a href={doc.url} target="_blank" rel="noreferrer" className="mt-2 inline-block text-sm text-navy-700">Buka PDF</a>
                            )}
                            <p className="mt-1 truncate text-xs text-slate-500">{doc.nama_asli}</p>
                        </div>
                    ))}
                    {(data.dokumens || []).length === 0 && <p className="text-sm text-slate-500">Belum ada dokumen.</p>}
                </div>

                {canUploadTtd && (
                    <div className="mt-5 grid gap-3 md:grid-cols-2">
                        <label className="rounded-xl border border-dashed p-4 text-sm">
                            Kontrak sudah TTD
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" className="mt-2 block w-full text-xs" onChange={(e) => uploadTtd('signed_kontrak', e.target.files[0])} />
                        </label>
                        <label className="rounded-xl border border-dashed p-4 text-sm">
                            PO sudah TTD
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" className="mt-2 block w-full text-xs" onChange={(e) => uploadTtd('signed_po', e.target.files[0])} />
                        </label>
                    </div>
                )}
            </section>
        </div>
    );
}
