<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ipayug_ipayug', function (Blueprint $table) {
            $table->id();

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
            $table->foreignId('payment_id')->nullable()->constrained('account_payment')->onDelete('set null');
            $table->boolean('is_processed')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipayug_ipayug');
    }
};
