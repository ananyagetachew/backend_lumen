<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveredItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivered_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('original_production_item_id');
            $table->unsignedInteger('package_id');
            $table->double('length');
            $table->double('width');
            $table->double('thick');
            $table->integer('pcs');
            $table->string('remark')->default('');
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('delivered_items');
    }
}
