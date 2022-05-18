<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brackets', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->integer('match')->nullable();
            $table->bigInteger('player_id')->unsigned()->nullable();
            $table->bigInteger('tournament_id')->unsigned();
            $table->timestamps(); 
            $table->nestedSet();

            $table->foreign('player_id')->on('players')->references('id')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('tournament_id')->on('tournaments')->references('id')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('tournaments', function(Blueprint $table){
            $table->boolean('started');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brackets');
        Schema::table('tournaments', function(Blueprint $table){
            $table->dropColumn('started');
        });
    }
};
