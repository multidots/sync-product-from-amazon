<?php
/**
 * If this file is called directly, abort.
 *
 * @package sync-product-from-amazon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="sync-product-from-amazon__help">
	<h2><?php esc_html_e( 'Introduction', 'sync-product-from-amazon' ); ?></h2>
	<p class="sync-product-from-amazon__help-para"><?php echo wp_kses_post( __( 'Welcome to the Sync Product From Amazons help page! This plugin allows you to fetch products from Amazon using the <a href="https://webservices.amazon.in/paapi5/documentation/" target="_blank">Amazon Advertising API</a> directly into WordPress. You can adjust the settings below to configure the plugin according to your preferences.', 'sync-product-from-amazon' ) ); ?></p>
	<div class="sync-product-from-amazon__accordion-main">
		<div class="sync-product-from-amazon__accordion">
			<div class="sync-product-from-amazon__accordion-wrap">
				<span class="sync-product-from-amazon__accordion-wrap-title"><?php esc_html_e( 'API Settings', 'sync-product-from-amazon' ); ?></span>
				<span class="icon"></span>
			</div>
			<div class="sync-product-from-amazon__accordion-content" style="display:none;">
				<ul class="sync-product-from-amazon__help_ul">
					<li><strong><?php esc_html_e( 'Amazon Region:', 'sync-product-from-amazon' ); ?></strong> <?php echo wp_kses_post( __( 'The AWS region corresponding to the target locale where you are sending requests. This varies based on the Amazon locale. For example, if you\'re sending a request to the US locale, the region value should be <strong>US</strong>.', 'sync-product-from-amazon' ) ); ?></li>
					<li><strong><?php esc_html_e( 'API Access Key:', 'sync-product-from-amazon' ); ?></strong> <?php echo wp_kses_post( __( 'You can obtain the Amazon Product Advertising API <strong>Access Key</strong>, <strong>Secret Key</strong>, and <strong>Partner Tag</strong> from your <a href="https://webservices.amazon.in/paapi5/documentation/register-for-pa-api.html" target="_blank">Amazon Associates account</a>.', 'sync-product-from-amazon' ) ); ?></li>
					<li><strong><?php esc_html_e( 'Enable Shortcode:', 'sync-product-from-amazon' ); ?></strong> <?php echo wp_kses_post( __( 'You can use a shortcode in a post or page to display product details from Amazon by providing an ASIN number. The shortcode will fetch the product information and display it on the frontend. The format of the shortcode is: <code>[sync_product_from_amazon asin="asin_number"]</code>. You can find the ASIN number on the product page or in the URL of the Amazon product. To display multiple products, you can enter comma-separated ASIN numbers in the shortcode, like this: <code>[sync_product_from_amazon asin="asin_number_1,asin_number_2,asin_number_3"]</code>.', 'sync-product-from-amazon' ) ); ?></li>
					<li><strong><?php esc_html_e( 'Enable Gutenberg Block:', 'sync-product-from-amazon' ); ?></strong> <?php echo wp_kses_post( __( 'Additionally, you can use a Gutenberg block for the same purpose. This block will also fetch product details from Amazon using the ASIN number and display them on the frontend. The block\'s name is <strong>Sync Product From Amazon</strong>."', 'sync-product-from-amazon' ) ); ?></li>
					<li><strong><?php esc_html_e( 'Cache:', 'sync-product-from-amazon' ); ?></strong> <?php echo wp_kses_post( __( 'When using either the shortcode or the Gutenberg block, the plugin stores product data in the database for <strong>24 hours</strong> to minimize API calls. For example, if you enter <code>asin_number_1</code> to fetch product details, the data will be cached, and subsequent requests for <code>asin_number_1</code> will retrieve data from the database rather than calling the API again. This cache will be cleared automatically after <strong>24 hours</strong>. If you want to clear it manually, click the <strong>Click here to Clear the Cache</strong> button.', 'sync-product-from-amazon' ) ); ?></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="sync-product-from-amazon__accordion-main">
		<div class="sync-product-from-amazon__accordion">
			<div class="sync-product-from-amazon__accordion-wrap">
				<span class="sync-product-from-amazon__accordion-wrap-title"><?php esc_html_e( 'Import Products', 'sync-product-from-amazon' ); ?></span>
				<span class="icon"></span>
			</div>
			<div class="sync-product-from-amazon__accordion-content" style="display:none;">
				<ul class="sync-product-from-amazon__help_ul">
					<li><?php esc_html_e( 'You can import products in bulk with this plugin, allowing you to add multiple products at once using the Import Product feature.', 'sync-product-from-amazon' ); ?></li>
					<li><?php echo wp_kses_post( __( 'Simply enter the ASIN numbers into the provided text area, then select the post where you want the products to be added and choose the post status. You can import products into post type by entering multiple <strong>(upto 10)</strong> ASIN numbers.', 'sync-product-from-amazon' ) ); ?></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="sync-product-from-amazon__accordion-main">
		<div class="sync-product-from-amazon__accordion">
			<div class="sync-product-from-amazon__accordion-wrap">
				<span class="sync-product-from-amazon__accordion-wrap-title"><?php echo esc_html_e( 'Important Notes', 'sync-product-from-amazon' ); ?></span>
				<span class="icon"></span>
			</div>
			<div class="sync-product-from-amazon__accordion-content" style="display:none;">
				<ul class="sync-product-from-amazon__help_ul">
					<li><?php echo wp_kses_post( __( 'Effective March 9, 2020, Amazon mandates the use of version 5.0 of the Product Advertising API. This updated version offers a more efficient response format, but some data, including <strong>Product Descriptions</strong> and <strong>Customer Reviews</strong>, is no longer accessible.', 'sync-product-from-amazon' ) ); ?></li>
					<li><?php esc_html_e( 'If you already have an Amazon Affiliate account, you will need to either migrate your current API keys or generate new ones to ensure the plugin functions correctly. Once you have the new keys, be sure to enter them in the plugin’s API settings.', 'sync-product-from-amazon' ); ?></li>
					<li><?php echo wp_kses_post( __( 'Additionally, Amazon requires <strong>full approval of your affiliate account</strong> before granting access to the Product Advertising API. As a result, you may not be able to use the plugin immediately until API access is provided.', 'sync-product-from-amazon' ) ); ?></li>
					<li><?php echo wp_kses_post( __( 'For those who don’t yet have an Amazon Affiliate account or API keys, the setup process is free and relatively straightforward, typically taking around <strong>15 to 20 minutes</strong>. After your account is ready, simply install the plugin, input your Partner Tag and API keys on the Plugin Settings page, and you’ll be all set to start adding products to your site!', 'sync-product-from-amazon' ) ); ?></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="sync-product-from-amazon__accordion-main">
		<div class="sync-product-from-amazon__accordion">
			<div class="sync-product-from-amazon__accordion-wrap">
				<span class="sync-product-from-amazon__accordion-wrap-title"><?php echo esc_html_e( 'Some common Issues', 'sync-product-from-amazon' ); ?></span>
				<span class="icon"></span>
			</div>
			<div class="sync-product-from-amazon__accordion-content" style="display:none;">
				<ul class="sync-product-from-amazon__help_ul">
					<li><?php echo wp_kses_post( __( 'Even after your Amazon Product Advertising API application is approved, it may take <strong>several weeks</strong> before you can access the API.', 'sync-product-from-amazon' ) ); ?></li>
					<li><?php esc_html_e( 'As you add more products, API requests increase, which can lead to higher overhead. To optimize performance, the caching system groups API requests together, reducing the number of individual requests.', 'sync-product-from-amazon' ); ?></li>
					<li><?php echo wp_kses_post( __( 'Be aware that <strong>Amazon OneLink scripts</strong> can interfere with standard product links, causing them to malfunction. If you plan to use Amazon OneLink on your site and still want to include product links, it’s best to limit OneLink usage to pages without product listings. Similarly, <strong>Amazon Ads</strong> can cause issues with product links, just like OneLink scripts.', 'sync-product-from-amazon' ) ); ?></li>
					<li><?php esc_html_e( 'Some products or product details are not available through the Amazon Product Advertising API. When this occurs, either the product or specific product elements will not appear on your site.', 'sync-product-from-amazon' ); ?></li>
					<li><?php echo wp_kses_post( __( 'To maintain access to the Amazon Product Advertising API, you are required to make at least <strong>two referral sales every 30 days</strong>. <strong>If this threshold is not met, Amazon may deactivate your account, causing the plugin to stop displaying products.</strong> Should this happen, you can reapply for API access, update your Amazon keys in the plugin settings, and your products will reappear (all Shortcodes, Gutenberg block and settings will remain intact, but the products will not be shown until access is restored).', 'sync-product-from-amazon' ) ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
