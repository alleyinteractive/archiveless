<?php
/**
 * General test file
 *
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
 *
 * @package Archiveless
 */

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

/**
 * General Test Case
 */
class Test_General extends Test_Case {
	use Refresh_Database;

	protected $archiveless_post;

	protected $archiveable_post;

	protected $archiveable_post_custom_status;

	protected function setUp(): void {
		parent::setUp();

		$category_id = static::factory()->term->create(
			[
				'taxonomy' => 'category',
				'name'     => 'archives',
			]
		);

		$author_id = static::factory()->user->create(
			[
				'role'        => 'author',
				'user_login'  => 'test_author',
				'description' => 'test_author',
			]
		);

		$defaults = [
			'post_date'     => '2015-01-01 00:00:01',
			'post_category' => [ $category_id ],
			'post_author'   => $author_id,
			'post_content'  => 'Lorem ipsum',
		];

		$this->archiveless_post = static::factory()->post->create(
			array_merge(
				$defaults,
				[
					'post_title'  => 'archiveless post',
					'post_status' => 'archiveless',
				]
			)
		);

		$this->archiveable_post = static::factory()->post->create(
			array_merge(
				$defaults,
				[
					'post_title'  => 'archiveable post',
					'post_status' => 'publish',
				]
			)
		);

		// Register another custom post status that is public.
		register_post_status(
			'other-public-status',
			[
				'public'              => true,
				'exclude_from_search' => false,
			]
		);

		$this->archiveable_post_custom_status = static::factory()->post->create(
			[
				'post_title'  => 'Test Archiveless Post',
				'post_status' => 'other-public-status',
			]
		);
	}

	public function test_verify_post_status_exists() {
		$this->assertContains( 'archiveless', get_post_stati() );
	}

	public function test_accesible_as_singular() {
		$this->get( get_permalink( $this->archiveless_post ) )
			->assertQueryTrue( 'is_singular', 'is_single' )
			->assertQueriedObjectId( $this->archiveless_post )
			->assertElementExists( 'head/meta[@name="robots"][@content="noindex,nofollow"]' );
	}

	public function test_non_archiveless_post_singular() {
		$this->get( get_permalink( $this->archiveable_post ) )
			->assertQueryTrue( 'is_singular', 'is_single' )
			->assertQueriedObjectId( $this->archiveable_post )
			->assertElementMissing( 'head/meta[@name="robots"][@content="noindex,nofollow"]' );
	}

	public function test_archiveless_is_method() {
		$this->assertTrue( Archiveless::is( $this->archiveless_post ) );
		$this->assertTrue( Archiveless::is( get_post( $this->archiveless_post ) ) );

		$this->assertFalse( Archiveless::is( $this->archiveable_post ) );
		$this->assertFalse( Archiveless::is( get_post( $this->archiveable_post ) ) );
	}

