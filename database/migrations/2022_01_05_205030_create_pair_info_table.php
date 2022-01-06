<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePairInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pair_info', function (Blueprint $table) {
            $table->id();
            $table->string('ticker', 100)->nullable();
            $table->double('min_qty', 15, 8)->nullable();
            $table->double('min_amount', 15, 8)->nullable();
            $table->integer('n_decimals')->nullable()->default(0);
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
        Schema::dropIfExists('pair_info');
    }
}
