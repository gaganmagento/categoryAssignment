<?php
namespace Category\Product\Model\Session;

class Storage extends \Magento\Framework\Session\Storage
{
    /**
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param string $storeManager
     * @param string $namespace
     * @param array $data
     */
    protected $storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $namespace = 'customcategory',
        array $data = []
    ) {
        parent::__construct($namespace, $data);
    }
}
