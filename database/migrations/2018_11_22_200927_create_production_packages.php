<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductionPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('original_package_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->double('unit_price');
            $table->double('bullnose')->default(0.00);
            $table->double('groove')->default(0.00);
            $table->boolean('active')->default(true );
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
        Schema::dropIfExists('production_packages');
    }
}
