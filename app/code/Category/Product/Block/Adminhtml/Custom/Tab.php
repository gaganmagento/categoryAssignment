<?php

namespace Category\Product\Block\Adminhtml\Custom;

class Tab extends \Magento\Backend\Block\Template
{
   


    protected $_template = 'category/category.phtml';

    protected $blockGrid;

    protected $registry;

    protected $_productCollectionFactory;
    
    protected $jsonEncoder;
     protected $categoryproductFactory;

     /**
     * AssignProducts constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Category\Product\Model\Category $categoryproductFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->jsonEncoder = $jsonEncoder;
        $this->_categoryproductFactory = $categoryproductFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {

        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                '\Category\Product\Block\Adminhtml\Category\Tab\Product',
                'category.product.grid.custom'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Return HTML of grid block
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * @return string
     */
    public function getProductsJson()
    {

     $products = $this->getCategory()->getProductsPosition();
        if (!empty($products)) {
            return $this->jsonEncoder->encode($products);
        }
        return '{}';    

    }

    /**
     * Retrieve current category instance
     *
     * @return array|null
     */
    public function getCategory()
    {
        return $this->registry->registry('category');
    }
}

   