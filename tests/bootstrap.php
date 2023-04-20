<?php
/**
 * Initialize the plugin for testing.
 *
 * @package Archiveless
 */

\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->loaded(
		function () {
			require_once __DIR__ . '/../archiveless.php';
			// switch_theme( 'twentytwentytwo' );
			// Set the permalink structure.
			update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
		}
	)
	->install();
