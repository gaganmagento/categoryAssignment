<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Category\Product\Controller\Adminhtml\Category;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Category save controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magento\Catalog\Controller\Adminhtml\Category implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    protected $_resourceConnection;  
    private $categoryManagement;   
    protected $_registry;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
   

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * The list of inputs that need to convert from string to boolean
     * @var array
     */
    protected $stringToBoolInputs = [
        'custom_use_parent_settings',
        'custom_apply_to_products',
        'is_active',
        'include_in_menu',
        'is_anchor',
        'use_default' => ['url_key'],
        'use_config' => ['available_sort_by', 'filter_price_range', 'default_sort_by']
    ];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    protected $_productRepository;
    protected $_productCollectionFactory;
    protected $_json;
    protected $_resource;


    
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;
    private $cookieManager;
  
    

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    protected $categoryproductFactory;
    protected $categoryFactory;
    protected $categoryRepository;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
         \Magento\Catalog\Model\ProductRepository $productRepository,
         CategoryManagementInterface $categoryManagement,
         \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
         \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagementInterface,
         \Magento\Catalog\Model\CategoryFactory $CategoryFactory,
         \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Registry $registry,
        \Category\Product\Model\Category $categoryproductFactory,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Category\Product\Model\Session $session,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Config $eavConfig = null,
        \Psr\Log\LoggerInterface $logger = null
    ) {
        parent::__construct($context, $dateFilter);
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_productRepository = $productRepository;
        $this->session = $session;
        $this->categoryRepository = $categoryRepository;
        $this->_categoryproductFactory = $categoryproductFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->cookieManager = $cookieManager;
        $this->categoryFactory = $CategoryFactory;
        $this->categoryManagement = $categoryManagement;
        $this->_resourceConnection = $resourceConnection;
        $this->_resource = $resource;
        $this->_json = $json;
        $this->layoutFactory = $layoutFactory;
        $this->_registry = $registry;
        $this->storeManager = $storeManager;
        $this->categoryLinkManagement = $categoryLinkManagementInterface;
        $this->eavConfig = $eavConfig ?: ObjectManager::getInstance()
            ->get(\Magento\Eav\Model\Config::class);
        $this->logger = $logger ?: ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class);
    }

    /**
     * Filter category data
     *
     * @deprecated 101.0.8
     * @param array $rawData
     * @return array
     */
    protected function _filterCategoryPostData(array $rawData)
    {
        $data = $rawData;
        if (isset($data['image']) && is_array($data['image'])) {
            if (!empty($data['image']['delete'])) {
                $data['image'] = null;
            } else {
                if (isset($data['image'][0]['name']) && isset($data['image'][0]['tmp_name'])) {
                    $data['image'] = $data['image'][0]['name'];
                } else {
                    unset($data['image']);
                }
            }
        }
        return $data;
    }

    /**
     * Category save
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {

        $categoryPostData = $this->getRequest()->getPostValue();
        $recurisive = 0;
        $cateId = $this->session->getCatId();
        $this->session->unsCatId();
        $connection = $this->_resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_category_product');
        $table1 = $connection->getTableName('catalog_product_entity');
        if(!empty($categoryPostData['entity_id'])){
            $query1 = "select checked_category  from  category_product where selected_category =".(int)$categoryPostData['entity_id'];
            $uncheckSelectedCats =  $connection->fetchAll($query1);
        }else{
            $uncheckSelectedCats = '';
        }
        if(!empty($cateId['0'])){
            if($cateId['0'] != 4){
             $categorySku = array();
             $categoryProductId = array();
             $productsSku = array();
             $productsId = array();
             $customCategory = array();
             $subCategory = array();
             $unassignCategories = array();
             $data = array();
             
             $collection = $this->_productCollectionFactory->create();
            foreach($cateId['0'] as $productCollection){
                if(in_array('0',$cateId['0'])){
                    $cateId['0'] = array_diff($cateId['0'], array('0'));
                    $recurisive = 1;
                    $getCategoryList = $this->getSubCategoryByParentID($productCollection);
                    foreach ($getCategoryList as $id => $category){
                        $subCategory[]= $id;
                    }
                     
                    $subCat = array_merge($cateId['0'],$subCategory);
                    foreach($subCat as $categoryId){
                        $query = "select ".$table1.".sku,".$table.".product_id  from ".$table."
                        INNER JOIN ".$table1." ON ".$table1.".entity_id = ".$table.".product_id where ".$table.".category_id = ".$categoryId.";";
                        $data[] =  $connection->fetchAll($query);
                    }
                    if(!empty($data)){
                    $data1 = call_user_func_array('array_merge', $data);
                    foreach($data1 as $item){
                         $categorySku[] = $item['sku'];
                         $categoryProductId[] = $item['product_id'];
                    }
                }
                
                }else{
                    
                    $query = "select ".$table1.".sku,".$table.".product_id  from ".$table."
                    INNER JOIN ".$table1." ON ".$table1.".entity_id = ".$table.".product_id where ".$table.".category_id = ".$productCollection.";";
                    $data[] =  $connection->fetchAll($query);
                }
            }
               if(!empty($data)){
                    $data1 = call_user_func_array('array_merge', $data);
                    foreach($data1 as $item){
                         $categorySku[] = $item['sku'];
                         $categoryProductId[] = $item['product_id'];
                    }
                }
            }
        }
        if(!empty($categoryPostData['entity_id'])){
            $currentCat = $categoryPostData['entity_id'];
        }else{
            $currentCat = '';
        }
        if(empty($categorySku)){

            $categorySku = array();
        }
        if(empty($categoryProductId)){
            $categoryProductId = array();
        }
        $productsCategory = array();
        if(!empty($categoryPostData['custom_product'])){
            $productId = json_decode($categoryPostData['custom_product']);
            foreach ($productId as $pId => $numbers) {
                $id= $pId;
                $productById= $this->_productRepository->getById($id);
                $productsSku[] = $productById->getSku();
                $productsId[]= $pId;
            }
        }
        if(empty($productsSku)){
            $productsSku = array();
        }
        if(empty($productsId)){
            $productsId = array();
        }
        $allSkus = array_merge($categorySku,$productsSku);
        $allProductId = array_merge($categoryProductId,$productsId);
        if(!empty($allProductId)){
            $data = array_unique($allProductId);
            $allProductsIdjson = json_encode(array_fill_keys($data,'0'));
        }
        if(!empty($allSkus)){

            $data = array_unique($allSkus);
            $allSkusjson = json_encode(array_fill_keys($data,'0'));
            if(!empty($categoryPostData['entity_id'])){
            $categoryId = explode(" ", $categoryPostData['entity_id']);
            }
            if(!empty($cateId['0'])){
                if($cateId['0'] != 4){
                $cateIdjson = implode(",",$cateId['0']);
                $categoryIds = array_unique(array_merge($cateId['0'],$categoryId));
            }
        }else{
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $products = $objectManager->get('Magento\Catalog\Model\Product')->getCollection()->addAttributeToFilter('sku',$allSkus);
                foreach($products as $sku){
                    $productid[]= $sku->getCategoryIds();
                }
                $flat = call_user_func_array('array_merge', $productid);
                $categoryIds = array_merge($flat,$categoryId);

            }
        }
     
        $checkcatUnassign = array();
        $checkedCategory = $this->_categoryproductFactory->getCollection();
        $selectedCat = $checkedCategory->addFieldToFilter('selected_category', ['eq' => $currentCat]);
        if(count($selectedCat)>0){
               
            foreach($selectedCat as $entityId){
                $checkedEntity = $entityId->getEntityId();
                $dataAssignedProduct = $entityId->getAssignedProduct();
                $checkcatUnassign[] = $entityId->getCheckedCategory();
            }
            if(!empty($dataAssignedProduct)){
                $assignProductsColl =  $this->_json->unserialize($dataAssignedProduct);
                $unAssignProduct = array_keys($assignProductsColl); 
                $unSku= array();
                foreach($unAssignProduct as $id){
                    $unassignSku = $this->_productRepository->getById($id);
                    $unSku[]= $unassignSku->getSku();
                }
            }   
            $checkedCategoryCollection = $this->_categoryproductFactory->load($checkedEntity);
            $checkedCategoryCollection->setSelectedCategory($categoryPostData['entity_id']);
            if(!empty($allProductsIdjson)){
                $checkedCategoryCollection->setAssignedProduct($allProductsIdjson);
            }else{
                $checkedCategoryCollection->setAssignedProduct(Null);
            }
            if(!empty($cateId)){
            if(!empty($cateIdjson)){
                $checkedCategoryCollection->setCheckedCategory($cateIdjson);
            }else{
                $checkedCategoryCollection->setCheckedCategory(Null);
            }
        }
            $checkedCategoryCollection->setProductRecurisive($recurisive);
            $checkedCategoryCollection->save();
     
        }else{

            $categoryProduct  = $this->_categoryproductFactory;
            if(!empty($categoryPostData['entity_id'])){

                $categoryProduct->setSelectedCategory($categoryPostData['entity_id']);
            }else{
                 $categoryProduct->setSelectedCategory(Null);
            }
            if(!empty($allProductId)){
                $categoryProduct->setAssignedProduct($allProductsIdjson);
            }else{
                $categoryProduct->setAssignedProduct(Null);

            }
            if(!empty($cateIdjson)){
                $categoryProduct->setCheckedCategory($cateIdjson);
            }
            $categoryProduct->setProductRecurisive($recurisive);
            $categoryProduct->save();
        }
        if(!empty($categoryIds)){
            foreach($categoryIds as $catId){
                foreach($allProductId as $productid){
                    $query = "select product_id from ".$table." where category_id= ".$categoryPostData['entity_id']." and product_id = ".$productid.";";
                    $empty = $connection->fetchAll($query);
                    if(count($empty)==0){
                    $query = "INSERT INTO `".$table."` (`category_id`, `product_id`, `position`)  VALUES  ('".$categoryPostData['entity_id']."','".$productid."','0');";
                    $connection->query($query);
                }


                }
            }
        }
         if(!empty($uncheckSelectedCats)){
            $savedCheckedCategory = explode(",",$uncheckSelectedCats['0']['checked_category']);
         }
        if(!empty($cateId['0']) && $cateId['1'] == 4){  
        if(!empty($savedCheckedCategory)) {   
            $data2 = array();    
            $categoryUnassign = array_diff($savedCheckedCategory, $cateId['0']);
            if(!empty($categoryUnassign)){
                foreach($categoryUnassign as $changedCategories){
                    if(!empty($changedCategories)){
                    $query = "SELECT `product_id` FROM `catalog_category_product` WHERE `category_id` =".$changedCategories;
                    $result = $connection->fetchAll($query);
                        if(!empty($result)){
                            foreach($result as $pId){
                                $query = "DELETE FROM `catalog_category_product` WHERE `category_id`=".$categoryPostData['entity_id']." AND `product_id`=".$pId['product_id'];
                                $connection->query($query);
                            }     
                        }
                    }

                }
                 $productQuery = "SELECT `product_id` FROM `catalog_category_product` WHERE `category_id` =".$categoryPostData['entity_id'];
                 $products = $connection->fetchAll($productQuery);
                 foreach($products as $ids){
                    $data2[] = $ids['product_id'];
                 }
                 $data1 = json_encode(array_fill_keys($data2,0));
                 $query1 = "UPDATE `category_product` SET `assigned_product` = '".$data1."' WHERE `selected_category` = ".$categoryPostData['entity_id'];
                 $connection->query($query1);
                
            }


        }
    }
    if(!empty($cateId['0'])){
        if($cateId['0'] == 4){
            // $query1 = "SELECT `checked_category` FROM `category_product` WHERE `selected_category` =".$categoryPostData['entity_id'];
            // $all = $connection->fetchAll($query1);
            $checkCat = explode(",", $uncheckSelectedCats['0']['checked_category']);
            // echo "<pre>"; print_r($checkCat); die();
            foreach($checkCat as $cat){
                $query = "SELECT `product_id` FROM `catalog_category_product` WHERE `category_id` =".$cat;
                $result = $connection->fetchAll($query);
                    if(!empty($result)){
                        foreach($result as $pId){
                             $query = "DELETE FROM `catalog_category_product` WHERE `category_id`=".$categoryPostData['entity_id']." AND `product_id`=".$pId['product_id'];
                             $connection->query($query);
                        }
                    }
            }
             $productQuery = "SELECT `product_id` FROM `catalog_category_product` WHERE `category_id` =".$categoryPostData['entity_id'];
             $products = $connection->fetchAll($productQuery);
             foreach($products as $ids){
                    $data2[] = $ids['product_id'];
             }
             if(!empty($data2)){
             $data1 = json_encode(array_fill_keys($data2,0));
             $query1 = "UPDATE `category_product` SET `assigned_product` = '".$data1."' WHERE `selected_category` = ".$categoryPostData['entity_id'];
             $connection->query($query1);
         }
        }
    }
if(empty($cateId)){
    if(isset($categoryPostData['custom_product'])){
        $id = array();
        $data2 = array();
        $data = $categoryPostData['custom_product'];
        $productId = json_decode($data);
        foreach($productId as $key => $value){
            $id[] = $key;
        }
        $productQuery = "SELECT `product_id` FROM `catalog_category_product` WHERE `category_id` =".$categoryPostData['entity_id'];
        $products = $connection->fetchAll($productQuery);
        foreach($products as $ids){
            $data2[] = $ids['product_id'];
        }
        $allproductidunsassign = array_diff($data2, $id);
        foreach($allproductidunsassign as $unassignproduct){
            $query = "DELETE FROM `catalog_category_product` WHERE `category_id`=".$categoryPostData['entity_id']." AND `product_id`=".$unassignproduct;
            $connection->query($query);
        }
        $query1 = "UPDATE `category_product` SET `assigned_product` = '".$data."' WHERE `selected_category` = ".$categoryPostData['entity_id'];
        $connection->query($query1);

    }
}
                                                      
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $category = $this->_initCategory();

        if (!$category) {
            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }       
         

        $isNewCategory = !isset($categoryPostData['entity_id']);
        $categoryPostData = $this->stringToBoolConverting($categoryPostData);
        $categoryPostData = $this->imagePreprocessing($categoryPostData);
        $categoryPostData = $this->dateTimePreprocessing($category, $categoryPostData);
        $storeId = $categoryPostData['store_id'] ?? null;
        $store = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store->getCode());
        $parentId = $categoryPostData['parent'] ?? null;
        if ($categoryPostData) {
            $category->addData($categoryPostData);
            if ($parentId) {
                $category->setParentId($parentId);
            }
            if ($isNewCategory) {
                $parentCategory = $this->getParentCategory($parentId, $storeId);
                $category->setPath($parentCategory->getPath());
                $category->setParentId($parentCategory->getId());
                $category->setLevel(null);
            }

            /**
             * Process "Use Config Settings" checkboxes
             */

            $useConfig = [];
            if (isset($categoryPostData['use_config']) && !empty($categoryPostData['use_config'])) {
                foreach ($categoryPostData['use_config'] as $attributeCode => $attributeValue) {
                    if ($attributeValue) {
                        $useConfig[] = $attributeCode;
                        $category->setData($attributeCode, null);
                    }
                }
            }

            $category->setAttributeSetId($category->getDefaultAttributeSetId());

            if (isset($categoryPostData['category_products'])
                && is_string($categoryPostData['category_products'])
                && !$category->getProductsReadonly()
            ) {
                $products = json_decode($categoryPostData['category_products'], true);
                $category->setPostedProducts($products);
            }

            try {
                $this->_eventManager->dispatch(
                    'catalog_category_prepare_save',
                    ['category' => $category, 'request' => $this->getRequest()]
                );
                /**
                 * Check "Use Default Value" checkboxes values
                 */
                if (isset($categoryPostData['use_default']) && !empty($categoryPostData['use_default'])) {
                    foreach ($categoryPostData['use_default'] as $attributeCode => $attributeValue) {
                        if ($attributeValue) {
                            $category->setData($attributeCode, null);
                        }
                    }
                }

                /**
                 * Proceed with $_POST['use_config']
                 * set into category model for processing through validation
                 */
                $category->setData('use_post_data_config', $useConfig);

                $categoryResource = $category->getResource();
                if ($category->hasCustomDesignTo()) {
                    $categoryResource->getAttribute('custom_design_from')->setMaxValue($category->getCustomDesignTo());
                }

                $validate = $category->validate();
                if ($validate !== true) {
                    foreach ($validate as $code => $error) {
                        if ($error === true) {
                            $attribute = $categoryResource->getAttribute($code)->getFrontend()->getLabel();
                            throw new \Magento\Framework\Exception\LocalizedException(
                                __('The "%1" attribute is required. Enter and try again.', $attribute)
                            );
                        } else {
                            $this->messageManager->addErrorMessage(
                                __(
                                    'Something went wrong while saving the category.'
                                )
                            );
                            $this->logger->critical('Something went wrong while saving the category.');
                            $this->_getSession()->setCategoryData($categoryPostData);
                        }
                    }
                }

                $category->unsetData('use_post_data_config');

                $category->save();
                $this->messageManager->addSuccessMessage(__('You saved the category.'));
                // phpcs:disable Magento2.Exceptions.ThrowCatch
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e);
                $this->logger->critical($e);
                $this->_getSession()->setCategoryData($categoryPostData);
                // phpcs:disable Magento2.Exceptions.ThrowCatch
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the category.'));
                $this->logger->critical($e);
                $this->_getSession()->setCategoryData($categoryPostData);
            }
        }

        $hasError = (bool)$this->messageManager->getMessages()->getCountByType(
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );

        if ($this->getRequest()->getPost('return_session_messages_only')) {
            $category->load($category->getId());
            // to obtain truncated category name
            /** @var $block \Magento\Framework\View\Element\Messages */
            $block = $this->layoutFactory->create()->getMessagesBlock();
            $block->setMessages($this->messageManager->getMessages(true));

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData(
                [
                    'messages' => $block->getGroupedHtml(),
                    'error' => $hasError,
                    'category' => $category->toArray(),
                ]
            );
        }

        $redirectParams = $this->getRedirectParams($isNewCategory, $hasError, $category->getId(), $parentId, $storeId);

        return $resultRedirect->setPath(
            $redirectParams['path'],
            $redirectParams['params']
        );
    }

    /**
     * Sets image attribute data to false if image was removed
     *
     * @param array $data
     * @return array
     */
    public function imagePreprocessing($data)
    {
        $entityType = $this->eavConfig->getEntityType(CategoryAttributeInterface::ENTITY_TYPE_CODE);

        foreach ($entityType->getAttributeCollection() as $attributeModel) {
            $attributeCode = $attributeModel->getAttributeCode();
            $backendModel = $attributeModel->getBackend();

            if (isset($data[$attributeCode])) {
                continue;
            }

            if (!$backendModel instanceof \Magento\Catalog\Model\Category\Attribute\Backend\Image) {
                continue;
            }

            $data[$attributeCode] = '';
        }

        return $data;
    }

    /**
     * Converting inputs from string to boolean
     *
     * @param array $data
     * @param array $stringToBoolInputs
     *
     * @return array
     */
    public function stringToBoolConverting($data, $stringToBoolInputs = null)
    {
        if (null === $stringToBoolInputs) {
            $stringToBoolInputs = $this->stringToBoolInputs;
        }
        foreach ($stringToBoolInputs as $key => $value) {
            if (is_array($value)) {
                if (isset($data[$key])) {
                    $data[$key] = $this->stringToBoolConverting($data[$key], $value);
                }
            } else {
                if (isset($data[$value])) {
                    if ($data[$value] === 'true') {
                        $data[$value] = true;
                    }
                    if ($data[$value] === 'false') {
                        $data[$value] = false;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Get parent category
     *
     * @param int $parentId
     * @param int $storeId
     *
     * @return \Magento\Catalog\Model\Category
     */
    protected function getParentCategory($parentId, $storeId)
    {
        if (!$parentId) {
            if ($storeId) {
                $parentId = $this->storeManager->getStore($storeId)->getRootCategoryId();
            } else {
                $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
            }
        }
        return $this->_objectManager->create(\Magento\Catalog\Model\Category::class)->load($parentId);
    }

    /**
     * Get category redirect path
     *
     * @param bool $isNewCategory
     * @param bool $hasError
     * @param int $categoryId
     * @param int $parentId
     * @param int $storeId
     *
     * @return array
     */
    protected function getRedirectParams($isNewCategory, $hasError, $categoryId, $parentId, $storeId)
    {
        $params = ['_current' => true];
        if ($storeId) {
            $params['store'] = $storeId;
        }

        if ($isNewCategory && $hasError) {
            $path = 'catalog/*/add';
            $params['parent'] = $parentId;
        } else {
            $path = 'catalog/*/edit';
            $params['id'] = $categoryId;
        }
        return ['path' => $path, 'params' => $params];
    }

    private function getSubCategoryByParentID(int $categoryId): array
    {
        $categoryData = [];

        $getSubCategory = $this->getCategoryData($categoryId);
        foreach ($getSubCategory->getChildrenData() as $category) {
            $categoryData[$category->getId()] = [
                'name'=> $category->getName(),
                'url'=> $category->getUrl()
            ];
            if (count($category->getChildrenData())) {
                $getSubCategoryLevelDown = $this->getCategoryData($category->getId());
                foreach ($getSubCategoryLevelDown->getChildrenData() as $subcategory) {
                        $categoryData[$subcategory->getId()]  = [
                            'name'=> $subcategory->getName(),
                            'url'=> $subcategory->getUrl()
                        ];
                }
            }
        }

        return $categoryData;
    }

     private function getCategoryData(int $categoryId): ?CategoryTreeInterface
    {
        try {
            $getSubCategory = $this->categoryManagement->getTree($categoryId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Category not found", [$e]);
            $getSubCategory = null;
        }

        return $getSubCategory;
    }
}
