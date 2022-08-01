<?php

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

class Test_General extends Test_Case {
	use Refresh_Database;

	protected $archiveless_post;

	protected $archiveable_post;

	protected function setUp(): void {
		parent::setUp();

		$category_id = $this->factory->term->create( array(
			'taxonomy' => 'category',
			'name' => 'archives',
		) );

		$author_id = $this->factory->user->create( array(
			'role'        => 'author',
			'user_login'  => 'test_author',
			'description' => 'test_author',
		) );

		$defaults = array(
			'post_date'     => '2015-01-01 00:00:01',
			'post_category' => array( $category_id ),
			'post_author'   => $author_id,
			'post_content'  => 'Lorem ipsum',
		);

		$this->archiveless_post = $this->factory->post->create( array_merge( $defaults, array( 'post_title' => 'archiveless post', 'post_status' => 'archiveless' ) ) );
		$this->archiveable_post = $this->factory->post->create( array_merge( $defaults, array( 'post_title' => 'archiveable post', 'post_status' => 'publish' ) ) );
	}

	public function test_verify_post_status_exists() {
		$this->assertContains( 'archiveless', get_post_stati() );
	}

	public function test_accesible_as_singular() {
		$this->go_to( get_permalink( $this->archiveless_post ) );

		$this->assertQueriedObjectId( $this->archiveless_post );
		$this->assertQueryTrue( 'is_singular', 'is_single' );
	}

	/**
	 * @dataProvider inaccessible
	 */
	public function test_inaccessible( $url, $conditional ) {
		$this->go_to( $url );

		$this->assertFalse( is_singular() );
		$this->assertTrue( call_user_func( $conditional ), "Asserting that {$conditional}() is true" );
		$this->assertTrue( have_posts() );
		$this->assertContains( $this->archiveable_post, wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
		$this->assertNotContains( $this->archiveless_post, wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	}

	public function inaccessible() {
		return array(
			array( '/', 'is_home' ), // Homepage
			array( '/?year=2015&monthnum=01', 'is_date' ), // Date archive
			array( '/?category_name=archives', 'is_category' ), // Tax archive
			array( '/?author_name=test_author', 'is_author' ), // Author archive
			array( '/?s=Lorem+ipsum', 'is_search' ), // Search
			array( '/?feed=rss', 'is_feed' ), // Feeds
		);
	}

	public function test_future_post_transition() {
		// Create a future post and add the archiveless post meta value
		$post_id = $this->factory->post->create( array(
			'post_title' => 'future archiveless post',
			'post_date' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			'post_status' => 'future',
		) );
		add_post_meta( $post_id, 'archiveless', '1' );

		// Verify that the post correctly inserted with the 'future' status
		$this->assertEquals( 'future', get_post_status( $post_id ) );

		// Set a new date in the past
		$new_date = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		wp_update_post( array(
			'ID' => $post_id,
			'post_date' => $new_date,
			'post_date_gmt' => get_gmt_from_date( $new_date ),
		) );

		// Verify that the post transitioned to 'archiveless' (due to the post meta)
		$this->assertEquals( 'archiveless', get_post_status( $post_id ) );
	}

	/**
	 * Ensures that the post status updates successfully when the archiveless
	 * post meta is added or updated.
	 */
	public function test_post_meta_hooks() {
		// Create a post.
		$post_id = $this->factory->post->create( array(
			'post_title' => 'Test Archiveless Post',
			'post_status' => 'publish',
		) );

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
}
