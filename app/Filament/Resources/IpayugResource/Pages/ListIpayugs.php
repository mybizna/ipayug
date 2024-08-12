<?php

namespace Modules\Ipayug\Filament\Resources\IpayugResource\Pages;

use Modules\Ipayug\Filament\Resources\IpayugResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIpayugs extends ListRecords
{
    protected static string $resource = IpayugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
