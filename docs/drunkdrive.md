# DrunkDrive API

Backend domain for ride/driver flows on this Laravel + Sanctum app.
Base path: `/api/v1/drunkdrive`

Existing E-libra routes under `/api/v1/*` are unchanged.

## Roles (RBAC)

| Product name | Seeded `user_roles.role` | Notes |
|---|---|---|
| user | `user` | Rider |
| driver | `driver` | Must complete licence + address |
| admin | `admin` | Create/manage stores; see all trip history |
| superadmin | `super_admin` | Alias accepted in middleware (`superadmin` ↔ `super_admin`) |

Guards use existing `RoleMiddleware` + Sanctum Bearer tokens.

## Auth

| Method | Path | Auth |
|---|---|---|
| POST | `/auth/register` | public — rider account |
| POST | `/auth/register-driver` | public — driver + licence + address |
| POST | `/auth/login` | public |
| GET | `/auth/me` | sanctum |

### Driver licence intake

`register-driver` accepts:

- Manual: `licence_number`, `licence_class`, `licence_country`, `licence_issued_at`, `licence_expires_at`
- Scan: `licence_scan` (jpg/png/webp/pdf, max 10 MB)
- Address: `address_line` (required), city/region/postal/country, optional lat/lng

At least one of manual `licence_number` or `licence_scan` is required.
OCR is a stub (`licence_ocr_stub` JSON) — scan is stored; replace stub later.

## Stores (admin / superadmin)

| Method | Path | Auth |
|---|---|---|
| GET | `/stores` | public (active only for non-admins) |
| GET | `/stores/{id}` | public |
| POST | `/stores` | admin, super_admin |
| PUT/PATCH | `/stores/{id}` | admin, super_admin |
| DELETE | `/stores/{id}` | admin, super_admin |

## Drivers

| Method | Path | Auth |
|---|---|---|
| GET | `/drivers/nearby?latitude=&longitude=&radius_km=` | sanctum |
| GET | `/driver/profile` | driver |
| POST/PUT | `/driver/licence` | driver |
| POST | `/driver/location` | driver — body: lat/lng, optional `is_available` |
| PUT | `/driver/availability` | driver |
| GET | `/admin/drivers` | admin |
| PATCH | `/admin/drivers/{driverProfile}/status` | admin — `pending\|approved\|rejected\|suspended` |

Nearby uses Haversine distance; only `approved` + `is_available` drivers with a current location are returned.

## Fare

| Method | Path | Auth |
|---|---|---|
| GET | `/fare/formula` | public |
| POST | `/fare/quote` | public — `distance_km` **or** pickup/dropoff lat/lng |

### Formula

Anchors: **1 km → 2000 R**, **5 km → 5000 R**.

Product mention of “Km^2” cannot be a pure quadratic through the origin (that cannot hit both anchors). Implementation is the **linear fit** through those two points:

```
fare_R(d) = round(1250 + 750 × d)
```

Equivalent:

```
fare_R(d) = round(2000 + 750 × max(0, d − 1))
```

`d` = kilometres; fare is an integer in **R**.

## Trips / history

| Method | Path | Auth |
|---|---|---|
| POST | `/trips` | user, driver, admin |
| GET | `/trips/history` | user (own), driver (own/assigned), admin (all + filters) |
| GET | `/trips/{id}` | owner / assigned driver / admin |
| PATCH | `/trips/{id}/status` | driver/admin (rider may cancel) |

Statuses: `requested`, `accepted`, `in_progress`, `completed`, `cancelled`.

## Seed accounts (password: `password`)

- `superadmin@drunkdrive.test` — super_admin
- `admin@drunkdrive.test` — admin
- `user@drunkdrive.test` — user
