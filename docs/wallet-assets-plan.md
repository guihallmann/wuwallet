# Hand-off: Wallet creation/editing with assets and target percentages

## Goal

Wallet create and edit must require an asset allocation: one or more assets, each with a target percentage. The total must be exactly 100.00%. The allocation is stored in `wallet_assets` so the future suggestion algorithm can read it.

## Decisions

- Asset picker: combobox with server-side search (the `assets` table can hold thousands of tickers).
- Sector filter: a select next to the search narrows results by `assets.sector`. The sector list comes from distinct `sector` values, passed as a page prop.
- Precision: exactly 100.00, at most 2 decimals, matching `DECIMAL(8,2)`. Compare in integer hundredths to avoid float errors.
- Scope: create and edit, plus an allocation summary on the wallet list.
- Removing an asset on edit is rejected when its `current_quantity` > 0.

## Existing context

- [app/Models/WalletAsset.php](../app/Models/WalletAsset.php): `target_percentage` is `DECIMAL(8,2)`, unique index on `wallet_id, asset_id`. No migration needed.
- [app/Http/Controllers/WalletController.php](../app/Http/Controllers/WalletController.php): `store`, `edit`, `update`, `mapWallets`. `index` and `clientWallets` already eager-load `assets.asset`.
- [app/Http/Requests/StoreWalletRequest.php](../app/Http/Requests/StoreWalletRequest.php) and [UpdateWalletRequest.php](../app/Http/Requests/UpdateWalletRequest.php): only `name` and `objective_text` today.
- [app/Policies/WalletPolicy.php](../app/Policies/WalletPolicy.php): only clients can create, update or delete wallets.
- [app/Concerns/PasswordValidationRules.php](../app/Concerns/PasswordValidationRules.php): pattern to follow for a rules concern.
- Frontend: [resources/js/pages/wallets/](../resources/js/pages/wallets/) uses Inertia `<Form>` with Wayfinder `WalletController.store.form()`. No popover or combobox component exists; `@radix-ui/react-popover` and `cmdk` are not installed.
- Tests: [tests/Feature/WalletManagementTest.php](../tests/Feature/WalletManagementTest.php) has helpers `manager()`, `analystFor()`, `clientFor()`. Assets are created with `Asset::query()->create(...)`.

## Phase 1: Backend

1. No migration.
2. Add `AssetController@index`, a JSON search.
   - Filters by `q` on ticker or name, max 20 rows, ordered by ticker.
   - Optional `sector` param: exact match on `assets.sector`, combinable with `q`. With a sector and an empty `q`, it returns the first 20 assets of that sector.
   - Validate inputs: `q` nullable string max 50, `sector` nullable string max 100.
   - Returns `id`, `ticker`, `name`, `sector`, `current_price`.
   - Route in [routes/wallets.php](../routes/wallets.php) under `auth`, name `assets.search`, client-only.
3. Add `app/Concerns/WalletAssetValidationRules.php`.
   - `assets`: required, array, min 1, max 50.
   - `assets.*.asset_id`: required, `distinct`, `exists:assets,id`.
   - `assets.*.target_percentage`: required, numeric, `gt:0`, `lte:100`, `decimal:0,2`.
   - After-validation hook: convert each percentage to integer hundredths; the sum must equal 10000, otherwise add an error on `assets`.
4. Use the concern in `StoreWalletRequest` and `UpdateWalletRequest`.
5. `WalletController::store`: `DB::transaction` that creates the wallet, then its `WalletAsset` rows.
6. `WalletController::update`: `DB::transaction`.
   - Update wallet fields.
   - Update existing assets, create new ones, delete removed ones. Preserve `current_quantity` for kept assets.
   - Reject removing an asset with `current_quantity` > 0 with a validation error.
7. `WalletController::edit`: pass `wallet.assets` as `{asset_id, ticker, name, target_percentage}` rows (eager load `assets.asset`).
   - `create` and `edit` also pass `sectors`: sorted distinct `assets.sector` values. Use `Inertia::once()` or a short cache, since it only changes on asset sync.
8. `mapWallets`: add `allocation` (`ticker`, `name`, `target_percentage`, highest first).
9. Optional: `AssetFactory` and `WalletAssetFactory`.

## Phase 2: Frontend

Depends on step 2 for the search endpoint.

10. Add `resources/js/components/asset-allocation-field.tsx`.
    - Search input with debounced fetch to the assets search endpoint and a results dropdown (add `@radix-ui/react-popover` or build with the existing `Input`).
    - Sector select ("All sectors" by default) built from the `sectors` prop. Changing it refetches with `sector` and the current `q`.
    - Show each result's sector in the dropdown.
    - One row per selected asset: ticker, name, percentage `Input`, remove button.
    - Running total with remaining/excess; destructive style unless exactly 100.00.
    - "Distribute equally" button; the last asset takes the rounding remainder.
11. Submit as nested fields `assets[i][asset_id]` and `assets[i][target_percentage]` inside the Inertia `<Form>`. Show errors with `InputError` (`errors.assets` and `assets.i.target_percentage` keys).
12. Use the field in [create.tsx](../resources/js/pages/wallets/create.tsx) and [edit.tsx](../resources/js/pages/wallets/edit.tsx). Edit is pre-filled from props. Disable submit until the total is 100.00; the backend stays authoritative.
13. [wallets/index.tsx](../resources/js/pages/wallets/index.tsx): show the allocation as compact ticker-and-percent badges in both `manage` and `view` modes.
14. Regenerate the Wayfinder files for the new route and changed props, then run `types:check`.

## Phase 3: Tests

Add to `WalletManagementTest.php` or a new feature test.

- Valid create with 2 or more assets creates the wallet and rows with the right percentages.
- Create fails when: sum is 99.99 or 100.01; asset list is empty; asset is duplicated; percentage is 0, negative or has more than 2 decimals; asset id does not exist.
- Create with 33.33 + 33.33 + 33.34 passes.
- No wallet is created when the assets are invalid (rollback).
- Update: kept assets keep `current_quantity`, new ones are created, removed ones are deleted.
- Update refuses to remove an asset with `current_quantity` > 0.
- Analysts and managers get 403 on the search endpoint and on create and update.
- Search endpoint returns filtered results and respects the limit.
- Search by `sector` returns only that sector, works alone and combined with `q`.
- Create and edit pages receive the distinct, sorted `sectors` prop.

## Verification

1. `sail artisan test` for the wallet and asset tests.
2. `vendor/bin/phpstan` and `vendor/bin/pint --test`.
3. `npm run types:check` and `npm run check`.
4. Manual check in the browser: create a wallet with 3 assets summing to 100, edit it to add and remove an asset, confirm the allocation shows on the list.

## Out of scope

- The suggestion algorithm.
- Creating transactions or updating `current_quantity`.
- Asset CRUD pages.
- An analyst-side allocation editor.
