<?php

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/quickorder.php');

class QuickOrderCreate extends PaymentModule
{
	public $active = true;
}

$context = Context::getContext();
$quickorder = new QuickOrder();
if (Tools::isSubmit('submitQorder'))
{
			$errors = array();
			if (!Validate::isLoadedObject($context->cart))
				$errors[] = $quickorder->l('Cart not found', 'ajax');
			if (Cart::getNbProducts($context->cart->id) <= 0)
				$errors[] = $quickorder->l('You must add minimum 1 quantity', 'ajax');
			if (!Tools::getValue('email') || !Validate::isEmail(Tools::getValue('email')))
				$errors[] = $quickorder->l('Invalid e-mail address', 'ajax');
			if (!Tools::getValue('phone') || !Validate::isPhoneNumber(Tools::getValue('phone')))
				$errors[] = $quickorder->l('You must register at least one phone number', 'ajax');
			if (!Tools::getValue('firstname') || !Validate::isName(Tools::getValue('firstname')))
				$errors[] = $quickorder->l('Name is empty or contains error', 'ajax');
			if (!Tools::getValue('payment') || !Validate::isName(Tools::getValue('payment')))
				$errors[] = $quickorder->l('Payment is empty or contains error', 'ajax');
			if (!Tools::getValue('lastname') || !Validate::isName(Tools::getValue('lastname')))
				$errors[] = $quickorder->l('Last name is empty or contains error', 'ajax');
			if (!Tools::getValue('address'))
				$errors[] = $quickorder->l('Address is empty or contains error', 'ajax');

				if (!empty($errors))
					die(Tools::jsonEncode(array('hasError' => true, 'errors' => $errors)));
				else
				{
					if (Configuration::get('QUI_CREATE_ORDER'))
					{

						if (!$context->customer->isLogged())
						{
							$customer = new Customer();

							$customer->passwd = md5(time()._COOKIE_KEY_);
							$customer->firstname = Tools::getValue('firstname');
							$customer->lastname = Tools::getValue('lastname');
							$customer->email = Tools::getValue('email');
							$customer->active = 1;
							$customer->is_guest = (Configuration::get('QUI_CREATE_CUSTOMER') ? '0' : '1');

							$customer->add();
						}

						if (Configuration::get('QUI_CREATE_ADDRESS'))
						{
							$address = new Address();

							if (Configuration::get('QUI_COUNTRY'))
								$address->id_country = Country::getByIso(Configuration::get('QUI_COUNTRY'));

							$address->firstname = Tools::getValue('firstname');
							$address->lastname = Tools::getValue('lastname');
							$address->phone_mobile = Tools::getValue('phone');
							$address->other = 'Эл.адрес:'.' '.Tools::getValue('email')."\r\n".'Адрес:'.' '.Tools::getValue('address')."\r\n".'Оплата:'.' '.Tools::getValue('payment');
							$address->address1 = Tools::getValue('address');
							$address->city = ' ';
							$address->alias = 'quickorder_' . substr(md5(time()._COOKIE_KEY_), 0, 7);
							$address->id_customer = $customer->id;

							$address->save();
							$cart->id_address_invoice = (int)($address->id);
							$cart->id_address_delivery = (int)($address->id);
							$id_address = (int)($address->id);

							$cart->update();

							CartRule::autoRemoveFromCart($context);
							CartRule::autoAddToCart($context);

							if (!$context->cart->isMultiAddressDelivery())
								$context->cart->setNoMultishipping();
						}
						else
						{
							$message = new Message();
							$message->id_cart = $cart->id;
							$message->message = 
							'Имя:'.' '.Tools::getValue('firstname')."\r\n".'Фамилия:'.' '.Tools::getValue('lastname')."\r\n".'Эл.адрес:'.' '.Tools::getValue('email')."\r\n".'Адрес:'.' '.Tools::getValue('address')."\r\n".'Телефон:'.' '.Tools::getValue('phone');
							$message->private = true;
							$message->add();
						}

						if (Configuration::get('QUI_CARRIER'))
							$cart->id_carrier = Configuration::get('QUI_CARRIER');

						if (Configuration::get('QUI_PAYMENT'))
							$payment = Module::getInstanceById(Configuration::get('QUI_PAYMENT'));

						$cart->id_customer = (int)$customer->id;
						$cookie->id_customer = (int)$customer->id;
						$cookie->update();

						if (Tools::getValue('comment'))
						{
							$message = new Message();
							$message->id_cart = $cart->id;
							$message->message = 'Комментарий:'.' '.Tools::getValue('comment');
							$message->private = true;
							$message->add();
						}

						$cart->update();

						$total = $cart->getOrderTotal(true, Cart::BOTH);
						$order = new QuickOrderCreate();

						if (Configuration::get('QUI_PAYMENT'))
							$order->name = $payment->name;

						$order->validateOrder((int)$cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $payment->displayName, null, array(), null, false, ($cart->secure_key ? $cart->secure_key : false));
						die(true);
					}
					else
					{

						$products_list = '';
						foreach ($cart->getProducts() as $key => $product)
						{
							$price = Product::getPriceStatic((int)$product['id_product'], false, ($product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null), 6, null, false, true, $product['cart_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
							$price_wt = Product::getPriceStatic((int)$product['id_product'], true, ($product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null), 2, null, false, true, $product['cart_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});

							$customization_quantity = 0;
							if (isset($customized_datas[$product['id_product']][$product['id_product_attribute']]))
							{
								$customization_text = '';
								foreach ($customized_datas[$product['id_product']][$product['id_product_attribute']] as $customization)
								{
									if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD]))
										foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text)
											$customization_text .= $text['name'].': '.$text['value'].'<br />';

									if (isset($customization['datas'][Product::CUSTOMIZE_FILE]))
										$customization_text .= sprintf(Tools::displayError('%d image(s)'), count($customization['datas'][Product::CUSTOMIZE_FILE])).'<br />';

									$customization_text .= '---<br />';
							}

							$customization_text = rtrim($customization_text, '---<br />');

							$customization_quantity = (int)$product['customizationQuantityTotal'];
							$products_list .=
							'<tr style="background-color: '.($key % 2 ? '#DDE2E6' : '#EBECEE').';">
								<td style="padding: 0.6em 0.4em;width: 15%;">'.$product['reference'].'</td>
								<td style="padding: 0.6em 0.4em;width: 30%;"><strong>'.$product['name'].(isset($product['attributes']) ? ' - '.$product['attributes'] : '').' - '.Tools::displayError('Customized').(!empty($customization_text) ? ' - '.$customization_text : '').'</strong></td>
								<td style="padding: 0.6em 0.4em; width: 20%;">'.Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ?  Tools::ps_round($price, 2) : $price_wt, $context->currency, false).'</td>
								<td style="padding: 0.6em 0.4em; width: 15%;">'.$customization_quantity.'</td>
								<td style="padding: 0.6em 0.4em; width: 20%;">'.Tools::displayPrice($customization_quantity * (Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt), $context->currency, false).'</td>
							</tr>';
						}

						if (!$customization_quantity || (int)$product['cart_quantity'] > $customization_quantity)
							$products_list .=
							'<tr style="background-color: '.($key % 2 ? '#DDE2E6' : '#EBECEE').';">
								<td style="padding: 0.6em 0.4em;width: 15%;">'.$product['reference'].'</td>
								<td style="padding: 0.6em 0.4em;width: 30%;"><strong>'.$product['name'].(isset($product['attributes']) ? ' - '.$product['attributes'] : '').'</strong></td>
								<td style="padding: 0.6em 0.4em; width: 20%;">'.Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt, $context->currency, false).'</td>
								<td style="padding: 0.6em 0.4em; width: 15%;">'.((int)$product['cart_quantity'] - $customization_quantity).'</td>
								<td style="padding: 0.6em 0.4em; width: 20%;">'.Tools::displayPrice(((int)$product['cart_quantity'] - $customization_quantity) * (Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt), $context->currency, false).'</td>
							</tr>';
						}

						$data = array(
							'{shop_name}' => Configuration::get('PS_SHOP_NAME'),
							'{firstname}' => Tools::getValue('firstname'),
							'{lastname}' => Tools::getValue('lastname'),
							'{email}' => Tools::getValue('email'),
							'{address}' => Tools::getValue('address'),
							'{phone}' => Tools::getValue('phone'),
							'{comment}' => Tools::getValue('comment'),
							'{items}' => $products_list, 
						);

						if (Validate::isEmail(Configuration::get('QUI_CREATE_ORDER_EMAIL')))
							$id_lang = (int)$context->language->id; 
							$iso = Language::getIsoById($id_lang);
							if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/quick.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/quick.html'))
								Mail::Send($id_lang, 'quick', Mail::l('New order (without registration)'), $data, Configuration::get('QUI_CREATE_ORDER_EMAIL'), null, strval(Configuration::get('PS_SHOP_EMAIL')), strval(Configuration::get('PS_SHOP_NAME')), null, null, dirname(__FILE__).'/mails/');

						unset($cart);
						unset($cookie->id_cart);
						die(true);
						
					}
				}
}
else
{
	$context->smarty->assign('total', $context->cart->getOrderTotal(true, Cart::BOTH));
	return $context->smarty->display(dirname(__FILE__).'/quickform.tpl');
}