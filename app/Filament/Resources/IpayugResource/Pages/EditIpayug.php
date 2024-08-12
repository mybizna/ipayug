<?php

namespace Modules\Ipayug\Filament\Resources\IpayugResource\Pages;

use Modules\Ipayug\Filament\Resources\IpayugResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIpayug extends EditRecord
{
    protected static string $resource = IpayugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
