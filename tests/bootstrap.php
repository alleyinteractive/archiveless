<?php
/**
 * Initialize the plugin for testing.
 */

\Mantle\Testing\manager()
	->loaded( function () {
		require_once __DIR__ . '/../archiveless.php';

		// Set the permalink structure.
		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
	} )
	->install();
