<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('cipa_identifier')->nullable();
            $table->string('type')->nullable();
            $table->string('care_of')->nullable();
            $table->string('line_1')->nullable();
            $table->string('line_2')->nullable();
            $table->string('post_code')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_type')->nullable();
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
        Schema::dropIfExists('addresses');
    }
}
