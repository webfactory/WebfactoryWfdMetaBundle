<?php

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\MappingException;
use RuntimeException;

/**
 * Helper class to obtain the root table name for a given Doctrine entity FQCN.
 * Can also provide the primary key value for any given Doctrine entity.
 */
class DoctrineMetadataHelper
{
    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * DoctrineMetadataHelper constructor.
     */
    public function __construct(ClassMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Figures out the root table name in the database containing entities of a given class.
     *
     * @param string $classname FQCN for the Doctrine entity class to resolve
     *
     * @return string Database table name for the root table belonging to this entity.
     */
    public function getRootTableName(string $classname): string
    {
        try {
            /** @var $meta ClassMetadata */
            $meta = $this->metadataFactory->getMetadataFor($classname);
            if (!$meta->isInheritanceTypeNone()) {
                $meta = $this->metadataFactory->getMetadataFor($meta->rootEntityName);
            }

            return $meta->getTableName();
        } catch (MappingException $e) {
            throw new RuntimeException("Could not resolve root table name for '$classname'. Check that this is a Doctrine entity class.", 0, $e);
        }
    }

    /**
     * Returns the value of the primary key of the Doctrine entity.
     *
     * @param object $entity
     *
     * @return int
     */
    public function getPrimaryKey($entity)
    {
        /** @var $meta ClassMetadata */
        $meta = $this->metadataFactory->getMetadataFor(\get_class($entity));

        return $meta->getFieldValue($entity, $meta->getSingleIdentifierFieldName());
    }
}
