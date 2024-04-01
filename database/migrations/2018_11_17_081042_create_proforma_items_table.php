<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProformaItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proforma_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('package_id');
            $table->double('length');
            $table->double('width');
            $table->double('thick');
            $table->integer('pcs');
            $table->string('remark')->default('');
            $table->boolean('active')->default(true );
            $table->foreign('package_id')->references('id')->on('proforma_packages');
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
        Schema::dropIfExists('proforma_items');
    }
}
