# AGENTS.md

Symfony 8.1 + API Platform 4.3 REST API. Local development uses DDEV with PHP
8.5, Nginx/FPM, and PostgreSQL 17. The public API uses a DTO-first design and
the browser dashboard uses a separate session-based firewall.

## Commands

Run application commands inside the DDEV web container. Prefix Composer and
console commands with `ddev` when running locally.

```bash
ddev composer build      # Full pipeline: prepare -> lint -> check -> test
ddev composer prepare    # composer validate --strict + auto-fix PHP CS Fixer
ddev composer lint       # Lint Symfony container and Twig templates
ddev composer check      # PHP CS Fixer check, PHPStan, and Psalm
ddev composer test       # Doctrine schema validation + Codeception
ddev composer report     # Codeception HTML report + PHP Metrics
```

Useful setup commands:

```bash
ddev start
ddev composer install
ddev console lexik:jwt:generate-keypair
ddev console doctrine:migrations:migrate
ddev console app:user:create
ddev launch
```

## Console Commands

- `app:user:create` (`src/Command/CreateUserCommand.php`) creates a user
  account interactively.
- Passwords are never passed as arguments and must be at least 12 characters.
- The `--role` option is repeatable and validates `ROLE_...` names.
- The command normalizes and validates email addresses and persists within a
  transaction.

## Authentication And Routes

There are two authentication systems. Do not describe the application as
JWT-only or assume that API and browser authentication share a session.

### API

- `POST /login_check` accepts JSON `{"username": ..., "password": ...}`.
- The `username` value is the user's email address.
- A successful login returns an RSA-signed JWT with a one-hour default TTL.
- API requests use `Authorization: Bearer <token>` and are stateless.
- Login attempts are rate-limited to five per minute.
- API operations are authorized by `security` expressions on the API resource
  attributes.
- `GET /users` requires `ROLE_ADMIN`.
- `GET /users/{uuid}` is available to the owner or `ROLE_ADMIN`.
- `GET /me` resolves the authenticated API user and has no identifier variable.
- `GET /users/{userId}/profile` is available to the profile owner or
  `ROLE_ADMIN`.

### Browser

- `/login`, `/dashboard`, and `/logout` use the stateful `main` firewall.
- `/dashboard` is protected and redirects anonymous visitors to `/login`.
- `/login` and `/logout` use CSRF protection.
- `DashboardController` renders Twig templates; it is not an API resource
  controller.
- The `/logout` controller method is intercepted by the firewall and should
  not be called directly.

Relevant configuration is in `config/packages/security.yaml`,
`config/routes/security.yaml`, and `src/Controller/DashboardController.php`.

## API Platform Patterns

- Keep public API DTOs in `src/ApiResource/`; keep Doctrine entities in
  `src/Entity/`.
- API DTOs are normally `final readonly` classes using constructor promotion.
- DTO UUID identifiers use `#[ApiProperty(identifier: true)]`.
- Put custom API Platform state providers and processors in `src/State/`.
- Use API Platform 4 state terminology: providers/processors, not data
  providers.
- Wire the Doctrine model with `stateOptions: new Options(entityClass: ...)`
  and set a custom provider when entity-to-DTO behavior is not automatic.
- `UserProvider` handles user collection pagination, `/me`, UUID lookup, and
  entity-to-DTO conversion.
- `UserProfileProvider` handles the linked profile path and reuses
  `UserProvider::toResource()` for nested user output.
- `CanonicalUserIriConverter` decorates `api_platform.iri_converter` so `/me`
  responses use the canonical `/users/{uuid}` IRI.
- API resources are currently read-only. If adding mutations, keep write logic
  in an API Platform processor rather than a controller.
- Keep passwords and other persistence-only fields off public DTOs.
- Put operation authorization on the `#[ApiResource]` operation metadata, not
  only in a provider. Providers load data; security expressions decide access.

## Project Structure

```text
.ddev/          DDEV and Docker configuration
.github/        GitHub Actions workflow
config/         Symfony, routing, security, service, and bundle configuration
migrations/     Doctrine migrations
public/         HTTP document root and front controller
src/
  ApiResource/  API Platform DTOs and operation metadata
  Command/      Symfony console commands
  Controller/   Attribute-routed browser controllers
  DataFixtures/ Doctrine fixtures; currently empty
  Entity/       Doctrine entities and shared traits
  Repository/   Repository implementations and interfaces
  State/        API Platform providers and IRI customization
tests/
  Unit/         Isolated component tests
  Functional/   Symfony kernel, HTTP, Doctrine, security, and serialization
  Acceptance/   PhpBrowser tests against https://api.ddev.site
```

Symfony discovers application services from `src/` through
`config/services.yaml` with autowiring and autoconfiguration. Explicit service
decoration or other special wiring belongs in that file.

## Persistence And Database

