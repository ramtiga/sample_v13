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

    public function test_show_displays_department_detail(): void
    {
        $department = $this->createDepartment('HR', '人事部');

        $response = $this->get('/departments/' . $department->getId());

        $response->assertOk();
        $response->assertSee('人事部');
        $response->assertSee('HR');
    }

    public function test_show_returns_404_for_missing_department(): void
    {
        $response = $this->get('/departments/99999');

        $response->assertNotFound();
    }

    public function test_store_creates_a_new_department(): void
    {
        $response = $this->post('/departments', [
            'code' => 'MKT',
            'name' => 'マーケティング部',
            'sort_order' => 3,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('departments.index'));

        $department = $this->em->getRepository(Department::class)->findOneBy(['code' => 'MKT']);
        $this->assertNotNull($department);
        $this->assertSame('マーケティング部', $department->getName());
        $this->assertSame(3, $department->getSortOrder());
        $this->assertTrue($department->isActive());
    }

    public function test_store_fails_validation_with_duplicate_code(): void
    {
        $this->createDepartment('DEV', '開発部');

        $response = $this->post('/departments', [
            'code' => 'DEV',
            'name' => '別の開発部',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        $response = $this->post('/departments', []);

        $response->assertSessionHasErrors(['code', 'name']);
    }
}
