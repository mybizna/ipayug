<?php

namespace Modules\Ipayug\Models;

use Modules\Base\Models\BaseModel;

class Ipayug extends BaseModel
{
    /**
     * The fields that can be filled
     *
     * @var array<string>
     */
    protected $fillable = ['item_id', 'status', 'txncd', 'ivm', 'qwh', 'afd', 'poi', 'uyt', 'ifd', 'agd', 'mc', 'p1', 'p2', 'p3', 'p4', 'payment_id', 'is_processed'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "ipayug";

}
