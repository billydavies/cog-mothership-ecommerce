<?php

namespace Message\Mothership\Ecommerce\Gateway;

use Message\Mothership\Commerce\Payable\PayableInterface;

/**
 * Interface for gateway purchase controllers.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
interface PurchaseControllerInterface
{
	/**
	 * Purchase a payable.
	 *
	 * @param  PayableInterface $payable
	 * @param  array            $stages  URLs and references for redirecting the
	 *                                   customer.
	 * @param  array            $options
	 * @return \Message\Cog\HTTP\Response
	 */
	public function purchase(PayableInterface $payable, array $stages, array $options = null);
}