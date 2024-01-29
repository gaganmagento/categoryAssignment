<?php
namespace Category\Product\Block;
use Magento\Catalog\Model\CategoryFactory;


class Theme extends \Magento\Framework\View\Element\Template
{

    protected $_categoryFactory;
    protected $_session;
    public $_storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $session,
        CategoryFactory $categoryFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
         $this->_categoryFactory = $categoryFactory;
         $this->categoryRepository = $categoryRepository;
         $this->_storeManager=$storeManager;
         $this->_session = $session;
        parent::__construct($context, $data);
    }

    public function getCategoryUrlById($categoryId)
    {
        $category = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
        return $category->getUrl();
    }
    
    
    /* $categoryId as category id */
    public function getCategoryById($categoryId){
        $_category = $this->_categoryFactory->create();

        $category = $_category->load($categoryId);

        //Get category collection
        $collection = $category->getCollection()
                ->addAttributeToSelect('include_in_menu')
                ->addIsActiveFilter()
                ->addOrderField('position')
                ->addIdFilter($category->getChildren());
        return $collection;
    }
    public function getCategoryDetail($catId){
        try {
            return $category = $this->categoryRepository->get($catId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return ['response' => 'Category Not Found'];
        }
    }
    public function getBaseUrl(){
        $url = $this->_storeManager->getStore()->getBaseUrl();
        return $url; 
    }
    public function getCustomerLoggedIn(){
        if ($this->_session->isLoggedIn()) {
        return 1;
    } else {
        return 0;
    }
    }
}