import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import FilePondField from '@/components/FilePondField';

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

export default function PengajuanForm({ mode, pengajuanPublicId, onSaved, onSubmitted }) {
    const [step, setStep] = useState(0);
    const [form, setForm] = useState(emptyForm);
    const [dealers, setDealers] = useState([]);
    const [publicId, setPublicId] = useState(pengajuanPublicId || '');
    const [dokumens, setDokumens] = useState([]);
    const [files, setFiles] = useState({});
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
    }, []);

    useEffect(() => {
        setStep(0);
        setFiles({});
        setDokumens([]);
        setPublicId(pengajuanPublicId || '');
        setForm(emptyForm);

        if (mode !== 'edit' || !pengajuanPublicId) {
            return undefined;
        }

        window.showLoader();
        window.$.ajax({
            url: `/pengajuan/${pengajuanPublicId}/json`,
            type: 'GET',
            success: (res) => {
                setPublicId(res.public_id);
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

        return undefined;
    }, [mode, pengajuanPublicId]);

    const setField = (name, value) => setForm((prev) => ({ ...prev, [name]: value }));

    const payload = () => ({
        ...form,
        dealer_id: isDealer ? user.dealer_id : form.dealer_id,
        angsuran,
    });

    const uploadQueued = (savedPublicId) => {
        const jobs = Object.entries(files).filter(([, file]) => file);
        if (jobs.length === 0) {
            return window.$.Deferred().resolve().promise();
        }

        return jobs.reduce((promise, [tipe, file]) => promise.then(() => {
            const data = new FormData();
            data.append('tipe', tipe);
            data.append('file', file);
            return window.$.ajax({
                url: `/pengajuan/${savedPublicId}/dokumen`,
                type: 'POST',
                data,
                processData: false,
                contentType: false,
            });
        }), window.$.Deferred().resolve().promise());
    };

    const saveDraft = (thenSubmit = false) => {
        window.showLoader();
        const isUpdate = Boolean(publicId);
        window.$.ajax({
            url: isUpdate ? `/pengajuan/${publicId}` : '/pengajuan',
            type: isUpdate ? 'PUT' : 'POST',
            data: payload(),
            success: (res) => {
                const savedPublicId = res.public_id || publicId;
                setPublicId(savedPublicId);
                uploadQueued(savedPublicId)
                    .done(() => {
                        if (thenSubmit) {
                            window.$.ajax({
                                url: `/pengajuan/${savedPublicId}/submit`,
                                type: 'POST',
                                success: (submitRes) => {
                                    window.hideLoader();
                                    window.toastr.success(submitRes.message);
                                    if (onSubmitted) onSubmitted(savedPublicId);
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
                        if (onSaved) onSaved(savedPublicId);
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

    return (
        <div className="space-y-5">
            <ol className="grid grid-cols-3 gap-2">
                {STEPS.map((label, index) => (
                    <li
                        key={label}
                        className={`rounded-lg px-3 py-2 text-center text-xs font-medium sm:text-sm ${index === step ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}
                    >
                        {index + 1}. {label}
                    </li>
                ))}
            </ol>

            {step === 0 && (
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="konsumen_nama">Nama konsumen</Label>
                        <Input id="konsumen_nama" value={form.konsumen_nama} onChange={(e) => setField('konsumen_nama', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="konsumen_nik">NIK</Label>
                        <Input id="konsumen_nik" maxLength={16} value={form.konsumen_nik} onChange={(e) => setField('konsumen_nik', e.target.value.replace(/\D/g, ''))} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="konsumen_tgl_lahir">Tanggal lahir</Label>
                        <Input id="konsumen_tgl_lahir" type="date" value={form.konsumen_tgl_lahir} onChange={(e) => setField('konsumen_tgl_lahir', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="status_perkawinan">Status perkawinan</Label>
                        <Select id="status_perkawinan" value={form.status_perkawinan} onChange={(e) => setField('status_perkawinan', e.target.value)}>
                            <option value="belum_menikah">Belum menikah</option>
                            <option value="menikah">Menikah</option>
                            <option value="cerai">Cerai</option>
                        </Select>
                    </div>
                    {form.status_perkawinan === 'menikah' && (
                        <div className="space-y-2">
                            <Label htmlFor="data_pasangan">Data pasangan</Label>
                            <Input id="data_pasangan" value={form.data_pasangan} onChange={(e) => setField('data_pasangan', e.target.value)} />
                        </div>
                    )}
                </div>
            )}

            {step === 1 && (
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="dealer_id">Dealer</Label>
                        {isDealer ? (
                            <Input disabled value={dealers.find((d) => String(d.id) === String(user.dealer_id))?.nama || 'Dealer Anda'} />
                        ) : (
                            <Select id="dealer_id" value={form.dealer_id} onChange={(e) => setField('dealer_id', e.target.value)}>
                                <option value="">Pilih dealer</option>
                                {dealers.map((dealer) => (
                                    <option key={dealer.id} value={dealer.id}>{dealer.nama}</option>
                                ))}
                            </Select>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="merk_kendaraan">Merk</Label>
                        <Input id="merk_kendaraan" value={form.merk_kendaraan} onChange={(e) => setField('merk_kendaraan', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="model_kendaraan">Model</Label>
                        <Input id="model_kendaraan" value={form.model_kendaraan} onChange={(e) => setField('model_kendaraan', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="tipe_kendaraan">Tipe</Label>
                        <Input id="tipe_kendaraan" value={form.tipe_kendaraan} onChange={(e) => setField('tipe_kendaraan', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="warna_kendaraan">Warna</Label>
                        <Input id="warna_kendaraan" value={form.warna_kendaraan} onChange={(e) => setField('warna_kendaraan', e.target.value)} />
                    </div>
                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="harga_kendaraan">Harga kendaraan</Label>
                        <Input id="harga_kendaraan" type="number" min="0" value={form.harga_kendaraan} onChange={(e) => setField('harga_kendaraan', e.target.value)} />
                    </div>
                </div>
            )}

            {step === 2 && (
                <div className="space-y-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="asuransi">Asuransi</Label>
                            <Select id="asuransi" value={form.asuransi} onChange={(e) => setField('asuransi', e.target.value)}>
                                <option>All Risk</option>
                                <option>TLO</option>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="down_payment">Down Payment</Label>
                            <Input id="down_payment" type="number" min="0" value={form.down_payment} onChange={(e) => setField('down_payment', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="lama_kredit">Lama kredit (bulan)</Label>
                            <Select id="lama_kredit" value={form.lama_kredit} onChange={(e) => setField('lama_kredit', e.target.value)}>
                                {[12, 24, 36, 48, 60].map((n) => (
                                    <option key={n} value={n}>{n} bulan</option>
                                ))}
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Angsuran / bulan</Label>
                            <Input readOnly value={angsuran ? `Rp ${angsuran.toLocaleString('id-ID')}` : '-'} />
                        </div>
                    </div>
                    <div>
                        <h3 className="text-sm font-semibold">Dokumen awal</h3>
                        <p className="mb-3 text-sm text-muted-foreground">JPG, PNG, atau PDF. Maksimal 5 MB. Wajib lengkap sebelum submit.</p>
                        <div className="grid gap-3 md:grid-cols-2">
                            {DOC_AWAL.map((doc) => {
                                const uploaded = dokumens.find((item) => item.tipe === doc.tipe);
                                return (
                                    <FilePondField
                                        key={doc.tipe}
                                        label={doc.label}
                                        file={files[doc.tipe] || null}
                                        existingLabel={uploaded?.nama_asli}
                                        onFile={(file) => setFiles((prev) => ({ ...prev, [doc.tipe]: file }))}
                                    />
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}

            <div className="flex flex-wrap justify-between gap-3">
                <Button type="button" variant="outline" disabled={step === 0} onClick={() => setStep((s) => s - 1)}>Kembali</Button>
                <div className="flex gap-2">
                    <Button type="button" variant="outline" onClick={() => saveDraft(false)}>Simpan Draft</Button>
                    {step < 2 ? (
                        <Button type="button" onClick={() => setStep((s) => s + 1)}>Lanjut</Button>
                    ) : (
                        <Button type="button" variant="gold" onClick={() => saveDraft(true)}>Kirim Pengajuan</Button>
                    )}
                </div>
            </div>
        </div>
    );
}
