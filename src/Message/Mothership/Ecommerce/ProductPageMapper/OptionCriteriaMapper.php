<?php

namespace Message\Mothership\Ecommerce\ProductPageMapper;

use Message\Mothership\Commerce\Product;

/**
 * Product page mapper that relates products to pages through a field name,
 * optional group name and optional product option criteria.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
class OptionCriteriaMapper extends SimpleMapper
{
	/**
	 * @{inheritDoc}
	 */
	public function getPagesForProduct(Product\Product $product, array $options = null, $limit = null)
	{
		$params = array(
			'productID' => $product->id,
			'fieldNames' => $this->_validFieldNames,
			'pageTypes' => $this->_validPageTypes,
		);

		$query = '
			SELECT
				page.page_id
			FROM
				page
			JOIN
				page_content AS product_content ON (
					page.page_id = product_content.page_id
				AND product_content.field_name IN (:fieldNames?js)
		';

		if (count($this->_validGroupNames)) {
			$query .= 'AND product_content.group_name IN (:groupNames?js)';
			$params['groupNames'] = $this->_validGroupNames;
		}

		$query .= '
				)
			LEFT JOIN
				page_content AS option_name_content ON (
					page.page_id = option_name_content.page_id
				AND option_name_content.group_name = "product"
				AND option_name_content.field_name = "option"
				AND option_name_content.data_name  = "name"
				)
			LEFT JOIN
				page_content AS option_value_content ON (
					page.page_id = option_value_content.page_id
				AND option_value_content.group_name = "product"
				AND option_value_content.field_name = "option"
				AND option_value_content.data_name  = "value"
				)
			WHERE
				page.type IN (:pageTypes?js)
			AND product_content.value_int  = :productID?i
		';

		if (null !== $options) {
			$query .= ' AND option_name_content.value_string IN (:optionNames?js)';
			$query .= ' AND option_value_content.value_string IN (:optionValues?js)';
			$params['optionNames']  = array_keys($options);
			$params['optionValues'] = array_values($options);
		}

		$query .= ' ORDER BY position_left ASC';

		if (null !== $limit) {
			$query .= ' LIMIT :limit?i';
			$params['limit'] = $limit;
		}

		return $this->_loadPages($query, $params);
	}
}