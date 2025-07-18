<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sync_Product_From_Amazon
 * @subpackage Sync_Product_From_Amazon/admin
 * @author     Multidots <info@multidots.com>
 */

namespace Sync_Product_From_Amazon\Inc;

use Sync_Product_From_Amazon\Inc\Traits\Singleton;

/**
 * Main class file.
 */
class Admin {

	use Singleton;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * API settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $spfa_api_options    Settings of this plugin.
	 */
	private $spfa_api_options;

	/**
	 * Field settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $spfa_field_options    Settings of this plugin.
	 */
	private $spfa_field_options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SPFA_VERSION' ) ) {
			$this->version = SPFA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->setup_admin_hooks();
	}
	/**
	 * Function is used to define admin hooks.
	 *
	 * @since   1.0.0
	 */
	public function setup_admin_hooks() {

		$this->spfa_api_options   = get_option( 'spfa_api_options' ) ?? array();
		$this->spfa_field_options = get_option( 'spfa_field_options' ) ?? array();

		// Register shortcode.
		if ( isset( $this->spfa_api_options['shortcode'] ) ) {
			add_shortcode( 'sync_product_from_amazon', array( $this, 'sync_product_from_amazon_shortcode' ) );
		}

		add_action( 'admin_menu', array( $this, 'sync_product_from_amazon_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'sync_product_from_amazon_admin_page_init' ) );
		add_action( 'init', array( $this, 'sync_product_from_amazon_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'spfa_post_type_fields' ) );

		add_filter( 'the_content', array( $this, 'spfa_override_content' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'sync-product-from-amazon', SPFA_URL . 'assets/build/admin.css', array(), $this->version, 'all' );

		$amazon_custom_menu_icon_css = '
			#toplevel_page_sync-product-from-amazon .wp-menu-image img {
				width: 20px;
				height: 20px;
			}';
		wp_add_inline_style( 'sync-product-from-amazon', $amazon_custom_menu_icon_css );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'sync-product-from-amazon', SPFA_URL . 'assets/build/admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			'sync-product-from-amazon',
			'siteConfig',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'loadmore_post_nonce' ),
			)
		);
	}

	/**
	 * Function is used to register custom post type
	 * fields to display in InspectorControl of post
	 * sidebar.
	 */
	public function spfa_post_type_fields() {

		$post_id      = get_the_ID();
		$product_asin = get_post_meta( $post_id, 'spfa_product_asin', true ) ?? '';

		if ( empty( $product_asin ) ) {
			return;
		}

		// Register the block script.
		wp_enqueue_script(
			'spfa-custom-post-fields',
			SPFA_URL . 'assets/build/js/blocks/amazon-product-custom-fields/index.js',
			array( 'wp-editor', 'wp-plugins' ),
			$this->version,
			true
		);
	}

	/**
	 * Function is used to get amazon product price.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function sync_product_from_amazon_shortcode( $atts ) {

		if ( ! isset( $atts['asin'] ) ) {
			return '';
		}

		$asin = $atts['asin'];

		$asin = str_replace( ' ', '', $asin );

		$args = array(
			'body' => array(
				'asin' => $asin,
			),
		);

		$api_request   = wp_safe_remote_post( site_url( '/wp-json/sync-product-from-amazon/v1/fetch-product' ), $args );
		$response_body = wp_remote_retrieve_body( $api_request );
		$api_response  = json_decode( $response_body );

		ob_start();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if (
			! empty( $api_response->ItemsResult->Items ) &&
			is_array( $api_response->ItemsResult->Items ) &&
			count( $api_response->ItemsResult->Items ) > 0
		) {
			foreach ( $api_response->ItemsResult->Items as $item ) {
				?>
				<div class="sync-product-from-amazon">
					<div class="sync-product-from-amazon__left_wrapper">
						<?php
						if ( ! empty( $this->spfa_field_options['show_product_image'] ) ) {
							?>
							<div class="sync-product-from-amazon__image">
								<img src="<?php echo esc_url( $item->Images->Primary->Medium->URL ); ?>" alt="<?php echo esc_attr( $item->ItemInfo->Title->DisplayValue ); ?>">
							</div>
							<?php
						}
						?>
						<div class="sync-product-from-amazon__details_wrap">
							<div class="sync-product-from-amazon__price_detail">
								<?php if ( ! empty( $this->spfa_field_options['show_product_price'] ) ) { ?>
									<div class="sync-product-from-amazon__org_price">
										<span>
											<?php
												$org_price = (float) $item->Offers->Listings[0]->Price->Amount + (float) $item->Offers->Listings[0]->Price->Savings->Amount ?? 0;
												echo esc_html( substr( $item->Offers->Listings[0]->Price->Savings->DisplayAmount, 0, 1 ) . $org_price );
											?>
										</span>
									</div>
									<div class="sync-product-from-amazon__sale_price">
										<span><?php echo esc_html( $item->Offers->Listings[0]->Price->DisplayAmount ); ?></span>
									</div>
								<?php } ?>
							</div>
							<?php if ( ! empty( $this->spfa_field_options['show_cta_link'] ) ) { ?>
								<a href="<?php echo esc_url( $item->DetailPageURL ); ?>" class="sync-product-from-amazon__button" target="_blank"><span class="sync-product-from-amazon__link"><?php esc_html_e( 'View on Amazon', 'sync-product-from-amazon' ); ?></span></a>
							<?php } ?>
						</div>
					</div>
					<div class="sync-product-from-amazon__content">
						<?php
						if ( ! empty( $this->spfa_field_options['show_product_title'] ) ) {
							?>
							<h4 class="sync-product-from-amazon__title"><?php echo esc_html( $item->ItemInfo->Title->DisplayValue ); ?></h4>
							<?php
						}
						?>
						<?php
						if (
							! empty( $this->spfa_field_options['show_product_features'] ) &&
							is_array( $item->ItemInfo->Features->DisplayValues ) &&
							count( $item->ItemInfo->Features->DisplayValues ) > 0
							) {
							?>
							<div class="sync-product-from-amazon__features">
								<?php
								$count = 0;
								echo '<ul>';
								foreach ( $item->ItemInfo->Features->DisplayValues as $feature ) {
									++$count;
									echo '<li class="sync-product-from-amazon__feature_item"';
									if ( $count > 3 ) {
										echo ' style="display: none;"';
									}
									echo '>' . esc_html( $feature ) . '</li>';
								}
								?>
								<a id="spfa_toggle_features" class="sync-product-from-amazon__feature_item_show_more">Show more</a>
								<?php
								echo '</ul>';
								?>
								
							</div>
						<?php } ?>
						
					</div>
				</div>
				<?php
			}
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return ob_get_clean();
	}

	/**
	 * Function is used to create plugin page
	 */
	public function sync_product_from_amazon_add_plugin_page() {
		$icon_url = SPFA_URL . 'assets/images/sync-product-from-amazon-icon.jpg';
		add_menu_page(
			__( 'Sync Product From Amazon', 'sync-product-from-amazon' ),
			__( 'Sync Product From Amazon', 'sync-product-from-amazon' ),
			'manage_options',
			'sync-product-from-amazon',
			array( $this, 'sync_product_from_amazon_create_admin_page' ),
			// 'dashicons-products',
			$icon_url,
			2
		);

		add_submenu_page(
			'sync-product-from-amazon',
			__( 'Add New Product', 'sync-product-from-amazon' ),
			__( 'Add New Product', 'sync-product-from-amazon' ),
			'manage_options',
			'add-new-product',
			array( $this, 'add_new_product_callback' ),
		);
	}

	/**
	 * Function is used to create admin page
	 */
	public function sync_product_from_amazon_create_admin_page() {
		// Check if the nonce is set and valid before saving the settings.
		if ( isset( $_POST['spfa_tabs_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spfa_tabs_nonce'] ) ), 'spfa_tabs_action' ) ) {
			// Nonce is invalid.
			wp_die( 'Security check failed' );
		}

		$active_tab = isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'api-settings';
		?>
		<h2 class="sync-product-from-amazon__heading"><?php esc_html_e( 'Sync Product From Amazon', 'sync-product-from-amazon' ); ?></h2>
		<div class="sync-product-from-amazon__main wrap">
			<h2 class="nav-tab-wrapper">
				<a href="?page=sync-product-from-amazon&tab=api-settings" class="nav-tab <?php echo 'api-settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'API Settings', 'sync-product-from-amazon' ); ?></a>
				<a href="?page=sync-product-from-amazon&tab=field-settings" class="nav-tab <?php echo 'field-settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Field Settings', 'sync-product-from-amazon' ); ?></a>
				<a href="?page=sync-product-from-amazon&tab=import-products" class="nav-tab <?php echo 'import-products' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import Products', 'sync-product-from-amazon' ); ?></a>
				<a href="?page=sync-product-from-amazon&tab=help" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'sync-product-from-amazon' ); ?></a>
			</h2>
			<?php settings_errors(); ?>

			<form class="sync-product-from-amazon__setting-form <?php echo ( 'field-settings' === $active_tab ) ? 'sync-product-from-amazon__field-setting-form' : ''; ?>" method="post" action="options.php">
				<?php
				wp_nonce_field( 'spfa_tabs_action', 'spfa_tabs_nonce' );

				if ( 'api-settings' === $active_tab ) {
					?>
				<div id="spfa_test_api_success" class="is-dismissible notice notice-success">
					<p><strong><?php esc_html_e( 'API Connection is working successfully.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_test_api_fail" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'API Connection does not working. Please check API Key, API Secret and Partner Tag.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_test_api_empty_credentials" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please enter API Key, API Secret Key and Partner Tag to test API connection.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_test_product_valid_asin" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please add the valid ASIN Numbers to test the API connection.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_clear_cache_success" class="is-dismissible notice notice-success">
					<p><strong><?php esc_html_e( 'Cache has been successfully deleted.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_clear_cache_fail" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please add the valid ASIN Numbers to test the API connection.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
					<?php
					settings_fields( 'spfa_api_settings_group' );
					do_settings_sections( 'spfa-api-settings' );
				} elseif ( 'field-settings' === $active_tab ) {
					settings_fields( 'spfa_field_settings_group' );
					do_settings_sections( 'spfa-field-settings' );
				} elseif ( 'import-products' === $active_tab ) {
					settings_fields( 'spfa_import_products_group' );
					do_settings_sections( 'spfa-import-product-settings' );
				} elseif ( 'help' === $active_tab ) {
					include_once SPFA_DIR . 'inc/templates/setting-help.php';
				}

				if ( 'import-products' !== $active_tab && 'help' !== $active_tab ) {
					submit_button();
				}

				if ( 'import-products' === $active_tab ) {
					?>
				<p>
					<?php esc_html_e( 'You can import products into post type by entering multiple (upto 10) ASIN numbers.', 'sync-product-from-amazon' ); ?>
				</p>
				<button id="spfa_import_products_button" class="button button-primary sync-product-from-amazon__import-products-btn"><?php esc_html_e( 'Import Products', 'sync-product-from-amazon' ); ?></button>
				<div id="spfa_import_product_success" class="is-dismissible notice notice-success">
					<p><strong><?php esc_html_e( 'Products has been successfully imported.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_import_product_fail" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'There has been some errors while importing Products. Please confirm the ASIN numbers are correct.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_import_product_empty_asin" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please add ASIN Numbers to fetch and import products.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_import_product_valid_asin" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please add the valid ASIN Numbers to fetch products.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="sync_product_from_amazon_loader" class="sync-product-from-amazon__loader" style="display: none;"></div>
					<?php
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Function is used to create admin page
	 */
	public function add_new_product_callback() {
		$post_id = 0; // Initialize the variable.
		if ( isset( $_POST['add_new_amazon_product_post'] ) ) {

			if ( ! isset( $_POST['add_new_amazon_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['add_new_amazon_product_nonce'] ) ), 'add_new_amazon_product' ) ) {
				wp_die( 'Security check failed' );
			}

			$product_asin       = ! empty( $_POST['spfa_product_asin'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_asin'] ) ) : '';
			$product_title      = ! empty( $_POST['spfa_product_title'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_title'] ) ) : '';
			$product_features   = ! empty( $_POST['spfa_product_features'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_features'] ) ) : '';
			$product_image      = ! empty( $_POST['spfa_product_image'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_image'] ) ) : '';
			$product_link       = ! empty( $_POST['spfa_product_link'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_link'] ) ) : '';
			$product_price      = ! empty( $_POST['spfa_product_price'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_price'] ) ) : '';
			$product_sale_price = ! empty( $_POST['spfa_product_sale_price'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_sale_price'] ) ) : '';
			$product_status     = ! empty( $_POST['spfa_product_status'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_product_status'] ) ) : 'publish';
			$post_type          = ! empty( $_POST['spfa_post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['spfa_post_type'] ) ) : 'post';

			$post_data = array(
				'post_title'   => $product_title,
				'post_content' => $product_features,
				'post_status'  => $product_status,
				'post_type'    => $post_type,
			);

			$post_id = wp_insert_post( $post_data );
			if ( ! is_wp_error( $post_id ) && $post_id ) {
				update_post_meta( $post_id, 'spfa_product_asin', $product_asin );
				update_post_meta( $post_id, 'spfa_product_link', $product_link );
				update_post_meta( $post_id, 'spfa_product_price', $product_price );
				update_post_meta( $post_id, 'spfa_product_sale_price', $product_sale_price );

				// Fetch and set the product image.
				$image = media_sideload_image( $product_image, $post_id, '', 'id' );
				if ( ! is_wp_error( $image ) ) {
					set_post_thumbnail( $post_id, $image );
				}
				$message      = esc_html__( 'Product added successfully.', 'sync-product-from-amazon' );
				$notice_class = 'notice-success';
			} else {
				$message      = esc_html__( 'Product not added.', 'sync-product-from-amazon' );
				$notice_class = 'notice-error';
			}
		} else {
			$message      = '';
			$notice_class = '';

		}
		?>
		<h2 class="sync-product-from-amazon__heading"><?php esc_html_e( 'Add New Product', 'sync-product-from-amazon' ); ?></h2>
		<div class="sync-product-from-amazon__main wrap">
			<?php
			if ( ! empty( $message ) ) {
				echo '<div id="setting-error-settings_updated" class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			}
			?>
			<form class="sync-product-from-amazon__setting-form" method="post" action="">
				<div id="spfa_add_product_empty_asin" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please add the ASIN Number to fetch product details.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<div id="spfa_add_product_valid_asin" class="is-dismissible notice notice-error">
					<p><strong><?php esc_html_e( 'Please add the valid ASIN Number to fetch product details.', 'sync-product-from-amazon' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__( 'Dismiss this notice.', 'sync-product-from-amazon' ); ?></span></button>
				</div>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="spfa_product_asin"><?php esc_html_e( 'Enter ASIN Number*', 'sync-product-from-amazon' ); ?>
						<span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'After adding ASIN Number click on "Fetch Product Details" button.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span>
						</label></th>
						<td>
							<input type="text" name="spfa_product_asin" id="spfa_product_asin" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter ASIN Number (required)', 'sync-product-from-amazon' ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td class="sync-product-from-amazon__add-product-td">
							<button id="spfa_fetch_product_details" class="button button-primary sync-product-from-amazon__add-product-btn"><?php esc_html_e( 'Fetch Product Details', 'sync-product-from-amazon' ); ?></button>
						</td>
					<tr>
						<th scope="row"><label for="spfa_product_title"><?php esc_html_e( 'Product Title*', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Enter the title of the product; this field is required.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td>
							<input type="text" name="spfa_product_title" id="spfa_product_title" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter product title (required)', 'sync-product-from-amazon' ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spfa_product_image"><?php esc_html_e( 'Product Image URL', 'sync-product-from-amazon' ); ?>
						<span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Provide the URL for the product image to display in your listing.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span>
						</label></th>
						<td class="sync-product-from-amazon__product-image-td">
							<input type="text" name="spfa_product_image" id="spfa_product_image" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter Image URL (optional)', 'sync-product-from-amazon' ); ?>">
							<div class="sync-product-from-amazon__product-image-preview">
								<img src="" id="spfa_product_image_preview" style="display: none;"
								class="sync-product-from-amazon__product-image-preview-img">
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spfa_product_features"><?php esc_html_e( 'Product Features', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'List the key features of the product to highlight its main attributes.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td><textarea name="spfa_product_features" id="spfa_product_features" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter product features (optional)', 'sync-product-from-amazon' ); ?>"></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="spfa_product_link"><?php esc_html_e( 'Product Link', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Enter the Amazon Product\'s page URL.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td><input type="text" name="spfa_product_link" id="spfa_product_link" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter product\'s link (optional)', 'sync-product-from-amazon' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="spfa_product_price"><?php esc_html_e( 'Product Price', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Add the price of the product.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td><input type="text" name="spfa_product_price" id="spfa_product_price" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter product\'s price (optional)', 'sync-product-from-amazon' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="spfa_product_sale_price"><?php esc_html_e( 'Product Sale Price', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Enter the discounted sale price of the product, if applicable.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td><input type="text" name="spfa_product_sale_price" id="spfa_product_sale_price" class="regular-text" placeholder="<?php echo esc_attr__( 'Please enter product\'s sale price (optional)', 'sync-product-from-amazon' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="spfa_product_status"><?php esc_html_e( 'Product Status', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Choose the publication status for the post.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td>
							<select class="sync-product-from-amazon__select-box" name="spfa_product_status" id="spfa_product_status">
								<option value="publish"><?php esc_html_e( 'Publish', 'sync-product-from-amazon' ); ?></option>
								<option value="draft"><?php esc_html_e( 'Draft', 'sync-product-from-amazon' ); ?></option>
								<option value="private"><?php esc_html_e( 'Private', 'sync-product-from-amazon' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Select Post type', 'sync-product-from-amazon' ); ?><span class="sync-product-from-amazon__help-tip">
							<span 
								class="sync-product-from-amazon__help-inner-tip" 
								tabindex="0" 
								aria-label="<?php echo esc_attr__( 'Choose the type of the post.', 'sync-product-from-amazon' ); ?>"
							>
							</span>
						</span></label></th>
						<td>
							<select class="sync-product-from-amazon__select-box" name="spfa_post_type" id="spfa_post_type">
								<option value="post"><?php esc_html_e( 'Post', 'sync-product-from-amazon' ); ?></option>
								<option value="page"><?php esc_html_e( 'Page', 'sync-product-from-amazon' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td><input type="submit" name="add_new_amazon_product_post" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Create New Post', 'sync-product-from-amazon' ); ?>"></td>
					</tr>
					<?php
						wp_nonce_field( 'add_new_amazon_product', 'add_new_amazon_product_nonce' );
					?>
				</table>
			</form>
		</div>
		<?php
	}

	/**
	 * Function is used register settings.
	 */
	public function sync_product_from_amazon_admin_page_init() {
		register_setting(
			'spfa_api_settings_group',
			'spfa_api_options',
			array( $this, 'spfa_api_setting_fields_sanitize' )
		);

		register_setting(
			'spfa_field_settings_group',
			'spfa_field_options',
			array( $this, 'spfa_field_setting_fields_sanitize' )
		);

		// API Settings.
		$this->get_api_settings_fields();

		// Field Settings.
		$this->get_field_settings_fields();

		// Import Products.
		$this->get_import_products_fields();
	}

	/**
	 * Function is used to register meta fields.
	 */
	public function sync_product_from_amazon_page_init() {

		$post_types = array( 'post', 'page' );

		foreach ( $post_types as $post_type ) {
			$fields = array( 'spfa_product_asin', 'spfa_product_price', 'spfa_product_sale_price', 'spfa_product_link' );

			foreach ( $fields as $field ) {
				register_post_meta(
					$post_type,
					$field,
					array(
						'show_in_rest'      => true,
						'type'              => 'string',
						'single'            => true,
						'sanitize_callback' => 'sanitize_text_field',
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	/**
	 * Function is used to register API Settings Fields.
	 */
	public function get_api_settings_fields() {
		add_settings_section(
			'spfa_api_setting_section',
			__( 'Settings', 'sync-product-from-amazon' ),
			array( $this, 'sync_product_from_amazon_section_info' ),
			'spfa-api-settings'
		);

		add_settings_field(
			'spfa_amazon_region',
			__( 'Region', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Select the Amazon AWS region for the services you want to connect.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_region_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_amazon_region',
			),
		);

		add_settings_field(
			'spfa_amazon_api_access_key',
			__( 'API Access Key', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Enter your Amazon API Access Key to authenticate API requests.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_api_access_key_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_amazon_api_access_key',
			),
		);

		add_settings_field(
			'spfa_amazon_api_secret_key',
			__( 'API Secret Key', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Enter your Amazon API Secret Key for secure access to the API.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_api_secret_key_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_amazon_api_secret_key',
			),
		);

		add_settings_field(
			'spfa_amazon_partner_tag',
			__( 'Partner Tag', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Enter your Amazon Partner Tag for tracking and attribution in the affiliate program.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_partner_tag_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_amazon_partner_tag',
			),
		);

		add_settings_field(
			'spfa_asin_number',
			__( 'Enter random ASIN Number', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'From Amazon product\'s page add ASIN number of any product.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_asin_number_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_asin_number',
			),
		);

		add_settings_field(
			'spfa_test_api_connection',
			__( 'Test API Connection', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'After adding API credentials, you can click on this button to test API connection.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_test_api_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_test_api_connection',
			),
		);

		add_settings_field(
			'spfa_amazon_shortcode',
			__( 'Enable Shortcode?', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Check this to enable and use shortcode to display products.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_shortcode_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_amazon_shortcode',
			),
		);

		add_settings_field(
			'spfa_amazon_gutenberg_block',
			__( 'Enable Gutenberg Block?', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Check this to enable and use Gutenberg block to display products.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_amazon_gutenberg_block_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section',
			array(
				'label_for' => 'spfa_amazon_gutenberg_block',
			),
		);

		add_settings_field(
			'spfa_clear_cache',
			__( 'Do you want to clear the cache?', 'sync-product-from-amazon' ),
			array( $this, 'spfa_clear_cache_callback' ),
			'spfa-api-settings',
			'spfa_api_setting_section'
		);
	}

	/**
	 * Function is used to register Field Settings Fields.
	 */
	public function get_field_settings_fields() {
		add_settings_section(
			'spfa_field_settings_section',
			__( 'Field Settings', 'sync-product-from-amazon' ),
			array( $this, 'spfa_field_setting_section_info' ),
			'spfa-field-settings'
		);

		add_settings_field(
			'spfa_show_product_title',
			__( 'Do you want to display Product\'s Title?', 'sync-product-from-amazon' ),
			array( $this, 'spfa_show_product_title_callback' ),
			'spfa-field-settings',
			'spfa_field_settings_section',
			array(
				'label_for' => 'spfa_show_product_title',
			),
		);

		add_settings_field(
			'spfa_show_product_image',
			__( 'Do you want to display Product\'s Image?', 'sync-product-from-amazon' ),
			array( $this, 'spfa_show_product_image_callback' ),
			'spfa-field-settings',
			'spfa_field_settings_section',
			array(
				'label_for' => 'spfa_show_product_image',
			),
		);

		add_settings_field(
			'spfa_show_product_features',
			__( 'Do you want to display Product\'s Features?', 'sync-product-from-amazon' ),
			array( $this, 'spfa_show_product_features_callback' ),
			'spfa-field-settings',
			'spfa_field_settings_section',
			array(
				'label_for' => 'spfa_show_product_features',
			),
		);

		add_settings_field(
			'spfa_show_product_price',
			__( 'Do you want to display Product\'s Price?', 'sync-product-from-amazon' ),
			array( $this, 'spfa_show_product_price_callback' ),
			'spfa-field-settings',
			'spfa_field_settings_section',
			array(
				'label_for' => 'spfa_show_product_price',
			),
		);

		add_settings_field(
			'spfa_show_cta_link',
			__( 'Do you want to display Product\'s link?', 'sync-product-from-amazon' ),
			array( $this, 'spfa_show_cta_link_callback' ),
			'spfa-field-settings',
			'spfa_field_settings_section',
			array(
				'label_for' => 'spfa_show_cta_link',
			),
		);
	}

	/**
	 * Function is used to register Import Products Fields.
	 */
	public function get_import_products_fields() {
		add_settings_section(
			'spfa_import_products_section',
			__( 'Import Products', 'sync-product-from-amazon' ),
			array( $this, 'spfa_import_products_section_info' ),
			'spfa-import-product-settings'
		);

		add_settings_field(
			'spfa_import_products_textarea',
			__( 'Enter ASIN Numbers to import', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip sync-product-from-amazon__help-tip-textarea"><span class="sync-product-from-amazon__help-inner-tip sync-product-from-amazon__help-inner-tip-textarea" tabindex="0" aria-label="' . esc_attr__( 'Input one or more (upto 10) ASIN numbers (comma separated) to import product details from Amazon.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_import_products_textarea_callback' ),
			'spfa-import-product-settings',
			'spfa_import_products_section',
			array(
				'label_for' => 'spfa_import_products_textarea',
			),
		);

		add_settings_field(
			'spfa_import_product_post_type',
			__( 'Select Post type', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Choose the type of post.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_import_product_post_type_callback' ),
			'spfa-import-product-settings',
			'spfa_import_products_section',
			array(
				'label_for' => 'spfa_import_product_post_type',
			),
		);

		add_settings_field(
			'spfa_import_product_status',
			__( 'Select Status', 'sync-product-from-amazon' ) . '<span class="sync-product-from-amazon__help-tip"><span class="sync-product-from-amazon__help-inner-tip" tabindex="0" aria-label="' . esc_attr__( 'Choose the status for the post.', 'sync-product-from-amazon' ) . '"></span></span>',
			array( $this, 'spfa_import_product_status_callback' ),
			'spfa-import-product-settings',
			'spfa_import_products_section',
			array(
				'label_for' => 'spfa_import_product_status',
			),
		);
	}

	/**
	 * Function is used to sanitise inputs.
	 *
	 * @param array $input Input data.
	 * @return array
	 */
	public function spfa_api_setting_fields_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['region'] ) ) {
			$sanitary_values['region'] = sanitize_text_field( $input['region'] );
		}

		if ( isset( $input['api_access_key'] ) ) {
			$sanitary_values['api_access_key'] = sanitize_text_field( $input['api_access_key'] );
		}

		if ( isset( $input['api_secret_key'] ) ) {
			$sanitary_values['api_secret_key'] = sanitize_text_field( $input['api_secret_key'] );
		}

		if ( isset( $input['partner_tag'] ) ) {
			$sanitary_values['partner_tag'] = sanitize_text_field( $input['partner_tag'] );
		}

		if ( isset( $input['asin_number'] ) ) {
			$sanitary_values['asin_number'] = sanitize_text_field( $input['asin_number'] );
		}

		if ( isset( $input['shortcode'] ) ) {
			$sanitary_values['shortcode'] = sanitize_text_field( $input['shortcode'] );
		}

		if ( isset( $input['gutenberg_block'] ) ) {
			$sanitary_values['gutenberg_block'] = sanitize_text_field( $input['gutenberg_block'] );
		}

		return $sanitary_values;
	}

	/**
	 * Function is used to sanitise inputs.
	 *
	 * @param array $input Input data.
	 * @return array
	 */
	public function spfa_field_setting_fields_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['show_product_title'] ) ) {
			$sanitary_values['show_product_title'] = sanitize_text_field( $input['show_product_title'] );
		}

		if ( isset( $input['show_product_image'] ) ) {
			$sanitary_values['show_product_image'] = sanitize_text_field( $input['show_product_image'] );
		}

		if ( isset( $input['show_product_features'] ) ) {
			$sanitary_values['show_product_features'] = sanitize_text_field( $input['show_product_features'] );
		}

		if ( isset( $input['show_product_price'] ) ) {
			$sanitary_values['show_product_price'] = sanitize_text_field( $input['show_product_price'] );
		}

		if ( isset( $input['show_cta_link'] ) ) {
			$sanitary_values['show_cta_link'] = sanitize_text_field( $input['show_cta_link'] );
		}

		return $sanitary_values;
	}

	/**
	 * Used to show section info.
	 */
	public function spfa_import_products_section_info() {}

	/**
	 * Used to show section info.
	 */
	public function sync_product_from_amazon_section_info() {
		echo '<p>' . esc_html__( 'Configure the settings for fetching products from the Amazon Products Advertising API.', 'sync-product-from-amazon' ) . '</p>';
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_region_callback() {
		$regions = array(
			'com'    => __( 'US (default)', 'sync-product-from-amazon' ),
			'com.au' => __( 'Australia', 'sync-product-from-amazon' ),
			'com.be' => __( 'Belgium', 'sync-product-from-amazon' ),
			'com.br' => __( 'Brazil', 'sync-product-from-amazon' ),
			'ca'     => __( 'Canada', 'sync-product-from-amazon' ),
			'eg'     => __( 'Egypt', 'sync-product-from-amazon' ),
			'fr'     => __( 'France', 'sync-product-from-amazon' ),
			'de'     => __( 'Germany', 'sync-product-from-amazon' ),
			'in'     => __( 'India', 'sync-product-from-amazon' ),
			'it'     => __( 'Italy', 'sync-product-from-amazon' ),
			'co.jp'  => __( 'Japan', 'sync-product-from-amazon' ),
			'com.mx' => __( 'Mexico', 'sync-product-from-amazon' ),
			'nl'     => __( 'Netherlands', 'sync-product-from-amazon' ),
			'pl'     => __( 'Poland', 'sync-product-from-amazon' ),
			'sg'     => __( 'Singapore', 'sync-product-from-amazon' ),
			'sa'     => __( 'Saudi Arabia', 'sync-product-from-amazon' ),
			'es'     => __( 'Spain', 'sync-product-from-amazon' ),
			'se'     => __( 'Sweden', 'sync-product-from-amazon' ),
			'com.tr' => __( 'Turkey', 'sync-product-from-amazon' ),
			'ae'     => __( 'United Arab Emirates', 'sync-product-from-amazon' ),
			'co.uk'  => __( 'United Kingdom', 'sync-product-from-amazon' ),
		);
		?>
		<select name="spfa_api_options[region]" id="spfa_amazon_region" class="sync-product-from-amazon__select-box">
			<?php
			foreach ( $regions as $key => $value ) {
				$selected = ( isset( $this->spfa_api_options['region'] ) && $this->spfa_api_options['region'] === $key ) ? 'selected' : '';
				echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $value ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_api_access_key_callback() {
		printf(
			'<input class="regular-text" type="text" name="spfa_api_options[api_access_key]" id="spfa_amazon_api_access_key" value="%s" placeholder="%s" required>',
			isset( $this->spfa_api_options['api_access_key'] ) ? esc_attr( $this->spfa_api_options['api_access_key'] ) : '',
			esc_html__( 'Please enter API Access Key (required)', 'sync-product-from-amazon' ),
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_api_secret_key_callback() {
		printf(
			'<input class="regular-text" type="text" name="spfa_api_options[api_secret_key]" id="spfa_amazon_api_secret_key" value="%s" placeholder="%s" required>',
			isset( $this->spfa_api_options['api_secret_key'] ) ? esc_attr( $this->spfa_api_options['api_secret_key'] ) : '',
			esc_html__( 'Please enter API Secret Key (required)', 'sync-product-from-amazon' ),
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_partner_tag_callback() {
		printf(
			'<input class="regular-text" type="text" name="spfa_api_options[partner_tag]" id="spfa_amazon_partner_tag" value="%s" placeholder="%s" required>',
			isset( $this->spfa_api_options['partner_tag'] ) ? esc_attr( $this->spfa_api_options['partner_tag'] ) : '',
			esc_html__( 'Please enter Partner Tag (required)', 'sync-product-from-amazon' ),
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_asin_number_callback() {
		printf(
			'<input class="regular-text" type="text" name="spfa_api_options[asin_number]" id="spfa_asin_number" value="%s" placeholder="%s" required>',
			isset( $this->spfa_api_options['asin_number'] ) ? esc_attr( $this->spfa_api_options['asin_number'] ) : '',
			esc_html__( 'Please enter Sample ASIN Number (required)', 'sync-product-from-amazon' ),
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_test_api_callback() {
		printf(
			'<button class="button button-primary" id="spfa_test_api_connection">%s</button>',
			esc_html__( 'Test API Connection', 'sync-product-from-amazon' )
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_shortcode_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_api_options[shortcode]" id="spfa_amazon_shortcode" value="%s" %s />',
			isset( $this->spfa_api_options['shortcode'] ) ? true : false,
			isset( $this->spfa_api_options['shortcode'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_amazon_gutenberg_block_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_api_options[gutenberg_block]" id="spfa_amazon_gutenberg_block" value="%s" %s />',
			isset( $this->spfa_api_options['gutenberg_block'] ) ? true : false,
			isset( $this->spfa_api_options['gutenberg_block'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_clear_cache_callback() {
		printf(
			'<button class="button button-link-delete" id="spfa_app_delete_cache">%s</button>',
			esc_html__( 'Click here to clear the cache', 'sync-product-from-amazon' )
		);
	}

	/**
	 * Used to show section info.
	 */
	public function spfa_field_setting_section_info() {
		echo '<p>' . esc_html__( 'Choose the field settings to display on frontend. These setting will work with our Shortcode and Gutenberg block.', 'sync-product-from-amazon' ) . '</p>';
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_show_product_title_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_field_options[show_product_title]" id="spfa_show_product_title" value="%s" %s />',
			isset( $this->spfa_field_options['show_product_title'] ) ? true : false,
			isset( $this->spfa_field_options['show_product_title'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_show_product_image_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_field_options[show_product_image]" id="spfa_show_product_image" value="%s" %s />',
			isset( $this->spfa_field_options['show_product_image'] ) ? true : false,
			isset( $this->spfa_field_options['show_product_image'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_show_product_features_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_field_options[show_product_features]" id="spfa_show_product_features" value="%s" %s />',
			isset( $this->spfa_field_options['show_product_features'] ) ? true : false,
			isset( $this->spfa_field_options['show_product_features'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_show_product_price_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_field_options[show_product_price]" id="spfa_show_product_price" value="%s" %s />',
			isset( $this->spfa_field_options['show_product_price'] ) ? true : false,
			isset( $this->spfa_field_options['show_product_price'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_show_cta_link_callback() {
		printf(
			'<input class="checkbox" type="checkbox" name="spfa_field_options[show_cta_link]" id="spfa_show_cta_link" value="%s" %s />',
			isset( $this->spfa_field_options['show_cta_link'] ) ? true : false,
			isset( $this->spfa_field_options['show_cta_link'] ) ? 'checked="checked"' : ''
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_import_products_textarea_callback() {
		printf(
			'<textarea name="spfa_import_products_textarea" id="spfa_import_products_textarea" class="regular-text" placeholder="%s"></textarea>',
			esc_html__( 'Please enter ASIN Numbers separated by comma.', 'sync-product-from-amazon' )
		);
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_import_product_post_type_callback() {
		?>
		<select class="sync-product-from-amazon__select-box" name="spfa_import_product_post_type" id="spfa_import_product_post_type">
			<option value="post"><?php esc_html_e( 'Post', 'sync-product-from-amazon' ); ?></option>
			<option value="page"><?php esc_html_e( 'Page', 'sync-product-from-amazon' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Settings field callback function.
	 */
	public function spfa_import_product_status_callback() {
		?>
		<select class="sync-product-from-amazon__select-box" name="spfa_import_product_status" id="spfa_import_product_status">
			<option value="publish"><?php esc_html_e( 'Publish', 'sync-product-from-amazon' ); ?></option>
			<option value="draft"><?php esc_html_e( 'Draft', 'sync-product-from-amazon' ); ?></option>
			<option value="private"><?php esc_html_e( 'Private', 'sync-product-from-amazon' ); ?></option>
		</select>
		<?php
	}


	/**
	 * Function is used to override the content.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function spfa_override_content( $content ) {

		$post_id = get_the_ID();

		$product_title      = get_the_title( $post_id );
		$product_content    = get_the_content( $post_id );
		$product_image      = get_the_post_thumbnail_url( $post_id, 'medium' ) ?? '';
		$product_asin       = get_post_meta( $post_id, 'spfa_product_asin', true ) ?? '';
		$product_price      = get_post_meta( $post_id, 'spfa_product_price', true ) ?? '';
		$product_sale_price = get_post_meta( $post_id, 'spfa_product_sale_price', true ) ?? '';
		$product_link       = get_post_meta( $post_id, 'spfa_product_link', true ) ?? '';

		if ( empty( $product_asin ) ) {
			return $content;
		}

		ob_start();

		if ( ! empty( $product_asin ) || ! empty( $product_price ) || ! empty( $product_sale_price ) || ! empty( $product_link ) ) {
			?>
			<div class="sync-product-from-amazon">
				<div class="sync-product-from-amazon__left_wrapper">
					<?php
					if ( ! empty( $this->spfa_field_options['show_product_image'] ) && ! empty( $product_image ) ) {
						?>
						<div class="sync-product-from-amazon__image">
							<img src="<?php echo esc_url( $product_image ); ?>" alt="<?php echo esc_attr( $product_title ); ?>">
						</div>
						<?php
					}
					?>
					<div class="sync-product-from-amazon__details_wrap">
						<div class="sync-product-from-amazon__price_detail">
							<?php if ( ! empty( $this->spfa_field_options['show_product_price'] ) && ! empty( $product_price ) ) { ?>
								<div class="sync-product-from-amazon__org_price">
									<span>
										<?php
											echo esc_html( $product_price );
										?>
									</span>
								</div>
								<div class="sync-product-from-amazon__sale_price">
									<span><?php echo esc_html( $product_sale_price ); ?></span>
								</div>
							<?php } ?>
						</div>
						<?php if ( ! empty( $this->spfa_field_options['show_cta_link'] ) ) { ?>
							<a href="<?php echo esc_url( $product_link ); ?>" class="sync-product-from-amazon__button" target="_blank"><span class="sync-product-from-amazon__link"><?php esc_html_e( 'View on Amazon', 'sync-product-from-amazon' ); ?></span></a>
						<?php } ?>
					</div>
				</div>
				<div class="sync-product-from-amazon__content">
					<?php
					if ( ! empty( $this->spfa_field_options['show_product_title'] ) ) {
						?>
						<h4 class="sync-product-from-amazon__title"><?php echo esc_html( $product_title ); ?></h4>
						<?php
					}
					?>
					<?php
					if (
						! empty( $this->spfa_field_options['show_product_features'] ) &&
						! empty( $product_content )
						) {
						?>
						<div class="sync-product-from-amazon__features">
							<?php
								echo wp_kses_post( $product_content );
							?>
						</div>
					<?php } ?>
				</div>
			</div>
			<?php
		}
		$content = ob_get_clean();
		return $content;
	}
}
