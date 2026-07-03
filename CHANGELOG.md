# Changelog

All notable changes to `laravel-chatbot` will be documented in this file.

## v2.0.1 - 2026-07-03

### Added
- **Service-layer test coverage** deferred from v2.0.0. `Http`/openai `ClientFake` + `Response::from` fixture-backed tests for `ChatBotService` (create / update / delete / failure paths / unmigrated-thread guard) and `RawService` (conversation create/retrieve/delete + response create). Suite: 8 → 17 tests.

## v2.0.0 - 2026-07-03

The 2.x line migrates from the **OpenAI Assistants API** (shut down on **2026-08-26**) to the **Responses + Conversations API**. This is a breaking change. See [UPGRADE.md](UPGRADE.md).

### Changed
- **Backbone**: threads/runs/messages → **Responses + Conversations API** (`openai-php/laravel ^0.20`). Responses are synchronous, so the run-polling loop is gone entirely.
- **Requirements**: PHP 8.2+, Laravel 11.29/12.12/13 (Laravel 10 dropped — openai-php ^0.20 does not support it).
- **Config**: `assistant_id`/`sleep_seconds`/`run_max_attempts` removed; added `model` (default `gpt-5.4-mini`), `instructions`, and optional `prompt_id`.
- **Thread model**: new nullable `remote_conversation_id` column; `remote_thread_id` kept as a legacy migration source (not backfilled).
- **RawService**: renamed Assistants-thread methods to Conversation methods (`createConversationAsRaw`, `conversationAsRaw`, `updateConversationAsRaw`, `deleteConversationAsRaw`, `listConversationItemsAsRaw`) and added Response raw methods (`createResponseAsRaw`, `responseAsRaw`).
- `ChatBotService`/`RawService` now resolve the shared `OpenAI\Contracts\ClientContract` singleton from the container.
- Dropped the `OpenAI-Beta: assistants=v2` header.

### Added
- `chatbot:migrate-to-conversations` command — idempotent, `--dry-run`, `--limit`; rebuilds each thread's local transcript into a new Conversation.
- `HalilCosdu\ChatBot\Exceptions\ChatBotException` — thrown on failed/incomplete/no-text responses, and when `update()` is called on a thread without a `remote_conversation_id`.
- Migration stub `add_remote_conversation_id_to_threads_table`.
- `UPGRADE.md`.
- `ThreadFactory` / `ThreadMessageFactory` for testing.
- Tests for the migration command (dry-run paths). Test suite: 5 → 8.

### Removed
- `WaitsForThreadRunCompletion` trait and `HalilCosdu\ChatBot\Exceptions\ThreadRunException` — Responses are synchronous, no run polling.
- `RawService` Assistants-thread methods (replaced — see UPGRADE).

### Out of scope (deferred to 2.1)
Streaming, background responses, tool/function loops, prompt variables, richer conversation-item storage, lazy per-thread migration. Dropping the `remote_thread_id` column is deferred to a future major.

## v1.3.1 - 2026-07-03

> **Maintenance-only release.** The 1.x line is built on the OpenAI Assistants API, which shuts down on **2026-08-26**. A **2.0** migration to the Responses + Conversations API is in development (target stable 2026-08-12). Until then, 1.x receives only critical fixes.

### Fixed
- **`WaitsForThreadRunCompletion` no longer loops forever.** The run poller is now bounded by `run_max_attempts` (default 600) and throws `HalilCosdu\ChatBot\Exceptions\ThreadRunException` on terminal failure statuses (`failed`, `cancelled`, `expired`, `incomplete`), on `requires_action` (unsupported — tool-output submission is out of scope), and on timeout. Previously a run that never reached `completed` would poll indefinitely.

### Changed
- **CI** now tests PHP 8.4 (matrix: 8.2/8.3/8.4 × Laravel 11/12/13). Note: `openai-php/laravel` does not support Laravel 10, so Laravel 10 is not in the matrix despite the broad `illuminate/contracts` constraint.
- Bumped GitHub Actions: `actions/checkout` v6, `stefanzweifel/git-auto-commit-action` v7, `dependabot/fetch-metadata` v3.0.0, `ad-m/github-push-action` v1.0.0. Supersedes dependabot #36, #38, #40, #41. The `openai-php/laravel` bump (#39) is deferred to v2.
- **PHPStan was broken** by an invalid `checkMissingIterableValueType` option; removed and baseline regenerated.
- `ChatBotService` and `RawService` now type-hint `OpenAI\Contracts\ClientContract` instead of the final concrete `Client`, so they can be substituted/faked in tests.

### Added
- Test coverage for `WaitsForThreadRunCompletion` (completed / terminal failure / `requires_action` / timeout / continued polling). 8 → 13 tests.
- New config key `run_max_attempts` (default 600).

