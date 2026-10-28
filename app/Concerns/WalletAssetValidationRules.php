<?php

declare(strict_types=1);

namespace App\Concerns;

trait WalletAssetValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function walletAssetRules(): array
    {
        return [
            'assets' => [
                'required', 'array', 'min:1', 'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $total = array_sum(array_map(
                        fn (array $asset): int => (int) round((float) ($asset['target_percentage'] ?? 0) * 100),
                        array_filter($value, 'is_array'),
                    ));

                    if ($total !== 10000) {
                        $fail('A soma dos percentuais dos ativos deve ser exatamente 100%.');
                    }
                },
            ],
            'assets.*.asset_id' => ['required', 'integer', 'distinct', 'exists:assets,id'],
            'assets.*.target_percentage' => ['required', 'numeric', 'gt:0', 'lte:100', 'decimal:0,2'],
        ];
    }
}
