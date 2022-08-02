<?php
/**
 * Archiveless Plugin: Archiveless Class
 *
 * @package Archiveless
 */

/**
 * The main class for the Archiveless plugin.
 */
class Archiveless {

	/**
	 * Contains the singleton instance of the class after initialization.
	 *
	 * @access private
	 * @var Archiveless
	 */
	private static $instance;

	/**
	 * The post status slug used by this plugin.
	 *
	 * @access public
	 * @var string
	 */
	public static $status = 'archiveless';

	/**
	 * The meta key used by this plugin.
	 *
	 * @access protected
	 * @var string
	 */
	protected static $meta_key = 'archiveless';

	/**
	 * Archiveless constructor.
	 */
	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	/**
	 * Instance method. Initializes the instance, if not done already, and returns it.
	 *
	 * @return Archiveless The active instance of the class.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Archiveless();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Determine if gutenberg editor exists and
	 * the post type has support for the `custom-fields`.
	 *
	 * @return boolean
	 */
	public function is_block_editor() {
		$is_block_editor = false;

		// Do we have access to current screen?
		if ( did_action( 'current_screen' ) && is_admin() ) {
			$current_screen = get_current_screen();

			if ( $current_screen instanceof WP_Screen && post_type_supports( $current_screen->post_type, 'custom-fields' ) ) {
				$is_block_editor = wp_validate_boolean( $current_screen->is_block_editor );
			}
		}

		return $is_block_editor;
	}

