<?php


namespace Category\Product\Model;



    class Category extends \Magento\Framework\Model\AbstractModel implements \Category\Product\Model\Api\Data\Category 
    {  

        const CACHE_TAG = 'category_product';
        const assigned_product = 'assigned_product';
        const selected_category = 'selected_category';
        const checked_category = 'checked_category';
        const product_recurisive = 'product_recurisive';
        const created_at = 'created_at';
        const updated_at = 'updated_at';
        const entity_id  = 'entity_id';

        protected function _construct()
        {
            $this->_init('Category\Product\Model\ResourceModel\Category');
        }
             public function getEntityId() {
                        return $this->getData(self::entity_id);
                 }

             public function getAssignedProduct() {
                        return $this->getData(self::assigned_product);
                 }

         public function setAssignedProduct($assignedProduct) {
                    return $this->setData(self::assigned_product, $assignedProduct);
             }

             public function getSelectedCategory() {
                        return $this->getData(self::selected_category);
                 }

         public function setSelectedCategory($selectedCategory) {
                    return $this->setData(self::selected_category, $selectedCategory);
             }


             public function getProductRecurisive() {
                        return $this->getData(self::product_recurisive);
                 }

         public function setProductRecurisive($productRecurisive) {
                    return $this->setData(self::product_recurisive, $productRecurisive);
             }

             public function getCheckedCategory() {
                        return $this->getData(self::checked_category);
                 }

         public function setCheckedCategory($checkedCategory) {
                    return $this->setData(self::checked_category, $checkedCategory);
             }
              public function getCreatedAt() {
                        return $this->getData(self::created_at);
                 }

         public function setCreatedAt($createdAt) {
                    return $this->setData(self::created_at, $createdAt);
             }
              public function getUpdatedAt() {
                        return $this->getData(self::updated_at);
                 }

         public function setUpdatedAt($updatedAt) {
                    return $this->setData(self::updated_at, $updatedAt);
             }
    }