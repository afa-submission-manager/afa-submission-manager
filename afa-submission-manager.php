<?php
/**
 * Plugin Name:       AFA - Mobile-Ready Submission Manager
 * Plugin URI:        https://github.com/afa-submission-manager/afa-submission-manager
 * Description:       Simplify form management and gain insights with our robust WordPress plugin.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            claudionhangapc, marciogoes
 * Author URI:        https://claudionhangapc.com/
 * License:           GPL v2 or later
 * License URI:       https://claudionhangapc/gpl-2.0.html
 * Text Domain:       afa-submission-manager
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require __DIR__ . '/vendor/autoload.php';

define( 'AFASM_PLUGIN_FILE', __FILE__ );
define( 'AFASM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFASM_PLUGIN_LANGUAGE_FOLDER', dirname( plugin_basename( __FILE__ ) ) . '/languages' );

use AFASM\Includes\Plugins\JWT\AFASM_JWT_Plugin;
use AFASM\Includes\Routes\AFASM_Route;
use AFASM\Includes\Database\AFASM_Database_Installer;
use AFASM\Includes\Admin\AFASM_Admin_Options;
use AFASM\Includes\Plugins\AFASM_Constant;
use AFASM\Includes\Plugins\AFASM_Language;
use AFASM\Includes\Plugins\Notification\AFASM_Notification_Hooks_Plugin;

/**
 * Init api.
 */
function afasm_rest_init() {
	$namespace = AFASM_Constant::API_NAMESPACE . '/' . AFASM_Constant::API_VERSION;
	( new AFASM_Route( $namespace ) )->init();

	add_filter( 'rest_pre_dispatch', array( new AFASM_JWT_Plugin(), 'validate_token_rest_pre_dispatch' ), 10, 3 );
}

/**
* Add actions
*/
add_action( 'rest_api_init', 'afasm_rest_init' );

/**
* Register hooks.
*/
register_activation_hook( AFASM_PLUGIN_FILE, array( new AFASM_Database_Installer(), 'install' ) );


( new AFASM_Admin_Options() )->init();

add_action( 'plugins_loaded', array( new AFASM_Language(), 'all_forms_load_textdomain' ) );

add_filter( 'plugin_locale', array( new AFASM_Language(), 'enforce_locale' ), 10, 2 );

( new AFASM_Notification_Hooks_Plugin() )->loads_hooks();
