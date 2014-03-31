<?php

namespace Message\Mothership\Ecommerce\Collection;

/**
 * Temporary class to be removed once collections have been refactored.
 */
class Collection implements \IteratorAggregate, \Countable
{
	protected $_items = array();

	public function __construct(array $items = array())
	{
		foreach ($items as $item) {
			$this->add($item);
		}
	}

	public function add(GatewayInterface $item)
	{
		if ($this->exists($item->getName())) {
			throw new \InvalidArgumentException(sprintf(
				'Gateway `%s` is already defined',
				$item->getName()
			));
		}

		$this->_items[$item->getName()] = $item;

		return $this;
	}

	public function get($name)
	{
		if (!$this->exists($name)) {
			throw new \InvalidArgumentException(sprintf('Gateway `%i` not set on collection', $name));
		}

		return $this->_items[$name];
	}

	public function all()
	{
		return $this->_items;
	}

	public function exists($name)
	{
		return array_key_exists($name, $this->_items);
	}

	public function count()
	{
		return count($this->_items);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->_items);
	}
}