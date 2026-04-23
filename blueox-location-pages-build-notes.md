# Blue Ox Location Pages — Build Notes & Retrospective

> Companion to `blueox-location-pages-spec.md`.  
> Documents what was built, what was tried and abandoned, gotchas encountered, and a pre-flight checklist for the next run.

---

## Repeating This on a New Site

> The two remaining sites are exact copies of goblueox.com. Use this section as the complete checklist. Estimated time with everything prepared: **under an hour**.

### Step 1 — Copy the theme files

```bash
rsync -av --delete \
  "/Users/tonyst.claire/Desktop/Blackhawk/Cursor/Sites/blueox/LSP-goblueox/wp-content/themes/hello-theme-child-master/" \
  "/Users/tonyst.claire/Local Sites/{new-site}/app/public/wp-content/themes/hello-theme-child-master/"
```

Use `rsync --delete`, never `cp -r`. See sync workflow lesson at the bottom of this file.

### Step 2 — Find the Elementor template ID on the new site

The constant `BLUEOX_LOCATION_ELEMENTOR_TEMPLATE_ID` in `elementor-location-override.php` is hardcoded to **8213** — the post ID of the Location Page Template on goblueox.com. Every site has its own post ID for this template.

1. In WP Admin → Templates → Theme Builder, open "Location Page Template"
2. Look at the URL: `post=XXXX` — that number is the ID
3. Update the constant in `inc/elementor-location-override.php`:

```php
define( 'BLUEOX_LOCATION_ELEMENTOR_TEMPLATE_ID', XXXX ); // ← replace 8213 with new ID
```

4. Rsync again after saving.

**This is the most common mistake when moving to a new site. If the body doesn't render, this is the first thing to check.**

### Step 3 — Confirm the Elementor template is published

In Elementor → Theme Builder → confirm "Location Page Template" status is **Published**. `do_location()` silently skips unpublished templates.

### Step 4 — Upload fallback images

Upload `maple-grove-hero.webp` and `maple-grove-2.webp` to the media library on the new site. The importer uses these as fallbacks when a location's own hero or secondary image file is missing. Without them, image fields are simply skipped rather than falling back.

### Step 5 — Dry run the importer

```bash
wp blueox import-locations \
  --file="/path/to/BlueOx_SEO_Final(Location).csv" \
  --images-dir="/path/to/Images" \
  --dry-run
```

Check the output for:
- Any `skipped-incomplete` rows (missing required content)
- Image `warn` lines — shows which images are missing and whether fallbacks will cover them
- Correct page count matches expectations

### Step 6 — Pilot import (single city)

```bash
wp blueox import-locations \
  --file="/path/to/BlueOx_SEO_Final(Location).csv" \
  --images-dir="/path/to/Images" \
  --only=maple-grove
```

Visit the page on the frontend and confirm:
- [ ] Elementor template body renders (not just header/footer)
- [ ] ACF dynamic fields show real content (H1, intro, sections)
- [ ] Hero image is visible
- [ ] Rank Math shows title/description in WP Admin

### Step 7 — Full import

```bash
wp blueox import-locations \
  --file="/path/to/BlueOx_SEO_Final(Location).csv" \
  --images-dir="/path/to/Images"
```

### Step 8 — Post-import checks

```bash
# Confirm page count
wp post list --post_type=page --meta_key=_wp_page_template \
  --meta_value=template-location.php --format=count

# Spot-check a page's ACF data
wp post meta get {POST_ID} h1
wp post meta get {POST_ID} rank_math_title
```

### What changes between sites

| Item | goblueox.com | New site |
|---|---|---|
| Elementor template ID | 8213 | Check WP Admin → Templates |
| Protected slugs | `minneapolis`, `rochester` | Same — hardcoded in `PROTECTED_SLUGS` |
| CSV file | Same format | Same file, or new content |
| Images folder | Same local path | Same local path |
| ACF field keys | `field_blueloc_*` | Same — defined in code, not DB |
| Fallback images | Upload to media library | Upload to media library |

### What does NOT need to change between sites

- All PHP files in `inc/` — same code, only the template ID constant changes
- `template-location.php` — identical
- The CSV column mapping — identical
- The ACF field group — identical (registered in PHP, not DB)

---

## What Was Built

### File inventory

