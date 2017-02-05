<?php

class blocksocialOverride extends blocksocial
{
    public function hookFooterTop($params) {
        return parent::hookDisplayFooter($params);
    }

}
