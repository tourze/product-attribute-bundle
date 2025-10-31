<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Repository\AttributeGroupRepository;

/**
 * @internal
 */
#[CoversClass(AttributeGroupRepository::class)]
#[RunTestsInSeparateProcesses]
class AttributeGroupRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return AttributeGroupRepository::class;
    }

    protected function getEntityClass(): string
    {
        return AttributeGroup::class;
    }

    protected function getRepository(): AttributeGroupRepository
    {
        return self::getService(AttributeGroupRepository::class);
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase required method
    }

    protected function createNewEntity(): AttributeGroup
    {
        $group = new AttributeGroup();
        $group->setCode('test_group_' . uniqid());
        $group->setName('Test Group');
        $group->setStatus(AttributeStatus::ACTIVE);

        return $group;
    }

    public function testFindActiveGroups(): void
    {
        $activeGroup = $this->createNewEntity();
        $activeGroup->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($activeGroup);

        $inactiveGroup = $this->createNewEntity();
        $inactiveGroup->setStatus(AttributeStatus::INACTIVE);
        self::getEntityManager()->persist($inactiveGroup);

        self::getEntityManager()->flush();

        $activeGroups = $this->getRepository()->findActiveGroups();

        $this->assertIsArray($activeGroups);
        foreach ($activeGroups as $group) {
            $this->assertEquals(AttributeStatus::ACTIVE, $group->getStatus());
        }
    }

    public function testFindByCode(): void
    {
        $uniqueCode = 'unique_code_' . uniqid();
        $group = $this->createNewEntity();
        $group->setCode($uniqueCode);
        $this->getRepository()->save($group);

        $found = $this->getRepository()->findByCode($uniqueCode);
        $this->assertInstanceOf(AttributeGroup::class, $found);
        $this->assertEquals($uniqueCode, $found->getCode());

        $notFound = $this->getRepository()->findByCode('non_existent');
        $this->assertNull($notFound);
    }

    public function testSave(): void
    {
        $group = $this->createNewEntity();

        $this->getRepository()->save($group);

        $this->assertNotNull($group->getId());

        $found = $this->getRepository()->find($group->getId());
        $this->assertInstanceOf(AttributeGroup::class, $found);
    }

    public function testRemove(): void
    {
        $group = $this->createNewEntity();
        $this->getRepository()->save($group);
        $id = $group->getId();

        $this->getRepository()->remove($group);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testIsCodeExists(): void
    {
        $uniqueCode = 'unique_group_code_' . uniqid();
        $group = $this->createNewEntity();
        $group->setCode($uniqueCode);
        $this->getRepository()->save($group);

        $this->assertTrue($this->getRepository()->isCodeExists($uniqueCode));
        $this->assertFalse($this->getRepository()->isCodeExists('non_existent_code'));
        $this->assertFalse($this->getRepository()->isCodeExists($uniqueCode, $group->getId()));
    }
}
