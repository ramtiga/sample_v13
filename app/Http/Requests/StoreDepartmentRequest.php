<?php

namespace App\Http\Requests;

use App\Entities\Department;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use LaravelDoctrine\ORM\Validation\DoctrinePresenceVerifier;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:' . Department::class . ',code'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Under the console kernel (e.g. `artisan test`), Laravel's own
     * ValidationServiceProvider is deferred and gets force-loaded at the end
     * of bootstrap via Application::loadDeferredProviders(), which re-binds
     * `validation.presence` to the default Eloquent-based verifier and
     * clobbers the Doctrine one registered earlier by DoctrineServiceProvider.
     * Explicitly set the Doctrine verifier here so `unique:` against a
     * Doctrine entity class works regardless of provider boot order.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->setPresenceVerifier(
            new DoctrinePresenceVerifier($this->container->make('registry')),
        );
    }
}
