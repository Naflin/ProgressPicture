<?php
/**
 * Plugin Name: ZTT Progress Pictures
 * Plugin URI: http://zerototoned.co
 * Description: Add progress pictures and keep them up do date
 * Version: 1.0.0
 * Author: Nathan Blue
 * Author URI: http://zerototoned.co
 * License: GPL2
 */

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (file_exists( plugin_dir_path( __FILE__ ) . 'ztt_progress_widget.php')) {
	include( plugin_dir_path( __FILE__ ) . 'ztt_progress_widget.php');
}

$ztt_progress_app = new ztt_progress_widget;
