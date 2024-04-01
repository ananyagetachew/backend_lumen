<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string("order_no");
            // deliveries that are converted from a proforma should preserve the proforma no, so proforma_no is optional here
            $table->string("proforma_no")->nullable();
            $table->string('company_name');
            $table->string('delivery_date');
            $table->string('fsno')->nullable();
            $table->longText('note')->nullable();
            $table->boolean('sent_to_production')->default(false);
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
        Schema::dropIfExists('delivery_orders');
    }
}
