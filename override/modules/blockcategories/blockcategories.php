<?php
class BlockCategoriesOverride extends BlockCategories
{
    public function hookCategoryTop($param)
    {
        $this->setLastVisitedCategory();
        $phpself = $this->context->controller->php_self;
        $current_allowed_controllers = array('category');

        if ($phpself != null && in_array($phpself, $current_allowed_controllers) && Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category)
        {
            $category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
            if (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') == 2 && !$category->is_root_category && $category->id_parent != Configuration::get('PS_HOME_CATEGORY') && $category->id_parent)
                $category = new Category($category->id_parent, $this->context->language->id);
            elseif (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') == 3 && !$category->is_root_category && !$category->getSubCategories($category->id, true))
                $category = new Category($category->id_parent, $this->context->language->id);
        }
        else
            $category = new Category((int)Configuration::get('PS_HOME_CATEGORY'), $this->context->language->id);

        $cacheId = $this->getCacheId($category ? $category->id : null);

        if (!$this->isCached('blockcategories.tpl', $cacheId))
        {
            $range = '';
            $maxdepth = Configuration::get('BLOCK_CATEG_MAX_DEPTH');
            if (Validate::isLoadedObject($category))
            {
                if ($maxdepth > 0)
                    $maxdepth += $category->level_depth;
                $range = 'AND nleft >= '.(int)$category->nleft.' AND nright <= '.(int)$category->nright;
            }

            $resultIds = array();
            $resultParents = array();
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite
			FROM `'._DB_PREFIX_.'category` c
			INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = '.(int)$this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
			INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON (cs.`id_category` = c.`id_category` AND cs.`id_shop` = '.(int)$this->context->shop->id.')
			WHERE (c.`active` = 1 OR c.`id_category` = '.(int)Configuration::get('PS_HOME_CATEGORY').')
			AND c.`id_category` != '.(int)Configuration::get('PS_ROOT_CATEGORY').'
			'.((int)$maxdepth != 0 ? ' AND `level_depth` <= '.(int)$maxdepth : '').'
			'.$range.'
			AND c.id_category IN (
				SELECT id_category
				FROM `'._DB_PREFIX_.'category_group`
				WHERE `id_group` IN ('.pSQL(implode(', ', Customer::getGroupsStatic((int)$this->context->customer->id))).')
			)
			ORDER BY `level_depth` ASC, '.(Configuration::get('BLOCK_CATEG_SORT') ? 'cl.`name`' : 'cs.`position`').' '.(Configuration::get('BLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC'));
            foreach ($result as &$row)
            {
                $resultParents[$row['id_parent']][] = &$row;
                $resultIds[$row['id_category']] = &$row;
            }

            $blockCategTree = $this->getTree($resultParents, $resultIds, $maxdepth, ($category ? $category->id : null));
            $this->smarty->assign('blockCategTree', $blockCategTree);

            if ((Tools::getValue('id_product') || Tools::getValue('id_category')) && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category)
            {
                $category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
                if (Validate::isLoadedObject($category))
                    $this->smarty->assign(array('currentCategory' => $category, 'currentCategoryId' => $category->id));
            }

            $this->smarty->assign('isDhtml', Configuration::get('BLOCK_CATEG_DHTML'));
            if (file_exists(_PS_THEME_DIR_.'modules/blockcategories/blockcategories.tpl'))
                $this->smarty->assign('branche_tpl_path', _PS_THEME_DIR_.'modules/blockcategories/category-tree-branch-category.tpl');
            else
                $this->smarty->assign('branche_tpl_path', _PS_MODULE_DIR_.'blockcategories/category-tree-branch-category.tpl');
        }
        return $this->display(__FILE__, 'blockcategories.tpl', $cacheId);
    }
}
