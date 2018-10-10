<?php

namespace Webfactory\Bundle\WfdMetaBundle;

class MetadataFacade
{
    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var DoctrineMetadataHelper|null
     */
    private $doctrineMetadataHelper;

    /**
     * MetadataFacade constructor.
     * @param Provider $provider
     * @param DoctrineMetadataHelper|null $doctrineMetadataHelper
     */
    public function __construct(Provider $provider, DoctrineMetadataHelper $doctrineMetadataHelper = null)
    {
        $this->provider = $provider;
        $this->doctrineMetadataHelper = $doctrineMetadataHelper;
    }

    /**
     * @param object $entity
     * @return int Unix timestamp
     */
    public function getLastTouchedForEntity($entity)
    {
        if (!$this->doctrineMetadataHelper) {
            throw new \RuntimeException('DoctrineMetadataHelper must be available to query information for Doctrine Entities. Tip: Is Doctrine ORM enabled and the doctrine.orm.entity_manager service available in the DIC?');
        }

        return $this->provider->getLastTouchedRow(
            $this->doctrineMetadataHelper->getRootTableName(get_class($entity)),
            $this->doctrineMetadataHelper->getPrimaryKey($entity)
        );
    }

    /**
     * @param string $classname
     * @return int Unix timestamp
     */
    public function getLastTouchedForEntityClass($classname)
    {
        if (!$this->doctrineMetadataHelper) {
            throw new \RuntimeException('DoctrineMetadataHelper must be available to query information for Doctrine Entities. Tip: Is Doctrine ORM enabled and the doctrine.orm.entity_manager service available in the DIC?');
        }

        return $this->getLastTouchedForTableName(
            $this->doctrineMetadataHelper->getRootTableName($classname)
        );
    }

    /**
     * @param string $tablename
     * @return int Unix timestamp
     */
    public function getLastTouchedForTableName($tablename)
    {
        return $this->provider->getLastTouched([$tablename]);
    }
}
