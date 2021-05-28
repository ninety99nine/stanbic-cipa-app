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
            $table->json('multiple_uins')->nullable();
            $table->string('info', 500)->nullable();
            $table->string('company_status')->nullable();
            $table->char('exempt', 1)->nullable();
            $table->char('foreign_company', 1)->nullable();
            $table->string('company_type')->nullable();
            $table->string('company_sub_type')->nullable();

            /**
             *  Note that we use datetime() instead of timestamp()
             *  because timestamp only supports dates after 1970,
             *  but in our case we might get dates before that
             *  time.
             */
            $table->datetime('incorporation_date')->nullable();
            $table->datetime('re_registration_date')->nullable();
            $table->string('old_company_number')->nullable();
            $table->datetime('dissolution_date')->nullable();

            $table->char('own_constitution_yn', 1)->nullable();
            $table->string('business_sector')->nullable();

            $table->unsignedTinyInteger('annual_return_filing_month')->nullable();
            $table->datetime('annual_return_last_filed_date')->nullable();

            $table->boolean('marked_as_client')->default(0);

            $table->json('details')->nullable();
            $table->timestamp('cipa_updated_at')->nullable();
            $table->timestamps();

            /**
             *  INDEXES
             *
             *  The unique('uin') is required to allow bulk upserts without duplication when
             *  importing an excel sheet of uin's. Refer to the following documentation.
             *
             *  Link: https://docs.laravel-excel.com/3.1/imports/model.html
             *
             *  We decided to also use the unique('name') incase we have to allow bulk import
             *  using a company name instead of the company uin.
             */
            $table->unique('uin');
            $table->unique('name');
            $table->index('company_status');
            $table->index('exempt');
            $table->index('foreign_company');
            $table->index('company_type');
            $table->index('company_sub_type');
            $table->index('old_company_number');
            $table->index('own_constitution_yn');
            $table->index('business_sector');
            $table->index('annual_return_filing_month');
            $table->index('annual_return_last_filed_date');
            $table->index('marked_as_client');

            $table->index('incorporation_date');
            $table->index('re_registration_date');
            $table->index('dissolution_date');
            $table->index('cipa_updated_at');

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
