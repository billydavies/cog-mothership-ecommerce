<?php

namespace Message\Mothership\Ecommerce\Gateway\Validation;

use Message\Cog\Location\StateList;
use Message\Cog\Location\CountryList;
use Message\Mothership\Commerce\Payable\PayableInterface;

/**
 * Validate an address on a payable has all the required components.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
class AddressValidator implements ValidatorInterface
{
	/**
	 * List of valid countries.
	 *
	 * @var \Message\Cog\Location\CountryList
	 */
	protected $_countries;

	/**
	 * List of valid states.
	 *
	 * @var \Message\Cog\Location\StateList
	 */
	protected $_states;

	/**
	 * The address type such as 'billing' or 'delivery'.
	 *
	 * @var string
	 */
	protected $_type;

	/**
	 * The required parts the address must have.
	 *
	 * @var array
	 */
	protected $_parts;

	/**
	 * List of errors created by an invalid payable.
	 *
	 * @var array
	 */
	protected $_errors = [];

	/**
	 * Construct the validator with the list of countries and states.
	 *
	 * @param string $type
	 */
	public function __construct(CountryList $countries, StateList $states)
	{
		$this->_countries = $countries;
		$this->_states    = $states;
	}

	/**
	 * Set the valid address type.
	 *
	 * @param  string $type
	 * @return AddressValidator
	 */
	public function setType($type)
	{
		$this->_type  = $type;

		return $this;
	}

	/**
	 * Set the required address parts.
	 *
	 * @param  array $parts
	 * @return AddressValidator
	 */
	public function setRequiredParts(array $parts)
	{
		$this->_parts = $parts;

		return $this;
	}

	/**
	 * Check if the payable is valid against the address validation rules.
	 *
	 * @todo Add validation of the address against valid countries.
	 *
	 * {@inheritDoc}
	 */
	public function isValid(PayableInterface $payable)
	{
		$valid = true;

		$address = $payable->getPayableAddress($this->_type);

		// Check the address exists
		if (! $address) {
			$this->_errors[] = sprintf("%s address is required", ucfirst($this->_type));

			return false;
		}

		// Check the address parts
		foreach ($this->_parts as $key => $part) {
			if ($key === "lines") {
				for ($line = 1; $line <= $part; $line++) {
					if (! property_exists($address, "lines") or ! is_array($address->lines) or ! isset($address->lines[$line])) {
						$valid = false;
						$this->_errors[] = sprintf("%s address line %d is required", ucfirst($this->_type), $line);
					}
				}
			}
			else {
				if (! property_exists($address, $part) or ! $address->$part) {
					$valid = false;
					$this->_errors[] = sprintf("%s address %s is required", ucfirst($this->_type), $part);
				}
			}
		}

		// Check the address state is set where required by the country and
		// matches the states within that country.
		$states = $this->_states->all();
		if (isset($states[$address->countryID])) {
			if (empty($address->stateID)) {
				$valid = false;
				$this->_errors[] = sprintf("%s address state is required", ucfirst($this->_type));
			}
			elseif (!isset($states[$address->countryID][$address->stateID])) {
				$valid = false;
				$this->_errors[] = sprintf("%s address state '%s' is not in the country '%s'",
					ucfirst($this->_type), $address->stateID, $address->countryID);
			}
		}

		return $valid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getErrors()
	{
		return $this->_errors;
	}
}