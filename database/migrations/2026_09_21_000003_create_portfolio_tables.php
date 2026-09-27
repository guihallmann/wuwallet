<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('tax_id', 14)->unique();
            $table->string('sector');
            $table->string('subsector')->nullable();
            $table->string('segment')->nullable();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('ticker');
            $table->string('asset_type');
            $table->decimal('current_price', 15, 2);
            $table->dateTime('price_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'ticker']);
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
        Schema::dropIfExists('companies');
    }
};
