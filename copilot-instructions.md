
---

## 🤖 copilot-instructions.md

Place this file in the root of the repository (or `.github/copilot-instructions.md`). It tells GitHub Copilot how to generate code for this specific project.

```markdown
# GitHub Copilot Instructions – PTT Walkie‑Talkie Solution

These instructions apply when you (Copilot) are generating code for this repository. Follow them strictly to ensure consistency, security, and test coverage.

## 🧭 Project Context

- **Type**: Multi‑tenant PTT system with Laravel backend, Android client, and Mumble voice integration.
- **Key constraints**: Low latency, background operation on Android, real‑time WebSocket commands, voice recording archival.
- **Languages**: PHP (Laravel), Kotlin (Android), JavaScript/React (admin UI), Docker, Shell.

## 🧱 Code Generation Rules

### 1. General

- Always include **unit tests** for any new class or function. Tests must be written in the same commit/request.
- Use **descriptive variable names** – no single letters except loop indices.
- Add **PHPDoc / KDoc** comments for public methods.
- Keep methods small (< 30 lines) and single‑purpose.
- Prefer **dependency injection** over facades or static calls in Laravel.
- In Android, avoid memory leaks: use `LifecycleObserver` and unregister listeners in `onDestroy`.

### 2. Backend (Laravel)

- **Models**: Use `$fillable` (not `$guarded`). Define relationships, casts, and factories.
- **Controllers**: Keep them thin – business logic goes into **Service classes** (e.g., `MumbleIceService`, `DeviceRegistrationService`).
- **Mumble Ice integration**: All calls to Murmur must go through `App\Services\MumbleIceService`. Never call Ice directly from controllers.
- **API responses**: Use `JsonResource` for consistent formatting. Return appropriate HTTP status codes (200, 201, 400, 403, 404).
- **Real‑time events**: Extend `ShouldBroadcast` and use private channels (`private-device.{id}`, `private-organization.{id}`). Always authenticate channels via `BroadcastServiceProvider`.
- **Testing**: Use `RefreshDatabase` for feature tests. Mock external services (Ice, Reverb) using `Mockery` or Laravel's `Http::fake`. Test that events are dispatched with `Event::fake()`.

### 3. Android (Kotlin)

- **Architecture**: Use **Foreground Services** for microphone and location. Do not use WorkManager for real‑time audio.
- **Permissions**: Request dangerous permissions at runtime (location, microphone). For Android 14+, declare `FOREGROUND_SERVICE_TYPE_MICROPHONE` and `FOREGROUND_SERVICE_TYPE_LOCATION`.
- **Mumble integration**: Use the **Jumble** library. Wrap it in a `MumbleManager` singleton that handles connection, reconnection, and PTT state.
- **Network calls**: Use **Retrofit** with coroutines. Store the base URL in `config.xml`.
- **Testing**: Write **Robolectric** tests for logic that doesn't require Android framework, and **instrumentation tests** (Espresso) for UI and service startup.
- **Boot receiver**: Implement a `BroadcastReceiver` for `BOOT_COMPLETED` that starts the foreground service.

### 4. Admin UI (React + Inertia)

- **Pages**: Use Inertia's `usePage()` hook. Keep page components under `resources/js/Pages/`.
- **Real‑time**: Use `Echo` with Reverb. Subscribe to private channels inside `useEffect` and clean up on unmount.
- **State management**: Prefer React Context or local state. Avoid Redux unless multiple far‑apart components need the same data.
- **Styling**: Use Tailwind CSS (already configured).
- **Testing**: Write **Laravel Dusk** tests for critical user flows (login, assign device to room, view live map).

### 5. Docker & Deployment

- **Dockerfile** for Laravel must have separate stages: `base`, `build` (for Composer/NPM), and `production`.
- **docker-compose.yml** must include healthchecks for every service (except redis).
- **Secrets**: Never hardcode credentials. Use environment variables in containers; in production, use Docker secrets or a vault.
- **Recordings volume**: Mount a volume to `/recordings` in the Murmur container; the Laravel container must also mount it (read‑only) to process new files.

### 6. Testing Requirements

Every code generation request must produce **at least one test** that covers the new logic. Use the following naming conventions:
- Laravel: `{ClassName}Test.php` (e.g., `MumbleIceServiceTest`)
- Android: `{ClassName}Test.kt` (unit) or `{ClassName}InstrumentedTest.kt` (instrumentation)

The test must be **runable** – no placeholder comments like `// TODO: write test`.

### 7. Prohibited Patterns

- ❌ Using `php artisan make:model` without `--factory` and `--seed` flags.
- ❌ Direct `new` operator for services inside controllers (use constructor injection).
- ❌ Hardcoding IMEI or serial number fallbacks (use `Settings.Secure.ANDROID_ID` as last resort).
- ❌ Storing plain text passwords – Mumble passwords must be hashed with `Hash::make()` before sending to Murmur Ice.
- ❌ Blocking the main thread on Android – use `Dispatchers.IO` for network calls and disk I/O.

### 8. Example Prompts for Copilot

When you ask Copilot to generate code, phrase your request like:

> “Generate a Laravel service `DeviceLocationService` with a method `storeBatch(array $locations, Device $device)`. It should validate that each location has latitude, longitude, and timestamp, then batch insert into `gps_logs` table. Write a unit test using a mock device and assert that 3 locations are inserted.”

> “Create an Android foreground service `LocationService` that uses FusedLocationProviderClient. The service should request location updates every 10 seconds, buffer points, and send them to the backend via Retrofit when the buffer reaches 5 points or 30 seconds have passed. Include instrumentation test that mocks the location client and verifies the HTTP call.”

## ✅ Checklist Before Submitting Code

- [ ] All new classes have unit tests.
- [ ] Laravel migrations are reversible (`down()` method).
- [ ] Android permissions are declared in `AndroidManifest.xml` and requested at runtime.
- [ ] No `dd()` or `Log.d()` left in production code.
- [ ] Environment variables are documented in `.env.example`.
- [ ] The code passes `php artisan test` (backend) and `./gradlew test` (Android).

By following these instructions, you will help build a maintainable, secure, and high‑performance PTT solution.