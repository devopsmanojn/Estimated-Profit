<?php

namespace Devopsmanoj\EstimatedProfit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Profit extends Column
{

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockState;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        array $components = [],
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_storeManager = $storeManager;
        $this->_stockState = $stockState;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $productId = (int)$item['entity_id'];
                $product = $this->_productFactory->create()->load($productId);
                $estimatedProfit = ($product->getCost() > 0) ? ($product->getPrice() - $product->getCost()) * $this->getStockQty($productId) : null;

                if ($estimatedProfit !== null && $estimatedProfit !== (float)$product->getData('estimated_profit')) {
                    $product->setData('estimated_profit', $estimatedProfit);
                    $product->save();
                }

                $item[$this->getData('name')] = $estimatedProfit;
            }
        }
        return $dataSource;
    }

    /**
     * Retrieve stock qty product
     *
     * @param int $productId
     * @return float
     */
    public function getStockQty(int $productId): float
    {
        return (float)$this->_stockState->getStockQty($productId, $this->getDefaultWebsiteId());
    }

    /**
     * @return string|int
     */
    public function getDefaultWebsiteId()
    {
        return $this->_storeManager->getDefaultStoreView()->getWebsiteId();
    }
}
