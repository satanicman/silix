<?php

if (!defined('_PS_VERSION_'))

    exit;

class homecategoryproducts extends Module
{

    protected $_html = '';
    protected static $cache_categories;

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
        if (!Configuration::get('HOME_CATEGORY_PRODUCTS_NBR'))
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_NBR', 5);

        if (!Configuration::get('HOME_CATEGORY_PRODUCTS_RANDOMIZE'))
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_RANDOMIZE', 0);

        if (!Configuration::get('HOME_CATEGORY_PRODUCTS_CATS'))
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_CATS', Configuration::get('PS_HOME_CATEGORY'));

        if (!parent::install()
            || !$this->registerHook('displayHomeTabContent')
            || !$this->registerHook('addproduct')
            || !$this->registerHook('updateproduct')
            || !$this->registerHook('deleteproduct')
            || !$this->registerHook('categoryUpdate')
        )
            return false;

        return true;
    }

    public function uninstall()
    {
        $this->_clearCache('*');
        return parent::uninstall();
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Menu Top Link'),
                    'icon' => 'icon-link'
                ),
                'input' => array(
                    array(
                        'type' => 'link_choice',
                        'label' => '',
                        'name' => 'link',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Number of products to be displayed'),
                        'name' => 'HOME_CATEGORY_PRODUCTS_NBR',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->l('Set the number of products that you would like to display on homepage (default: 8).'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Randomly display products'),
                        'name' => 'HOME_CATEGORY_PRODUCTS_RANDOMIZE',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->l('Enable if you wish the products to be displayed randomly (default: no).'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    )
                ),
                'submit' => array(
                    'name' => 'submitHomecategoryproducts',
                    'title' => $this->l('Save')
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
            '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'choices' => $this->renderChoicesSelect(),
            'selected_links' => $this->makeMenuOption(),
        );
        return $helper->generateForm(array($fields_form));
    }

    public function getContent()
    {
        $message = '';

        if (Tools::isSubmit('submitHomecategoryproducts'))
            $message = $this->_saveContent();

        return $message.$this->renderForm();
    }

    private function _saveContent()
    {
        $message = '';
        $errors = array();
        $selected = Tools::getValue('items');

        $nbr = Tools::getValue('HOME_CATEGORY_PRODUCTS_NBR');
        if (!Validate::isInt($nbr) || $nbr <= 0)
            $errors[] = $this->l('The number of products is invalid. Please enter a positive number.');

        $rand = Tools::getValue('HOME_CATEGORY_PRODUCTS_RANDOMIZE');
        if (!Validate::isBool($rand))
            $errors[] = $this->l('Invalid value for the "randomize" flag.');
        if (isset($errors) && count($errors))
            $message = $this->displayError(implode('<br />', $errors));
        else
        {
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_NBR', (int)$nbr);
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_RANDOMIZE', (bool)$rand);
            Configuration::updateValue('HOME_CATEGORY_PRODUCTS_CATS', (string)implode('|', $selected));

            $message = $this->displayConfirmation($this->l('Your settings have been updated.'));
        }

        return $message;
    }

    private function renderChoicesSelect()
    {
        $html = '<select multiple="multiple" id="availableItems" style="width: 300px; height: 160px;">';
        foreach (Category::getCategories() as $category) {
            foreach ($category as $c) {
                if(in_array($c['infos']['id_category'], array(Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY'))))
                    continue;
                if(in_array($c['infos']['id_category'], explode('|', Configuration::get('HOME_CATEGORY_PRODUCTS_CATS'))))
                    continue;

                $html .= '<option value="' . $c['infos']['id_category'] . '">' . $c['infos']['name'] . '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    private function makeMenuOption()
    {
        $html = '<select multiple="multiple" name="items[]" id="items" style="width: 300px; height: 160px;">';
        foreach (explode('|', Configuration::get('HOME_CATEGORY_PRODUCTS_CATS')) as $cat) {
            $c = new Category($cat, $this->context->language->id, $this->context->shop->id);
            $html .= '<option selected="selected" value="' . $c->id_category . '">' . $c->name . '</option>';
        }
        $html .= '<select>';
        return $html;
    }

    public function getConfigFieldsValues()
    {
        return array(
            'HOME_CATEGORY_PRODUCTS_NBR' => Tools::getValue('HOME_CATEGORY_PRODUCTS_NBR', (int)Configuration::get('HOME_CATEGORY_PRODUCTS_NBR')),
            'HOME_CATEGORY_PRODUCTS_RANDOMIZE' => Tools::getValue('HOME_CATEGORY_PRODUCTS_RANDOMIZE', (bool)Configuration::get('HOME_CATEGORY_PRODUCTS_RANDOMIZE')),
        );
    }

    public function hookDisplayHome($params)
    {
        if (!$this->isCached('homecategoryproducts.tpl', $this->getCacheId()))
        {
            $this->_cacheCategories();
            $this->context->smarty->assign(
                array(
                    'categories' => homecategoryproducts::$cache_categories
                )
            );
        }

        return $this->display(__FILE__, 'homecategoryproducts.tpl', $this->getCacheId());
    }

    public function hookDisplayHomeTabContent($params)
    {
        return $this->hookDisplayHome($params);
    }



    public function hookAddProduct($params)
    {
        $this->_clearCache('*');
    }

    public function hookUpdateProduct($params)
    {
        $this->_clearCache('*');
    }

    public function hookDeleteProduct($params)
    {
        $this->_clearCache('*');
    }

    public function hookCategoryUpdate($params)
    {
        $this->_clearCache('*');
    }

    public function _clearCache($template, $cache_id = NULL, $compile_id = NULL)
    {
        parent::_clearCache('homecategoryproducts.tpl');
    }

    private function _cacheCategories()
    {

        if (!isset(homecategoryproducts::$cache_categories))
        {
            foreach (explode('|', Configuration::get('HOME_CATEGORY_PRODUCTS_CATS')) as $cat) {
                $c = new Category($cat, $this->context->language->id, $this->context->shop->id);

                $nb = (int)Configuration::get('HOME_CATEGORY_PRODUCTS_NBR');
                if (Configuration::get('HOME_CATEGORY_PRODUCTS_RANDOMIZE'))
                    $c->products = $c->getProducts((int)Context::getContext()->language->id, 1, ($nb ? $nb : 8), null, null, false, true, true, ($nb ? $nb : 8));
                else
                    $c->products = $c->getProducts((int)Context::getContext()->language->id, 1, ($nb ? $nb : 8), 'position');

                homecategoryproducts::$cache_categories[] = $c;
            }
        }

        if (homecategoryproducts::$cache_categories === false || empty(homecategoryproducts::$cache_categories))
            return false;
    }
}