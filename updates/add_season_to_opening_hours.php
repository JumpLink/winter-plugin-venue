<?php namespace JumpLink\Venue\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddSeasonToOpeningHours extends Migration
{
    public function up()
    {
        Schema::table('jumplink_venue_opening_hours', function ($table) {
            $table->string('season')->nullable()->after('description');
            $table->string('period_text')->nullable()->after('season');
        });
    }

    public function down()
    {
        Schema::table('jumplink_venue_opening_hours', function ($table) {
            $table->dropColumn(['season', 'period_text']);
        });
    }
}
