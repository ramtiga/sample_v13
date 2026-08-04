<?php

namespace Tests\Feature;

use App\Entities\Department;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->app->make(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);

        parent::tearDown();
    }

    private function createDepartment(string $code = 'DEV', string $name = '開発部', int $sortOrder = 1): Department
    {
        $department = new Department();
        $department->setCode($code);
        $department->setName($name);
        $department->setSortOrder($sortOrder);
        $department->setIsActive(true);

        $this->em->persist($department);
        $this->em->flush();

        return $department;
    }

    public function test_index_displays_existing_departments(): void
    {
        $this->createDepartment('DEV', '開発部');
        $this->createDepartment('SALES', '営業部');

        $response = $this->get('/departments');

        $response->assertOk();
        $response->assertSee('開発部');
        $response->assertSee('営業部');
    }
}
