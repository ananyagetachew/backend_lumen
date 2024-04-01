<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveredOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivered_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('original_production_order_id');
            $table->string('order_no');
            $table->string('issued_by');
            $table->string('approved_by');
            $table->string('recieved_by');
            $table->string('driver_plate_no');
            $table->string('driver_id_no');
            $table->string('driver_name');
            $table->string('company_name');
            $table->string('delivery_date');
            $table->string('fsno')->nullable();
            $table->longText('note')->nullable();
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
        Schema::dropIfExists('delivered_orders');
    }
}
