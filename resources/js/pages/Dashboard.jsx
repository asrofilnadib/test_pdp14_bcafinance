import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const CARDS = [
    { key: 'total', label: 'Total Pengajuan', hint: 'Semua status', className: 'bg-navy-900 text-white' },
    { key: 'submitted', label: 'Menunggu Approval', hint: 'Perlu review atasan', className: 'bg-white' },
    { key: 'approved', label: 'Disetujui', hint: 'Siap cetak dokumen', className: 'bg-white' },
    { key: 'printed', label: 'Dicetak', hint: 'Menunggu TTD', className: 'bg-white' },
    { key: 'signed', label: 'Sudah TTD', hint: 'Siap pencairan', className: 'bg-white' },
    { key: 'disbursed', label: 'Dicairkan', hint: 'Proses selesai', className: 'bg-white' },
];

const STATUS_META = [
    { key: 'draft', label: 'Draft', color: '#94a3b8' },
    { key: 'submitted', label: 'Menunggu Approval', color: '#d97706' },
    { key: 'approved', label: 'Disetujui', color: '#059669' },
    { key: 'rejected', label: 'Ditolak', color: '#b42318' },
    { key: 'printed', label: 'Dicetak', color: '#0284c7' },
    { key: 'signed', label: 'Sudah TTD', color: '#4f46e5' },
    { key: 'disbursed', label: 'Dicairkan', color: '#102844' },
];

const FUNNEL_STEPS = [
    { key: 'submitted', label: 'Diajukan' },
    { key: 'approved', label: 'Disetujui' },
    { key: 'printed', label: 'Dicetak' },
    { key: 'signed', label: 'Sudah TTD' },
    { key: 'disbursed', label: 'Dicairkan' },
];

function applyTheme() {
    if (!window.Highcharts) return;
    window.Highcharts.setOptions({
        chart: { style: { fontFamily: 'Instrument Sans, sans-serif' }, backgroundColor: 'transparent' },
        credits: { enabled: false },
        colors: ['#102844', '#c9a227', '#059669', '#0284c7', '#4f46e5', '#d97706', '#b42318'],
        title: { style: { color: '#102844', fontWeight: '600' } },
        legend: { itemStyle: { color: '#334155' } },
        tooltip: { shared: true },
        exporting: { enabled: true },
    });
}

function makeChart(id, options) {
    if (!window.Highcharts || !document.getElementById(id)) return null;
    try {
        return window.Highcharts.chart(id, options);
    } catch (error) {
        console.error(error);
        return null;
    }
}

function renderCharts(stats) {
    if (!window.Highcharts || !stats) return [];

    applyTheme();
    const counts = stats.counts || {};
    const charts = [];

    charts.push(makeChart('chart-status', {
        chart: { type: 'column' },
        title: { text: 'Pengajuan per status' },
        xAxis: { categories: STATUS_META.map((item) => item.label), crosshair: true },
        yAxis: { min: 0, title: { text: 'Jumlah' }, allowDecimals: false },
        legend: { enabled: false },
        series: [{
            name: 'Pengajuan',
            data: STATUS_META.map((item) => ({ y: counts[item.key] || 0, color: item.color })),
        }],
    }));

    charts.push(makeChart('chart-pie', {
        chart: { type: 'pie' },
        title: { text: 'Komposisi status' },
        tooltip: { pointFormat: '{series.name}: <b>{point.y}</b> ({point.percentage:.1f}%)' },
        plotOptions: {
            pie: {
                innerSize: '55%',
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: { enabled: true, format: '{point.name}: {point.y}' },
            },
        },
        series: [{
            name: 'Pengajuan',
            data: STATUS_META.map((item) => ({
                name: item.label,
                y: counts[item.key] || 0,
                color: item.color,
            })),
        }],
    }));

    charts.push(makeChart('chart-funnel', {
        chart: { type: 'funnel' },
        title: { text: 'Funnel proses kredit' },
        plotOptions: {
            series: {
                dataLabels: { enabled: true, format: '{point.name}: {point.y}', softConnector: true },
                center: ['50%', '50%'],
                neckWidth: '25%',
                neckHeight: '20%',
                width: '70%',
            },
        },
        legend: { enabled: false },
        series: [{
            name: 'Pengajuan',
            data: FUNNEL_STEPS.map((item) => [item.label, counts[item.key] || 0]),
        }],
    }));

    charts.push(makeChart('chart-monthly', {
        chart: { type: 'areaspline' },
        title: { text: 'Tren 6 bulan terakhir' },
        xAxis: { categories: (stats.monthly || []).map((item) => item.label) },
        yAxis: { title: { text: 'Jumlah pengajuan' }, allowDecimals: false, min: 0 },
        series: [{
            name: 'Pengajuan masuk',
            data: (stats.monthly || []).map((item) => item.total),
            color: '#102844',
            fillColor: {
                linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                stops: [
                    [0, 'rgba(16, 40, 68, 0.35)'],
                    [1, 'rgba(16, 40, 68, 0.02)'],
                ],
            },
        }],
    }));

    charts.push(makeChart('chart-dealer', {
        chart: { type: 'bar' },
        title: { text: 'Pengajuan per dealer' },
        xAxis: { categories: (stats.dealers || []).map((item) => item.nama), title: { text: null } },
        yAxis: { min: 0, title: { text: 'Jumlah' }, allowDecimals: false },
        legend: { enabled: false },
        series: [{
            name: 'Pengajuan',
            data: (stats.dealers || []).map((item) => item.total),
            color: '#c9a227',
        }],
    }));

    return charts;
}

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

    useEffect(() => {
        if (!stats) return undefined;
        const charts = renderCharts(stats);
        return () => charts.forEach((chart) => chart?.destroy());
    }, [stats]);

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

            <section className="grid gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>Status pengajuan</CardTitle></CardHeader>
                    <CardContent><div id="chart-status" className="h-[360px]" /></CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Komposisi</CardTitle></CardHeader>
                    <CardContent><div id="chart-pie" className="h-[360px]" /></CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Funnel kredit</CardTitle></CardHeader>
                    <CardContent><div id="chart-funnel" className="h-[360px]" /></CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Tren bulanan</CardTitle></CardHeader>
                    <CardContent><div id="chart-monthly" className="h-[360px]" /></CardContent>
                </Card>
                <Card className="xl:col-span-2">
                    <CardHeader><CardTitle>Per dealer</CardTitle></CardHeader>
                    <CardContent><div id="chart-dealer" className="h-[320px]" /></CardContent>
                </Card>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 className="font-semibold text-navy-900">Langkah cepat</h3>
                <div className="mt-4 grid gap-3 md:grid-cols-3">
                    {(role === 'dealer' || role === 'marketing') && (
                        <a href="/pengajuan" className="rounded-xl border border-slate-200 p-4 hover:border-gold-500">
                            <p className="font-medium text-navy-900">Buat pengajuan</p>
                            <p className="mt-1 text-sm text-slate-500">Input data konsumen, kendaraan, dan unggah berkas lewat modal.</p>
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
