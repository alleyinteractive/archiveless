<?php
/**
 * Contains functions for working with assets (primarily JavaScript).
 *
 * @package Archiveless
 */

namespace Archiveless;

/**
 * Validate file paths to prevent a PHP error if a file doesn't exist.
 *
 * @param string $path The file path to validate.
 * @return bool        True if the path is valid and the file exists.
 */
function validate_path( string $path ): bool {
	return ( 0 === validate_file( $path ) || 2 === validate_file( $path ) ) && file_exists( $path );
}

/**
 * Load the php index files from the build directory for blocks, slotfills, and any other scripts with an index.php file.
 */
function load_scripts(): void {
	$files = glob( dirname( __DIR__ ) . '/build/**/index.php' );

	if ( ! empty( $files ) ) {
		foreach ( $files as $path ) {
			if ( validate_path( $path ) ) {
				require_once $path;  // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}
	}
}
