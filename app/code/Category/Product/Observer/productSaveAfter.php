<?php
namespace Category\Product\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\Framework\App\ResourceConnection;

class productSaveAfter implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    public $productCategory;
    protected $categoryproductFactory;
    protected $_resourceConnection;
    protected $categoryLinkManagement;
    

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagementInterface,
         ResourceConnection $resourceConnection,
        \Category\Product\Model\Category $categoryproductFactory,
      ProductCategoryList $productCategory
    ) {
        $this->_objectManager = $objectManager;
         $this->productCategory = $productCategory;
         $this->_resourceConnection = $resourceConnection;
         $this->categoryLinkManagement = $categoryLinkManagementInterface;
         $this->_categoryproductFactory = $categoryproductFactory;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $id = $product->getEntityId();
        $data = array();
        $connection = $this->_resourceConnection->getConnection();
        $table = $connection->getTableName('category_product');

        $categoryIds = $this->productCategory->getCategoryIds($id);
        $category = [];
        if ($categoryIds) {
            $category = array_unique($categoryIds);
        }
        $checkedCategory= array();
        $assignCategory  = array();
        foreach($category as $selectedId){
        $query = "select selected_category from ".$table." where checked_category like '%".$selectedId."%'";
        $checkedCategory[] =  $connection->fetchAll($query); 
        }
        if(!empty($checkedCategory)){
            $arraySingle = call_user_func_array('array_merge', $checkedCategory);
            foreach($checkedCategory as $key => $value){
                   foreach($value as $key=>$data){
                      $assignCategory[]= $data['selected_category']; 
                   }
             }
        }
        // echo "<pre>"; print_r($assignCategory); print_r($category); die;
                if(!empty($assignCategory)){
                // $checkedCategoryArr =  call_user_func_array('array_merge', $assignCategory);
                $checkedcat = array_unique(array_merge($assignCategory,$category));
                $this->categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    $checkedcat
);
            }
           
    }
}
