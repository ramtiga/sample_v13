<?php

namespace App\Providers;

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Any table not managed by a Doctrine entity is invisible to
     * doctrine:schema:* commands, so Eloquent-owned tables never get dropped.
     */
    private const DOCTRINE_MANAGED_TABLES = [
        'posts',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(EntityManagerInterface $em): void
    {
        $em->getConnection()->getConfiguration()->setSchemaAssetsFilter(
            static fn (string|object $assetName): bool => in_array(
                is_string($assetName) ? $assetName : $assetName->getName(),
                self::DOCTRINE_MANAGED_TABLES,
                true
            )
        );
    }
}
