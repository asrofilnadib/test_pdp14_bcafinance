import { useEffect, useState } from 'react';

const CARDS = [
    { key: 'total', label: 'Total Pengajuan', hint: 'Semua status', className: 'bg-navy-900 text-white' },
    { key: 'submitted', label: 'Menunggu Approval', hint: 'Perlu review atasan', className: 'bg-white' },
    { key: 'approved', label: 'Disetujui', hint: 'Siap cetak dokumen', className: 'bg-white' },
    { key: 'printed', label: 'Dicetak', hint: 'Menunggu TTD', className: 'bg-white' },
    { key: 'signed', label: 'Sudah TTD', hint: 'Siap pencairan', className: 'bg-white' },
    { key: 'disbursed', label: 'Dicairkan', hint: 'Proses selesai', className: 'bg-white' },
];

export default function Dashboard() {
    const [stats, setStats] = useState(null);
    const role = window.authUser?.role;

    useEffect(() => {
        window.showLoader();
        window.$.ajax({
            url: '/dashboard/stats',
            type: 'GET',
            success: (res) => setStats(res),
            error: window.ajaxError,
            complete: window.hideLoader,
        });
    }, []);

    const valueOf = (key) => {
        if (!stats) return '—';
        if (key === 'total') return stats.total;
        return stats.counts?.[key] ?? 0;
    };

    return (
        <div className="space-y-6">
            <section className="overflow-hidden rounded-2xl bg-navy-900 p-6 text-white shadow-sm">
                <p className="text-sm text-gold-400">Selamat bekerja, {window.authUser?.name}</p>
                <h2 className="mt-1 text-2xl font-semibold">Alur kredit kendaraan PT. JKL</h2>
                <p className="mt-2 max-w-2xl text-sm text-slate-300">
                    Digitalisasi pengganti fotokopi, routing kertas, dan cetak manual: input pengajuan, approval, cetak kontrak/PO, unggah TTD, lalu pencairan.
                </p>
            </section>

            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {CARDS.map((card) => (
                    <article key={card.key} className={`rounded-2xl border border-slate-200 p-5 shadow-sm ${card.className}`}>
                        <p className={`text-sm ${card.key === 'total' ? 'text-slate-300' : 'text-slate-500'}`}>{card.label}</p>
                        <p className="mt-2 text-3xl font-semibold">{valueOf(card.key)}</p>
                        <p className={`mt-1 text-xs ${card.key === 'total' ? 'text-gold-400' : 'text-slate-400'}`}>{card.hint}</p>
                    </article>
                ))}
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 className="font-semibold text-navy-900">Langkah cepat</h3>
                <div className="mt-4 grid gap-3 md:grid-cols-3">
                    {(role === 'dealer' || role === 'marketing') && (
                        <a href="/pengajuan/create" className="rounded-xl border border-slate-200 p-4 hover:border-gold-500">
                            <p className="font-medium text-navy-900">Buat pengajuan</p>
                            <p className="mt-1 text-sm text-slate-500">Input data konsumen, kendaraan, dan unggah berkas.</p>
                        </a>
                    )}
                    {role === 'atasan_marketing' && (
                        <a href="/pengajuan" className="rounded-xl border border-slate-200 p-4 hover:border-gold-500">
                            <p className="font-medium text-navy-900">Review pengajuan</p>
                            <p className="mt-1 text-sm text-slate-500">Setujui atau tolak pengajuan yang masuk.</p>
                        </a>
                    )}
                    {role === 'admin_backoffice' && (
                        <a href="/pengajuan" className="rounded-xl border border-slate-200 p-4 hover:border-gold-500">
                            <p className="font-medium text-navy-900">Cetak & cairkan</p>
                            <p className="mt-1 text-sm text-slate-500">Generate kontrak/PO lalu catat pencairan dana.</p>
                        </a>
                    )}
                    <a href="/pengajuan" className="rounded-xl border border-slate-200 p-4 hover:border-gold-500">
                        <p className="font-medium text-navy-900">Lihat daftar</p>
                        <p className="mt-1 text-sm text-slate-500">Pencarian dan paging dikerjakan di server.</p>
                    </a>
                </div>
            </section>
        </div>
    );
}
