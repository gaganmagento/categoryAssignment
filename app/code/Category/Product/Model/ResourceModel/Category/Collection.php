<?php


namespace Category\Product\Model\ResourceModel\Category;


use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;


class Collection extends AbstractCollection

{

    protected function _construct()
    {

        $this->_init(
        'Category\Product\Model\Category',
        'Category\Product\Model\ResourceModel\Category');

    }

}