<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareholdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->string('cipa_identifier')->nullable();
            $table->char('nominee', 1)->nullable();
            $table->string('appointment_date')->nullable();
            $table->datetime('ceased_date')->nullable();
            $table->unsignedBigInteger('shareholder_of_company_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_type')->nullable();
            $table->timestamps();

            /**
             *  INDEXES
             */
            $table->index('ceased_date');
            $table->index('appointment_date');
            $table->index(['owner_id', 'owner_type']);
            $table->index('shareholder_of_company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shareholders');
    }
}
