<?php

class BlockCmsOverride extends BlockCms
{
    public function hookFooterTop($params) {
        return parent::hookFooter($params);
    }

}
