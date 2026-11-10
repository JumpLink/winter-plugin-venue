<?php namespace JumpLink\Venue\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateTables extends Migration
{
    public function up()
    {
        Schema::create('jumplink_venue_menus', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->text('description');
            $table->date('valid_from')->nullable();
            $table->date('valid_through')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->mediumText('sections')->nullable();
            $table->timestamps();
        });

        Schema::create('jumplink_venue_opening_hours', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('kind')->default('regular');
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->text('days')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('opens', 8)->nullable();
            $table->string('closes', 8)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_through')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('jumplink_venue_faqs', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('question');
            $table->text('answer');
            $table->boolean('assistant_may_say')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jumplink_venue_faqs');
        Schema::dropIfExists('jumplink_venue_opening_hours');
        Schema::dropIfExists('jumplink_venue_menus');
    }
}
