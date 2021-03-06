<?php

namespace Message\Mothership\Ecommerce\ProductPage;

use Message\Mothership\Commerce\Product;

use Message\Mothership\CMS\Page;
use Message\Mothership\CMS\PageType;

use Message\Cog\ValueObject\DateRange;

/**
 * @todo move parent creation stuff to ParentCreate class
 *
 * Class Create
 * @package Message\Mothership\Ecommerce\ProductPage
 */
class Create
{
	/**
	 * The defautlt page type to create
	 */
	const PAGE_TYPE     = 'product';
	/**
	 * To be used as the value of Options::PAGE_VARIANTS in the config if the 
	 * page does not require variant values to be set.
	 */
	const INDIVIDUAL    = 'individual';

	/**
	 * The defualt content field which holds the product description
	 */
	const DESC_FIELD    = 'description';
	/**
	 * The default group which the product/product option fields exist within
	 */
	const PRODUCT_GROUP = 'product';
	/**
	 * The default product field
	 */
	const PRODUCT_FIELD = 'product';
	/**
	 * The default product option field
	 */
	const OPTION_FIELD  = 'option';

	/**
	 * @var \Message\Mothership\CMS\Page\Create
	 */
	private $_pageCreate;

	/**
	 * @var \Message\Mothership\CMS\PageType\Collection
	 */
	private $_pageTypes;

	/**
	 * @var \Message\Mothership\CMS\Page\ContentLoader
	 */
	private $_contentLoader;

	/**
	 * @var \Message\Mothership\CMS\Page\ContentEdit
	 */
	private $_contentEdit;

	/**
	 * @var \Message\Mothership\CMS\PageType\PageTypeInterface
	 */
	private $_listingPageType;

	/**
	 * @var ProductPageCreateEventDispatcher
	 */
	private $_dispatcher;

	/**
	 * @var ParentPageCreateEventDispatcher
	 */
	private $_parentDispatcher;

	/**
	 * @var Exists
	 */
	private $_exists;

	/**
	 * @var array
	 */
	private $_mapping;

	/**
	 * Allow creation of duplicate pages
	 * @var boolean
	 */
	private $_allowDuplicates = false;

	private $_defaults = [
		Options::CREATE_PAGES  => true,
		Options::PARENT        => null,
		Options::LISTING_TYPE  => null,
		Options::PAGE_VARIANTS => self::INDIVIDUAL,
		Options::CSV_PORT      => false,
	];

	public function __construct(
		Page\Create $pageCreate,
		Page\Edit $pageEdit,
		Page\Loader $pageLoader,
		Page\ContentLoader $contentLoader,
		Page\ContentEdit $contentEdit,
		PageType\Collection $pageTypes,
		$listingPageType,
		ProductPageCreateEventDispatcher $dispatcher,
		ParentPageCreateEventDispatcher $parentDispatcher,
		Exists $exists,
		array $productPageTypeMapping
	)
	{
		if ($listingPageType !== null && !$listingPageType instanceof PageType\PageTypeInterface) {
			throw new \InvalidArgumentException('Variable $listingPageType must be instance of PageType\PageTypeInterface or null');
		}

		$this->_pageCreate       = $pageCreate;
		$this->_pageEdit         = $pageEdit;
		$this->_pageLoader       = $pageLoader;
		$this->_contentLoader    = $contentLoader;
		$this->_contentEdit      = $contentEdit;
		$this->_pageTypes        = $pageTypes;
		$this->_listingPageType  = $listingPageType;
		$this->_dispatcher       = $dispatcher;
		$this->_parentDispatcher = $parentDispatcher;
		$this->_exists           = $exists;
		$this->_mapping          = $productPageTypeMapping;
	}

