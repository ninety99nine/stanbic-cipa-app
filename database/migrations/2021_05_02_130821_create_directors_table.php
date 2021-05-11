<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('directors', function (Blueprint $table) {
            $table->id();
            $table->string('cipa_identifier')->nullable();
            $table->unsignedBigInteger('individual_id')->nullable();
            $table->string('appointment_date')->nullable();
            $table->datetime('ceased_date')->nullable();
            $table->unsignedBigInteger('director_of_company_id')->nullable();
            $table->timestamps();

            /**
             *  INDEXES
             */
            $table->index('individual_id');
            $table->index('appointment_date');
            $table->index('ceased_date');
            $table->index('director_of_company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('directors');
    }
}
