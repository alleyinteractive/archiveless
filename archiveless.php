<?php
/**
 * Plugin Name: Archiveless
 * Plugin URI: https://github.com/alleyinteractive/archiveless
 * Description: Hide posts from archives performantly
 * Version: 1.1.2
 * Author: Alley Interactive
 * Author URI: https://alley.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tested up to: 6.8
 * Requires PHP: 8.2
 *
 * @package Archiveless
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// Include Archiveless class.
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/class-archiveless.php';

// Initialize Archiveless.
add_action(
	'after_setup_theme',
	function (): void {
		Archiveless::instance();
	}
);
