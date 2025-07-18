<?php
/**
 * Amazon Advertising API functionality of the plugin.
 *
 * It is used to get the product details from the Amazon Advertising API.
 *
 * @package    Sync_Product_From_Amazon
 * @subpackage Sync_Product_From_Amazon/api
 * @author     Multidots <info@multidots.com>
 */

namespace Sync_Product_From_Amazon\Inc;

use Sync_Product_From_Amazon\Inc\Traits\Singleton;

/**
 * Amazon Advertising API class.
 */
class API {


	use Singleton;

	/**
	 * Amazon API URL.
	 *
	 * @var string
	 */
	public $url = null; // Amazon API URL.

	/**
	 * Amazon API Access Key.
	 *
	 * @var string
	 */
	private $_access_key = null;

	/**
	 * Amazon API Secret Key.
	 *
	 * @var string
	 */
	private $_secret_key = null;

	/**
	 * Amazon API Associate Partner Tag.
	 *
	 * @var string
	 */
	private $_associate_tag = null;

	/**
	 * Amazon API Region Name.
	 *
	 * @var string
	 */
	private $_region_name = 'us-east-1';

	/**
	 * Amazon API Service Name.
	 *
	 * @var string
	 */
	private $_service_name = 'ProductAdvertisingAPI';

	/**
	 * Amazon API Headers.
	 *
	 * @var array
	 */
	private $_aws_headers = array();

	/**
	 * Amazon API Payload.
	 *
	 * @var string
	 */
	private $_payload = '';

	/**
	 * Amazon API Host.
	 *
	 * @var string
	 */
	private $_host = 'webservices.amazon.com';

	/**
	 * Amazon API Path.
	 *
	 * @var string
	 */
	private $_path = '/paapi5/getitems';

	/**
	 * Amazon API HTTP Method.
	 *
	 * @var string
	 */
	private $_http_method = 'POST';

	/**
	 * Amazon API HMAC Algorithm.
	 *
	 * @var string
	 */
	private $_hmac_algorithm = 'AWS4-HMAC-SHA256';

	/**
	 * Amazon API AWS4 Request.
	 *
	 * @var string
	 */
	private $_aws4_request = 'aws4_request';

	/**
	 * Amazon API Signed Header.
	 *
	 * @var string
	 */
	private $_str_signed_header = null;

	/**
	 * Amazon API X-Amz-Date.
	 *
	 * @var string
	 */
	private $_x_amz_date = null;

	/**
	 * Amazon API Current Date.
	 *
	 * @var string
	 */
	private $_current_date = null;

	/**
	 * Amazon API Operator.
	 *
	 * @var string
	 */
	private $_operator = null;

	/**
	 * Amazon API Regions Array.
	 *
	 * @var array
	 */
	private $_regions_array = array(
		'com'    => 'us-east-1',
		'com.au' => 'us-west-2',
		'com.be' => 'eu-west-1',
		'com.br' => 'us-east-1',
		'ca'     => 'us-east-1',
		'eg'     => 'eu-west-1',
		'fr'     => 'eu-west-1',
		'de'     => 'eu-west-1',
		'in'     => 'eu-west-1',
		'it'     => 'eu-west-1',
		'co.jp'  => 'us-west-2',
		'com.mx' => 'us-east-1',
		'nl'     => 'eu-west-1',
		'pl'     => 'eu-west-1',
		'sg'     => 'us-west-2',
		'sa'     => 'eu-west-1',
		'es'     => 'eu-west-1',
		'se'     => 'eu-west-1',
		'com.tr' => 'eu-west-1',
		'ae'     => 'eu-west-1',
		'co.uk'  => 'eu-west-1',
	);

	/**
	 * API settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $spfa_api_options    Settings of this plugin.
	 */
	private $spfa_api_options;

