<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('uin')->nullable();
            $table->string('name')->nullable();
            $table->string('info', 500)->nullable();
            $table->string('company_status')->nullable();
            $table->boolean('exempt')->default(false);
            $table->boolean('foreign_company')->default(false);
            $table->string('company_type')->nullable();
            $table->string('company_sub_type')->nullable();

            $table->timestamp('incorporation_date')->nullable();
            $table->timestamp('re_registration_date')->nullable();
            $table->string('old_company_number')->nullable();
            $table->timestamp('dissolution_date')->nullable();

            $table->boolean('own_constitution_yn')->default(false);
            $table->string('business_sector')->nullable();

            $table->unsignedTinyInteger('annual_return_filing_month')->nullable();
            $table->timestamp('annual_return_last_filed_date')->nullable();

            $table->json('details')->nullable();
            $table->timestamp('cipa_updated_at')->nullable();
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
        Schema::dropIfExists('companies');
    }
}
