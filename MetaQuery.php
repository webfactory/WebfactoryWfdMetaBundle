<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Eine Abfrage von wfd_meta-Daten, die vorher für bestimmte Tabellen oder
 * Doctrine ORM-Entitätsklassen konfiguriert werden kann.
 */
class MetaQuery
{

    /** @var Provider */
    protected $provider;

    /** @var ContainerInterface */
    protected $container;

    protected $tables = array();

    public function __construct(Provider $provider, ContainerInterface $container)
    {
        $this->provider = $provider;
        $this->container = $container;
    }

    public function addEntity($classname)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        foreach ($classname as $class) {
            try {
                $meta = $em->getClassMetadata($class);
                if (!$meta->isInheritanceTypeNone()) {
                    $meta = $em->getClassMetadata($meta->rootEntityName);
                }
                $this->addTable($meta->getTableName());
            } catch (MappingException $e) {
                throw new \RuntimeException("webfactory/wfdmeta-bundle: Ein MetaQuery soll für die Klasse '$class' konfiguriert werden, die keine bekannte Doctrine-Entität ist.", 0, $e);
            }
        }
    }

    public function addTable($tableName)
    {
        foreach ((array)$tableName as $t) {
            $this->tables[$t] = true;
        }
    }

    public function getLastTouched()
    {
        return $this->provider->getLastTouched(array_keys($this->tables));
    }
}
