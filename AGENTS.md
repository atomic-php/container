# Repository Guidelines

## Project Structure & Modules

- `src/` — Library source (`Atomic\\Container` namespace). One class per file.
- `tests/` — PHPUnit tests (`*Test.php`) and stubs.
- `benchmarks/` — Microbenchmarks and runner script.
- `vendor/` — Composer dependencies (do not edit).

Example: `Atomic\\Container\\ContainerBuilder` lives at `src/ContainerBuilder.php`; related tests at `tests/Unit/ContainerBuilderTest.php`.

## Build, Test, and Development

- Install deps: `composer install`
- Run tests: `composer test` (PHPUnit)
- Static analysis: `composer psalm`
- Style check: `composer cs-check`
- Auto-fix style: `composer cs-fix`
- Benchmarks: `composer benchmark`

CI runs tests, Psalm, and CS checks on PHP 8.4.

## Coding Style & Naming

- Standard: PSR-12 (enforced via PHP-CS-Fixer).
- Rules: short arrays, ordered imports, no unused imports, trailing commas.
- Indentation: 4 spaces; UTF-8; `declare(strict_types=1);` at top.
- Names: Classes StudlyCaps, methods camelCase, constants UPPER_SNAKE.
- Files: One class per file, filename matches class.
- Namespaces: `Atomic\\Container\\...` under `src/`.

## Testing Guidelines

- Framework: PHPUnit 10. Place tests in `tests/` ending with `Test.php`.
- Use mocks where appropriate (`$this->createMock(...)`).
- Keep tests deterministic and fast; favor small, focused cases.
- Run locally with `composer test`; ensure green before pushing.

## Commit & Pull Requests

- Commits: short imperative subject (e.g., "Add alias resolution in container").
- Scope commits logically; keep diffs minimal and relevant.
- PRs must include: clear description, rationale, before/after behavior, and linked issues.
- Requirements: tests updated/added, Psalm clean, CS clean, CI passing.
- Performance-impacting changes: include benchmark results (`composer benchmark`).

## Additional Notes

- PHP: ^8.4 required. Follow PSR-11 contracts; avoid introducing runtime deps.
- Public API stability: avoid breaking changes; discuss in an issue first.
