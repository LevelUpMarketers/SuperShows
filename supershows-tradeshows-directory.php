<?php
/**
 * Plugin Name: SuperShows Trade Shows Directory
 * Plugin URI: https://superpath.com
 * Description: Custom trade show directory for SuperPath with a dedicated custom table for searchable records.
 * Version: 0.1.0
 * Author: SuperPath
 * Author URI: https://superpath.com
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: supershows-tradeshows-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUPERSHOWS_TRADE_SHOWS_VERSION', '0.1.0' );
define( 'SUPERSHOWS_TRADE_SHOWS_FILE', __FILE__ );
define( 'SUPERSHOWS_TRADE_SHOWS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUPERSHOWS_TRADE_SHOWS_DB_VERSION', '1.1.0' );

require_once SUPERSHOWS_TRADE_SHOWS_PATH . 'includes/class-supershows-tradeshows-activator.php';

/**
 * Runs plugin activation tasks.
 *
 * @return void
 */
function supershows_tradeshows_activate(): void {
	SuperShows_TradeShows_Activator::activate();
}
register_activation_hook( __FILE__, 'supershows_tradeshows_activate' );
