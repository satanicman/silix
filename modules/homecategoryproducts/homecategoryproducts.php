<?php

if (!defined('_PS_VERSION_'))

    exit;

class homecategoryproducts extends Module

{
    public function __construct()
    {
        $this->name = 'homecategoryproducts';
        $this->tab = 'other';
        $this->version = '0.1';
        $this->author = 'http://vk.com/id24260100';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Home category products');
        $this->description = $this->l('Show products from your categories on home page');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('MUMODULE_NAME'))
            $this->warning = $this->l('No name provided');
    }

    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);


        if (!Configuration::get('HOME_CATEGORY_PRODUCTS_NBR'))
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_NBR', 5);

        if (!Configuration::get('HOME_CATEGORY_PRODUCTS_CATS'))
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_CATS', Configuration::get('PS_HOME_CATEGORY'));

        if (!parent::install()
            || !$this->registerHook('displayHomeTabContent'))
            return false;

        return true;
    }

    public function uninstall()
    {
        $this->_clearCache('*');
        return parent::uninstall();
    }

    public function getContent()
    {
        $message = '';

        if (Tools::isSubmit('submit_' . $this->name))
            $message = $this->_saveContent();

        $this->_displayContent($message);
        $categories = array();
        foreach (Category::getCategories() as $category) {
            foreach ($category as $c) {
                if(in_array($c['infos']['id_category'], array(Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY'))))
                    continue;

                if(in_array($c['infos']['id_category'], explode('|', Configuration::get('HOME_CATEGORY_PRODUCTS_CATS'))))
                    $c['infos']['selected'] = 1;
                $categories[] = $c['infos'];
            }
        }

        $this->context->smarty->assign(array(
            'module_name' => $this->name,
            'categories' => $categories
        ));

        return $this->display(__FILE__, 'views/templates/admin/homecategoryproducts-admin.tpl');
    }

    private function _saveContent()
    {
        $message = '';
        if($selected = Tools::getValue('categories')) {
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_CATS', implode('|', $selected));
            $message = $this->displayConfirmation($this->l("Success"));
        }

        return $message;
    }

    private function _displayContent($message)
    {
        $this->context->smarty->assign(array(
            'message' => $message
        ));
    }
}