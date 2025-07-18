<?php
/**
 * Registers the spfa/sync-product-from-amazon block.
 *
 * @global array    $attrs   Block attributes passed to the render callback.
 * @global string   $content Block content from InnerBlocks passed to the render callback.
 * @global WP_Block $block   Block registration object.
 *
 * @package sync-product-from-amazon
 */

namespace Sync_Product_From_Amazon\Blocks;

use Sync_Product_From_Amazon\Inc\Block_Base;
use WP_Block;

/**
 *  Class for the spfa/sync-product-from-amazon block.
 */
class Sync_Product_From_Amazon extends Block_Base {

	/**
	 * Field settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $spfa_app_field_options    Settings of this plugin.
	 */
	private $spfa_app_field_options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->_block = 'sync-product-from-amazon';
		$this->setup_hooks();
	}

	/**
	 * To register action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		$this->spfa_app_field_options = get_option( 'spfa_app_field_options' ) ?? array();
	}

	/**
	 * Render block.
	 *
	 * @param array    $attributes   Block attributes.
	 * @param string   $content      Block content.
	 * @param WP_Block $block        Block object.
	 * @return string
	 */
	public function render_callback(
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		array $attributes,
		string $content,
		WP_Block $block
		// phpcs:enable
	): string {

		// get string of attributes of the features that the block supports.
		$wrapper_attributes = get_block_wrapper_attributes();

		$block_attributes = $attributes['productDetails'] ?? array();

		ob_start();

		foreach ( $block_attributes as $block_attribute ) {
			// attributes.
			$title               = isset( $block_attribute['title'] ) ? $block_attribute['title'] : '';
			$media_url           = isset( $block_attribute['mediaURL'] ) ? $block_attribute['mediaURL'] : '';
			$asin                = isset( $block_attribute['asin'] ) ? $block_attribute['asin'] : '';
			$features            = isset( $block_attribute['features'] ) ? $block_attribute['features'] : array();
			$product_url         = isset( $block_attribute['productURL'] ) ? $block_attribute['productURL'] : '';
			$price               = isset( $block_attribute['price'] ) ? $block_attribute['price'] : '';
			$sale_price          = isset( $block_attribute['salePrice'] ) ? $block_attribute['salePrice'] : '';
			$discount_percentage = isset( $block_attribute['discountPercentage'] ) ? $block_attribute['discountPercentage'] : '';
			?>
			<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
				<div class="sync-product-from-amazon">
					<div class="sync-product-from-amazon__left_wrapper">
						<?php
						if ( ! empty( $this->spfa_app_field_options['show_product_image'] ) ) {
							?>
						<div class="sync-product-from-amazon__image">
							<img src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						</div>
							<?php
						}
						?>
						<div class="sync-product-from-amazon__details_wrap">
							<div class="sync-product-from-amazon__price_detail">
								<?php
								if ( ! empty( $this->spfa_app_field_options['show_product_price'] ) ) {
									?>
									<div class="sync-product-from-amazon__price">
										<?php if ( ! empty( $price ) ) : ?>
											<div class="sync-product-from-amazon__org_price"><span><?php echo esc_html( $price ); ?></span></div>
										<?php endif; ?>
										<?php if ( ! empty( $sale_price ) ) : ?>
											<div class="sync-product-from-amazon__sale_price"><span><?php echo esc_html( $sale_price ); ?></span></div>
										<?php endif; ?>
										<?php if ( ! empty( $discount_percentage ) ) : ?>
											<span class="sync-product-from-amazon__discount-percentage"><?php echo esc_html( $discount_percentage ); ?></span>
										<?php endif; ?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
							if ( ! empty( $this->spfa_app_field_options['show_cta_link'] ) ) {
								?>
								<a href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer" class="sync-product-from-amazon__button"><span class="sync-product-from-amazon__link "><?php esc_html_e( 'View on Amazon', 'sync-product-from-amazon' ); ?></span></a>
								<?php
							}
							?>
						</div>
					</div>
					<div class="sync-product-from-amazon__content">
						<?php
						if ( ! empty( $this->spfa_app_field_options['show_product_title'] ) ) {
							?>
							<h4 class="sync-product-from-amazon__title"><?php echo esc_html( $title ); ?></h4>
							<?php
						}
						?>
						<?php
						if ( ! empty( $this->spfa_app_field_options['show_product_features'] ) ) {
							?>
							<div class="sync-product-from-amazon__features">
								<ul>
									<?php
									$count = 0;
									foreach ( $features as $feature ) {
										$count++;
										?>
										<li class="sync-product-from-amazon__feature_item"<?php if ( $count > 3 ) { echo ' style="display: none;"'; } ?>>
											<?php echo esc_html( $feature ); ?>
										</li>
										<?php
									}
									?>
									<a class="sync-product-from-amazon__feature_item_show_more">Show more</a>
								</ul>
							</div>
							<?php
						}
						?>
					</div>
			</div>
			<?php
		}

		return ob_get_clean();
	}
}
