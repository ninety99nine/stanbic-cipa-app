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
            $table->string('ownership_type')->nullable();
            $table->string('shareholder_name')->nullable();
            $table->unsignedBigInteger('shareholder_id')->nullable();
            $table->unsignedBigInteger('shareholder_of_company_id')->nullable();
            $table->unsignedBigInteger('director_id')->nullable();
            $table->char('is_director', 1)->nullable();
            $table->timestamps();
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
