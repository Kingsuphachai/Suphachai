<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
public function up(): void
{
    // หาและลบ FK เดิม (ถ้ามี) โดยไม่พังถ้าไม่พบชื่อเดิม
    $dbName = DB::connection()->getDatabaseName();
    $constraint = DB::selectOne(
        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'station_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1",
        [$dbName]
    );

    if ($constraint && isset($constraint->CONSTRAINT_NAME)) {
        $name = $constraint->CONSTRAINT_NAME;
        try {
            DB::statement("ALTER TABLE `reports` DROP FOREIGN KEY `{$name}`");
        } catch (\Throwable $e) {
            // ignore if already dropped
        }
    }

    Schema::table('reports', function (Blueprint $table) {
        $table->foreign('station_id')
              ->references('id')->on('charging_stations')
              ->cascadeOnDelete();
    });
}

public function down(): void
{
    // ลบ FK แบบ cascade ถ้ามี แล้วสร้างใหม่แบบไม่ cascade
    $dbName = DB::connection()->getDatabaseName();
    $constraint = DB::selectOne(
        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'station_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1",
        [$dbName]
    );

    if ($constraint && isset($constraint->CONSTRAINT_NAME)) {
        $name = $constraint->CONSTRAINT_NAME;
        try {
            DB::statement("ALTER TABLE `reports` DROP FOREIGN KEY `{$name}`");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    Schema::table('reports', function (Blueprint $table) {
        $table->foreign('station_id')
              ->references('id')->on('charging_stations');
    });
}

};
