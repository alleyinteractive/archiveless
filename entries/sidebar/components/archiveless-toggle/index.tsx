import { PluginPostStatusInfo } from '@wordpress/editor';

import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { usePostMetaValue } from '@alleyinteractive/block-editor-tools';

/**
 * Archiveless Toggle Control
 */
function ArchivelessToggle() {
  const [archiveless, setArchiveless] = usePostMetaValue('archiveless');

  return (
    <PluginPostStatusInfo>
      <ToggleControl
        label={__('Hide from Archives', 'archiveless')}
        help={
          archiveless
            ? __('Hide from homepage, search, and archive pages. Show on the post permalink.', 'archiveless')
            : null
        }
        checked={archiveless}
        onChange={setArchiveless}
      />
    </PluginPostStatusInfo>
  );
}

export default ArchivelessToggle;