	public function test_always_included_outside_of_main_query_by_default_with_wp_query() {
		$post_ids = $this->query(
			[
				'fields'           => 'ids',
				'posts_per_page'   => -1,
				'suppress_filters' => false,
			]
		);

		$this->assertContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	/**
	 * Test that archiveless posts are included in get_posts() calls by default.
	 * get_posts() sets a default post_status argument of 'publish'.
	 */
	public function test_not_included_with_get_posts_by_default() {
		$post_ids = get_posts(
			[
				'fields'           => 'ids',
				'posts_per_page'   => -1,
				'suppress_filters' => false,
			]
		);

		$this->assertNotContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	public function test_always_included_outside_of_main_query_with_post_status_publish_with_get_posts_and_post_statuses() {
		$post_ids = get_posts(
			[
				'fields'           => 'ids',
				'post_status'      => [ 'archiveless', 'publish' ],
				'posts_per_page'   => -1,
				'suppress_filters' => false,
			]
		);

		$this->assertContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	public function test_always_included_outside_of_main_query_with_post_status_publish_with_get_posts_and_include_archiveless() {
		$post_ids = get_posts(
			[
				'fields'              => 'ids',
				'include_archiveless' => true,
				'posts_per_page'      => -1,
				'suppress_filters'    => false,
			]
		);

		$this->assertContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	public function test_always_included_outside_of_main_query_with_post_status_any_with_wp_query() {
		$post_ids = $this->query(
			[
				'fields'           => 'ids',
				'posts_per_page'   => 100,
				'suppress_filters' => false,
				'post_status'      => 'any',
			]
		);

		$this->assertContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	public function test_always_included_outside_of_main_query_with_post_status_any_with_get_posts() {
		$post_ids = get_posts(
			[
				'fields'           => 'ids',
				'posts_per_page'   => 100,
				'suppress_filters' => false,
				'post_status'      => 'any',
			]
		);

		$this->assertContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	public function test_query_archiveless_posts_only() {
		$post_ids = $this->query(
			[
				'fields'           => 'ids',
				'post_status'      => 'archiveless',
				'posts_per_page'   => 100,
				'suppress_filters' => false,
			]
		);

		$this->assertContains( $this->archiveless_post, $post_ids );
		$this->assertNotContains( $this->archiveable_post, $post_ids );
	}

	public function test_optionally_excluded_outside_of_main_query_with_exclude_archiveless() {
		$post_ids = $this->query(
			[
				'exclude_archiveless' => true,
				'fields'              => 'ids',
				'posts_per_page'      => 100,
				'suppress_filters'    => false,
			]
		);

		$this->assertNotContains( $this->archiveless_post, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
	}

	public function test_draft_post_hidden_from_archive() {
		$category = static::factory()->category->create();

		$post = static::factory()->post->with_terms( $category )->create(
			[
				'post_status' => 'draft',
			]
		);

		$this->get( get_category_link( $category ) )
			->assertOk()
			->assertDontSee( get_the_title( $post ) );

		$this->assertNotContains( $post, wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	}

	/**
	 * Test that an archiveless post is not accessible under multiple conditions.
	 *
	 * @dataProvider inaccessible
	 */
	public function test_inaccessible( $url, $conditional ) {
		$this->get( $url )
			->assertOk()
			->assertSee( get_the_title( $this->archiveable_post ) )
			->assertDontSee( get_the_title( $this->archiveless_post ) );

		$this->assertFalse( is_singular() );
		$this->assertTrue( $conditional(), "Asserting that {$conditional}() is true" );
	}

	public function inaccessible() {
		return [
			[ '/', 'is_home' ], // Homepage.
			[ '/2015/01/', 'is_date' ], // Date archive.
			[ '/category/archives/', 'is_category' ], // Tax archive.
			[ '/author/test_author/', 'is_author' ], // Author archive.
			[ '/?s=Lorem+ipsum', 'is_search' ], // Search.
		];
	}

	public function test_future_post_transition() {
		// Create a future post and add the archiveless post meta value.
		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'future archiveless post',
				'post_date'   => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
				'post_status' => 'future',
			]
		);
		add_post_meta( $post_id, 'archiveless', '1' );

		// Verify that the post correctly inserted with the 'future' status.
		$this->assertEquals( 'future', get_post_status( $post_id ) );

		// Set a new date in the past.
		$new_date = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		wp_update_post(
			[
				'ID'            => $post_id,
				'post_date'     => $new_date,
				'post_date_gmt' => get_gmt_from_date( $new_date ),
			]
		);

		// Verify that the post transitioned to 'archiveless' (due to the post meta).
		$this->assertEquals( 'archiveless', get_post_status( $post_id ) );
	}

	/**
	 * Ensures that the post status updates successfully when the archiveless
	 * post meta is added or updated.
	 */
	public function test_post_meta_hooks() {
		// Create a post.
		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'Test Archiveless Post',
				'post_status' => 'publish',
			]
		);

		// Verify that the post status is 'publish'.
		$this->assertEquals( 'publish', get_post_status( $post_id ) );

		// Add the archiveless postmeta (rather than updating it) and ensure the post status changes.
		add_post_meta( $post_id, 'archiveless', '1' );
		$this->assertEquals( 'archiveless', get_post_status( $post_id ) );

		// Update the post meta value and ensure it changes back to publish.
		update_post_meta( $post_id, 'archiveless', '0' );
		$this->assertEquals( 'publish', get_post_status( $post_id ) );

		// Update the post meta value and ensure it changes back to archiveless.
		update_post_meta( $post_id, 'archiveless', '1' );
		$this->assertEquals( 'archiveless', get_post_status( $post_id ) );
	}

	public function test_post_preview() {
		$post_id = static::factory()->post->create(
			[
				'post_title'  => 'Test Archiveless Preview Post',
				'post_status' => 'draft',
			]
		);

		$this->get( get_preview_post_link( $post_id ) )->assertNotFound();
		$this->get( remove_query_arg( 'preview', get_preview_post_link( $post_id ) ) )->assertNotFound();

		$this->acting_as( 'editor' );

		$this->get( get_preview_post_link( $post_id ) )
			->assertOk()
			->assertQueriedObjectId( $post_id );

		// Attempt without 'preview' being passed.
		$this->get( remove_query_arg( 'preview', get_preview_post_link( $post_id ) ) )
			->assertOk()
			->assertQueriedObjectId( $post_id );
	}

	public function test_custom_post_status_singular() {
		$this->get( get_permalink( $this->archiveable_post_custom_status ) )
			->assertOk()
			->assertElementMissing( 'head/meta[@name="robots"][@content="noindex,nofollow"]' );
	}

	public function test_custom_post_status_query_included() {
		// Ensure the custom post status is queryable.
		$post_ids = $this->query(
			[
				'fields'           => 'ids',
				'posts_per_page'   => 100,
				'suppress_filters' => false,
			]
		);

		$this->assertContains( $this->archiveable_post_custom_status, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
		$this->assertContains( $this->archiveless_post, $post_ids );

		// Make the query again but exclude the archiveless post.
		$post_ids = $this->query(
			[
				'exclude_archiveless' => true,
				'fields'              => 'ids',
				'posts_per_page'      => 100,
				'suppress_filters'    => false,
			]
		);

		$this->assertContains( $this->archiveable_post_custom_status, $post_ids );
		$this->assertContains( $this->archiveable_post, $post_ids );
		$this->assertNotContains( $this->archiveless_post, $post_ids );
	}

	/**
	 * Make a query and retrieve with WP_Query the posts only.
	 *
	 * Similar to `get_posts()` but does not set any defaults like `get_posts()` does.
	 *
	 * @param array $args Query arguments.
	 * @return array \WP_Post[]|int[] Array of posts.
	 */
	protected function query( array $args ): array {
		$query = new \WP_Query( $args );

		return $query->posts;
	}
}
