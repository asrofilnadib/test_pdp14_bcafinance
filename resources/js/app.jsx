import { createRoot } from 'react-dom/client';
import Dashboard from './pages/Dashboard';
import PengajuanList from './pages/PengajuanList';
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

    if (page === 'pengajuan-list') {
        root.render(<PengajuanList canCreate={el.dataset.canCreate === '1'} />);
        return;
    }

    if (page === 'pengajuan-detail') {
        root.render(<PengajuanDetail pengajuanPublicId={el.dataset.publicId} />);
    }
});
