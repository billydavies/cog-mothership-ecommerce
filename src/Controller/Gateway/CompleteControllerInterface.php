<?php

namespace Message\Mothership\Ecommerce\Controller\Gateway;

use Message\Cog\HTTP\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Message\Mothership\Commerce\Payable\PayableInterface;
use Message\Mothership\Commerce\Order\Entity\Payment\MethodInterface;

/**
 * Interface for complete controllers.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
interface CompleteControllerInterface
{
	/**
	 * Successfully complete a payable.
	 *
	 * @param  PayableInterface $payable
	 * @param  string           $reference
	 * @param  MethodInterface  $method
	 * @return JsonResponse
	 */
	public function success(PayableInterface $payable, $reference, MethodInterface $method);

	/**
	 * Cancel a payable.
	 *
	 * @param  PayableInterface $payable
	 * @return Response
	 */
	public function cancel(PayableInterface $payable);

	/**
	 * Fail a payable.
	 *
	 * @param  PayableInterface $payable
	 * @return Response
	 */
	public function failure(PayableInterface $payable);
}