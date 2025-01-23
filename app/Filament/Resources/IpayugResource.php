<?php

namespace Modules\Ipayug\Filament\Resources;

use Modules\Base\Filament\Resources\BaseResource;
use Modules\Ipayug\Models\Ipayug;

class IpayugResource extends BaseResource
{
    protected static ?string $model = Ipayug::class;

    protected static ?string $slug = 'ipayug/ipayug';

    protected static ?string $navigationGroup = 'Account';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationParentItem = 'Gateway';

}
