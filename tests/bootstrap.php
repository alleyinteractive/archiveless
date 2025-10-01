<?php
/**
 * Initialize the plugin for testing.
 *
 * @package Archiveless
 */

\Mantle\Testing\manager()
	->theme( 'twentytwentythree' )
	->loaded( fn () => require_once __DIR__ . '/../archiveless.php' )
	->install();
