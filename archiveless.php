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

	public $status = 'archiveless';

	protected $meta_key = 'archiveless';

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
	 * Register all actions and filters.
	 */
	public function setup() {
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );

		if ( is_admin() ) {
			add_action( 'post_submitbox_misc_actions', array( $this, 'add_ui' ) );
			add_action( 'add_meta_boxes', array( $this, 'fool_edit_form' ) );
		} else {
			// add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
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
		register_post_status( $this->status, apply_filters( 'archiveless_post_status_args', array(
			'label'                     => __( 'Hidden from Archives', 'archiveless' ),
			'label_count'               => _n_noop( 'Hidden from Archives <span class="count">(%s)</span>', 'Hidden from Archives <span class="count">(%s)</span>', 'archiveless' ),
			'exclude_from_search'       => ! ( defined( 'WP_CLI' ) && WP_CLI ),
			'public'                    => true,
			'publicly_queryable'        => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
		) ) );
	}

	/**
	 * Add the checkbox to the post edit screen to give the option to hide a
	 * post from archives.
	 */
	public function add_ui() {
		global $post;
		?>
		<div class="misc-pub-section">
			<input type="hidden" name="<?php echo esc_attr( $this->meta_key ) ?>" value="0" />
			<label><input type="checkbox" name="<?php echo esc_attr( $this->meta_key ) ?>" value="1" <?php checked( '1', get_post_meta( $post->ID, $this->meta_key, true ) ) ?> /> <?php esc_html_e( 'Hide from Archives', 'archiveless' ) ?></label>
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
		if ( 'publish' == $data['post_status'] ) {
			if ( isset( $_POST[ $this->meta_key ] ) ) {
				if ( '1' === $_POST[ $this->meta_key ] ) {
					$data['post_status'] = $this->status;
				}
			} elseif ( ! empty( $postarr['ID'] ) && '1' === get_post_meta( $postarr['ID'], $this->meta_key, true ) ) {
				$data['post_status'] = $this->status;
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
		if ( isset( $_POST[ $this->meta_key ] ) ) {
			update_post_meta( $post_id, $this->meta_key, intval( $_POST[ $this->meta_key ] ) );
		}
	}

	/**
	 * Fool the edit screen into thinking that an archiveless post status is
	 * actually 'publish'. This lets WordPress use its hard-coded post statuses
	 * seamlessly.
	 */
	public function fool_edit_form() {
		global $post;
		if ( $this->status == $post->post_status ) {
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
		if ( $query->is_main_query() && ! $query->is_singular() && false !== strpos( $where, " OR {$wpdb->posts}.post_status = '{$this->status}'" ) ) {
			$where = str_replace( " OR {$wpdb->posts}.post_status = '{$this->status}'", '', $where );
		}

		return $where;
	}
}
add_action( 'after_setup_theme', array( 'Archiveless', 'instance' ) );
