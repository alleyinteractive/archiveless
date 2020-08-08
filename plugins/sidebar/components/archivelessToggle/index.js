/* global React, wp */

import PropTypes from 'prop-types';

const {
  components: {
    createSlotFill,
    ToggleControl,
  },
  compose: {
    compose,
  },
  data: {
    withDispatch,
    withSelect,
  },
  i18n: {
    __,
  },
} = wp;

const { Fill: PluginPostStatusInfo } = createSlotFill('PluginPostStatusInfo');

class ArchivelessToggle extends React.PureComponent {
  static propTypes = {
    meta: PropTypes.shape({
      archiveless: PropTypes.bool,
    }).isRequired,
    onUpdate: PropTypes.func.isRequired,
    post: PropTypes.shape({}).isRequired,
  };

  /**
   * Renders the PluginSidebar.
   * @returns {object} JSX component markup.
   */
  render() {
    const {
      meta = {},
      onUpdate,
    } = this.props;

    const {
      archiveless = false,
    } = meta;

    return (
      <PluginPostStatusInfo>
        <hr />
        <ToggleControl
          label={__('Hide from Archives', 'archiveless')}
          checked={archiveless}
          onChange={(value) => onUpdate(
            'archiveless',
            value
          )}
        />
      </PluginPostStatusInfo>
    );
  }
}

export default compose([
  withSelect((select) => {
    const editor = select('core/editor');
    const {
      archiveless = false,
    } = editor.getEditedPostAttribute('meta') || {};

    return {
      meta: {
        archiveless,
      },
      post: editor.getCurrentPost(),
    };
  }),
  withDispatch((dispatch) => ({
    onUpdate: (metaKey, metaValue) => {
      dispatch('core/editor').editPost({
        meta: {
          [metaKey]: metaValue,
        },
      });
    },
  })),
])(ArchivelessToggle);
