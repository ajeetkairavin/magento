<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Model\Order;

use Magento\Sales\Model\ResourceModel\Metadata;
use Magento\Sales\Api\Data\ShipmentSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;

/**
 * Repository class for @see \Magento\Sales\Api\Data\ShipmentInterface
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShipmentRepository implements \Magento\Sales\Api\ShipmentRepositoryInterface
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var SearchResultFactory
     */
    protected $searchResultFactory = null;

    /**
     * @var \Magento\Sales\Api\Data\ShipmentInterface[]
     */
    protected $registry = [];

    /**
     * @param Metadata $metadata
     * @param SearchResultFactory $searchResultFactory
     */
    public function __construct(
        Metadata $metadata,
        SearchResultFactory $searchResultFactory
    ) {
        $this->metadata = $metadata;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * Loads a specified shipment.
     *
     * @param int $id
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id)
    {
        if (!$id) {
            throw new InputException(__('Id required'));
        }

        if (!isset($this->registry[$id])) {
            /** @var \Magento\Sales\Api\Data\ShipmentInterface $entity */
            $entity = $this->metadata->getNewInstance()->load($id);
            if (!$entity->getEntityId()) {
                throw new NoSuchEntityException(__('Requested entity doesn\'t exist'));
            }

            $this->registry[$id] = $entity;
        }

        return $this->registry[$id];
    }

    /**
     * Find shipments by criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return \Magento\Sales\Api\Data\ShipmentInterface[]
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria)
    {
        //@TODO: fix search logic
        /** @var \Magento\Sales\Api\Data\ShipmentSearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $searchResult->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setCurPage($searchCriteria->getCurrentPage());
        $searchResult->setPageSize($searchCriteria->getPageSize());

        return $searchResult;
    }

    /**
     * Deletes a specified shipment.
     *
     * @param \Magento\Sales\Api\Data\ShipmentInterface $entity
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Magento\Sales\Api\Data\ShipmentInterface $entity)
    {
        try {
            $this->metadata->getMapper()->delete($entity);

            unset($this->registry[$entity->getEntityId()]);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete shipment'), $e);
        }

        return true;
    }

    /**
     * Deletes shipment by Id.
     *
     * @param int $id
     * @return bool
     */
    public function deleteById($id)
    {
        $entity = $this->get($id);

        return $this->delete($entity);
    }

    /**
     * Performs persist operations for a specified shipment.
     *
     * @param \Magento\Sales\Api\Data\ShipmentInterface $entity
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     * @throws CouldNotSaveException
     */
    public function save(\Magento\Sales\Api\Data\ShipmentInterface $entity)
    {
        try {
            $this->metadata->getMapper()->save($entity);
            $this->registry[$entity->getEntityId()] = $entity;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save shipment'), $e);
        }

        return $this->registry[$entity->getEntityId()];
    }

    /**
     * Creates new shipment instance.
     *
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function create()
    {
        return $this->metadata->getNewInstance();
    }
}
