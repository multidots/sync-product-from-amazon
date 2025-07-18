<?php
/**
 * Dynamic Blocks.
 *
 * @package sync-product-from-amazon
 */

namespace Sync_Product_From_Amazon\Inc;

use Sync_Product_From_Amazon\Inc\Traits\Singleton;

/**
 * Class Blocks
 */
class Blocks {
	use Singleton;

	/**
	 * Plugin settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $spfa_api_options    API Settings of this plugin.
	 */
	private $spfa_api_options;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		$this->spfa_api_options = get_option( 'spfa_api_options' ) ?? array();

		if ( isset( $this->spfa_api_options['gutenberg_block'] ) ) {
			// load class.
			$this->setup_hooks();
		}
	}

	/**
	 * To register action/filter.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	protected function setup_hooks() {

		/**
		 * Load blocks classes.
		 */
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'spfa_custom_block_category' ) );
	}

	/**
	 * Automatically registers all blocks that are located within the includes/blocks directory
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Register all the blocks in the theme.
		if ( file_exists( SPFA_SRC_BLOCK_DIR_PATH ) ) {
			$block_json_files = glob( SPFA_SRC_BLOCK_DIR_PATH . '/*/block.json' );

			// auto register all blocks that were found.
			foreach ( $block_json_files as $filename ) {
				// Retrieve block meta data.
				$metadata = wp_json_file_decode( $filename, array( 'associative' => true ) );
				if ( empty( $metadata ) || empty( $metadata['name'] ) ) {
					continue;
				}

				$block_name = $metadata['name'];
				$class_name = $this->block_class_from_string( $block_name );

				if ( $class_name && class_exists( $class_name, true ) ) {
					$block = $class_name::get_instance();
					$block->init();
				}
			}
		}
	}

	/**
	 * Take a string with a block name, return the class name.
	 *
	 * @param string $string string to generate class name from.
	 *
	 * @return string|null class name with namespace
	 */
	public static function block_class_from_string( string $string ): ?string {
		// Force lowercase. Normalize.
		$string = strtolower( $string );

		// Default namespace for blocks.
		$namespace = 'Sync_Product_From_Amazon\Blocks\\';

		// Remove namespace from block name.
		if ( false !== strpos( $string, 'spfa/' ) ) {
			$string = str_replace( 'spfa/', '', $string );
		}

		// Blow up names on the hyphens.
		$split = explode( '-', $string );

		// Upper Case Words when we join things back together.
		// implode is used on the variable that is exploded above.
		return $namespace . implode( '_', array_map( 'ucfirst', (array) $split ) );
	}

	/**
	 * Register Custom Block Category
	 *
	 * @param string $categories return categories array.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function spfa_custom_block_category( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'sync-product-from-amazon',
					'title' => __( 'Sync Product From Amazon Block', 'sync-product-from-amazon' ),
					'icon'  => 'welcome-add-page',
				),
			),
			$categories
		);
	}
}