	/**
	 * Register all actions and filters.
	 */
	public function setup() {
		add_action( 'init', [ $this, 'register_post_status' ] );
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'wp_loaded', [ $this, 'filter_rest_response' ] );
		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'updated_post_meta' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'updated_post_meta' ], 10, 4 );

		add_action( 'save_post', [ $this, 'save_post' ] );
		add_action( 'wp_head', [ $this, 'no_index' ] );

		if ( is_admin() ) {
			add_action( 'post_submitbox_misc_actions', [ $this, 'add_ui' ] );
			add_action( 'add_meta_boxes', [ $this, 'fool_edit_form' ] );
		} else {
			add_action( 'pre_get_posts', [ $this, 'on_pre_get_posts' ] );
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
					// translators: post count.
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

		// Ensure there is a post ID before attempting to look up postmeta.
		if ( empty( $post->ID ) ) {
			return;
		}
		?>
		<div class="misc-pub-section">
			<input type="hidden" name="<?php echo esc_attr( self::$meta_key ); ?>" value="0" />
			<label><input type="checkbox" name="<?php echo esc_attr( self::$meta_key ); ?>" value="1" <?php checked( '1', get_post_meta( $post->ID, self::$meta_key, true ) ); ?> /> <?php esc_html_e( 'Hide from Archives', 'archiveless' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Set the custom post status when the meta key changes.
	 *
	 * WordPress, unfortunately, doesn't provide a great way to _manage_ custom
	 * post statuses. While we can register and use them just fine, there are
	 * areas of the Admin where statuses are hard-coded. This method is part of
	 * this plugin's trickery to provide a seamless integration.
	 *
	 * @param int    $meta_id     ID of updated metadata entry.
	 * @param int    $object_id   ID of the object metadata is for.
	 * @param string $meta_key    Metadata key.
	 * @param mixed  $meta_value Metadata value. Serialized if non-scalar.
	 */
	public function updated_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Only handle updates to this plugin's meta key.
		if ( self::$meta_key !== $meta_key ) {
			return;
		}

		// If we are autosaving or the current post is a revision, bail.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $object_id )
			|| wp_is_post_autosave( $object_id )
		) {
			return;
		}

		// Try to get the post object.
		$post_object = get_post( $object_id );
		if ( empty( $post_object->post_status ) ) {
			return;
		}

		// Get the current status and switch if necessary.
		$is_archiveless = 1 === (int) $meta_value;
		if ( $is_archiveless && 'publish' === $post_object->post_status ) {
			// Archiveless was requested, but the post's status is currently publish, so we need to change it.
			$post_object->post_status = self::$status;
		} elseif ( ! $is_archiveless && self::$status === $post_object->post_status ) {
			// Archiveless was turned off, so we need to set the post status back to publish.
			$post_object->post_status = 'publish';
		} else {
			// No change, so bail early.
			return;
		}

		// Update the post with the new status.
		wp_update_post( $post_object );
	}

	/**
	 * Add a filter to all post types for REST response modification.
	 *
	 * @return void
	 */
	public function filter_rest_response() {
		// Override the post status in the REST response to avoid Gutenbugs.
		foreach ( get_post_types() as $allowed_post_type ) {
			add_filter( 'rest_prepare_' . $allowed_post_type, [ $this, 'rest_prepare_post_data' ] );
		}
	}

	/**
	 * Filters the post data for a response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @return WP_REST_Response The modified response.
	 */
	public function rest_prepare_post_data( $response ) {
		// Override the post status if it is 'archiveless'.
		if ( ! empty( $response->data['status'] ) && self::$status === $response->data['status'] ) {
			$response->data['status'] = 'publish';
		}

		return $response;
	}

	/**
	 * Fires when a post is transitioned from one status to another.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		// Only fire if transitioning to publish.
		if ( 'publish' !== $new_status || $new_status === $old_status ) {
			return;
		}

		// Only fire if archiveless postmeta is set to true.
		if ( 1 !== (int) get_post_meta( $post->ID, self::$meta_key, true ) ) {
			return;
		}

		// Change the post status to `archiveless` and update.
		$post->post_status = self::$status;
		wp_update_post( $post );
	}

	/**
	 * Store the value of the "Hide form Archives" checkbox to post meta.
	 *
	 * @param  int $post_id Post ID.
	 */
	public function save_post( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST[ self::$meta_key ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
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

		// Ensure there is a post status before attempting to set it.
		if ( empty( $post->post_status ) ) {
			return;
		}

		if ( self::$status === $post->post_status ) {
			$post->post_status = 'publish';
		}
	}

	/**
	 * Modify the query to hide archiveless posts on non-singular pages.
	 *
	 * Optionally allow archiveless posts to be hidden for other queries by
	 * passing 'exclude_archiveless'.
	 *
	 * @param \WP_Query $query Current WP_Query object.
	 */
	public function on_pre_get_posts( $query ) {
		// Don't modify the query if the post_status is set. A status of 'any'
		// or 'publish' is ignored since get_post() sets 'publish' as the
		// default post_status value when not defined.
		if (
			! empty( $query->get( 'post_status' ) )
			&& 'any' !== $query->get( 'post_status' )
			&& 'publish' !== $query->get( 'post_status' )
		) {
			return;
		}

		$post_statuses = $this->get_default_post_statuses( $query );

		// Determine if archiveless posts should be included or excluded from
		// the current query.
		if (
			( $query->is_main_query() && $query->is_singular() )
			|| ( ! $query->is_main_query() && ! $query->get( 'exclude_archiveless' ) )
		) {
			$query->set(
				'post_status',
				array_merge( $post_statuses, [ self::$status ] )
			);
		} else {
			// Exclude archiveless posts from the query.
			$query->set( // phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
				'post_status',
				array_diff( $post_statuses, [ self::$status ] ),
			);
		}
	}

	/**
	 * Retrieve the default post statuses to show for a request.
	 * Imitates the default behavior of WP_Query.
	 *
	 * @todo Include private post statuses for logged in users.
	 *
	 * @param \WP_Query $query Current WP_Query object.
	 * @return string[]
	 */
	public function get_default_post_statuses( $query ) {
		return $query->is_search()
			? array_keys(
				get_post_stati(
					[
						'exclude_from_search' => false,
						'publicly_queryable'  => true,
					]
				)
			)
			: array_keys( get_post_stati( [ 'publicly_queryable' => true ] ) );
	}

	/**
	 * Return robots meta if archiveless.
	 */
	public function no_index() {
		global $post;

		// Ensure there is a post ID before attempting to look up postmeta.
		if ( empty( $post->ID ) ) {
			return;
		}

		if ( '1' === get_post_meta( $post->ID, self::$meta_key, true ) ) {
			echo '<meta name="robots" content="noindex,nofollow" />';
		}
	}
}
