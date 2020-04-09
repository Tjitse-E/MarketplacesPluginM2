<?php

namespace EffectConnect\Marketplaces\Cron;

use EffectConnect\Marketplaces\Api\ConnectionRepositoryInterface;
use EffectConnect\Marketplaces\Helper\ApiHelper;
use EffectConnect\Marketplaces\Helper\LogHelper;
use EffectConnect\Marketplaces\Model\Connection;
use EffectConnect\PHPSdk\Core\Exception\InvalidKeyException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ImportOrders
 * @package EffectConnect\Marketplaces\Cron
 */
class ImportOrders
{
    /**
     * @var ApiHelper
     */
    protected $_apiHelper;

    /**
     * @var ConnectionRepositoryInterface
     */
    protected $_connectionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var LogHelper
     */
    protected $_logHelper;

    /**
     * ImportOrders constructor.
     *
     * @param ApiHelper $apiHelper
     * @param ConnectionRepositoryInterface $connectionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LogHelper $logHelper
     */
    public function __construct(
        ApiHelper $apiHelper,
        ConnectionRepositoryInterface $connectionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LogHelper $logHelper
    ) {
        $this->_apiHelper               = $apiHelper;
        $this->_connectionRepository    = $connectionRepository;
        $this->_searchCriteriaBuilder   = $searchCriteriaBuilder;
        $this->_logHelper               = $logHelper;
    }

    /**
     * Executes when called by the cronjob.
     */
    public function execute()
    {
        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('is_active', true)
            ->create();
        $connections    = $this->_connectionRepository->getList($searchCriteria);

        /** @var Connection $connection */
        foreach ($connections->getItems() as $connection) {
            try {
                $api = $this->_apiHelper->getConnectionApi($connection->getEntityId());
                $api->importOrders();
            } catch (NoSuchEntityException $e) {
                $this->_logHelper->logOrdersImportConnectionFailed($connection->getEntityId(), $e->getMessage());
                continue;
            } catch (InvalidKeyException $e) {
                $this->_logHelper->logOrdersImportConnectionFailed($connection->getEntityId(), $e->getMessage());
                continue;
            }
        }
    }
}