| File | Purpose | Loads on |
|---|---|---|
| `inc/acf-location.php` | ACF field group for location pages. Targets `template-location.php`. Flat named fields — no repeaters. | All requests |
| `inc/elementor-location-override.php` | Queues the Elementor Theme Builder template on `template_redirect` for location pages. No DB writes — survives template edits. | All requests |
| `inc/location-importer.php` | Core importer class. CSV parsing, page create/update, ACF writes, image sideloading, Rank Math meta. | Admin + WP-CLI only |
| `inc/cli-location-import.php` | WP-CLI wrapper: `wp blueox import-locations`. | WP-CLI only |
| `inc/admin-location-import.php` | Admin UI at Tools → Import Locations. | Admin only |
| `inc/cli-elementor-set-conditions.php` | One-time script to set Elementor display conditions via the Pro API. No longer needed for rendering — kept for reference and UI tidiness. | Manual (`wp eval-file`) |
| `template-location.php` | WordPress page template. Calls `elementor_theme_do_location('single')` so the Theme Builder body renders. | Frontend (location pages only) |
| `functions.php` | Wires all files via `require_once`. Importer/admin files are admin-only for performance. | All requests |

### ACF field group

- **Location rule:** `page_template == template-location.php`
- **Field key prefix:** `field_blueloc_`
- **Tabs:** Hero, Intro, Body (sections 1–4), FAQ, Testimonials, Images, CTA, Schema
- **FAQ fields:** `faq_1_question / faq_1_answer` through `faq_3_*` — flat named, not a repeater
- **Testimonial fields:** `testimonial_1_quote / testimonial_1_author` through `testimonial_3_*` — flat named, not a repeater
- **Schema field:** `schema_type` — always written as `HVACBusiness` by the importer

### Importer key facts

- Creates `post_type = page` with `_wp_page_template = template-location.php`
- Column C = WP post title, Column D = page slug (extracts last path segment from URL)
- Skips rows 1–3 (headers/example), processes row 4 onwards
- Protected slugs: `minneapolis`, `rochester` — never created or overwritten
- Images: looked up from `--images-dir` as `{slug}-hero.webp`, `{slug}-2.webp`, `{slug}.webp`
- Fallback images: `maple-grove-hero.webp` and `maple-grove-2.webp` — used if a location's own image is missing
- Image sideloading is cached via `_blueox_img_hero_{slug}` post meta — never re-uploads on re-runs
- Rank Math keys written directly: `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`, `rank_math_keywords`, `rank_math_og_title`, `rank_math_og_description`

---

## Current State Summary

- Location pages are standard WordPress pages (`post_type = page`)
- Each page has `_wp_page_template = template-location.php` in post meta
- The Elementor Theme Builder template is injected at runtime via `elementor-location-override.php` using `add_doc_to_location()` — **no display conditions are needed and none need to be set**
- This approach survives any number of template saves/edits in the Elementor editor
- The only thing that breaks it: template 8213 being deleted, unpublished, or the constant having the wrong ID

---

## How to Run the Importer

Open **Local Site Shell** for the site, then:

```bash
# 1. Dry run — no writes, reports planned actions and image status
wp blueox import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/BlueOx_SEO_Final(Location).csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images" \
  --dry-run

# 2. Pilot — single city
wp blueox import-locations \
  --file="/path/to/BlueOx_SEO_Final(Location).csv" \
  --images-dir="/path/to/Images" \
  --only=maple-grove

# 3. Full run
wp blueox import-locations \
  --file="/path/to/BlueOx_SEO_Final(Location).csv" \
  --images-dir="/path/to/Images"
```

---

## Pre-Flight Checklist

### Environment
- [ ] Using **Local's Site Shell**, not regular Terminal
- [ ] Theme files synced via `rsync --delete` (not `cp -r`)
- [ ] CSV file path is correct and accessible
- [ ] Images folder path is correct and accessible
- [ ] `maple-grove-hero.webp` and `maple-grove-2.webp` are in the WP media library

### Elementor
- [ ] `BLUEOX_LOCATION_ELEMENTOR_TEMPLATE_ID` in `elementor-location-override.php` matches the actual template post ID on this site
- [ ] The "Location Page Template" is **Published** in Elementor → Theme Builder
- [ ] After pilot import, confirm body renders on the frontend (not just header/footer)

### ACF
- [ ] Visit an imported page in WP Admin — confirm "Location Page Fields" metabox appears
- [ ] Spot-check at least one field value in the metabox

