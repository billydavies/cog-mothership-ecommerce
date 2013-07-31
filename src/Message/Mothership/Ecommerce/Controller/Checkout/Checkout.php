<?php

namespace Message\Mothership\Ecommerce\Controller\Checkout;

use Message\Cog\Controller\Controller;

/**
 * Class Checkout
 * @package Message\Mothership\Ecommerce\Controller\Fulfillment
 *
 * Controller for processing orders in Fulfillment
 */
class Checkout extends Controller
{
	public function index()
	{

		return $this->render('Message:Mothership:Ecommerce::Checkout:checkout', array(
			'form' => $this->checkoutForm(),
		));
	}

	public function process()
	{
		$basket = $this->get('basket');
		$form = $this->checkoutForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			foreach($data['items'] as $unitID => $quantity) {
				$product = $this->get('product.loader')->getByUnitID($unitID);
				$unit = $product->units->get($unitID);
				$basket->updateQuantity($unit, $quantity);
			}
		}

		return $this->redirectToReferer();
	}

	public function checkoutForm()
	{
		$form = $this->get('form');
		$form->setName('confirm_basket')
			->setAction($this->generateUrl('ms.ecom.checkout.action'))
			->setMethod('post');

		$basketDisplay = array();
		foreach ($this->get('basket')->getOrder()->items as $item) {
			if (!isset($basketDisplay[$item->unitID]['quantity'])) {
				$basketDisplay[$item->unitID]['quantity'] = 0;
			}
			$basketDisplay[$item->unitID]['item'] = $item;
			$basketDisplay[$item->unitID]['quantity'] += 1;
		}

		$defaults = array();
		foreach ($basketDisplay as $item) {
			$defaults[$item['item']->unitID] = $item['quantity'];
		}

		$itemsForm = $this->get('form')
			->setName('items')
			->setDefaultValues($defaults)
			->addOptions(array(
				'auto_initialize' => false,
			)
		);

		foreach ($basketDisplay as $item) {
			$itemsForm->add((string) $item['item']->unitID, 'choice', implode(' / ',$item['item']->options), array(
				'choices' => range(1,50)
			));
		}

		$form->add($itemsForm->getForm(), 'form');

		return $form;
	}
}