import ArchivelessToggle from './components/archiveless-toggle';

const { plugins: { registerPlugin } } = wp;

registerPlugin('archiveless-toggle', { render: ArchivelessToggle });
