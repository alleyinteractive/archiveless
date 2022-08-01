<?php
/**
 * Initialize the plugin for testing.
 */

\Mantle\Testing\manager()
	->on( 'muplugins_loaded', fn () => require_once dirname( __FILE__ ) . '/../archiveless.php' )
	->install();
