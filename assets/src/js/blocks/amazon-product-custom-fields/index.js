/* eslint-disable camelcase */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

const amazonProductCustomFields = () => {
	// eslint-disable-next-line react-hooks/rules-of-hooks
	const postType = useSelect(
		(select) => select('core/editor').getCurrentPostType(),
		[]
	);

	// eslint-disable-next-line react-hooks/rules-of-hooks
	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

	const {
		md_product_asin: mdProductAsin = '',
		md_product_price: mdProductPrice = '',
		md_product_sale_price: mdProductSalePrice = '',
		md_product_link: mdProductLink = '',
	} = meta || {};

	const onProductAsinChange = (newAsinValue) => {
		setMeta({ ...meta, md_product_asin: newAsinValue });
	};

	const onProductPriceChange = (newProductPrice) => {
		setMeta({ ...meta, md_product_price: newProductPrice });
	};

	const onProductSalePriceChange = (newProductSalePrice) => {
		setMeta({
			...meta,
			md_product_sale_price: newProductSalePrice,
		});
	};

	const onProductLinkChange = (newProductLink) => {
		setMeta({ ...meta, md_product_link: newProductLink });
	};

	return (
		<PluginDocumentSettingPanel
			name="md-amazon-product-custom-fields-panel"
			title={__(
				'Amazon Product Custom Fields',
				'sync-product-from-amazon'
			)}
			className="md-amazon-product-custom-fields-panel"
		>
			<TextControl
				label={__('Product ASIN', 'sync-product-from-amazon')}
				value={mdProductAsin}
				onChange={onProductAsinChange}
				disabled
			/>
			<TextControl
				label={__('Product Price', 'sync-product-from-amazon')}
				value={mdProductPrice}
				onChange={onProductPriceChange}
			/>
			<TextControl
				label={__('Product Sale Price', 'sync-product-from-amazon')}
				value={mdProductSalePrice}
				onChange={onProductSalePriceChange}
			/>
			<TextControl
				label={__('Product Link', 'sync-product-from-amazon')}
				value={mdProductLink}
				onChange={onProductLinkChange}
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin('amazon-product-custom-fields', {
	render: amazonProductCustomFields,
});
