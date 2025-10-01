import { registerPlugin } from '@wordpress/plugins';
import ArchivelessToggle from './components/archiveless-toggle';

/**
 * Registers the ArchivelessToggle component as a plugin in the WordPress editor sidebar.
 */
registerPlugin('archiveless-toggle', { render: ArchivelessToggle });
