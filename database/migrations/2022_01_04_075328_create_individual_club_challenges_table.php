<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndividualClubChallengesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individual_club_challenges', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('challenger_id')
                ->constrained('users', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table
                ->foreignId('opponent_id')
                ->constrained('users', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table
                ->foreignId('fixture_id')->nullable()
                ->constrained('fixtures', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->enum('status', ['PENDING', 'ACCEPTED']);
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
        Schema::dropIfExists('individual_challanges');
    }
}