	/**
	 * Current User ID.
	 *
	 * @var string
	 */
	private $user_id = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->setup_api_hooks();
	}

	/**
	 * All public facing hook will be placed under this function.
	 */
	public function setup_api_hooks() {
		add_action( 'rest_api_init', array( $this, 'spfa_register_api_endpoint_fetch_product' ) );
		add_action( 'rest_api_init', array( $this, 'spfa_register_api_endpoint_import_product' ) );
		add_action( 'rest_api_init', array( $this, 'spfa_register_api_endpoint_clear_cache' ) );

		$this->spfa_api_options = get_option( 'spfa_api_options' );

		$this->_host          = ! empty( $this->spfa_api_options['region'] ) ? 'webservices.amazon.' . $this->spfa_api_options['region'] : 'webservices.amazon.com';
		$this->_region_name   = ! empty( $this->spfa_api_options['region'] ) ? $this->_regions_array[ $this->spfa_api_options['region'] ] : 'us-east-1';
		$this->_access_key    = ! empty( $this->spfa_api_options['api_access_key'] ) ? $this->spfa_api_options['api_access_key'] : '';
		$this->_secret_key    = ! empty( $this->spfa_api_options['api_secret_key'] ) ? $this->spfa_api_options['api_secret_key'] : '';
		$this->_associate_tag = ! empty( $this->spfa_api_options['partner_tag'] ) ? $this->spfa_api_options['partner_tag'] : '';
		$this->_x_amz_date    = $this->get_time_stamp();
		$this->_current_date  = $this->get_date();
		$this->_operator      = 'GetItems';
		$this->url            = sprintf(
			'https://%s/%s',
			untrailingslashit( $this->_host ),
			$this->unleadingslashit( $this->_path )
		);

		$this->user_id = get_current_user_id();
	}

	/**
	 * Register Endpoint to fetch Amazon Product.
	 */
	public function spfa_register_api_endpoint_fetch_product() {
		register_rest_route(
			'sync-product-from-amazon/v1',
			'/fetch-product',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'fetch_amazon_product' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register Endpoint to fetch and import Amazon Product.
	 */
	public function spfa_register_api_endpoint_import_product() {
		register_rest_route(
			'sync-product-from-amazon/v1',
			'/import-product',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_amazon_product' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register Endpoint to clear cache.
	 */
	public function spfa_register_api_endpoint_clear_cache() {
		register_rest_route(
			'sync-product-from-amazon/v1',
			'/clear-cache',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_cache' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Callback function to clear cache.
	 */
	public function clear_cache() {
		global $wpdb;
		try {
			$wpdb->query( $wpdb->prepare( 'DELETE FROM `' . $wpdb->prefix . 'options` WHERE `option_name` LIKE %s', '%_transient_spfa_%' ) ); // phpcs:ignore
			$wpdb->query( $wpdb->prepare( 'DELETE FROM `' . $wpdb->prefix . 'options` WHERE `option_name` LIKE %s', '%_transient_timeout_spfa_%' ) ); // phpcs:ignore
			echo 'Transients deleted';
		} catch ( \Exception $e ) {
			printf( esc_html( 'Error: %s' ), esc_html( $wpdb->last_error ) );
		}
	}

	/**
	 * Callback function to handle Amazon product REST Endpoint.
	 *
	 * @param array $request Array of Request parameters.
	 */
	public function fetch_amazon_product( $request ) {

		$asin            = $request['asin'];
		$test_connection = $request['testConnection'] ?? false;

		$resources = array(
			'Images.Primary.Small',
			'Images.Primary.Medium',
			'Images.Primary.Large',
			'ItemInfo.Title',
			'ItemInfo.ProductInfo',
			'ItemInfo.Features',
			'ItemInfo.ByLineInfo',
			'Offers.Listings.Condition',
			'Offers.Listings.Price',
			'Offers.Listings.Promotions',
			'BrowseNodeInfo.BrowseNodes',
			'ParentASIN',
		);

		if ( ! is_array( $asin ) ) {
			$asin = str_replace( ' ', '', $asin );
			$asin = strpos( $asin, ',' ) ? explode( ',', $asin ) : array( $asin );
		}

		$this->set_payload( $asin, $resources );

		$args = array(
			'headers' => $this->get_headers(),
			'body'    => $this->get_payload(),
		);

		$args_body    = $args['body'];
		$args_body    = json_decode( $args_body, true );
		$item_ids     = $args_body['ItemIds'];
		$item_ids_str = ( is_array( $item_ids ) && count( $item_ids ) > 1 ) ? 'spfa_' . implode( '_', $item_ids ) : 'spfa_' . $item_ids[0];

		$get_transient = get_transient( $item_ids_str );
		if ( $get_transient && ! $test_connection ) {
			return json_decode( $get_transient );
		}

		$response      = wp_safe_remote_post( $this->url, $args );
		$response_body = '';

		if ( ! is_wp_error( $response ) ) {
			$response_body        = wp_remote_retrieve_body( $response );
			$response_body_decode = json_decode( $response_body );

			if ( 200 === wp_remote_retrieve_response_code( $response ) && ! empty( $response_body_decode->ItemsResult->Items ) ) {
				if ( count( $item_ids ) < 4 && ! $test_connection ) {
					set_transient( $item_ids_str, $response_body, DAY_IN_SECONDS );
				}
			} else {
				$response_body = array(
					'status'  => 500,
					'message' => 'No product found.',
				);
			}
		}

		return json_decode( $response_body );
	}

	/**
	 * Get product details.
	 *
	 * @param array $request Request data.
	 *
	 * @return array
	 */
	public function import_amazon_product( $request ) {

		$asin        = $request['asin'];
		$post_status = $request['postStatus'] ?? 'publish';
		$post_type   = $request['postType'] ?? 'post';

		if ( strpos( $asin, ',' ) ) {
			$asin = explode( ',', $asin );
		} else {
			$asin = array( $asin );
		}

		$asin = array_filter( $asin );

		if ( count( $asin ) > 10 ) {
			$response = array(
				'status'  => 'error',
				'message' => 'You can import maximum 10 products at a time.',
			);

			return wp_json_encode( $response );
		}

		$resources = array(
			'Images.Primary.Small',
			'Images.Primary.Medium',
			'Images.Primary.Large',
			'ItemInfo.Title',
			'ItemInfo.ProductInfo',
			'ItemInfo.Features',
			'ItemInfo.ByLineInfo',
			'Offers.Listings.Condition',
			'Offers.Listings.Price',
			'Offers.Listings.Promotions',
			'BrowseNodeInfo.BrowseNodes',
			'ParentASIN',
		);

		$this->set_payload( $asin, $resources );

		$args = array(
			'headers' => $this->get_headers(),
			'body'    => $this->get_payload(),
		);

		$args['body'] = str_replace( '\r', '', $args['body'] );

		$response      = wp_safe_remote_post( $this->url, $args );
		$response_body = '';

		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );

			if ( 200 === wp_remote_retrieve_response_code( $response ) && ! empty( $response_body ) ) {
				$response_body = json_decode( $response_body );

				// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( is_array( $response_body->ItemsResult->Items ) && count( $response_body->ItemsResult->Items ) > 0 ) {
					foreach ( $response_body->ItemsResult->Items as $item ) {

						$product_asin     = ! empty( $item->ASIN ) ? sanitize_text_field( $item->ASIN ) : '';
						$product_title    = ! empty( $item->ItemInfo->Title->DisplayValue ) ? sanitize_text_field( $item->ItemInfo->Title->DisplayValue ) : '';
						$product_features = ! empty( $item->ItemInfo->Features->DisplayValues ) ? $this->sanitize_array( $item->ItemInfo->Features->DisplayValues ) : array();
						$product_image    = ! empty( $item->Images->Primary->Large->URL ) ? esc_url_raw( wp_unslash( $item->Images->Primary->Large->URL ) ) : '';
						$product_link     = ! empty( $item->DetailPageURL ) ? esc_url_raw( wp_unslash( $item->DetailPageURL ) ) : '';

						$price = floatval( $item->Offers->Listings[0]->Price->Amount ) + floatval( $item->Offers->Listings[0]->Price->Savings->Amount );

						$product_price      = mb_substr( $item->Offers->Listings[0]->Price->DisplayAmount, 0, 1 ) . $price ?? '';
						$product_sale_price = ! empty( $item->Offers->Listings[0]->Price->DisplayAmount ) ? sanitize_text_field( wp_unslash( $item->Offers->Listings[0]->Price->DisplayAmount ) ) : '';

						$post_data = array(
							'post_title'   => $product_title,
							'post_content' => ( is_array( $product_features ) && count( $product_features ) > 0 ) ? implode( PHP_EOL, $product_features ) : $product_features,
							'post_status'  => $post_status,
							'post_type'    => $post_type,
							'post_author'  => $this->user_id,
						);

						$post_id = wp_insert_post( $post_data );

						update_post_meta( $post_id, 'spfa_product_asin', $product_asin );
						update_post_meta( $post_id, 'spfa_product_link', $product_link );
						update_post_meta( $post_id, 'spfa_product_price', $product_price );
						update_post_meta( $post_id, 'spfa_product_sale_price', $product_sale_price );

						if ( ! function_exists( 'media_sideload_image' ) ) {
							require_once ABSPATH . 'wp-admin/includes/media.php';
							require_once ABSPATH . 'wp-admin/includes/file.php';
							require_once ABSPATH . 'wp-admin/includes/image.php';
						}

						$image = media_sideload_image( $product_image, $post_id, '', 'id' );
						if ( ! is_wp_error( $image ) ) {
							set_post_thumbnail( $post_id, $image );
						}

						set_post_thumbnail( $post_id, $image );
					}

					$response = array(
						'status'  => 'success',
						'message' => 'Product has been imported successfully.',
					);

					return wp_json_encode( $response );
				}
				// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		return json_decode( $response_body );
	}

	/**
	 * Create payload to pass to API request.
	 *
	 * @param array $asin ASIN Number.
	 * @param array $resources Resources.
	 */
	protected function set_payload( array $asin, array $resources ): void {

		// Remove empty values.
		$asin = array_unique( array_values( array_filter( $asin ) ) );

		$payload = array(
			'PartnerTag'            => $this->_associate_tag,
			'PartnerType'           => 'Associates',
			'LanguagesOfPreference' => array( 'en_US' ),
			'Marketplace'           => ! empty( $this->spfa_api_options['region'] ) ? 'www.amazon.' . $this->spfa_api_options['region'] : 'www.amazon.com',
			'Resources'             => $resources,
		);

		if ( 'GetVariations' === $this->_operator ) {
			$payload['ASIN'] = reset( $asin );
		} else {
			if ( ! is_array( $asin ) ) {
				$asin = str_replace( ' ', '', $asin );
				$asin = str_replace( '\r', '', $asin );
				if ( strpos( $asin, ',' ) ) {
					$asin = explode( ',', $asin );
				} else {
					$asin = array( $asin );
				}
			}

			$payload['ItemIds']    = array_filter( $asin );
			$payload['ItemIdType'] = 'ASIN';
		}

		ksort( $payload );

		$this->_payload = wp_json_encode( $payload );
	}

	/**
	 * Prepare canonical request string.
	 *
	 * @return string
	 */
	private function prepare_canonical_request(): string {

		$canonical_url  = array(
			$this->_http_method,
			$this->_path,
			'',
		);
		$signed_headers = array();

		foreach ( $this->_aws_headers as $key => $value ) {
			$signed_headers[] = strtolower( $key ) . ';';
			$canonical_url[]  = strtolower( $key ) . ':' . $value;
		}

		$canonical_url[]          = '';
		$this->_str_signed_header = substr( implode( '', $signed_headers ), 0, -1 );
		$canonical_url[]          = $this->_str_signed_header;
		$canonical_url[]          = $this->generate_hex( $this->_payload );

		return implode( PHP_EOL, $canonical_url );
	}

	/**
	 * Prepare string for signature.
	 *
	 * @param string $canonical_url Canonical URL.
	 *
	 * @return string
	 */
	private function prepare_string_to_sign( string $canonical_url ): string {

		$string_to_sign = array();

		/* Add algorithm designation, followed by a newline character. */
		$string_to_sign[] = $this->_hmac_algorithm;

		/* Append the request date value, followed by a newline character. */
		$string_to_sign[] = $this->_x_amz_date;

		/* Append the credential scope value, followed by a newline character. */
		$string_to_sign[] = sprintf(
			'%s/%s/%s/%s',
			$this->_current_date,
			$this->_region_name,
			$this->_service_name,
			$this->_aws4_request
		);

		/* Append the hash of the canonical request */
		$string_to_sign[] = $this->generate_hex( $canonical_url );

		return implode( PHP_EOL, $string_to_sign );
	}

	/**
	 * Calculate the signature for API request.
	 *
	 * @param string $string_to_sign Calculate signature.
	 *
	 * @return string
	 */
	private function calculate_signature( string $string_to_sign ): string {

		/* Derive signing key */
		$signature_key = $this->get_signature_key( $this->_secret_key, $this->_current_date, $this->_region_name, $this->_service_name );

		/* Calculate the signature. */
		$signature = hash_hmac( 'sha256', $string_to_sign, $signature_key, true );

		/* Encode signature (byte[]) to Hex */
		return strtolower( bin2hex( $signature ) );
	}

	/**
	 * Returns payload for body of request.
	 *
	 * @return string
	 */
	public function get_payload(): string {
		return $this->_payload;
	}

	/**
	 * Returns headers for request.
	 *
	 * @return array
	 */
	public function get_headers(): array {

		$this->_aws_headers['Content-Encoding'] = 'amz-1.0';
		$this->_aws_headers['Content-Type']     = 'application/json';
		$this->_aws_headers['Host']             = $this->_host;
		$this->_aws_headers['X-Amz-Date']       = $this->_x_amz_date;
		$this->_aws_headers['X-Amz-Target']     = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $this->_operator;

		/* Sort headers */
		ksort( $this->_aws_headers );

		/* Create a Canonical Request for Signature Version 4. */
		$canonical_url = $this->prepare_canonical_request();

		/* Create a String to Sign for Signature Version 4. */
		$string_to_sign = $this->prepare_string_to_sign( $canonical_url );

		/* Calculate the AWS Signature Version 4. */
		$signature = $this->calculate_signature( $string_to_sign );

		if ( ! empty( $signature ) ) {
			$this->_aws_headers['Authorization'] = $this->build_authorization_string( $signature );
		}

		return $this->_aws_headers;
	}

	/**
	 * Builds string for request authorization.
	 *
	 * @param string $str_signature Builds Authorization string.
	 *
	 * @return string
	 */
	private function build_authorization_string( string $str_signature ): string {

		return sprintf(
			'%s Credential=%s/%s/%s/%s/%s,SignedHeaders=%s,Signature=%s',
			$this->_hmac_algorithm,
			$this->_access_key,
			$this->_current_date,
			$this->_region_name,
			$this->_service_name,
			$this->_aws4_request,
			$this->_str_signed_header,
			$str_signature
		);
	}

	/**
	 * Generates a hash.
	 *
	 * @param string $data Data.
	 *
	 * @return string
	 */
	private function generate_hex( string $data ): string {

		return hash( 'sha256', $data );
	}

	/**
	 * Gets signature key.
	 *
	 * @param string $key Key.
	 * @param string $date Date.
	 * @param string $region Region.
	 * @param string $service Service.
	 *
	 * @return string
	 */
	private function get_signature_key( string $key, string $date, string $region, string $service ): string {

		$k_secret  = 'AWS4' . $key;
		$k_date    = hash_hmac( 'sha256', $date, $k_secret, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );

		return hash_hmac( 'sha256', $this->_aws4_request, $k_service, true );
	}

	/**
	 * Get time stamp.
	 *
	 * @return string
	 */
	private function get_time_stamp(): string {

		return gmdate( 'Ymd\THis\Z' );
	}

	/**
	 * Get current date string.
	 *
	 * @return string
	 */
	private function get_date(): string {

		return gmdate( 'Ymd' );
	}

	/**
	 * Remove forward slash from the beginning of a string.
	 *
	 * @param string $url_string String from which forward slash is to be removed from beginning.
	 * @return string String with forward slash removed from beginning.
	 */
	public static function unleadingslashit( $url_string ): string {
		return ltrim( $url_string, '/' );
	}

	/**
	 * Function to sanitize an array.
	 *
	 * @param array $array_data Array to sanitize.
	 * @return array Sanitized array.
	 */
	private function sanitize_array( $array_data ) {
		if ( ! is_array( $array_data ) ) {
			return false;
		}

		foreach ( $array_data as $key => $value ) {
			// If it's an array, recursively sanitize it.
			if ( is_array( $value ) ) {
				$array_data[ $key ] = $this->sanitize_array( $value );
			} else {
				// Example: Use sanitize_text_field for strings, adjust as needed.
				$array_data[ $key ] = sanitize_text_field( $value );
			}
		}

		return $array_data;
	}
}
