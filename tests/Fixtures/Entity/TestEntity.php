<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test_table')]
class TestEntity
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;
}
