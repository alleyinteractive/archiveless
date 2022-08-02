<?php

use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testkit\Test_Case;

class Test_Settings extends Test_Case {
  use Admin_Screen;

  public function test_is_block_editor() {
    $screen = \WP_Screen::get( 'edit-post' );
    $screen->post_type       = 'post';
    $screen->is_block_editor = true;

    set_current_screen( $screen );

    $this->assertTrue( is_admin() );
    $this->assertTrue( Archiveless::instance()->is_block_editor() );
  }

  public function test_is_not_block_editor() {
    $screen = \WP_Screen::get( 'edit-post' );
    $screen->post_type       = 'post';
    $screen->is_block_editor = false;

    set_current_screen( $screen );

    $this->assertTrue( is_admin() );
    $this->assertFalse( Archiveless::instance()->is_block_editor() );
  }
}