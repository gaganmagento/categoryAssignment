<?php
namespace Category\Product\Controller\Adminhtml\Category;
 
class Custom extends \Magento\Framework\App\Action\Action
{
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Category\Product\Model\Session $session
        
    ) {
        $this->session = $session;
        parent::__construct($context);
    }
    /**
     * CSV Create and Download
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $categoryPostData = $this->getRequest()->getPostValue(); 
        $catids=[];
        foreach ($categoryPostData as $key => $value) {
           
            $catids[]=$value;
        }
        $this->session->setCatId($catids);

    }
}