- Development: PostgreSQL 17 through DDEV.
- Tests: SQLite at `data/database_test.sqlite`.
- Doctrine entity mappings use PHP attributes under `src/Entity/`.
- UUIDs are used as entity and API identifiers.
- `User` and `UserProfile` use `TimestampableTrait` for `createdAt` and
  `updatedAt`.
- Stof Doctrine Extensions supplies timestamp behavior.
- Repository interfaces are injected into providers and commands to keep
  application code independent of concrete Doctrine repositories.
- Migrations may require separate PostgreSQL and SQLite SQL paths. Check both
  databases after schema changes.
- `AppFixtures` currently contains no seed data; functional tests create their
  own users and profiles.

Run migrations with:

```bash
ddev console doctrine:migrations:migrate
```

## Request Flow

For an API request such as `GET /users/{uuid}`:

1. `public/index.php` creates `App\Kernel`.
2. Symfony security validates the JWT and loads `App\Entity\User`.
3. API Platform selects the operation declared in `src/ApiResource/User.php`.
4. The operation security expression checks role or ownership.
5. `UserProvider` queries `UserRepositoryInterface`.
6. The provider maps the entity to the public `User` DTO.
7. API Platform serializes that DTO using the negotiated response format.

For `/me`, the provider reads `Security::getUser()` and ignores identifiers in
the path or query string. The custom IRI converter changes self-links from the
operation path `/me` to the canonical `/users/{uuid}` path.

For `/users/{userId}/profile`, the profile provider loads the linked user and
profile, maps the nested user through `UserProvider`, and returns a profile
DTO. Missing users and profiles resolve to 404 responses.

## Testing

The project uses Codeception, not direct PHPUnit test execution, for its three
test suites:

- `Unit/` uses only assertions for isolated behavior.
- `Functional/` boots the Symfony test kernel, uses Doctrine, and cleans the
  database between tests.
- `Acceptance/` uses PhpBrowser against the live DDEV URL and requires DDEV.

```bash
ddev exec vendor/bin/codecept run Unit
ddev exec vendor/bin/codecept run Functional
ddev exec vendor/bin/codecept run Acceptance
ddev exec vendor/bin/codecept run Functional tests/Functional/SomeTestCest.php
```

`tests/bootstrap.php` forces the test environment, uses SQLite, and generates
ephemeral RSA keys in `var/test-jwt/`. Codeception shuffles tests by default,
so tests must not depend on execution order. CI runs Unit and Functional with
`--fail-fast`; Acceptance is excluded because the workflow does not start
DDEV.

Read these tests for examples:

- `tests/Functional/AuthenticationCest.php` for JWT login.
- `tests/Functional/UserResourceCest.php` for ownership, `/me`, and pagination.
- `tests/Functional/UserProfileResourceCest.php` for subresource behavior.
- `tests/Functional/ApiContractCest.php` for formats, CORS, OpenAPI, and
  response shape.
- `tests/Functional/DashboardCest.php` for session login and logout.

## Static Analysis And Style

- PHPStan runs at level max against `src/` only.
- Psalm runs at error level 1, finds unused code, and ignores
  `src/DataFixtures/`.
- PHP CS Fixer uses Symfony rules, strict comparisons, strict parameters, and
  risky rules.
- PHP CS Fixer excludes `config/`, `var/`, `public/bundles`, `public/build`,
  `tests/Support/`, `vendor/`, `assets/`, `public/index.php`, and
  `importmap.php`.

## CI

`.github/workflows/symfony.yml` runs on pushes to `master` and `develop` and
on pull requests targeting `master`. It uses PHP 8.5, installs dependencies
with `composer update`, creates the SQLite test database, runs `composer
check`, and executes:

```bash
vendor/bin/codecept run --fail-fast Unit Functional
```

## Contributor Guidance

For a new read-only API resource:

1. Add the Doctrine entity and validation rules in `src/Entity/`.
2. Add a repository and interface in `src/Repository/`.
3. Add the public DTO and operation metadata in `src/ApiResource/`.
4. Add a provider in `src/State/` when the default provider cannot produce
   the required DTO or query behavior.
5. Add and inspect the Doctrine migration in `migrations/`.
6. Add unit and functional contract tests.

For API contract changes, update the resource metadata and functional tests.
For schema changes, verify both PostgreSQL and SQLite migrations. For browser
changes, update the controller, Twig template, session security behavior, and
`DashboardCest` coverage.

Do not add API controllers for behavior that belongs in API Platform resources,
providers, or processors. Do not expose entities directly when a public DTO is
the established pattern.

## Generated And Local Files

Do not commit:

- `.env.local` or other local environment overrides
- `config/jwt/*.pem`
- `var/`
- `vendor/`
- `build/`
- `tests/Support/_generated/`
- `tests/_output/`
- `public/assets/` and `assets/vendor/`
