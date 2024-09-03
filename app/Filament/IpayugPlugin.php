<?php

namespace Modules\Ipayug\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class IpayugPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Ipayug';
    }

    public function getId(): string
    {
        return 'ipayug';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
