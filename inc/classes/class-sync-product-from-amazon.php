<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Sync_Product_From_Amazon
 * @subpackage Sync_Product_From_Amazon/includes
 * @author     Multidots <info@multidots.com>
 */

namespace Sync_Product_From_Amazon\Inc;

use Sync_Product_From_Amazon\Inc\Blocks;
use Sync_Product_From_Amazon\Inc\Traits\Singleton;

/**
 * Main class File.
 */
class Sync_Product_From_Amazon {


	use Singleton;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Sync_Product_From_Amazon_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SPFA_VERSION' ) ) {
			$this->version = SPFA_VERSION;
		} else {
			$this->version = '1.1.0';
		}
		$this->plugin_name = 'sync-product-from-amazon';

		// Filter to add action links.
		add_filter( 'plugin_action_links_' . $this->plugin_name . '/' . $this->plugin_name . '.php', array( $this, 'plugin_quick_links' ) );

		API::get_instance();
		Front::get_instance();
		Admin::get_instance();
		I18::get_instance();
		Blocks::get_instance();
	}

	/**
	 * Register quick links of the plugin.
	 *
	 * @param     array $actions    Quick links for the plugin.
	 * @return    array
	 */
	public function plugin_quick_links( array $actions ) {
		return array_merge(
			array(
				'settings' => '<a href="admin.php?page=sync-product-from-amazon&tab=api-settings">' . esc_html__( 'Settings', 'sync-product-from-amazon' ) . '</a>',
				'help'     => '<a href="admin.php?page=sync-product-from-amazon&tab=help">' . esc_html__( 'Help', 'sync-product-from-amazon' ) . '</a>',
			),
			$actions
		);
	}
}
