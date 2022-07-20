<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('files', function (Blueprint $table) {
            $table->id()
                ->comment('Record ID');

            $table->string('name')
                ->unique()
                ->nullable(false)
                ->comment('File name. Uniquely identify the file for users');

            $table->string('md5', 32)
                ->nullable(false)
                ->comment("File's MD5. Used to identify the file in the DFS (same file with different names is stored once)");

            $table->enum('status', ['LOADED', 'STORED', 'ZIPPED', 'FAILED'])
                ->default('LOADED')
                ->nullable(false)
                ->comment('Current status for the file parsing process: LOADED | STORED | ZIPPED | FAILED');

            $table->decimal('size', 19, 0)
                ->nullable(false)
                ->comment('Current file size in Bytes');

            $table->timestamps();
        });

        DB::statement("COMMENT ON TABLE files IS 'Store meta/management information about files'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
};
