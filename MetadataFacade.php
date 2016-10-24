<?php

namespace Webfactory\Bundle\WfdMetaBundle;

class MetadataFacade
{

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var DoctrineMetadataHelper
     */
    private $doctrineMetadataHelper;

    /**
     * MetadataFacade constructor.
     * @param Provider $provider
     * @param DoctrineMetadataHelper $doctrineMetadataHelper
     */
    public function __construct(Provider $provider, DoctrineMetadataHelper $doctrineMetadataHelper)
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