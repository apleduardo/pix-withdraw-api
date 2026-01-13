<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        // Insert default account for application startup (not used in integration tests)
        \Hyperf\DbConnection\Db::table('account')->insert([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Default App Account',
            'balance' => 1000.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account');
    }
};
