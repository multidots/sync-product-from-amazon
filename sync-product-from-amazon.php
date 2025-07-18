<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.multidots.com/
 * @since             1.0.0
 * @package           Sync_Product_From_Amazon
 *
 * @wordpress-plugin
 * Plugin Name:       Sync Product From Amazon
 * Plugin URI:        sync-product-from-amazon
 * Description:       This plugin will be used to retrieve data from Amazon using an ASIN number. It can fetch various Amazon product details such as price, sale price, CTA link, etc.
 * Version:           1.0.0
 * Author:            Multidots
 * Author URI:        https://www.multidots.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sync-product-from-amazon
 * Domain Path:       /languages
 */

namespace Sync_Product_From_Amazon;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SPFA_VERSION', '1.0.0' );
define( 'SPFA_URL', plugin_dir_url( __FILE__ ) );
define( 'SPFA_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPFA_BASEPATH', plugin_basename( __FILE__ ) );
define( 'SPFA_SRC_BLOCK_DIR_PATH', untrailingslashit( SPFA_DIR . 'assets/build/js/blocks' ) );

if ( ! defined( 'SPFA_PATH' ) ) {
	define( 'SPFA_PATH', __DIR__ );
}

// Load the autoloader.
require_once plugin_dir_path( __FILE__ ) . '/inc/helpers/autoloader.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_spfa_scaffold() {
	$plugin = new \Sync_Product_From_Amazon\Inc\Sync_Product_From_Amazon();
}
run_spfa_scaffold();
