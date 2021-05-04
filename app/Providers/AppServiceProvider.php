<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'company' => 'App\Models\Company',
            'business' => 'App\Models\Business',
            'individual' => 'App\Models\Individual'
        ]);

        /*
            To Help With Our HATEOAS (HAL) API - I include the following as suggested by Laravel
            ------------------------------------------------------------------------------------
            If you would like to disable the wrapping of the outer-most resource, you may use the
            "withoutWrapping" method on the base resource class. Typically, you should call this
            method from your AppServiceProvider or another service provider that is loaded on
            every request to your application:

            Reference: https://laravel.com/docs/5.7/eloquent-resources#concept-overview

        */
        JsonResource::withoutWrapping();
    }
}
