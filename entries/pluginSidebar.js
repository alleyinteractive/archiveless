/* global wp */

import ArchivelessToggle from '../plugins/sidebar/components/archivelessToggle';

const {
  plugins: {
    registerPlugin,
  },
} = wp;

registerPlugin('archiveless-toggle', {
  render: ArchivelessToggle,
});