### Documentation
- README: prominent deprecation banner pointing at the 2026-08-26 shutdown and the upcoming v2.

## v1.3.0 - 2026-04-09

### What's Changed

* Added Laravel 12 and Laravel 13 support
* Updated dev dependencies for broader version compatibility (Pest 3, PHPStan 2, Larastan 3, Orchestra Testbench 10/11)
* Updated CI workflow to test against Laravel 13, 12, 11 with PHP 8.3, 8.2
* Package now supports Laravel 10.x, 11.x, 12.x, and 13.x

## v1.2.3 - 2024-06-12

### What's Changed

* Switch from legacy to latest version of Assistants by @rashidlaasri in https://github.com/halilcosdu/laravel-chatbot/pull/18

**Full Changelog**: https://github.com/halilcosdu/laravel-chatbot/compare/v1.2.2...v1.2.3

## v1.2.2 - 2024-06-12

### What's Changed

* Update openai-php/laravel requirement from ^0.8.1 to ^0.9.1 by @dependabot in https://github.com/halilcosdu/laravel-chatbot/pull/14
* Update openai-php/laravel requirement from ^0.9.1 to ^0.10.1 by @dependabot in https://github.com/halilcosdu/laravel-chatbot/pull/15
* Update model property type by @rashidlaasri in https://github.com/halilcosdu/laravel-chatbot/pull/16

### New Contributors

* @rashidlaasri made their first contribution in https://github.com/halilcosdu/laravel-chatbot/pull/16

**Full Changelog**: https://github.com/halilcosdu/laravel-chatbot/compare/v1.2.1...v1.2.2

## v1.2.1 - 2024-05-04

### What's Changed

* V1.2.0 by @halilcosdu in https://github.com/halilcosdu/laravel-chatbot/pull/11

**Full Changelog**: https://github.com/halilcosdu/laravel-chatbot/compare/v1.2.0...v1.2.1

## v1.2.0 - 2024-05-04

### What's Changed

* Bump dependabot/fetch-metadata from 2.0.0 to 2.1.0 by @dependabot in https://github.com/halilcosdu/laravel-chatbot/pull/10

**Full Changelog**: https://github.com/halilcosdu/laravel-chatbot/compare/v1.1.0...v1.2.0

## v1.1.0 - 2024-04-27

### What's Changed

* Add raw data support for user-defined custom logic. by @halilcosdu in https://github.com/halilcosdu/laravel-chatbot/pull/8

### New Contributors

* @halilcosdu made their first contribution in https://github.com/halilcosdu/laravel-chatbot/pull/8

**Full Changelog**: https://github.com/halilcosdu/laravel-chatbot/compare/v1.0.4...v1.1.0

## v1.0.4 - 2024-04-24

Subject limited 10 words.

**Full Changelog**: https://github.com/halilcosdu/laravel-chatbot/compare/v1.0.3...v1.0.4

## v1.0.3 - 2024-04-23

OpenAI Assistant header added.

## v1.0.2 - 2024-04-22

v1.0.2, includes the following updates:

- The `ChatBotService` now has dependency injection for the `Client` class. This allows the service to use the `Client` instance that is registered in the Laravel service container, improving the structure and maintainability of the code.
  
- The `ChatBotServiceProvider` has been updated to bind the `Client` class to the Laravel service container. This ensures that whenever the `Client` class is type-hinted in the `ChatBotService`, Laravel's service container will automatically inject the instance that was registered in the service provider.
  

These changes improve the overall structure of the code and make it easier to manage dependencies within the `ChatBotService`.

## v1.0.1 - 2024-04-17

### Release Notes for v1.0.1

#### Bug Fixes

- Fixed the issue with the `$this` variable in test functions in Pest PHP. The `beforeEach` function now assigns `chatBotService` and `chatBot` to `$this`. Then, in the tests, you can use `$this->chatBotService` and `$this->chatBot` to access these instances.

#### Changes

- Updated the `ChatBotTest.php` file to use the `beforeEach` function to return an array of variables that you want to use in your tests.

#### Improvements

- Improved the test functions in `ChatBotTest.php` to use `$this->chatBotService` and `$this->chatBot` to access these instances.

#### Known Issues

- No known issues at this time.

#### Upgrade Notes

- This version is fully compatible with the previous version. You can upgrade to this version without any issues.

Please refer to the project documentation for more detailed information about this release.

## v1.0.0 - 2024-04-17

This package, `laravel-chatbot`, provides a robust and easy-to-use solution for integrating AI chatbots into your Laravel applications. Leveraging the power of OpenAI, it allows you to create, manage, and interact with chat threads directly from your Laravel application. Whether you're building a customer service chatbot or an interactive AI assistant, `laravel-chatbot` offers a streamlined, Laravel-friendly interface to the OpenAI API.
