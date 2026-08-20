import { useEffect, useMemo, useRef, useState } from 'react';

const STEPS = ['Konsumen', 'Kendaraan', 'Pinjaman & Dokumen'];
const DOC_AWAL = [
    { tipe: 'ktp', label: 'KTP' },
    { tipe: 'spk', label: 'SPK' },
    { tipe: 'bukti_bayar', label: 'Bukti Bayar Tanda Jadi' },
    { tipe: 'kk', label: 'Kartu Keluarga' },
];

const emptyForm = {
    dealer_id: '',
    konsumen_nama: '',
    konsumen_nik: '',
    konsumen_tgl_lahir: '',
    status_perkawinan: 'belum_menikah',
    data_pasangan: '',
    merk_kendaraan: '',
    model_kendaraan: '',
    tipe_kendaraan: '',
    warna_kendaraan: '',
    harga_kendaraan: '',
    asuransi: 'All Risk',
    down_payment: '',
    lama_kredit: '12',
    angsuran: '',
};

function money(value) {
    if (value === '' || value === null || Number.isNaN(Number(value))) return 0;
    return Number(value);
}

export default function PengajuanForm({ mode, pengajuanId }) {
    const [step, setStep] = useState(0);
    const [form, setForm] = useState(emptyForm);
    const [dealers, setDealers] = useState([]);
    const [id, setId] = useState(pengajuanId || '');
    const [dokumens, setDokumens] = useState([]);
    const [files, setFiles] = useState({});
    const selectRef = useRef(null);
    const selectizeRef = useRef(null);
    const user = window.authUser || {};
    const isDealer = user.role === 'dealer';

    const angsuran = useMemo(() => {
        const harga = money(form.harga_kendaraan);
        const dp = money(form.down_payment);
        const tenor = money(form.lama_kredit);
        if (!harga || !tenor) return 0;
        return Math.round(Math.max(harga - dp, 0) / tenor);
    }, [form.harga_kendaraan, form.down_payment, form.lama_kredit]);

    useEffect(() => {
        window.$.ajax({
            url: '/dealers/options',
            type: 'GET',
            success: (res) => setDealers(res),
            error: window.ajaxError,
        });

        if (mode === 'edit' && pengajuanId) {
            window.showLoader();
            window.$.ajax({
                url: `/pengajuan/${pengajuanId}/json`,
                type: 'GET',
                success: (res) => {
                    setId(String(res.id));
                    setForm({
                        dealer_id: res.dealer_id ? String(res.dealer_id) : '',
                        konsumen_nama: res.konsumen_nama || '',
                        konsumen_nik: res.konsumen_nik || '',
                        konsumen_tgl_lahir: res.konsumen_tgl_lahir || '',
                        status_perkawinan: res.status_perkawinan || 'belum_menikah',
                        data_pasangan: res.data_pasangan || '',
                        merk_kendaraan: res.merk_kendaraan || '',
                        model_kendaraan: res.model_kendaraan || '',
                        tipe_kendaraan: res.tipe_kendaraan || '',
                        warna_kendaraan: res.warna_kendaraan || '',
                        harga_kendaraan: res.harga_kendaraan || '',
                        asuransi: res.asuransi || 'All Risk',
                        down_payment: res.down_payment || '',
                        lama_kredit: String(res.lama_kredit || 12),
                        angsuran: res.angsuran || '',
                    });
                    setDokumens(res.dokumens || []);
                },
                error: window.ajaxError,
                complete: window.hideLoader,
            });
        }
    }, [mode, pengajuanId]);

    useEffect(() => {
        if (isDealer || !selectRef.current || !window.$) return undefined;
        if (selectizeRef.current) {
            selectizeRef.current.destroy();
            selectizeRef.current = null;
        }
        const $el = window.$(selectRef.current);
        $el.selectize({
            placeholder: 'Pilih dealer',
            onChange: (value) => setForm((prev) => ({ ...prev, dealer_id: value })),
        });
        selectizeRef.current = $el[0].selectize;
        if (form.dealer_id) {
            selectizeRef.current.setValue(form.dealer_id, true);
        }
        return () => {
            if (selectizeRef.current) {
                selectizeRef.current.destroy();
                selectizeRef.current = null;
            }
        };
    }, [dealers, isDealer]);

    useEffect(() => {
        if (selectizeRef.current && form.dealer_id) {
            selectizeRef.current.setValue(form.dealer_id, true);
        }
    }, [form.dealer_id]);

    const setField = (name, value) => setForm((prev) => ({ ...prev, [name]: value }));

    const payload = () => ({
        ...form,
        dealer_id: isDealer ? user.dealer_id : form.dealer_id,
        angsuran,
    });

    const uploadQueued = (pengajuanIdValue) => {
        const jobs = Object.entries(files).filter(([, file]) => file);
        if (jobs.length === 0) {
            return window.$.Deferred().resolve().promise();
        }

        const chain = jobs.reduce((promise, [tipe, file]) => promise.then(() => {
            const data = new FormData();
            data.append('tipe', tipe);
            data.append('file', file);
            return window.$.ajax({
                url: `/pengajuan/${pengajuanIdValue}/dokumen`,
                type: 'POST',
                data,
                processData: false,
                contentType: false,
            });
        }), window.$.Deferred().resolve().promise());

        return chain;
    };

    const saveDraft = (thenSubmit = false) => {
        window.showLoader();
        const isUpdate = Boolean(id);
        window.$.ajax({
            url: isUpdate ? `/pengajuan/${id}` : '/pengajuan',
            type: isUpdate ? 'PUT' : 'POST',
            data: payload(),
            success: (res) => {
                const savedId = res.id || id;
                setId(String(savedId));
                uploadQueued(savedId)
                    .done(() => {
                        if (thenSubmit) {
                            window.$.ajax({
                                url: `/pengajuan/${savedId}/submit`,
                                type: 'POST',
                                success: (submitRes) => {
                                    window.toastr.success(submitRes.message);
                                    window.location.href = `/pengajuan/${savedId}`;
                                },
                                error: (xhr) => {
                                    window.hideLoader();
                                    window.ajaxError(xhr);
                                },
                            });
                            return;
                        }
                        window.hideLoader();
                        window.toastr.success(res.message);
                        if (!isUpdate) {
                            window.history.replaceState({}, '', `/pengajuan/${savedId}/edit`);
                        }
                    })
                    .fail((xhr) => {
                        window.hideLoader();
                        window.ajaxError(xhr);
                    });
            },
            error: (xhr) => {
                window.hideLoader();
                window.ajaxError(xhr);
            },
        });
    };

    const inputClass = 'w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-navy-700';

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:p-6">
            <ol className="mb-6 grid grid-cols-3 gap-2">
                {STEPS.map((label, index) => (
                    <li key={label} className={`rounded-xl px-3 py-2 text-center text-sm ${index === step ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-500'}`}>
                        {index + 1}. {label}
                    </li>
                ))}
            </ol>

            {step === 0 && (
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <label className="mb-1 block text-sm font-medium">Nama konsumen</label>
                        <input className={inputClass} value={form.konsumen_nama} onChange={(e) => setField('konsumen_nama', e.target.value)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">NIK</label>
                        <input className={inputClass} maxLength={16} value={form.konsumen_nik} onChange={(e) => setField('konsumen_nik', e.target.value.replace(/\D/g, ''))} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Tanggal lahir</label>
                        <input type="date" className={inputClass} value={form.konsumen_tgl_lahir} onChange={(e) => setField('konsumen_tgl_lahir', e.target.value)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Status perkawinan</label>
                        <select className={inputClass} value={form.status_perkawinan} onChange={(e) => setField('status_perkawinan', e.target.value)}>
                            <option value="belum_menikah">Belum menikah</option>
                            <option value="menikah">Menikah</option>
                            <option value="cerai">Cerai</option>
                        </select>
                    </div>
                    {form.status_perkawinan === 'menikah' && (
                        <div>
                            <label className="mb-1 block text-sm font-medium">Data pasangan</label>
                            <input className={inputClass} value={form.data_pasangan} onChange={(e) => setField('data_pasangan', e.target.value)} />
                        </div>
                    )}
                </div>
            )}

            {step === 1 && (
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <label className="mb-1 block text-sm font-medium">Dealer</label>
                        {isDealer ? (
                            <input className={inputClass} disabled value={dealers.find((d) => String(d.id) === String(user.dealer_id))?.nama || 'Dealer Anda'} />
                        ) : (
                            <select ref={selectRef} defaultValue={form.dealer_id}>
                                <option value="">Pilih dealer</option>
                                {dealers.map((dealer) => (
                                    <option key={dealer.id} value={dealer.id}>{dealer.nama}</option>
                                ))}
                            </select>
                        )}
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Merk</label>
                        <input className={inputClass} value={form.merk_kendaraan} onChange={(e) => setField('merk_kendaraan', e.target.value)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Model</label>
                        <input className={inputClass} value={form.model_kendaraan} onChange={(e) => setField('model_kendaraan', e.target.value)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Tipe</label>
                        <input className={inputClass} value={form.tipe_kendaraan} onChange={(e) => setField('tipe_kendaraan', e.target.value)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Warna</label>
                        <input className={inputClass} value={form.warna_kendaraan} onChange={(e) => setField('warna_kendaraan', e.target.value)} />
                    </div>
                    <div className="md:col-span-2">
                        <label className="mb-1 block text-sm font-medium">Harga kendaraan</label>
                        <input type="number" min="0" className={inputClass} value={form.harga_kendaraan} onChange={(e) => setField('harga_kendaraan', e.target.value)} />
                    </div>
                </div>
            )}

            {step === 2 && (
                <div className="space-y-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-sm font-medium">Asuransi</label>
                            <select className={inputClass} value={form.asuransi} onChange={(e) => setField('asuransi', e.target.value)}>
                                <option>All Risk</option>
                                <option>TLO</option>
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium">Down Payment</label>
                            <input type="number" min="0" className={inputClass} value={form.down_payment} onChange={(e) => setField('down_payment', e.target.value)} />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium">Lama kredit (bulan)</label>
                            <select className={inputClass} value={form.lama_kredit} onChange={(e) => setField('lama_kredit', e.target.value)}>
                                {[12, 24, 36, 48, 60].map((n) => <option key={n} value={n}>{n} bulan</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium">Angsuran / bulan</label>
                            <input className={inputClass} readOnly value={angsuran ? `Rp ${angsuran.toLocaleString('id-ID')}` : '-'} />
                        </div>
                    </div>
                    <div>
                        <h3 className="font-semibold text-navy-900">Dokumen awal</h3>
                        <p className="mb-3 text-sm text-slate-500">JPG, PNG, atau PDF. Maksimal 5 MB. Wajib lengkap sebelum submit.</p>
                        <div className="grid gap-3 md:grid-cols-2">
                            {DOC_AWAL.map((doc) => {
                                const uploaded = dokumens.find((item) => item.tipe === doc.tipe);
                                return (
                                    <label key={doc.tipe} className="rounded-xl border border-dashed border-slate-300 p-4 text-sm">
                                        <span className="font-medium">{doc.label}</span>
                                        <input type="file" accept=".jpg,.jpeg,.png,.pdf" className="mt-2 block w-full text-xs" onChange={(e) => setFiles((prev) => ({ ...prev, [doc.tipe]: e.target.files[0] }))} />
                                        {uploaded && <span className="mt-1 block text-xs text-emerald-700">Sudah ada: {uploaded.nama_asli}</span>}
                                        {files[doc.tipe] && <span className="mt-1 block text-xs text-navy-700">Siap unggah: {files[doc.tipe].name}</span>}
                                    </label>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}

            <div className="mt-6 flex flex-wrap justify-between gap-3">
                <button type="button" className="rounded-xl border px-4 py-2 text-sm" disabled={step === 0} onClick={() => setStep((s) => s - 1)}>Kembali</button>
                <div className="flex gap-2">
                    <button type="button" className="rounded-xl border border-navy-900 px-4 py-2 text-sm font-semibold text-navy-900" onClick={() => saveDraft(false)}>Simpan Draft</button>
                    {step < 2 ? (
                        <button type="button" className="rounded-xl bg-navy-900 px-4 py-2 text-sm font-semibold text-white" onClick={() => setStep((s) => s + 1)}>Lanjut</button>
                    ) : (
                        <button type="button" className="rounded-xl bg-gold-500 px-4 py-2 text-sm font-semibold text-navy-950" onClick={() => saveDraft(true)}>Kirim Pengajuan</button>
                    )}
                </div>
            </div>
        </div>
    );
}
