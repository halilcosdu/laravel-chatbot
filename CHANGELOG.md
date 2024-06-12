# Changelog

All notable changes to `laravel-chatbot` will be documented in this file.

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
