# Feature Reference

This document describes the currently implemented application features. It is
kept separate from the README so the README can focus on setup and contributor
orientation while this file provides a compact behavior reference.

## API Authentication

- `POST /login_check` accepts JSON with `username` and `password`.
- The `username` value is the user's email address.
- Successful login returns an RSA-signed JWT.
- Tokens have a default lifetime of 3600 seconds.
- API requests are stateless and use `Authorization: Bearer <token>`.
- Login attempts are limited to five per minute.
- JWT key paths and the passphrase come from environment variables.
- Local PEM files are ignored by Git.

Example:

```bash
curl -X POST https://api.ddev.site/login_check \
    -H 'Content-Type: application/json' \
    -d '{"username":"user@example.com","password":"your-password"}'
```

## User Accounts

`App\Entity\User` implements both Symfony user interfaces needed for password
authentication.

- UUID primary key
- Unique email login identifier
- Email normalization by trimming and lowercasing
- Stored roles with implicit `ROLE_USER`
- Hashed passwords
- Automatic password hash upgrades through `UserRepository`
- `createdAt` and `updatedAt` through `TimestampableTrait`
- Validation for email, password, roles, and unique email addresses

The API does not expose the password field. `App\ApiResource\User` is a
read-only public DTO containing the UUID, email, and roles.

## User API

| Method | Path | Authorization |
|--------|------|---------------|
| `GET` | `/users` | `ROLE_ADMIN` |
| `GET` | `/users/{uuid}` | Resource owner or `ROLE_ADMIN` |
| `GET` | `/me` | Any authenticated API user |

The collection supports API Platform pagination through `items` and `page`.
The maximum page size is 50. The `/me` operation resolves the user from the
authenticated JWT and does not accept a user identifier in the path or query
string.

The custom `UserProvider` maps Doctrine entities to API DTOs. The custom
`CanonicalUserIriConverter` makes `/me` responses use the canonical
`/users/{uuid}` IRI in formats that expose resource links.

## User Profiles

`App\Entity\UserProfile` is a one-to-one extension of a user:

- UUID primary key
- Required user relation
- Optional `about` text
- Cascade deletion with the user
- Automatic timestamps

The profile API is read-only:

| Method | Path | Authorization |
|--------|------|---------------|
| `GET` | `/users/{userId}/profile` | Profile owner or `ROLE_ADMIN` |

`UserProfileProvider` loads the linked user and profile and returns a profile
DTO containing a nested public user DTO. A missing user or profile returns 404.

## Music Resources

The music API is a read-only, authenticated API based on schema.org music
types:

| Method | Path | Schema.org type |
|--------|------|-----------------|
| `GET` | `/music` | `MusicPlaylist` collection |
| `GET` | `/music/{uuid}` | `MusicPlaylist` |
| `GET` | `/music/recordings` | `MusicRecording` collection |
| `GET` | `/music/recordings/{uuid}` | `MusicRecording` |
| `GET` | `/music/albums` | `MusicAlbum` collection |
| `GET` | `/music/albums/{uuid}` | `MusicAlbum` |
| `GET` | `/music/artists` | `MusicGroup` collection |
| `GET` | `/music/artists/{uuid}` | `MusicGroup` |

`MusicPlaylist.track` is represented as an `ItemList` containing ordered
`ListItem` values. Each list item contains a `MusicRecording`. Recordings may
refer to a `MusicAlbum` through `inAlbum` and to one or more `MusicGroup`
values through `byArtist`. Albums contain ordered recordings and artists
expose related albums and recordings as resource references to avoid recursive
serialization.

The Doctrine model persists playlists, recordings, albums, artists, playlist
positions, album positions, and the many-to-many artist relationships. The
`numTracks` value is derived from the persisted playlist entries.

## Browser Dashboard

The browser UI uses Symfony's session-based firewall and is separate from JWT
API authentication.

- `GET|POST /login` provides a CSRF-protected form login.
- `GET /dashboard` requires an authenticated user and displays their email,
  roles, and optional profile text.
- `POST /logout` is intercepted by the firewall, clears the session, and
  redirects to `/login`.
- Anonymous dashboard visitors are redirected to login and returned to their
  original destination after successful authentication.

## Content Negotiation

API Platform supports:

- JSON-LD: `application/ld+json`
- HAL: `application/hal+json`
- JSON:API: `application/vnd.api+json`
- JSON: `application/json`
- XML: `application/xml`
- YAML: `application/x-yaml`

Swagger UI is available at `/docs` in development and test environments. The
OpenAPI document is available at `/docs.jsonopenapi`. Documentation is public,
but protected operations still require a JWT. API documentation and the
profiler are disabled in production.

## Cross-Origin Requests

Nelmio CORS handles requests under `/` and permits configured origins from
`CORS_ALLOW_ORIGIN`. Supported methods include GET, OPTIONS, POST, PUT, PATCH,
and DELETE. `Content-Type` and `Authorization` are accepted request headers.

## CLI User Creation

```bash
ddev console app:user:create [email] [--role=ROLE_NAME]
```

- The command must run interactively.
- Email can be supplied as an argument or entered interactively.
- Password and confirmation are hidden prompts.
- Passwords must be at least 12 characters.
- Roles can be repeated and must match `ROLE_[A-Z][A-Z0-9_]*`.
- Email values are normalized and validated.
- Duplicate email addresses are checked before persistence and protected by a
  database unique constraint.

## Environments And Quality

- DDEV development uses PostgreSQL 17.
- Tests use SQLite at `data/database_test.sqlite`.
- Doctrine migrations contain SQLite-specific paths where required.
- Unit and Functional tests run in CI.
- Acceptance tests target the live `https://api.ddev.site` environment and are
  intended for local DDEV runs.
- PHP CS Fixer, PHPStan, Psalm, container linting, Twig linting, and Doctrine
  schema validation are part of the development pipeline.

See `tests/Functional/ApiContractCest.php`,
`tests/Functional/UserResourceCest.php`, and
`tests/Functional/UserProfileResourceCest.php` for executable examples of the
API behavior described here.
