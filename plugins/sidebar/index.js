/* global wp */

import ArchivelessToggle from './components/archivelessToggle';

const {
  plugins: {
    registerPlugin,
  },
} = wp;

registerPlugin('archiveless-toggle', {
  render: ArchivelessToggle,
});
