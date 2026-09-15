# RESTful Web API Framework

[![Symfony](https://github.com/olecarl/api/actions/workflows/symfony.yml/badge.svg?branch=master)](https://github.com/olecarl/api/actions/workflows/symfony.yml)

A Symfony 8 REST API built with API Platform 4. The application exposes user,
user-profile, and schema.org-based music resources, authenticates API clients
with JWTs, and includes a small session-based browser dashboard for human
users. Local development runs
inside DDEV and uses PostgreSQL; automated tests use SQLite.

## Big Picture

The API follows a DTO-first design:

- API contracts are defined by readonly DTOs in `src/ApiResource/`.
- Database models and Doctrine mappings live in `src/Entity/`.
- API Platform state providers in `src/State/` load entities and map them to
  public DTOs.
- Repositories in `src/Repository/` own persistence queries.
- API operation authorization is declared on the resource metadata.

The application has two independent authentication flows:

- API requests use stateless JWT authentication with
  `Authorization: Bearer <token>`.
- `/login`, `/dashboard`, and `/logout` use Symfony's session-based form login
  firewall for the browser UI.

The HTTP entry point is `public/index.php`, which boots `App\Kernel`. Symfony
and API Platform then combine route configuration, resource attributes, state
providers, Doctrine, security, and serialization to handle each request.

## Stack

| Category | Technology |
|----------|------------|
| Language | PHP 8.5 |
| Framework | Symfony 8.1, API Platform 4.3 |
| Database | PostgreSQL 17 in development, SQLite in tests |
| ORM | Doctrine ORM 3.6 |
| Authentication | LexikJWTAuthenticationBundle and Symfony Security |
| Testing | Codeception 5.3, PHPUnit 12.5 |
| Static analysis | PHPStan level max, Psalm error level 1 |
| Code style | PHP CS Fixer 3 |
| Local environment | DDEV and Docker |

## Requirements

- [Docker](https://docs.docker.com/get-started/get-docker/)
- [DDEV](https://ddev.com)

DDEV is configured in `.ddev/config.yaml` with PHP 8.5, an Nginx/FPM
webserver, PostgreSQL 17, and the `api.ddev.site` hostname.

## Installation

```bash
# Clone the repository
git clone https://github.com/olecarl/api
cd api

# Start the PHP and PostgreSQL containers
ddev start

# Install dependencies inside the web container
ddev composer install
```

Create or update the uncommitted `.env.local` file with local values for
`APP_SECRET` and `JWT_PASSPHRASE`. Do not commit local secrets. The committed
`.env.dev` file selects PostgreSQL for the DDEV environment.

Generate the JWT key pair and apply the database migrations:

```bash
ddev console lexik:jwt:generate-keypair
ddev console doctrine:migrations:migrate
```

Create an account interactively:

```bash
ddev console app:user:create

# Assign a role; repeat the option when more than one role is needed
ddev console app:user:create --role=ROLE_ADMIN
```

The command requires an interactive terminal and a password of at least 12
characters. The password is never accepted as a command-line argument.

## Usage

```bash
# Open the application
ddev launch

# Show DDEV project details
ddev describe

# Show Symfony project information
ddev console about

# Open the database administration UI
ddev adminer
```

The main local URL is `https://api.ddev.site`.

## API

### Resources

| Method | Path | Access |
|--------|------|--------|
| `POST` | `/login_check` | Public; returns a JWT |
| `GET` | `/users` | `ROLE_ADMIN` |
| `GET` | `/users/{uuid}` | The user themselves or `ROLE_ADMIN` |
| `GET` | `/me` | Any authenticated API user |
| `GET` | `/users/{userId}/profile` | The profile owner or `ROLE_ADMIN` |
| `GET` | `/music` | Any authenticated API user |
| `GET` | `/music/{uuid}` | Any authenticated API user |
| `GET` | `/music/recordings` | Any authenticated API user |
| `GET` | `/music/recordings/{uuid}` | Any authenticated API user |
| `GET` | `/music/albums` | Any authenticated API user |
| `GET` | `/music/albums/{uuid}` | Any authenticated API user |
| `GET` | `/music/artists` | Any authenticated API user |
| `GET` | `/music/artists/{uuid}` | Any authenticated API user |

The user API is currently read-only. Password hashes are stored on the
Doctrine entity but are not fields on the API DTO and therefore are never
returned by these endpoints.

### Music resources

The music API is read-only and follows the related schema.org types:

- `MusicPlaylist` is exposed at `/music` and contains an ordered `track`
  `ItemList` with `ListItem` entries.
- `MusicRecording` is exposed at `/music/recordings` and may reference an
  album and one or more `MusicGroup` artists.
- `MusicAlbum` is exposed at `/music/albums` and contains ordered recordings.
- `MusicGroup` is exposed at `/music/artists` and represents bands as well as
  solo musicians.

Playlist and album track positions are persisted explicitly. Related albums
and artists are serialized as canonical resource IRIs where expanding them
would create recursive response graphs. All music resources use UUID
identifiers and the schema.org RDF types are included in JSON-LD responses and
OpenAPI documentation.

The `/users` collection supports API Platform pagination through the `items`
query parameter. The configured maximum page size is 50:

```text
/users?items=20&page=2
```

### JWT authentication

Request a token with the user's email in the `username` field:

```bash
curl -X POST https://api.ddev.site/login_check \
    -H 'Content-Type: application/json' \
    -d '{"username":"user@example.com","password":"your-password"}'
```

Use the returned token on API requests:

```bash
curl https://api.ddev.site/me \
    -H 'Authorization: Bearer <token>'

curl https://api.ddev.site/users \
    -H 'Authorization: Bearer <token>'
```

The login endpoint is rate-limited to five attempts per minute. JWTs have a
one-hour lifetime. API requests are stateless and do not use the browser
session.

### Response formats and documentation

API Platform negotiates the following response formats:

- JSON-LD: `application/ld+json`
- HAL: `application/hal+json`
- JSON:API: `application/vnd.api+json`
- JSON: `application/json`
- XML: `application/xml`
- YAML: `application/x-yaml`

Swagger UI is available at [`/docs`](https://api.ddev.site/docs) in development
and test environments. The OpenAPI document is available at
`/docs.jsonopenapi`. The docs are public so they can be viewed before login,
but protected API operations still require a JWT. Documentation and the API
profiler are disabled in production.

## Browser Dashboard

The browser UI is separate from the stateless API authentication:

- `GET|POST /login` renders and processes the Symfony form login.
- `GET /dashboard` displays the authenticated email, roles, and optional
  profile text.
- `POST /logout` invalidates the session and redirects to `/login`.

The login and logout forms use CSRF protection. An unauthenticated dashboard
visitor is redirected to `/login` and is returned to the originally requested
page after successful authentication.

## Project Structure

```text
.ddev/          Local DDEV and Docker configuration
.github/        GitHub Actions workflow
config/         Symfony, security, Doctrine, API Platform, and routing config
data/           SQLite test database
migrations/     Doctrine schema migrations
public/         HTTP document root and front controller
src/
  ApiResource/  Public API DTOs and API Platform operation metadata
  Command/      Symfony console commands
  Controller/   Attribute-routed browser controllers
  DataFixtures/ Doctrine fixtures; currently no application seed data
  Entity/       Doctrine entities and reusable entity traits
  Repository/   Repository implementations and query interfaces
  State/        API Platform providers and IRI conversion customization
templates/      Twig templates for the browser UI
tests/
  Unit/         Isolated component tests
  Functional/   Symfony kernel, HTTP, security, Doctrine, and serialization tests
  Acceptance/   PhpBrowser tests against the live DDEV site
```

## Request Flow

For an API request such as `GET /users/{uuid}`:

1. `public/index.php` boots Symfony and `App\Kernel`.
2. The API firewall validates the bearer token and loads `App\Entity\User`.
3. API Platform selects the operation declared on `src/ApiResource/User.php`.
4. The operation security expression checks the caller's role or ownership.
5. `src/State/UserProvider.php` queries `UserRepositoryInterface`.
6. The provider maps the entity to `App\ApiResource\User`.
7. API Platform serializes the DTO in the requested response format.

The `/me` operation is special: `UserProvider` reads the authenticated user
from Symfony Security instead of using an identifier from the URL. The
`CanonicalUserIriConverter` ensures serialized links for `/me` point to the
canonical `/users/{uuid}` resource path.

For `/users/{userId}/profile`, `UserProfileProvider` loads the user and its
one-to-one profile, then reuses `UserProvider` to map the nested user DTO.

## Database And Migrations

Development uses PostgreSQL 17 through DDEV:

```text
DATABASE_URL=postgres://db:db@db:5432/db?sslmode=disable&charset=utf8&serverVersion=17
```

Tests use the SQLite database at `data/database_test.sqlite`. The migrations
include platform-specific SQL where PostgreSQL and SQLite differ, so schema
changes must be checked against both environments.

Run migrations with:

```bash
ddev console doctrine:migrations:migrate
```

Entities use UUID identifiers and PHP attribute mappings. `User` and
`UserProfile` both use `TimestampableTrait`, with `createdAt` and `updatedAt`
populated by Stof Doctrine Extensions.

## Development

The Composer scripts are intended to run inside DDEV:

| Command | Description |
|---------|-------------|
| `ddev composer build` | Full pipeline: prepare, lint, check, and test |
| `ddev composer prepare` | Validate Composer config and auto-fix PHP style |
| `ddev composer lint` | Lint the Symfony container and Twig templates |
| `ddev composer check` | Run PHP CS Fixer in check mode, PHPStan, and Psalm |
| `ddev composer test` | Validate the Doctrine schema and run Codeception |
| `ddev composer report` | Generate Codeception HTML and PHP Metrics reports |

Run individual test suites:

```bash
ddev exec vendor/bin/codecept run Unit
ddev exec vendor/bin/codecept run Functional
ddev exec vendor/bin/codecept run Acceptance
```

Acceptance tests use PhpBrowser against `https://api.ddev.site` and require a
running DDEV environment. CI runs only the Unit and Functional suites.

Run one functional test file with:

```bash
ddev exec vendor/bin/codecept run Functional tests/Functional/UserResourceCest.php
```

Codeception shuffles tests by default. Functional tests use the Symfony test
kernel and Doctrine cleanup between tests. `tests/bootstrap.php` configures
the SQLite test database and generates ephemeral RSA keys under
`var/test-jwt/`.

## Where To Start

For a first change, read these files together:

1. `src/ApiResource/User.php` shows public operations, identifiers, and
   authorization metadata.
2. `src/State/UserProvider.php` shows how API entities become DTOs and how
   collection pagination is implemented.
3. `src/Entity/User.php` and `config/packages/security.yaml` show the data
   model and both authentication flows.
4. `tests/Functional/UserResourceCest.php` demonstrates the API contract and
   ownership rules.
5. `tests/Functional/ApiContractCest.php` covers formats, pagination, CORS,
   and OpenAPI behavior.

For a new read-only API resource, normally add an entity, repository and
interface, API DTO, state provider if the default provider is insufficient, a
Doctrine migration, and unit/functional tests. Keep persistence concerns in
entities and repositories; keep public response shape in API resources.

For API contract changes, update the resource metadata and its functional
contract tests. For schema changes, update the entity mapping and migration,
then validate against PostgreSQL and SQLite.

See [`FEATURES.md`](FEATURES.md) for the current feature and endpoint reference
and [`SECURITY.md`](SECURITY.md) for vulnerability reporting.
