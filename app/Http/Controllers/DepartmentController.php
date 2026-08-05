<?php

namespace App\Http\Controllers;

use App\Entities\Department;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\RedirectResponse;
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

    public function create(): View
    {
        return view('departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = new Department();
        $department->setCode($request->validated('code'));
        $department->setName($request->validated('name'));
        $department->setSortOrder((int) ($request->validated('sort_order') ?? 0));
        $department->setIsActive((bool) $request->boolean('is_active', true));

        $this->em->persist($department);
        $this->em->flush();

        return redirect()
            ->route('departments.index')
            ->with('status', '所属マスタを作成しました。');
    }

    public function edit(int $department): View
    {
        $entity = $this->findOrFail($department);

        return view('departments.edit', ['department' => $entity]);
    }

    public function update(UpdateDepartmentRequest $request, int $department): RedirectResponse
    {
        $entity = $this->findOrFail($department);

        $entity->setCode($request->validated('code'));
        $entity->setName($request->validated('name'));
        $entity->setSortOrder((int) ($request->validated('sort_order') ?? 0));
        $entity->setIsActive((bool) $request->boolean('is_active', true));

        $this->em->flush();

        return redirect()
            ->route('departments.index')
            ->with('status', '所属マスタを更新しました。');
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
