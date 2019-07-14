<?php

/*
	Plugin Name: Archiveless
	Plugin URI: https://github.com/alleyinteractive/archiveless
	Description: Hide posts from archives performantly
	Version: 0.1
	Author: Alley Interactive
	Author URI: http://www.alleyinteractive.com/
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class Archiveless {

	private static $instance;

	public static $status = 'archiveless';

	protected static $meta_key = 'archiveless';

	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Archiveless;
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Determine if gutenberg editor exists.
	 *
	 * @return boolean
	 */
	public function is_block_editor() {
		$is_block_editor = false;

		// Do we have access to current screen?
		if ( did_action( 'current_screen' ) && is_admin() ) {
			$current_screen = get_current_screen();
			$is_block_editor = $current_screen instanceof WP_Screen ? $current_screen->is_block_editor : false;
		}

		return $is_block_editor;
	}

	/**
	 * Register all actions and filters.
	 */
	public function setup() {
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_action( 'init', array( $this, 'register_post_meta' ) );

		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );

		if ( is_admin() ) {
			add_action( 'post_submitbox_misc_actions', array( $this, 'add_ui' ) );
			add_action( 'add_meta_boxes', array( $this, 'fool_edit_form' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		} else {
			add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		}
	}

	/**
	 * Register the custom post status.
	 */
	public function register_post_status() {
		/**
		 * Filters the arguments passed to `register_post_status()`.
		 *
		 * @see register_post_status().
		 */
		register_post_status(
			self::$status,
			apply_filters(
				'archiveless_post_status_args',
				[
					'label'                     => __( 'Hidden from Archives', 'archiveless' ),
					'label_count'               => _n_noop( 'Hidden from Archives <span class="count">(%s)</span>', 'Hidden from Archives <span class="count">(%s)</span>', 'archiveless' ),
					'exclude_from_search'       => true,
					'public'                    => true,
					'publicly_queryable'        => true,
					'show_in_admin_status_list' => true,
					'show_in_admin_all_list'    => true,
				]
			)
		);
	}

	/**
	 * Register the custom post meta.
	 */
	public function register_post_meta() {
		register_post_meta(
			'',
			self::$meta_key,
			[
				'type'              => 'boolean',
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_validate_boolean',
				'single'            => true,
			]
		);
	}

	/**
	 * Add the checkbox to the post edit screen to give the option to hide a
	 * post from archives.
	 */
	public function add_ui() {
		global $post;
		?>
		<div class="misc-pub-section">
			<input type="hidden" name="<?php echo esc_attr( self::$meta_key ) ?>" value="0" />
			<label><input type="checkbox" name="<?php echo esc_attr( self::$meta_key ) ?>" value="1" <?php checked( '1', get_post_meta( $post->ID, self::$meta_key, true ) ) ?> /> <?php esc_html_e( 'Hide from Archives', 'archiveless' ) ?></label>
		</div>
		<?php
	}

	/**
	 * Set the custom post status when post data is being inserted.
	 *
	 * WordPress, unfortunately, doesn't provide a great way to _manage_ custom
	 * post statuses. While we can register and use them just fine, there are
	 * areas of the Admin where statuses are hard-coded. This method is part of
	 * this plugin's trickery to provide a seamless integration.
	 *
	 * @param  array $data Slashed post data to be inserted into the database.
	 * @param  array $postarr Raw post data used to generate `$data`. This
	 *                        contains, amongst other things, the post ID.
	 * @return array $data, potentially with a new status.
	 */
	public function wp_insert_post_data( $data, $postarr ) {
		if ( 'publish' === $data['post_status'] ) {
			// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
			if ( isset( $_POST[ self::$meta_key ] ) ) {
				if ( '1' === $_POST[ self::$meta_key ] ) {
					$data['post_status'] = self::$status;
				}
			} elseif ( ! empty( $postarr['ID'] ) && '1' === get_post_meta( $postarr['ID'], self::$meta_key, true ) ) {
				$data['post_status'] = self::$status;
			}
		}

		return $data;
	}

	/**
	 * Store the value of the "Hide form Archives" checkbox to post meta.
	 *
	 * @param  int $post_id Post ID.
	 */
	public function save_post( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
		if ( isset( $_POST[ self::$meta_key ] ) ) {
			update_post_meta( $post_id, self::$meta_key, intval( $_POST[ self::$meta_key ] ) );
		}
	}

	/**
	 * Fool the edit screen into thinking that an archiveless post status is
	 * actually 'publish'. This lets WordPress use its hard-coded post statuses
	 * seamlessly.
	 */
	public function fool_edit_form() {
		global $post;
		if ( self::$status === $post->post_status ) {
			$post->post_status = 'publish';
		}
	}

	/**
	 * Hide archiveless posts on non-singular pages.
	 *
	 * @param  string $where MySQL WHERE clause.
	 * @param  WP_Query $query Current WP_Query object.
	 * @return string WHERE clause, potentially with 'archiveless' post status
	 *                      removed.
	 */
	public function posts_where( $where, $query ) {
		global $wpdb;

		$archiveless_status = self::$status;

		if (
			$query->is_main_query() &&
			! $query->is_singular() &&
			false !== strpos( $where, " OR {$wpdb->posts}.post_status = '{$archiveless_status}'" )
		) {
			$where = str_replace(
				" OR {$wpdb->posts}.post_status = '{$archiveless_status}'",
				'',
				$where
			);
		}

		return $where;
	}

	/**
	 * Enqueue general-purpose scripts in the admin.
	 */
	public function admin_enqueue_scripts() {

		// Only enqueue for Block Editor pages.
		if ( $this->is_block_editor() ) {
			wp_enqueue_script(
				'wp-starter-plugin-plugin-sidebar',
				plugins_url( 'build/pluginSidebar.js', __FILE__ ),
				[ 'wp-i18n', 'wp-edit-post' ],
				filemtime( plugin_dir_path( __FILE__ ) . 'build/pluginSidebar.js' ),
				true
			);
			$this->inline_locale_data( 'wp-starter-plugin-plugin-sidebar' );
		}
	}

	/**
	 * Creates a new Jed instance with specified locale data configuration.
	 *
	 * @param string $to_handle The script handle to attach the inline script to.
	 */
	public function inline_locale_data( string $to_handle ) {
		// Define locale data for Jed.
		$locale_data = [
			'' => [
				'domain' => 'wp-starter-plugin',
				'lang'   => is_admin() ? get_user_locale() : get_locale(),
			],
		];

		// Pass the Jed configuration to the admin to properly register i18n.
		wp_add_inline_script(
			$to_handle,
			'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ", 'wp-starter-plugin' );"
		);
	}
}
add_action( 'after_setup_theme', array( 'Archiveless', 'instance' ) );
