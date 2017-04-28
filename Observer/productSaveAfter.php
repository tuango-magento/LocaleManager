<?php
namespace Tuango\LocaleManager\Observer;

use Magento\Framework\Event\ObserverInterface;

class productSaveAfter implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->_objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * controller_action_catalog_product_save_entity_after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $product \Magento\Catalog\Model\Product
         */
        $product = $observer->getProduct();

        $storeLocales = self::getListLocaleForAllStores();
        if(isset($storeLocales[$product->getStoreId()])) {
            $productLocale = $storeLocales[$product->getStoreId()];
            $targetStores = array_filter($storeLocales, function($k) use ($productLocale) {
                return $k == $productLocale;
            });

            foreach($targetStores as $targetStoreID => $targetStoreLocale) {
                $this->_objectManager->create('Magento\Catalog\Model\Product')
                    ->setStoreId($product->getStoreId())
                    ->load($product->getId())
                    ->setStoreId($targetStoreID)
                    ->setCopyFromView(true)
                    ->save();
            }
        }


    }

    /**
     * Get list of Locale for all stores
     * @return array
     */
    public function getListLocaleForAllStores()
    {
        //Locale code
        $locale = [];
        $stores = $this->storeManager->getStores($withDefault = false);
        //Try to get list of locale for all stores;
        foreach($stores as $store) {
            $locale[$store->getId()] = $this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId());
        }
        return $locale;
    }
}