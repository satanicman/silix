<?php
class FrontController extends FrontControllerCore
{
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_ . 'fonts.css', 'all');
//        $this->addJS(_THEME_JS_DIR_ . 'global.js');
    }
}
