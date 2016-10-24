<?php

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException;

class DoctrineMetadataHelper
{

    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * DoctrineMetadataHelper constructor.
     * @param ClassMetadataFactory $metadataFactory
     */
    public function __construct(ClassMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Figures out the root table name in the database containing entities of a given class.
     *
     * @param string $classname FQCN for the Doctrine entity class to resolve
     * @return string Database table name for the root table belonging to this entity.
     */
    public function getRootTableName($classname)
    {
        try {
            /** @var $meta ClassMetadata */
            $meta = $this->metadataFactory->getMetadataFor($classname);
            if (!$meta->isInheritanceTypeNone()) {
                $meta = $this->metadataFactory->getMetadataFor($meta->rootEntityName);
            }
            return $meta->getTableName();
        } catch (MappingException $e) {
            throw new \RuntimeException("Could not resolve root table name for '$classname'. Check that this is a Doctrine entity class.", 0, $e);
        }
    }

    /**
     * Returns the value of the primary key of the Doctrine entity.
     *
     * @param object $entity
     * @return int
     */
    public function getPrimaryKey($entity)
    {
        /** @var $meta ClassMetadata */
        $meta = $this->metadataFactory->getMetadataFor(get_class($entity));
        return $meta->getFieldValue($entity, $meta->getSingleIdentifierFieldName());
    }
}