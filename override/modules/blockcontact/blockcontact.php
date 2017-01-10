<?php

class BlockcontactOverride extends Blockcontact
{
	public function hookDisplayTop($params)
	{
		$params['blockcontact_tpl'] = 'nav';
		return parent::hookDisplayRightColumn($params);
	}
}