### Rank Math
- [ ] Open an imported page in WP Admin, open Rank Math metabox, confirm title/description populated
- [ ] Check focus keyword is not blank

---

## Things to Watch Out For

### Elementor template ID is site-specific
The constant `BLUEOX_LOCATION_ELEMENTOR_TEMPLATE_ID = 8213` is specific to goblueox.com. **Every site needs this updated** before the theme is deployed. If location page bodies render blank, this is the first thing to check. Get the correct ID from WP Admin → Templates → Theme Builder → open the template → check the `post=` URL parameter.

### Image sideloading
- **File not found errors are silent warnings, not failures.** The page is still created; only the image field is skipped or uses a fallback. Check warn lines in the output.
- **Re-runs never re-upload images.** The `_blueox_img_hero_{slug}` post meta cache key prevents this. Delete that meta key to force a fresh upload.
- **`media_handle_sideload()` does not set alt text.** The importer handles this explicitly.

### Elementor template must stay Published
`do_location()` silently skips documents that aren't `publish`. If the body goes blank, check that the Elementor template hasn't been accidentally set to draft.

### ACF field group
- **If fields don't appear**, check the location rule: `page_template == template-location.php`.
- **Field key prefix is `field_blueloc_`**. Conflicting keys will be silently ignored by ACF.
- **`update_field()` takes the field name**, not key. If it fails silently, try the full key `field_blueloc_*`.

### CSV parsing
- **Rows 1–3 are skipped.** Real data starts at row 4.
- **Column A must contain `"Location"`** (exact string) or the row is ignored.
- **Column D is a full URL** — the importer extracts the last path segment as the slug.
- **UTF-8 BOM** is stripped automatically. Excel-exported CSVs often include it.
- **Testimonial separator is U+0097 (C1 control character, byte sequence `C2 97`).** This character is invisible in editors and spreadsheets and is not matched by PHP's `\s`. The parser normalises all C1 control characters (U+0080–U+009F) to a regular space before splitting. It also handles em dash, en dash, ` -- `, single hyphen, and 2+ space variants. Surrounding quotes are stripped automatically. If a testimonial row imports with an empty author, inspect the raw CSV bytes around the separator — `xxd` or a hex editor will show the actual byte sequence.

### Protected slugs
`minneapolis` and `rochester` are hardcoded in `PROTECTED_SLUGS`. If the new site has additional existing pages that must not be overwritten, add their slugs there before running.

### WordPress page template
`template-location.php` must call `elementor_theme_do_location('single')`, not just `the_content()`. If you see only the header and footer, this is the cause. The current version is correct — only an issue if the file gets accidentally overwritten.

### Sync workflow
Always use `rsync -av --delete` between the git repo and the Local site. `cp -r` does not delete removed files — deleted code keeps running on the site.

### Production deploy checklist
Before uploading any theme files to WPEngine via SFTP:
- [ ] Search `acf-location.php` for `debug_acf` — any debug hook must be removed before deploy
- [ ] Search all `inc/` files for `error_log(` — remove any temporary logging
- [ ] Remove hardcoded local paths (e.g. `/Users/…`) from any PHP files
- [ ] Run `git diff` to confirm exactly which files have changed since the last deploy — upload only those files

---

## Rank Math Notes

- Schema is **not written by the importer**. Set `schema_type` ACF field to `HVACBusiness` via import, then bulk-configure Rank Math schema after.
- OG images are not set. Rank Math falls back to the featured image if "Use Featured Image" is enabled in Rank Math settings.
- Focus keyword and secondary keywords come from CSV columns E and F.

---

## What Was Tried and Abandoned

### Custom Post Type `location` for root-level URLs

Registered a `location` CPT with `slug => '/'` for root-level URLs. The broad catch-all rule intercepted every root-level request including `/contact/`, `/about-us/` etc. and caused 404s.

The actual bug in the `request` filter was that it unset `location` from query vars but left `post_type=location` and `name=` behind — WordPress still tried to find a CPT post and 404'd. The fix would have been to also unset `post_type` and `name`. However, maintaining a root-level CPT request filter permanently adds fragility, so the approach was abandoned in favour of standard pages with a template meta check.

**Decision:** Standard `post_type = page` with `_wp_page_template = template-location.php`. Simpler, reliable, zero rewrite rule maintenance.

### Elementor custom condition class

Attempted to register a `Condition_Base` subclass so the template appeared in the Theme Builder conditions dropdown. Three iterations (sub-condition of `singular`, sub-condition of `page`, top-level via `register_condition_instance()`) — none appeared in the UI.

