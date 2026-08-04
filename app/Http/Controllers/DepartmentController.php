<?php

namespace App\Http\Controllers;

use App\Entities\Department;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepartmentController extends Controller
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function index(): View
    {
        $departments = $this->em
            ->getRepository(Department::class)
            ->findBy([], ['sortOrder' => 'ASC']);

        return view('departments.index', ['departments' => $departments]);
    }

    public function show(int $department): View
    {
        $entity = $this->findOrFail($department);

        return view('departments.show', ['department' => $entity]);
    }

    private function findOrFail(int $id): Department
    {
        $entity = $this->em->find(Department::class, $id);

        if ($entity === null) {
            throw new NotFoundHttpException('Department not found.');
        }

        return $entity;
    }
}
