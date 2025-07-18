import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';

const AMAZON_PRODUCT_DATA_ENDPOINT =
	'/sync-product-from-amazon/v1/fetch-product';

const Edit = (props) => {
	const { attributes, setAttributes } = props;
	const { productDetails } = attributes;

	const blockProps = useBlockProps();

	/**
	 * Local state.
	 */
	const [loading, setLoading] = useState(false);
	const [fetchError, setFetchError] = useState(false);
	const [asinError, setAsinError] = useState(false);
	const [asinFormatError, setAsinFormatError] = useState(false);
	const [asin, setAsin] = useState('');
	const [showMore, setShowMore] = useState(false);
	const [maxVisibleFeatures, setMaxVisibleFeatures] = useState(2);

	/**
	 * Fetch product by ASIN.
	 *
	 * @param {string} next new asin.
	 */
	const fetchProduct = async (next) => {
		if (!next) {
			setAsinError(true);
			return;
		}

		if (!isValidAsinFormat(next)) {
			setAsinFormatError(true);
			return;
		}

		setAsinFormatError(false);
		setLoading(true);

		try {
			const response = await apiFetch({
				path: AMAZON_PRODUCT_DATA_ENDPOINT,
				method: 'POST',
				data: { asin: next },
			});

			if (
				response.ItemsResult.Items &&
				response.ItemsResult.Items.length > 0
			) {
				const arrayItems = response.ItemsResult.Items;

				const productDataArray = arrayItems.map((element) => {
					const itemInfo = element.ItemInfo;
					const offers = element.Offers;
					const images = element.Images;
					const productPrice =
						parseFloat(offers.Listings[0].Price.Amount) +
						parseFloat(offers.Listings[0].Price.Savings.Amount);
					const productPriceCurrency =
						offers.Listings[0].Price.DisplayAmount.charAt(0) ?? 0;

					return {
						title: itemInfo.Title.DisplayValue,
						mediaURL: images.Primary.Medium.URL,
						productURL: element.DetailPageURL,
						asin: next,
						features: itemInfo.Features.DisplayValues,
						price: productPriceCurrency + productPrice.toFixed(2),
						salePrice: offers.Listings[0].Price.DisplayAmount,
						discountPercentage: offers.Listings[0].PercentageSaved,
					};
				});

				setAttributes({
					productDetails: productDataArray,
				});
			} else {
				setFetchError(true);
			}

			setLoading(false);
		} catch (error) {
			setFetchError(error?.message);
		} finally {
			setLoading(false);
		}
	};

	const toggleShowMore = () => {
		if (showMore) {
			setMaxVisibleFeatures(2);
		} else {
			setMaxVisibleFeatures(productDetails[0]?.features?.length || 0);
		}
		setShowMore(!showMore);
	};

	// Validate ASIN Number.
	const isValidAsinFormat = (asinNumber) => {
		return /^[A-Z0-9]{10}$/.test(asinNumber);
	};

	return (
		<>
			<div {...blockProps}>
				{loading ? (
					<div className="sync-product-from-amazon__loader"></div>
				) : (
					<div className="sync-product-from-amazon__container">
						{productDetails && (
							<div className="sync-product-from-amazon__product">
								{productDetails.map((product, index) => (
									<div
										key={index}
										className="sync-product-from-amazon"
									>
										<div className="sync-product-from-amazon__left_wrapper">
											<div className="sync-product-from-amazon__image">
												<img
													src={product.mediaURL}
													alt={product.title}
												/>
											</div>
											<div className="sync-product-from-amazon__details_wrap">
												<div className="sync-product-from-amazon__price_detail">
													<div className="sync-product-from-amazon__org_price">
														<span>
															{product.price}
														</span>
													</div>
													<div className="sync-product-from-amazon__sale_price">
														<span>
															{product.salePrice}
														</span>
													</div>
												</div>
												<a
													href={product.productURL}
													className="sync-product-from-amazon__button"
												>
													<span className="sync-product-from-amazon__link">
														{__(
															'View on Amazon',
															'sync-product-from-amazon'
														)}
													</span>
												</a>
											</div>
										</div>
										<div className="sync-product-from-amazon__content">
											<h4 className="sync-product-from-amazon__title">
												{product.title}
											</h4>
											<div className="sync-product-from-amazon__features">
												<ul>
													{product.features
														.slice(
															0,
															maxVisibleFeatures
														)
														.map(
															(
																feature,
																index
															) => (
																<li
																	key={index}
																	className="sync-product-from-amazon__feature_item"
																>
																	{feature}
																</li>
															)
														)}
													{product.features.length >
														2 && (
														<a
															variant="link"
															onClick={
																toggleShowMore
															}
															className="sync-product-from-amazon__show-more sync-product-from-amazon__feature_item_show_more"
														>
															{showMore
																? __(
																		'Show less',
																		'sync-product-from-amazon'
																  )
																: __(
																		'Show more',
																		'sync-product-from-amazon'
																  )}
														</a>
													)}
												</ul>
											</div>
										</div>
									</div>
								))}
							</div>
						)}
						{!productDetails ||
							(productDetails.length === 0 && (
								<p>
									{__(
										'Please enter ASIN number and click on Fetch Product button to fetch product details.',
										'sync-product-from-amazon'
									)}
								</p>
							))}
						{fetchError && (
							<p className="sync-product-from-amazon__error">
								{__(
									'Product not found. Please enter valid ASIN number.',
									'sync-product-from-amazon'
								)}
							</p>
						)}
					</div>
				)}
			</div>
			<InspectorControls>
				<PanelBody
					title={__('Block Settings', 'sync-product-from-amazon')}
					initialOpen={true}
				>
					<TextControl
						label={__(
							'Enter ASIN Number…',
							'sync-product-from-amazon'
						)}
						onChange={(value) => {
							setAsinError(false);
							if (isValidAsinFormat(value)) {
								setAsinFormatError(false);
							}
							setAsin(value);
						}}
					/>
					{asinError && (
						<p className="sync-product-from-amazon__error">
							{__(
								'Please add ASIN number.',
								'sync-product-from-amazon'
							)}
						</p>
					)}
					{asinFormatError && (
						<p className="sync-product-from-amazon__error">
							{__(
								'Please enter valid ASIN number.',
								'sync-product-from-amazon'
							)}
						</p>
					)}
					<Button
						variant="primary"
						onClick={() => {
							fetchProduct(asin);
						}}
					>
						{__('Fetch Product', 'sync-product-from-amazon')}
					</Button>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

export default Edit;
