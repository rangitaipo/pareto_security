# Pareto Security — Context Update

## Goal
Fix security issues in-place, then `git init` once at a reasonable state.
No new files except this one. No restructuring.

## Status
- All five prioritized fixes applied. `php -l` unavailable in environment.
- Git repo to be created after fixes.

## Findings (manual review)

### Stored XSS in admin log table (pareto_settings.php, `logfile_box()` ~L757) — FIXED
- Wrapped `$this_timestamp`, `$req_var[1]`, `$req_var[3]`, `$req_var[4]`, `$attack_string` in `esc_html()`.
- `$text_color` wrapped in `esc_attr()`, `$ip_addr` (HTML link) in `wp_kses_post()`.

### CSRF on settings form (pareto_settings.php) — FIXED
- Added `check_admin_referer( $this->settings_field . '-options' )` before any POST mutation in `kickoff()`.

### Authorization gap in `kickoff()` (pareto_settings.php ~L74) — FIXED
- Added `current_user_can( 'manage_options' )` check before any POST processing.

### IP spoofing via `check_ip()` (pareto_functions.php ~L2790) — FIXED
- Changed `FILTER_FLAG_IPV4 || FILTER_FLAG_IPV6` to `FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6` in `check_ip()`.
- `getREMOTE_ADDR()`, `is_cf()`, `_HTTP_HEADER_SHIELD()` do not have this bug (verified by search).

### Stored XSS in email report (pareto_functions.php, `write_log()` ~L244) — FIXED
- `$this->_client_ip` and `$this->get_filename()` now wrapped in `htmlentities_safe()`.

### Open redirect via `getURL()` (pareto_functions.php ~L2619)
- `header("Location: " . $this->getURL())` at ~L950 uses unvalidated `$_SERVER['REQUEST_URI']`.

### File deletion path bypass (pareto_functions.php, `check_filenames()` ~L1432)
- Uses `strpos()` to check file is in uploads dir. Windows path separator mismatch or symlink could bypass.

### `write_htaccess()` trusts input (pareto_functions.php ~L2485)
- `$mybans` comes from file content + internal ban list. IP is validated by `is_ip_address()` upstream but domain interpolation in ban markers uses `get_http_host()` which can be spoofed on IIS.

### Capability check inconsistency in `admin_menu()` (pareto_settings.php ~L351) — FIXED
- Changed both `current_user_can()` and `add_options_page()` to use `manage_options`.

## Fix Priority
1. XSS in `logfile_box()` — escape all output.
2. CSRF/auth in `kickoff()` — nonce + `current_user_can`.
3. `check_ip()` bitwise OR bug — fix to `|`.
4. Escape email report values.
5. Minor: consistent capability in `admin_menu()`.

## Resume Instructions
1. Read this file.
2. Open `pareto_settings.php`, go to `logfile_box()` (~L757), apply XSS fix.
3. Open `kickoff()` (~L79), add nonce + capability check.
4. Open `check_ip()` in `pareto_functions.php` (~L2790), fix `||` to `|`.
5. Escape email output in `write_log()` (~L244).
6. Fix `admin_menu()` capability.
7. Search for any other unescaped `echo` of `$req_var` or `$_SERVER` in `pareto_settings.php`.
8. Do NOT create any new .php files.
9. After fixes, run `php -l` on each file if PHP is available.
10. Then `git init`, add all, commit.

## Key File Map
- `pareto_security.php` — bootstrap, calls shields
- `pareto_setup.php` — constants, defaults, icon data URI
- `pareto_functions.php` — ~3500 lines, all shields + helpers
- `pareto_settings.php` — admin UI + settings, ~860 lines
- `uninstall.php` — cleanup
- `js/hokioi.js` — checkbox toggling
- `css/` — admin styles
