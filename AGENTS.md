# Repository Guidelines

## Project Structure & Module Organization

This repository contains three applications:

- `PropifyFrontend/`: customer-facing Vue 3 + Vite app. Use `src/pages/`, `src/components/`, `src/stores/`, `src/services/`, and `src/assets/`.
- `PropifyAdmin/`: admin Vue 3 + Vite app with the same broad `src/` split plus `composables/`.
- `PropifyBackend/`: Laravel 12 API. Core code is in `app/`, routes in `routes/`, database work in `database/`, and tests in `tests/Feature/` and `tests/Unit/`.

Repository-level notes and deployment helpers live beside these apps, including `architecture/`, `docker-compose.yml`, and `deploy.sh`.

## Build, Test, and Development Commands

Run commands from the relevant app directory:

- Frontend/Admin: `npm run dev` starts Vite locally; `npm run build` creates a production bundle; `npm run preview` serves the built bundle.
- Backend: `composer run setup` installs dependencies, prepares `.env`, migrates, and builds assets; `composer run dev` starts the local Laravel stack; `composer run test` runs the Laravel suite.
- Backend formatting: `vendor/bin/pint` applies Laravel Pint formatting.

Use `docker-compose.yml` when you need the full local stack.

## Coding Style & Naming Conventions

Follow `PropifyBackend/.editorconfig`: UTF-8, LF endings, four-space indentation, trimmed trailing whitespace, and final newlines; YAML uses two spaces. PHP classes use PascalCase (`CreateListingDto.php`), tests use `*Test.php`, Vue components use PascalCase (`PageHeader.vue`), and composables use `useXxx.js`.

Keep page orchestration in `pages/`, reusable UI in `components/`, API calls in `services/`, and shared state in `stores/`.

## Testing Guidelines

Backend tests use PHPUnit through Laravel's runner. Add feature tests under `PropifyBackend/tests/Feature/` and unit tests under `tests/Unit/`; name files after the subject, for example `AuthControllerTest.php`. Run `composer run test` before opening a backend PR.

No frontend test runner is currently configured, so verify UI changes manually with `npm run dev`.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit prefixes such as `feat:`. Keep commits concise and scoped, for example `feat: add package expiry notifications` or `fix: handle empty listing images`.

Pull requests should include a short summary, affected app(s), linked issue when applicable, and validation notes. Add screenshots for UI changes and call out migrations, environment updates, or API contract changes.

## Agent-Specific Notes

When using shell commands in this repository, prefer the provided RTK wrappers where available, such as `rtk git status`, `rtk git diff`, and `rtk read <file>`.

## Git Commit Policy

Codex must not create commits unless I explicitly request it.

Do not run these commands automatically:

```bash
git commit
rtk git commit
git push
rtk git push
```