	public function create(Product\Product $product, array $options = [], Product\Unit\Unit $unit = null, $variantName = null)
	{
		if ($unit && !$variantName) {
			throw new \LogicException('You must set a variant name to make pages for individual variants');
		}
		$options = $options + $this->_defaults;

		if (empty($options[Options::CREATE_PAGES])) {
			return false;
		}

		$variantValue = ($unit) ? $unit->getOption($variantName) : null;

		if (!$this->_allowDuplicates && $this->_exists->exists($product, $variantName, $variantValue)) {
			return false;
		}

		$page = $this->_getNewProductPage($product, $this->_getParentPage($product, $options), $variantValue);
		$page->publishDateRange = new DateRange(new \DateTime);

		$this->_setProductPageContent($page, $product, $options, $variantName, $variantValue);

		return $this->_dispatcher->dispatch($page, $product, $options[Options::CSV_PORT], $unit);

	}

	public function setListingPageType($pageType)
	{
		if ($pageType !== null && $pageType instanceof PageType\PageTypeInterface) {
			throw new \InvalidArgumentException('Variable $pageType must be instance of PageType\PageTypeInterface or null');
		}

		$this->_listingPageType = $pageType;

		return $this;
	}

	private function _getParentPage(Product\Product $product, array $options)
	{
		if (!$options[Options::PARENT]) {
			return null;
		}

		$grandparent = $this->_pageLoader->getByID($options[Options::PARENT]);

		if (!$grandparent) {
			throw new \LogicException(
				'Could not load grandparent page with ID of `' . $options[Options::PARENT] . '` for product `' . $product->id . '`'
			);
		}

		if ($options[Options::LISTING_TYPE] === Options::SHOP) {
			return $grandparent;
		}

		$grandParentChildren = $this->_pageLoader->getChildren($grandparent);

		$parentSiblings = [];

		foreach ($grandParentChildren as $page) {
			$parentSiblings[$page->title] = $page;
		}

		$parentTitle = $product->{$options[Options::LISTING_TYPE]};

		if (array_key_exists($parentTitle, $parentSiblings)) {
			return $parentSiblings[$parentTitle];
		}

		if (!isset($this->_listingPageType)) {
			throw new \LogicException("Cannot dispatch ParentPageCreateEvent as no listing PageType set. Is service (`product.page_type.listing`) defined within the installation?");
		}

		$parent = $this->_parentDispatcher->dispatch(
			$this->_listingPageType,
			$parentTitle,
			$product,
			$grandparent
		);

		$this->_dispatcher->dispatch($parent, null, $options[Options::CSV_PORT]);

		return $parent;
	}

	/**
	 * Allow or disallow duplicate page creation
	 * 
	 * @param  boolean $allow allow or disallow duplicate creation
	 * @return Create         $this
	 */
	public function allowDuplicates($allow = true)
	{
		$this->_allowDuplicates = (boolean) $allow;

		return $this;
	}

	private function _getNewProductPage(Product\Product $product, Page\Page $parent = null, $variantValue = null)
	{
		$pageType = array_key_exists($product->type->getName(), $this->_mapping) ? $this->_mapping[$product->type->getName()] : self::PAGE_TYPE;

		return $this->_pageCreate->create(
			$this->_pageTypes->get($pageType),
			$product->name . (trim($variantValue) ? ' (' . trim($variantValue) . ')' : ''),
			$parent
		);
	}

	private function _setProductPageContent(Page\Page $page, Product\Product $product, array $options, $variantName = null, $variantValue = null)
	{
		$content = $this->_contentLoader->load($page);

		$contentData = $this->_getProductContent($product, $options, $variantName, $variantValue);

		$content = $this->_contentEdit->updateContent($contentData, $content);
		$this->_contentEdit->save($page, $content);

		return $page;
	}

	private function _getProductContent(Product\Product $product, array $options, $variantName = null, $variantValue = null)
	{
		$content = [
			self::DESC_FIELD => $product->description,
			self::PRODUCT_GROUP => [
				self::PRODUCT_FIELD => $product->id
			]
		];

		if ($options[Options::PAGE_VARIANTS] !== self::INDIVIDUAL) {
			$content[self::PRODUCT_GROUP][self::OPTION_FIELD] = [
				'name'  => strtolower($variantName),
				'value' => $variantValue,
			];
		} else {
			$content[self::PRODUCT_GROUP][self::OPTION_FIELD] = [
				'name'  => null,
				'value' => null,
			];
		}

		return $content;
	}
}