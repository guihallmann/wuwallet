<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'company_id') || ! Schema::hasColumn('assets', 'asset_type')) {
            return;
        }

        if (! Schema::hasTable('companies')) {
            throw new RuntimeException('Cannot migrate legacy assets because the companies table is missing.');
        }

        $hasDuplicateTickers = DB::table('assets')
            ->select('ticker')
            ->groupBy('ticker')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateTickers) {
            throw new RuntimeException('Cannot make asset tickers unique while duplicate tickers exist.');
        }

        Schema::table('assets', function (Blueprint $table): void {
            $table->string('name')->nullable();
            $table->string('sector')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE assets
            SET name = COALESCE(
                    (SELECT name FROM companies WHERE companies.id = assets.company_id),
                    assets.ticker
                ),
                sector = COALESCE(
                    (SELECT subsector FROM companies WHERE companies.id = assets.company_id),
                    (SELECT sector FROM companies WHERE companies.id = assets.company_id),
                    'Sem classificação'
                )
            SQL);

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->change();
            $table->string('asset_type')->nullable()->change();
            $table->string('name')->nullable(false)->change();
            $table->string('sector')->nullable(false)->change();
            $table->unique('ticker');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('assets', 'company_id') || ! Schema::hasColumn('assets', 'name')) {
            return;
        }

        if (! Schema::hasTable('companies')) {
            throw new RuntimeException('Cannot restore the legacy asset schema because the companies table is missing.');
        }

        $unlinkedAssets = DB::table('assets')
            ->whereNull('company_id')
            ->get(['id', 'ticker', 'name', 'sector']);

        foreach ($unlinkedAssets as $asset) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => $asset->name,
                'code' => 'CATALOG-'.$asset->id,
                'sector' => $asset->sector,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('assets')
                ->where('id', $asset->id)
                ->update(['company_id' => $companyId]);
        }

        DB::table('assets')
            ->whereNull('asset_type')
            ->update(['asset_type' => 'stock']);

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropUnique('assets_ticker_unique');
            $table->dropColumn(['name', 'sector']);
            $table->foreignId('company_id')->nullable(false)->change();
            $table->string('asset_type')->nullable(false)->change();
        });
    }
};
