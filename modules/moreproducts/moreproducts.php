<?php

if (!defined('_PS_VERSION_'))

    exit;

class MoreProducts extends Module

{
    protected $category;
    protected $last = 0;
    private $cat_products;
    private $nbProducts;
    public $instant_search = false;

    public function __construct()
    {
        $this->name = 'moreproducts';
        $this->tab = 'content_management';
        $this->version = '3.6';
        $this->author = 'Skype: not_a_free_man';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('more products');
        $this->description = $this->l('ajax button loading more products in category and search page');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('MUMODULE_NAME'))
            $this->warning = $this->l('No name provided');
    }

    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (
            parent::install() == false
            || $this->registerHook('moreProducts') == false

            || $this->registerHook('header') == false)
            return false;

        Configuration::updateValue('PS_MORE_PRODUCTS_TYPE', 1);

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() && !$this->unregisterHook('moreProducts'))
            return false;

        return true;

    }

    public function getContent()
    {
        $message = '';

        if (Tools::isSubmit('submit_' . $this->name)) {
            $message = $this->_saveContent();
        }

        $this->_displayContent($message);

        $this->context->smarty->assign(array(
            'type' => Configuration::get('PS_MORE_PRODUCTS_TYPE'),
            'module_name' => $this->name
        ));

        return $this->display(__FILE__, 'views/admin/admin.tpl');
    }

    private function _saveContent()
    {
        Configuration::updateValue('PS_MORE_PRODUCTS_TYPE', Tools::getValue('type'));
        $message = $this->displayConfirmation($this->l("Success"));
        return $message;
    }

    private function _displayContent($message)
    {
        $this->context->smarty->assign(array(
            'message' => $message
        ));
    }

    public function hookHeader()
    {
        $this->context->controller->addJS(($this->_path).'js/moreproducts.js');
        $this->context->controller->addCSS(($this->_path).'css/moreproducts.css');
    }

    public function hookLeftColumn($params)
    {
        return $this->hookMoreProducts($params);
    }

    public function hookMoreProducts($params)
    {
        global $smarty;
        $result = array(
            'p' => (int)Tools::getValue('p', 1),
            'n' => (int)Tools::getValue('n', Configuration::get('PS_PRODUCTS_PER_PAGE')) + Configuration::get('PS_PRODUCTS_PER_PAGE'),
            'per_page' => (int)Configuration::get('PS_PRODUCTS_PER_PAGE'),
            'orderby' => Tools::getProductsOrder('by', (string)Tools::getValue('orderby', Configuration::get('PS_PRODUCTS_ORDER_BY'))),
            'orderway' => Tools::getProductsOrder('way', (string)Tools::getValue('orderway', Configuration::get('PS_PRODUCTS_ORDER_WAY'))),
            'show_type' => Configuration::get('PS_MORE_PRODUCTS_TYPE'),
        );

        if(Tools::getValue('id_category')) {
            $result['id_category'] = (int)Tools::getValue('id_category');
            $category = new Category($result['id_category'], $this->context->language->id, $this->context->shop->id);
            $result['nb_products'] = $category->getProducts(null, null, null, $result['orderby'], $result['orderway'], true);
        } elseif(Tools::getValue('search_query')) {
            $result['search_query'] = (string)Tools::getValue('search_query');
            $query = Tools::replaceAccentedChars(urldecode($result['search_query']));
            $search = Search::find($this->context->language->id, $query, $result['p'], $result['n'], $result['orderby'], $result['orderway']);
            Hook::exec('actionSearch', array('expr' => $query, 'total' => $search['total']));
            $result['nb_products'] = $search['total'];
        } else
            return false;

        $smarty->assign($result);

        return $this->display(__FILE__, 'views/front/moreproducts.tpl');
    }

    public function ajaxCall()
    {
        if(Tools::getValue('id_category')) {
            $result = $this->categoryInit();
        } elseif(Tools::getValue('search_query')) {
            $result = $this->searchInit();
        } else {
            die(Tools::jsonEncode(array('hasError' => 1, 'errors' => $this->_errors)));
        }

        $this->context->cookie->nb_item_per_page = Configuration::get('PS_PRODUCTS_PER_PAGE');

        die(Tools::jsonEncode(array('result' => $result, 'last' => $this->last, 'n' => Tools::getValue('n') + Configuration::get('PS_PRODUCTS_PER_PAGE'))));
    }

    protected function categoryInit() {
        global $smarty;
        $id_category = (int)Tools::getValue('id_category');
        $this->category = new Category($id_category, $this->context->language->id);

        if (isset($this->context->cookie->id_compare)) {
            $smarty->assign('compareProducts', CompareProduct::getCompareProducts((int)$this->context->cookie->id_compare));
        }

        // Product sort must be called before assignProductList()
        $this->context->controller->productSort();

        $this->assignScenes();
        $this->assignSubcategories();
        $this->assignProductList();

        if((float)_PS_VERSION_ === 1.5) {
            $smarty->assign(array(
                'category' => $this->category,
                'products' => (isset($this->cat_products) && $this->cat_products) ? $this->cat_products : null,
                'id_category' => (int)$this->category->id,
                'id_category_parent' => (int)$this->category->id_parent,
                'return_category_name' => Tools::safeOutput($this->category->name),
                'path' => Tools::getPath($this->category->id),
                'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                'categorySize' => Image::getSize(ImageType::getFormatedName('category')),
                'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
                'thumbSceneSize' => Image::getSize(ImageType::getFormatedName('m_scene')),
                'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
                'allow_oosp' => (int)Configuration::get('PS_ORDER_OUT_OF_STOCK'),
                'comparator_max_item' => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                'suppliers' => Supplier::getSuppliers()
            ));
        } else {
            $smarty->assign(array(
                'category' => $this->category,
                'description_short' => Tools::truncateString($this->category->description, 350),
                'products' => (isset($this->cat_products) && $this->cat_products) ? $this->cat_products : null,
                'id_category' => (int)$this->category->id,
                'id_category_parent' => (int)$this->category->id_parent,
                'return_category_name' => Tools::safeOutput($this->category->name),
                'path' => Tools::getPath($this->category->id),
                'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                'categorySize' => Image::getSize(ImageType::getFormatedName('category')),
                'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
                'thumbSceneSize' => Image::getSize(ImageType::getFormatedName('m_scene')),
                'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
                'allow_oosp' => (int)Configuration::get('PS_ORDER_OUT_OF_STOCK'),
                'comparator_max_item' => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                'suppliers' => Supplier::getSuppliers(),
            ));
        }

        return $smarty->fetch(_PS_THEME_DIR_.'category.tpl');
    }

    protected function assignScenes()
    {
        global $smarty;
        // Scenes (could be externalised to another controller if you need them)
        $scenes = Scene::getScenes($this->category->id, $this->context->language->id, true, false);
        $smarty->assign('scenes', $scenes);

        // Scenes images formats
        if ($scenes && ($scene_image_types = ImageType::getImagesTypes('scenes'))) {
            foreach ($scene_image_types as $scene_image_type) {
                if ($scene_image_type['name'] == ImageType::getFormatedName('m_scene')) {
                    $thumb_scene_image_type = $scene_image_type;
                } elseif ($scene_image_type['name'] == ImageType::getFormatedName('scene')) {
                    $large_scene_image_type = $scene_image_type;
                }
            }

            $smarty->assign(array(
                'thumbSceneImageType' => isset($thumb_scene_image_type) ? $thumb_scene_image_type : null,
                'largeSceneImageType' => isset($large_scene_image_type) ? $large_scene_image_type : null,
            ));
        }
    }

    /**
     * Assigns subcategory templates variables
     */
    protected function assignSubcategories()
    {
        global $smarty;
        if ($sub_categories = $this->category->getSubCategories($this->context->language->id)) {
            $smarty->assign(array(
                'subcategories'          => $sub_categories,
                'subcategories_nb_total' => count($sub_categories),
                'subcategories_nb_half'  => ceil(count($sub_categories) / 2)
            ));
        }
    }

    /**
     * Assigns product list template variables
     */
    public function assignProductList()
    {
        global $smarty;
        $hook_executed = false;
        Hook::exec('actionProductListOverride', array(
            'nbProducts'   => &$this->nbProducts,
            'catProducts'  => &$this->cat_products,
            'hookExecuted' => &$hook_executed,
        ));

        // The hook was not executed, standard working
        if (!$hook_executed) {
            $smarty->assign('categoryNameComplement', '');
            $this->orderBy = (string)Tools::getValue('orderBy', Configuration::get('PS_PRODUCTS_ORDER_BY'));
            $this->orderWay = (string)Tools::getValue('orderWay', Configuration::get('PS_PRODUCTS_ORDER_WAY'));
            $this->p = (int)Tools::getValue('p', 1);
            $this->n = (int)Tools::getValue('n', Configuration::get('PS_PRODUCTS_PER_PAGE'));
            $this->nbProducts = $this->category->getProducts(null, null, null, $this->orderBy, $this->orderWay, true);
            $this->context->controller->pagination((int)$this->nbProducts); // Pagination must be call after "getProducts"
            $this->cat_products = $this->category->getProducts($this->context->language->id, (int)$this->p, (int)$this->n, $this->orderBy, $this->orderWay);
        }
        // Hook executed, use the override
        else {
            // Pagination must be call after "getProducts"
            $this->context->controller->pagination($this->nbProducts);
        }

        if($this->nbProducts <= $this->n) {
            $this->last = 1;
        }

        if(method_exists($this->context->controller,'addColorsToProductList'))
            $this->context->controller->addColorsToProductList($this->cat_products);

        Hook::exec('actionProductListModifier', array(
            'nb_products'  => &$this->nbProducts,
            'cat_products' => &$this->cat_products,
        ));

        foreach ($this->cat_products as &$product) {
            if (isset($product['id_product_attribute']) && $product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity'])) {
                $product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
            }
        }

        $smarty->assign('nb_products', $this->nbProducts);
    }

    private function searchInit()
    {
        global $smarty;
        $original_query = Tools::getValue('q');
        $query = Tools::replaceAccentedChars(urldecode($original_query));

        //Only controller content initialization when the user use the normal search
        $this->context->controller->initContent();

        $product_per_page = isset($this->context->cookie->nb_item_per_page) ? (int)$this->context->cookie->nb_item_per_page : Configuration::get('PS_PRODUCTS_PER_PAGE');

        if ($this->instant_search && !is_array($query)) {
            $this->context->controller->productSort();
            $this->n = abs((int)(Tools::getValue('n', $product_per_page)));
            $this->p = abs((int)(Tools::getValue('p', 1)));
            $search = Search::find($this->context->language->id, $query, 1, 10, 'position', 'desc');
            Hook::exec('actionSearch', array('expr' => $query, 'total' => $search['total']));
            $nbProducts = $search['total'];
            $this->context->controller->pagination($nbProducts);

            if(method_exists($this->context->controller,'addColorsToProductList'))
                $this->context->controller->addColorsToProductList($search['result']);

            $smarty->assign(array(
                'products' => $search['result'], // DEPRECATED (since to 1.4), not use this: conflict with block_cart module
                'search_products' => $search['result'],
                'nbProducts' => $search['total'],
                'search_query' => $original_query,
                'instant_search' => $this->instant_search,
                'homeSize' => Image::getSize(ImageType::getFormatedName('home'))));
        } elseif (($query = Tools::getValue('search_query', Tools::getValue('ref'))) && !is_array($query)) {
            $this->context->controller->productSort();
            $this->n = abs((int)(Tools::getValue('n', $product_per_page)));
            $this->p = abs((int)(Tools::getValue('p', 1)));
            $this->orderBy = (string)Tools::getValue('orderBy', Configuration::get('PS_PRODUCTS_ORDER_BY'));
            $this->orderWay = (string)Tools::getValue('orderWay', Configuration::get('PS_PRODUCTS_ORDER_WAY'));
            $original_query = $query;
            $query = Tools::replaceAccentedChars(urldecode($query));
            $search = Search::find($this->context->language->id, $query, $this->p, $this->n, $this->orderBy, $this->orderWay);
            if (is_array($search['result'])) {
                foreach ($search['result'] as &$product) {
                    $product['link'] .= (strpos($product['link'], '?') === false ? '?' : '&').'search_query='.urlencode($query).'&results='.(int)$search['total'];
                }
            }

            Hook::exec('actionSearch', array('expr' => $query, 'total' => $search['total']));
            $nbProducts = $search['total'];
            $this->context->controller->pagination($nbProducts);

            if(method_exists($this->context->controller,'addColorsToProductList'))
                $this->context->controller->addColorsToProductList($search['result']);

            $smarty->assign(array(
                'products' => $search['result'], // DEPRECATED (since to 1.4), not use this: conflict with block_cart module
                'search_products' => $search['result'],
                'nbProducts' => $search['total'],
                'search_query' => $original_query,
                'homeSize' => Image::getSize(ImageType::getFormatedName('home'))));
        } elseif (($tag = urldecode(Tools::getValue('tag'))) && !is_array($tag)) {
            $nbProducts = (int)(Search::searchTag($this->context->language->id, $tag, true));
            $this->pagination($nbProducts);
            $result = Search::searchTag($this->context->language->id, $tag, false, $this->p, $this->n, $this->orderBy, $this->orderWay);
            Hook::exec('actionSearch', array('expr' => $tag, 'total' => count($result)));

            if(method_exists($this->context->controller,'addColorsToProductList'))
                $this->context->controller->addColorsToProductList($result);

            $smarty->assign(array(
                'search_tag' => $tag,
                'products' => $result, // DEPRECATED (since to 1.4), not use this: conflict with block_cart module
                'search_products' => $result,
                'nbProducts' => $nbProducts,
                'homeSize' => Image::getSize(ImageType::getFormatedName('home'))));
        } else {
            $smarty->assign(array(
                'products' => array(),
                'search_products' => array(),
                'pages_nb' => 1,
                'nbProducts' => 0));
        }
        $smarty->assign(array('add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'), 'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM')));

        return $smarty->fetch(_PS_THEME_DIR_.'search.tpl');
    }
}