<?php

class QuickOrder extends Module
{
	public $context;

	public function __construct()
	{
		$this->name = 'quickorder';
		$this->tab = 'front_office_features';
		$this->version = '0.2';
		$this->author = 'PrestaDev.ru';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Quick order');
		$this->description = $this->l('Quick order (without registration)');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('header'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall() ||
			!Configuration::deleteByName('QUI_CARRIER') ||
			!Configuration::deleteByName('QUI_PAYMENT') ||
			!Configuration::deleteByName('QUI_COUNTRY') ||
			!Configuration::deleteByName('QUI_CREATE_ORDER_EMAIL') ||
			!Configuration::deleteByName('QUI_CREATE_ORDER') ||
			!Configuration::deleteByName('QUI_CREATE_CUSTOMER') ||
			!Configuration::deleteByName('QUI_CREATE_ADDRESS'))
			return false;
		return true;
	}

	public function getContent()
	{
		if (Tools::isSubmit('submit'))
		{
			Configuration::updateValue('QUI_CARRIER', Tools::getValue('QUI_CARRIER'));
			Configuration::updateValue('QUI_PAYMENT', Tools::getValue('QUI_PAYMENT'));
			Configuration::updateValue('QUI_COUNTRY', Tools::getValue('QUI_COUNTRY'));
			Configuration::updateValue('QUI_CREATE_ORDER_EMAIL', Tools::getValue('QUI_CREATE_ORDER_EMAIL'));

			Configuration::updateValue('QUI_CREATE_ORDER', (int)Tools::isSubmit('QUI_CREATE_ORDER'));
			Configuration::updateValue('QUI_CREATE_CUSTOMER', (int)Tools::isSubmit('QUI_CREATE_CUSTOMER'));
			Configuration::updateValue('QUI_CREATE_ADDRESS', (int)Tools::isSubmit('QUI_CREATE_ADDRESS'));
		}

		foreach (Carrier::getCarriers($this->context->language->id, true, false, false, null, Carrier::ALL_CARRIERS) AS $carrier)
		{
			$this->_carrier .= '<option value="'.$carrier['id_carrier'].'" '.(Configuration::get('QUI_CARRIER') == $carrier['id_carrier'] ? 'selected="selected"' : '').'>'.$carrier['name'].'</option>';
		}

		foreach (Module::getPaymentModules() AS $payment)
		{
			$this->_payment .= '<option value="'.$payment['id_module'].'" '.(Configuration::get('QUI_PAYMENT') == $payment['id_module'] ? 'selected="selected"' : '').'>'.$payment['name'].'</option>';
		}

		foreach (Country::getCountries((int)$this->context->cookie->id_lang, true) AS $country)
		{
			$this->_country .= '<option value="'.$country['iso_code'].'" '.(Configuration::get('QUI_COUNTRY') == $country['iso_code'] ? 'selected="selected"' : '').'>'.$country['name'].'</option>';
		}

		return '<h2>'.$this->displayName.'</h2>' . ((Tools::isSubmit('submit')) ? '<div class="conf confirm">'.$this->l('Settings updated').'</div>' : '') . '

		<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
			<fieldset>
				<legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
				<div class="clear"></div>

				<label>Создать новый заказ</label>
				<div class="margin-form">
					<div style="margin-top:4px">
						<input type="checkbox" value="1" name="QUI_CREATE_ORDER" '.(Configuration::get('QUI_CREATE_ORDER') == '1' ? 'checked' : '').' /> '.$this->l('Yes').'
					</div>
					<p class="clear">Создание нового заказа внутри системы, <strong>если не выбрано - заказ будет отправлен на эл.адрес администратора</strong>.</p>
				</div>

				'.(Configuration::get('QUI_CREATE_ORDER') ? '' : '
				<label>Эл.адрес администратора:</label>
				<div class="margin-form">
					<input type="text" size="25" name="QUI_CREATE_ORDER_EMAIL" value="'.Tools::getValue('QUI_CREATE_ORDER_EMAIL', Configuration::get('QUI_CREATE_ORDER_EMAIL')).'" />
				</div>
				').'

				'.(Configuration::get('QUI_CREATE_ORDER') ? '
				<label>Создать клиента:</label>
				<div class="margin-form">
					<div style="margin-top:4px">
						<input type="checkbox" value="1" name="QUI_CREATE_CUSTOMER" '.(Configuration::get('QUI_CREATE_CUSTOMER') == '1' ? 'checked' : '').' /> '.$this->l('Yes').'
					</div>
					<p class="clear">Создать нового пользователя при оформлении заказа, если не выбрано будет создан аккаунт гостя.</p>
				</div>

				<label>Создать адрес:</label>
				<div class="margin-form">
					<div style="margin-top:4px">
						<input type="checkbox" value="1" name="QUI_CREATE_ADDRESS" '.(Configuration::get('QUI_CREATE_ADDRESS') == '1' ? 'checked' : '').' /> '.$this->l('Yes').'
					</div>
					<p class="clear">Создать новый адрес при  оформлении заказа.</p>
				</div>

				<label>Способ доставки:</label>
				<div class="margin-form">
				<select name="QUI_CARRIER">
					<option value="0">Не использовать</option>
					'.$this->_carrier.'
				</select>
				</div>

				<label>Способ оплаты:</label>
				<div class="margin-form">
				<select name="QUI_PAYMENT">
					<option value="0">Выберите ...</option>
					'.$this->_payment.'
				</select>
				<sup class="required">*</sup>
				</div>
				
				<div class="clear"></div>
				<label>Страна по-умолчанию:</label>
				<div class="margin-form">
				<select name="QUI_COUNTRY">
					<option value="">Выберите ...</option>
					'.$this->_country.'
				</select>
				<sup class="required">*</sup>
				</div>

				' : '').'

				<div class="clear"></div>
				<center><input type="submit" name="submit" value="'.$this->l('Update settings').'" class="button" /></center>
			</fieldset>
		</form>

		<div class="clear">&nbsp;</div>
		<fieldset><legend><img src="../img/admin/prefs.gif" />Доп. настройки для работы модуля</legend>
			<b>1. </b> Открыть файл <b>themes/ваша-тема/modules/blockcart/blockcart.tpl</b> или <b>modules/blockcart/blockcart.tpl</b><br />
			внутри: <p class="bold">&lt;p id="cart-buttons"&gt;...&lt;/p&gt;</p>
			добавить:<br />
			<p class="bold">&lt;br clear="all" /&gt;<br />            &lt;a id="quickorder" href="#" rel="nofollow" title="{l s=\'Quick order\' mod=\'quickorder\'}" class="exclusive_large"&gt;&lt;span&gt;&lt;/span&gt;{l s=\'Quick order\' mod=\'quickorder\'}&lt;/a&gt;
			</p>
			<b>2. </b> Выполнить перекомпиляцию шаблона, или очистить кеш смарти.<br />
			<div class="clear">&nbsp;</div>
		</fieldset>
		<div class="clear"></div>';
	}

	public function hookHeader()
	{
		$this->context->controller->addJQueryPlugin('fancybox');

		if (version_compare(_PS_VERSION_,'1.5.5','>='))
			$this->context->controller->addJS(($this->_path).'quickorder15.js');
		else
			$this->context->controller->addJS(($this->_path).'quickorder.js');

		$this->context->controller->addCSS(($this->_path).'quickorder.css', 'all');		
	}
}