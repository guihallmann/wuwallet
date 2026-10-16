<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticker')->unique();
            $table->string('name');
            $table->string('sector');
            $table->decimal('current_price', 15, 2);
            $table->dateTime('price_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('objective_text')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->decimal('target_percentage', 8, 2);
            $table->unsignedInteger('current_quantity')->default(0);
            $table->timestamps();
            $table->unique(['wallet_id', 'asset_id']);
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('transaction_type');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->dateTime('transaction_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallet_assets');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('assets');
    }
};
