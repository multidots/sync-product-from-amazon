/**
 * File admin.js.
 *
 * Handles admin scripts
 *
 * @param {jQuery} $ - The jQuery object.
 */
(function ($) {
	'use strict';

	const AMAZON_FETCH_PRODUCT_DATA_ENDPOINT =
		'/wp-json/sync-product-from-amazon/v1/fetch-product';
	const AMAZON_IMPORT_PRODUCT_DATA_ENDPOINT =
		'/wp-json/sync-product-from-amazon/v1/import-product';

	// Accordion on the help tab using direct document.on click
	$(document).on(
		'click',
		'.sync-product-from-amazon__accordion-main .sync-product-from-amazon__accordion-wrap',
		function () {
			const $container = $(this).closest(
				'.sync-product-from-amazon__accordion-main'
			); // Get the closest accordion container
			const $accordionContent = $container.find(
				'.sync-product-from-amazon__accordion-content'
			);
			const $icon = $container.find('.icon'); // Icon toggle

			// Slide toggle the content
			$accordionContent.stop(true, true).slideToggle(500);
			$icon.toggleClass('active');
		}
	);

	// Fetch product details for Add New Product page.
	$(document).on('click', '#md_fetch_product_details', function () {
		const asin = $('#md_product_asin').val();

		if ('' === asin) {
			document.getElementById('md_add_product_empty_asin').style.display =
				'block';
			return false;
		}

		if (!is_valid_asin_format(asin)) {
			document.getElementById('md_add_product_valid_asin').style.display =
				'block';
			return false;
		}

		const options = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({ asin }),
		};

		fetch(AMAZON_FETCH_PRODUCT_DATA_ENDPOINT, options)
			.then((response) => response.json())
			.then((response) => {
				if (response.ItemsResult.Items.length > 0) {
					const item = response.ItemsResult.Items[0];
					const productTitle = item.ItemInfo.Title.DisplayValue ?? '';
					const productImage = item.Images.Primary.Large.URL ?? '';
					const productFeatures =
						item.ItemInfo.Features.DisplayValues ?? [];
					const productLink = item.DetailPageURL ?? '';
					let productPrice = 0;
					const productSalePrice =
						item.Offers.Listings[0].Price.DisplayAmount ?? '';
					productPrice =
						parseFloat(item.Offers.Listings[0].Price.Amount) +
						parseFloat(
							item.Offers.Listings[0].Price.Savings.Amount
						);
					const productPriceCurrency =
						item.Offers.Listings[0].Price.DisplayAmount.charAt(0) ??
						0;

					if ('' !== productTitle) {
						$('#md_product_title').val(productTitle);
					}

					if ('' !== productImage) {
						const image = new Image();
						image.src = productImage;
						image.onload = function () {
							$('#md_product_image').val(productImage);
							$('#md_product_image_preview').show();
							$('#md_product_image_preview').attr(
								'src',
								productImage
							);
						};
					}

					if ('' !== productLink) {
						$('#md_product_link').val(productLink);
					}

					if ('' !== productPrice) {
						$('#md_product_price').val(
							productPriceCurrency + productPrice.toFixed(2)
						);
					}

					if ('' !== productSalePrice) {
						$('#md_product_sale_price').val(productSalePrice);
					}

					if (productFeatures.length > 0) {
						$('#md_product_features').text(
							productFeatures.join('\n')
						);
					}
				}
			});
	});

	// Fetch product details for Import Products page.
	$(document).on('click', '#md_import_products_button', function (e) {
		e.preventDefault();

		const postStatus =
			document.getElementById('md_import_product_status').value ??
			'publish';
		const postType =
			document.getElementById('md_import_product_post_type').value ??
			'post';

		let asinNumbers = '';

		asinNumbers = $('#md_import_products_textarea')
			.val()
			.replaceAll(' ', '');

		if ('' === asinNumbers) {
			document.getElementById(
				'md_import_product_empty_asin'
			).style.display = 'block';
			return false;
		}

		const asinNumbersArray = asinNumbers.split(',');

		for (let i = 0; i < asinNumbersArray.length; i++) {
			if (!is_valid_asin_format(asinNumbersArray[i])) {
				document.getElementById(
					'md_import_product_valid_asin'
				).style.display = 'block';

				$('#md_import_product_valid_asin p strong').text(
					'Invalid ASIN number: ' + asinNumbersArray[i]
				);
				return false;
			}
		}

		fetchProducts(asinNumbers, postStatus, postType);
	});

	// Fetch product details from Amazon API.
	function fetchProducts(asin, postStatus, postType) {
		const options = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({ asin, postStatus, postType }),
		};

		if ('' === asin) {
			document.getElementById(
				'md_import_product_empty_asin'
			).style.display = 'block';
			return false;
		}

		document.getElementById(
			'md_import_products_button'
		).style.pointerEvents = 'none';

		document.getElementById(
			'sync_product_from_amazon_loader'
		).style.display = 'block';

		fetch(AMAZON_IMPORT_PRODUCT_DATA_ENDPOINT, options)
			.then((response) => response.json())
			.then((response) => {
				response = JSON.parse(response);
				if ('success' === response.status) {
					document.getElementById(
						'md_import_product_success'
					).style.display = 'block';
				} else {
					document.getElementById(
						'md_import_product_fail'
					).style.display = 'block';
				}

				document.getElementById(
					'sync_product_from_amazon_loader'
				).style.display = 'none';

				document.getElementById(
					'md_import_products_button'
				).style.pointerEvents = 'auto';

				return response;
			});
	}

	// Handle Clear cache button click.
	$(document).on('click', '#md_app_delete_cache', function (e) {
		e.preventDefault();

		// eslint-disable-next-line no-alert
		if (true === confirm('Are you sure want to clear cache?')) {
			const options = {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
			};

			fetch(
				'/wp-json/sync-product-from-amazon/v1/clear-cache',
				options
			).then((response) => {
				if (200 === response.status) {
					document.getElementById(
						'md_clear_cache_success'
					).style.display = 'block';
				} else {
					document.getElementById(
						'md_clear_cache_fail'
					).style.display = 'block';
				}
			});
		}
	});

	// Handle Test API Connection button click.
	$(document).on('click', '#md_test_api_connection', function (e) {
		e.preventDefault();

		const apiKey = document.getElementById(
			'md_amazon_api_access_key'
		).value;
		const apiSecret = document.getElementById(
			'md_amazon_api_secret_key'
		).value;
		const partnerTag = document.getElementById(
			'md_amazon_partner_tag'
		).value;

		if ('' === apiKey || '' === apiSecret || '' === partnerTag) {
			document.getElementById(
				'md_test_api_empty_credentials'
			).style.display = 'block';
			return false;
		}

		const asin = document.getElementById('md_asin_number').value;

		if (!is_valid_asin_format(asin)) {
			document.getElementById(
				'md_test_product_valid_asin'
			).style.display = 'block';
			return false;
		}

		const testConnection = true;

		const options = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({ asin, testConnection }),
		};

		fetch(AMAZON_FETCH_PRODUCT_DATA_ENDPOINT, options).then((response) => {
			if (200 === response.status) {
				document.getElementById('md_test_api_success').style.display =
					'block';
			} else {
				document.getElementById('md_test_api_fail').style.display =
					'block';
			}
		});
	});

	// Validate ASIN Number.
	// eslint-disable-next-line camelcase
	function is_valid_asin_format(asinNumber) {
		return /^[A-Z0-9]{10}$/.test(asinNumber);
	}

	$(document).on(
		'click',
		'.sync-product-from-amazon__main .notice-dismiss',
		function (e) {
			e.preventDefault();
			$(this).parent().hide();
		}
	);
})(jQuery);
