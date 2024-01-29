<?php


namespace Category\Product\Model\ResourceModel;


use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Category extends AbstractDb

{

    protected function _construct()

    {

        $this->_init('category_product', 'entity_id');

    }

}