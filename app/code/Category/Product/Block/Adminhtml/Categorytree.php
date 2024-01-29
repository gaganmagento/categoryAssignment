<?php

namespace Category\Product\Block\Adminhtml;

class Categorytree extends \Magento\Framework\View\Element\Template
{   
    protected $_categoryCollectionFactory;
    protected $_categoryHelper;
    protected $categoryproductFactory;
    protected $CategoryFactory;
   

        
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,       
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Category\Product\Model\Category $categoryproductFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $CategoryFactory,
        array $data = []
    )
    {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryproductFactory = $categoryproductFactory;
        $this->categoryFactory = $CategoryFactory;
        parent::__construct($context, $data);
    }
    
    /**
     * Get category collection
     *
     * @param bool $isActive
     * @param bool|int $level
     * @param bool|string $sortBy
     * @param bool|int $pageSize
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    public function getCategoryCollection($isActive = true, $level = false, $sortBy = false, $pageSize = false)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');     
        
        // select only active categories
        if ($isActive) {
            $collection->addIsActiveFilter();
        }
                
        // select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }
        
        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }
        
        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize); 
        }   
        
        return $collection;
    }

    public function getCategory($selectedCategory){

        $checkedCategory = $this->_categoryproductFactory->getCollection();
        $selectedCat = $checkedCategory->addFieldToFilter('selected_category', ['eq' => $selectedCategory]);
        return $selectedCat;
    }
    public function getProductCollectionFromCategory($categoryId) {
        $category = $this->categoryFactory->create()->load($categoryId);
       return $category->getProductCollection()->addAttributeToSelect('*');

    }
   
}
?>