**Decision:** Abandoned. Replaced with `add_doc_to_location()` approach.

### `elementor/theme/get_location_templates` and `do_location` filters

Multiple sessions implementing these filter hooks. **They do not exist.** The method names and hook names look identical in Elementor's codebase. See Lessons Learned section below.

### ACF repeater fields for FAQ and Testimonials

Elementor Dynamic Tags bind unreliably to repeater sub-fields. Replaced with flat named fields (`faq_1_question` etc.) which bind directly and reliably.

### `the_content()` in template-location.php

Importer-created pages have empty `post_content`. `the_content()` renders nothing. Fixed by calling `elementor_theme_do_location('single')` explicitly.

---

## Lessons Learned — Elementor Hook Verification

> Also encoded as a **global Cursor rule** at `~/.cursor/rules/elementor-pro-hooks.mdc` and **project rule** at `.cursor/rules/elementor-hooks.mdc`. Applies automatically to all future Elementor work.

### The wrong hook problem

`add_filter('elementor/theme/get_location_templates', ...)` and `add_filter('elementor/theme/do_location', ...)` do not exist in Elementor Pro as `apply_filters` hooks. They are class method names. Extensive blog posts and tutorials make the same incorrect assumption.

**One grep command at the start would have prevented everything:**

```bash
grep -r "apply_filters.*'elementor/theme/get_location_templates'" wp-content/plugins/elementor-pro/
# Zero results → hook does not exist
```

### The correct way to inject a template without conditions

```php
add_action( 'template_redirect', function (): void {
    if ( ! is_singular() ) return;
    global $post;
    if ( empty( $post ) ) return;
    $template = get_post_meta( $post->ID, '_wp_page_template', true );
    if ( 'template-location.php' !== $template ) return;
    if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) return;
    \ElementorPro\Modules\ThemeBuilder\Module::instance()
        ->get_locations_manager()
        ->add_doc_to_location( 'single', BLUEOX_LOCATION_ELEMENTOR_TEMPLATE_ID );
}, 20 );
```

### Verified facts

| Claim | Reality |
|---|---|
| `elementor/theme/get_location_templates` is a filter | **FALSE** — method name only |
| `elementor/theme/do_location` is a filter | **FALSE** — method name only |
| Conditions cache option is `elementor_pro_conditions_cache` | **FALSE** — it is `elementor_pro_theme_builder_conditions` |
| `save_conditions()` takes an array of strings | **FALSE** — takes array of arrays |
| Conditions set via script persist after a template save | **FALSE** — Elementor overwrites them on every save |

### Source files to read for Theme Builder work

| File | What it contains |
|---|---|
| `elementor-pro/modules/theme-builder/api.php` | All public PHP functions |
| `elementor-pro/modules/theme-builder/classes/locations-manager.php` | `do_location()`, `add_doc_to_location()` |
| `elementor-pro/modules/theme-builder/classes/conditions-manager.php` | `save_conditions()`, conditions logic |
| `elementor-pro/modules/theme-builder/classes/conditions-cache.php` | Cache structure and option name |

### Sync workflow lesson

`cp -r` without `--delete` leaves deleted files in place. Old code keeps running on the Local site even after you've removed it from the repo. Always use:

```bash
rsync -av --delete "/path/to/repo/theme/" "/path/to/local-site/theme/"
```

Set up a shell alias (`blueox-sync`) so this is one command.

---

## Known Limitations / Future Work

| Item | Notes |
|---|---|
| Per-location phone numbers | Currently uses site-wide default. Future: add `phone` ACF field and per-city data in CSV. |
| Per-location addresses | Same as phone — uses site-wide Rochester default for now. |
| Location × service pages | Out of scope for this phase. Separate spec needed. |
| Image optimisation | Images sideloaded as-is (webp). Ensure source images are optimised before import. |
| Bulk Rank Math schema | Schema type set via ACF but Rank Math schema blocks need separate bulk configuration after import. |
| `location-swap.js` — HTTP geo API | Line 16 uses `http://ip-api.com` — should be `https://` to avoid mixed-content blocking on HTTPS sites. Pass to Juan. |
| `location-swap.js` — Section C DOM scan | `querySelectorAll("a[href]")` scans all links on every run. Could be scoped to header/nav container for performance. Pass to Juan. |
