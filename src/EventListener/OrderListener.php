<?php

namespace Message\Mothership\Ecommerce\EventListener;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;
use Message\Mothership\Commerce\Order;

/**
 * Order event listener.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
class OrderListener extends BaseListener implements SubscriberInterface
{
	/**
	 * {@inheritdoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			Order\Events::CREATE_COMPLETE => array(
				array('sendOrderConfirmationMail'),
			)
		);
	}

	public function sendOrderConfirmationMail(Order\Event\Event $event)
	{
		$order = $event->getOrder();
		$merchant = $this->get('cfg')->merchant;

		if ($order->type == 'web') {
			$payments = $this->get('order.payment.loader')->getByOrder($order);

			$factory = $this->get('mail.factory.order.confirmation')
				->set('order', $order)
				->set('payments', $payments);

			$this->get('mail.dispatcher')->send($factory->getMessage());
		}
	}
}