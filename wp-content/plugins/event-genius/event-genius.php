<?php
/*
Plugin Name: Event Genius
Description: Manage events and event registration with ease. Customizable registration forms, event calendars, and more.
Version: 1.8.1
Author: Event Genius
Author URI: https://wpeventgenius.com
License: GPLv2 or later
Text Domain: event-genius
*/

/*
Copyright 2026 by WP Event Genius LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Version and type constants
if ( ! defined( 'EVGE_VERSION' ) ) {
	define( 'EVGE_VERSION', '1.8.1' );
}

if ( ! defined( 'EVGE_FREE_VERSION' ) ) {
	define( 'EVGE_FREE_VERSION', true );
}

if ( ! defined( 'EVGE_TIER' ) ) {
	define( 'EVGE_TIER', 'free' );
}

// Prevent activation if another version is active
require_once plugin_dir_path( __FILE__ ) . 'includes/activate-check.php';
register_activation_hook( __FILE__, 'evge_prevent_activation_on_conflict' );

// Check for conflicts before loading
if ( ! evge_check_conflict_before_load() ) {
	return; // Another version is active, don't load this one
}

require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/init-free.php';
