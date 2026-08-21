<?php

namespace SmartDato\BrtDocumentoDiRitiro;

use SmartDato\BrtDocumentoDiRitiro\Allocators\DatabaseSegnacolloAllocator;
use SmartDato\BrtDocumentoDiRitiro\Contracts\SegnacolloAllocator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BrtDocumentoDiRitiroServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('brt-documento-di-ritiro')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_brt_documento_di_ritiro_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SegnacolloAllocator::class, DatabaseSegnacolloAllocator::class);
        $this->app->singleton(BrtDocumentoDiRitiro::class);
    }
}
