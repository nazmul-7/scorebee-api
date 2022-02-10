<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournamentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('tournament_name',191);
            $table->string('tournament_banner',191)->nullable();
            $table->string('tournament_logo',191)->nullable();
            $table->string('tournament_category',20)->comment('OPEN, CORPORATE, COMMUNITY, SCHOOL, OTHER, BOX CRICKET, SERIES');
            $table->string('tournament_type',20)->comment('SUPER LEAGUE, LEAGUE MATCHES, IPL SYSTEM, KNOCK OUT');

            $table->string('match_type',20)->comment('TEST MATCH, LIMITED OVERS');
            $table->unsignedTinyInteger('test_match_duration')->default(0);
            $table->unsignedTinyInteger('test_match_session')->default(0);
            $table->string('ball_type',20)->comment('TENNIS, LEATHER, OTHERS');

            $table->string('city',191);
            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_start')->default(0);
            $table->boolean('is_finished')->default(0);
            $table->boolean('is_verified_player')->default(0)->comment('only verified user approved for tournament');
            $table->boolean('is_whatsapp')->default(0)->comment('allow users to contact on whatsapp');

            $table->text('details',1000)->nullable();
            $table->text('tags',1000)->nullable();

            $table->unsignedTinyInteger('total_groups')->default(0);
            $table->unsignedTinyInteger('group_winners')->default(0);
            $table->enum('third_position', ['YES', 'NO'])->default('NO');
            $table->enum('league_format', ['IPL', 'SUPER LEAGUE', 'GROUP LEAGUE'])->nullable();
            $table->text('group_settings')->nullable();

            $table->foreignId('organizer_id');
            $table->string('organizer_name',191);
            $table->string('organizer_phone',20)->nullable();

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
        Schema::dropIfExists('tournaments');
    }
}
