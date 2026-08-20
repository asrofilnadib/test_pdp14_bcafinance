import { createRoot } from 'react-dom/client';
import Dashboard from './pages/Dashboard';
import PengajuanForm from './pages/PengajuanForm';
import PengajuanDetail from './pages/PengajuanDetail';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('react-root');
    if (!el) {
        return;
    }

    const page = el.dataset.page;
    const root = createRoot(el);

    if (page === 'dashboard') {
        root.render(<Dashboard />);
        return;
    }

    if (page === 'pengajuan-form') {
        root.render(<PengajuanForm mode={el.dataset.mode} pengajuanId={el.dataset.id || ''} />);
        return;
    }

    if (page === 'pengajuan-detail') {
        root.render(<PengajuanDetail pengajuanId={el.dataset.id} />);
    }
});
