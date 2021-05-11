<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOwnershipBundlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ownership_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('cipa_identifier')->nullable();
            $table->decimal('percentage_of_shares', 5, 2)->nullable();
            $table->unsignedBigInteger('number_of_shares')->nullable();
            $table->unsignedBigInteger('total_shares')->nullable();
            $table->unsignedTinyInteger('total_shareholder_occurances')->nullable();
            $table->boolean('is_shareholder_to_self')->default(0);
            $table->string('cipa_ownership_type')->nullable();
            $table->string('shareholder_name')->nullable();
            $table->unsignedBigInteger('shareholder_id')->nullable();
            $table->unsignedBigInteger('shareholder_of_company_id')->nullable();
            $table->unsignedBigInteger('director_id')->nullable();
            $table->timestamps();

            /**
             *  INDEXES
             */
            $table->index('percentage_of_shares');
            $table->index('total_shareholder_occurances');
            $table->index('is_shareholder_to_self');
            $table->index('shareholder_name');
            $table->index('shareholder_id');
            $table->index('shareholder_of_company_id');
            $table->index('director_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ownership_bundles');
    }
}
