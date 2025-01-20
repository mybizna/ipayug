<?php
namespace Modules\Ipayug\Models;

use Illuminate\Database\Schema\Blueprint;
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
    protected $table = "ipayug_ipayug";

    public function migration(Blueprint $table): void
    {

        $table->string('item_id')->nullable();
        $table->string('status')->nullable();
        $table->string('txncd')->nullable();
        $table->string('ivm')->nullable();
        $table->string('qwh')->nullable();
        $table->string('afd')->nullable();
        $table->string('poi')->nullable();
        $table->string('uyt')->nullable();
        $table->string('ifd')->nullable();
        $table->string('agd')->nullable();
        $table->string('mc')->nullable();
        $table->string('p1')->nullable();
        $table->string('p2')->nullable();
        $table->string('p3')->nullable();
        $table->string('p4')->nullable();
        $table->unsignedBigInteger('payment_id')->nullable();
        $table->boolean('is_processed')->nullable();

    }

    public function post_migration(Blueprint $table): void
    {
        $table->foreign('payment_id')->references('id')->on('account_payment')->onDelete('set null');
    }
}
