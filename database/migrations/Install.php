<?php

use CraftCms\Cms\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('oopspam')) {
            Schema::create('oopspam', function (Blueprint $table) {
                $table->integer('id', true);
                $table->tinyText('type')->nullable();
                $table->tinyText('endpoint')->nullable();
                $table->json('params')->nullable();
                $table->json('response')->nullable();
                $table->tinyInteger('isValid')->default(0);
                $table->tinyInteger('isSpam')->default(0);
                $table->tinyInteger('isReport')->default(0);
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
                $table->index(['dateCreated', 'id']);
            });
        }

        if (! Schema::hasTable('oopspam_usage')) {
            Schema::create('oopspam_usage', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('limit')->nullable();
                $table->integer('remaining')->nullable();
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
            });
        }

        if (! DB::table('oopspam_usage')->where('id', 1)->exists()) {
            DB::table('oopspam_usage')->insert([
                'id' => 1,
                'limit' => 0,
                'remaining' => 0,
                'dateCreated' => now(),
                'dateUpdated' => now(),
                'uid' => (string) Str::uuid(),
            ]);
        }

        if (! Schema::hasTable('oopspam_submissions')) {
            Schema::create('oopspam_submissions', function (Blueprint $table) {
                $table->binary('ipaddress', 16);
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oopspam_submissions');
        Schema::dropIfExists('oopspam_usage');
        Schema::dropIfExists('oopspam');
    }
};