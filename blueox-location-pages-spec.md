# Blue Ox Location Pages — Build Spec

## Context

Blue Ox has ~70 city-level location pages to build (e.g., `/maple-grove/`, `/austin/`, `/stillwater/`). The client has written full SEO-grade content for each city into a CSV (`BlueOx_SEO_Final(Location).csv`). The goal is to create a new `location` Custom Post Type, register ACF fields mapped to the CSV columns, wire those fields to the approved Elementor Theme Builder template, and run a CSV importer to populate all ~70 pages in one pass.

**This spec covers location pages only.** Location × service pages (e.g., `/maple-grove/services/ac-repair/`) are a separate future phase and are not in scope here.

---

## Key Constraints

- **URL structure** — Root-level slugs only: `/{city-slug}/`. The CPT must not inject a post-type prefix (no `/location/maple-grove/`). Custom rewrite rules handle this.
- **No parent city relationship** — These pages are standalone. Phone numbers and addresses default to the site-wide Rochester default for now. Dynamic per-location phone/address is a future enhancement.
- **Net-new pages** — Apart from `/minneapolis/` and `/rochester/` which already exist as standard WP pages, there are no existing location pages to delete or overwrite. The importer must detect these slug conflicts and log them rather than overwriting silently.
- **Elementor template is approved** — The design at `https://goblueox.com/minneapolis/service-areas/new-location/` is the approved template. No design work needed — only field wiring.
- **Rank Math Pro handles all SEO output** — Meta title, meta description, OG fields, and keyword fields must be written directly to Rank Math's own post meta keys. ACF fields are not needed for these — Rank Math has its own native fields in the post editor.
- **WP-CLI is available via Local's Site Shell** — No separate installation needed. Access it by clicking "Site Shell" in Local for the Blue Ox Dev site.
- **Importer is re-runnable** — The importer is idempotent, keyed on slug. Re-running produces zero changes if content hasn't changed.

---

## Image Assets

All processed images are stored locally and will be passed to the importer via the `--images-dir` flag.

**Single image folder:**
```
/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images/
```

| File | Source | Description |
|---|---|---|
| `{slug}-hero.webp` | Column AH (Google Drive, processed by Python script) | Hero image |
| `{slug}-2.webp` | Column AJ (Google Drive, processed by Python script) | Image 2 |
| `{slug}.webp` | Column AN (separate Drive folder, pre-downloaded) | Image 3 |

**Image processing applied to hero and image 2:**
- Resized to 1440px wide (height auto, aspect ratio maintained)
- Converted to WebP
- Quality auto-adjusted to stay under 200kb
- Original files were 14–17MB JPEGs — processed files are under 200kb (~99% size reduction)

**Image 3 (`{slug}.webp`)** was downloaded directly from the client's separate Google Drive folder (`1M02eOBUTFpfwkqDPA3P9Rug1DEVjtjie`) and is already in WebP format. No processing needed.

---

## CSV Column Mapping

File location: `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/BlueOx_SEO_Final(Location).csv`

Row 2 contains column headers. Row 4 is the first real data row. Row 3 contains example/instruction text — skip it.

| CSV Column | Column Name | Destination | Notes |
|---|---|---|---|
| A | Page Type | — | Skip — filter to "Location" rows only |
| B | Phase | — | Skip |
| C | Location Name | WordPress post title | Used as the WP post title, not an ACF field |
| D | URL Slug | Post slug | Extract last path segment e.g. `maple-grove` from full URL |
| E | Primary Keyword | `rank_math_focus_keyword` (Rank Math meta) | Write directly to Rank Math, no ACF field needed |
| F | Secondary Keywords | `rank_math_keywords` (Rank Math meta) | Write directly to Rank Math, no ACF field needed |
| G | Status | — | Skip |
| H | Assigned To | — | Skip |
| I | Due Date | — | Skip |
| J | Publish Date | — | Skip |
| K | Title Tag | `rank_math_title` (Rank Math meta) | Write directly to Rank Math, no ACF field needed |
| L | Meta Description | `rank_math_description` (Rank Math meta) | Write directly to Rank Math, no ACF field needed |
| M | Canonical URL | — | Skip — data is outdated |
| N | H1 Heading | `h1` (ACF field) | Required — skip row if missing. Pulled into page as H1 via Elementor Dynamic Tag |
| O | Hero Subheadline | `hero_subheadline` (ACF) | ACF textarea |
| P | Intro Paragraph | `intro_paragraph` (ACF) | ACF wysiwyg |
| Q | Section 1 Heading | `section_1_h2` (ACF) | Required |
| R | Section 1 Body | `section_1_body` (ACF) | ACF wysiwyg, required |
| S | Section 2 Heading | `section_2_h2` (ACF) | Required |
| T | Section 2 Body | `section_2_body` (ACF) | ACF wysiwyg, required |
| U | Section 3 Heading | `section_3_h2` (ACF) | Required |
| V | Section 3 Body | `section_3_body` (ACF) | ACF wysiwyg, required |
| W | Section 4 Heading | `section_4_h2` (ACF) | Optional |
| X | Section 4 Body | `section_4_body` (ACF) | ACF wysiwyg, optional |
| Y | FAQ Q1 | `faqs[0][question]` (ACF repeater) | Repeater row 1 |
| Z | FAQ A1 | `faqs[0][answer]` (ACF repeater) | Repeater row 1 |
| AA | FAQ Q2 | `faqs[1][question]` (ACF repeater) | Repeater row 2 |
| AB | FAQ A2 | `faqs[1][answer]` (ACF repeater) | Repeater row 2 |
| AC | FAQ Q3 | `faqs[2][question]` (ACF repeater) | Repeater row 3 |
| AD | FAQ A3 | `faqs[2][answer]` (ACF repeater) | Repeater row 3 |
| AE | Primary CTA Text | `primary_cta_text` (ACF) | ACF text |
| AF | CTA Button Text | — | Skip |
| AG | Phone # Display | — | Skip |
| AH | Hero Image | `hero_image` (ACF image field) | Use local file `{slug}-hero.webp` via --images-dir |
| AI | Hero Image Alt Text | `hero_alt` (ACF text) | Also set as WP attachment alt text |
| AJ | Image 2 | `image_2` (ACF image field) | Use local file `{slug}-2.webp` via --images-dir |
| AK | Image 2 Alt Text | `image_2_alt` (ACF text) | Also set as WP attachment alt text |
| AL | Image 3 URL | — | Skip — column is empty |
| AM | Image 3 Alt Text | `image_3_alt` (ACF text) | Alt text for image 3 (sourced from AN folder) |
| AN | Image 3 | `image_3` (ACF image field) | Use local file `{slug}.webp` via --images-dir |
| AO | Internal Links TO | — | Skip |
| AP | Internal Links FROM | — | Skip |
| AQ | Schema Type | `schema_type` (ACF select) | Values: LocalBusiness / HVACBusiness |
| AR | OG Title | `rank_math_og_title` (Rank Math meta) | Write directly to Rank Math, no ACF field needed |
| AS | OG Description | `rank_math_og_description` (Rank Math meta) | Write directly to Rank Math, no ACF field needed |
| AT | Review 1 | `testimonials[0][quote]` + `testimonials[0][author]` | Parse author from end of string |
| AU | Review 2 | `testimonials[1][quote]` + `testimonials[1][author]` | Parse author from end of string |
| AV | Review 3 | `testimonials[2][quote]` + `testimonials[2][author]` | Parse author from end of string |
| AW | Notes / Comments | — | Skip — for client use only |
| AX | LHS Standard Additions | — | Skip — for client use only |

---

## Approach

Custom Post Type (`location`) + ACF field group + Elementor Theme Builder template wiring + CSV importer (WP-CLI + Admin UI).

The CPT registers with `rewrite => false` and a custom rewrite rule that maps `/{slug}/` to the CPT without any post-type prefix in the URL. ACF fields match the content columns above. Rank Math fields are written directly via post meta. The approved Elementor template is wired to pull from ACF fields via Dynamic Tags. The importer reads the CSV and sideloads images from the local images folder.

---

## 1. Custom Post Type: `location`

**New file:** `wp-content/themes/hello-theme-child-master/inc/cpt-location.php`

- Register CPT `location` with `rewrite => false`
- Public, `has_archive => false`, supports `title`, `editor`, `thumbnail`, `custom-fields`, `revisions`
- Menu label: "Locations", menu icon: `dashicons-location-alt`
- Add custom rewrite rule: `^([^/]+)/?$` → `index.php?location=$matches[1]` with `top` priority
  - **Important:** This is a broad rule — it must be registered carefully so it doesn't conflict with existing root-level WP pages. Test thoroughly after flushing rewrites.
- Override `post_type_link` filter to generate permalink as `/{slug}/`
- One-time rewrite flush on activation using a version option flag

---

## 2. ACF Field Group

**New file:** `wp-content/themes/hello-theme-child-master/inc/acf-location.php`

Registered via PHP (in git, not JSON export). Location rule: `Post Type == location`.

Note: SEO fields (meta title, meta description, OG title, OG description, focus keyword, secondary keywords) are **not** duplicated as ACF fields — Rank Math Pro displays and manages these natively in its own meta box in the post editor.

Fields organized via ACF Tab fields:

- **Hero** — `h1` (text, required), `hero_subheadline` (textarea), `hero_image` (image), `hero_alt` (text)
- **Intro** — `intro_paragraph` (wysiwyg)
- **Body Sections** — fixed pairs `section_1_h2` / `section_1_body` … `section_4_h2` / `section_4_body` (wysiwyg). Fixed fields not a repeater — Elementor Pro Dynamic Tags bind reliably to named ACF fields; repeater binding requires Loop Grid which adds unnecessary complexity for body content.
- **FAQ** — repeater `faqs` (3 rows): `question` (text), `answer` (textarea)
- **Testimonials** — repeater `testimonials` (3 rows): `author` (text), `quote` (textarea)
- **Images** — `image_2` (image), `image_2_alt` (text), `image_3` (image), `image_3_alt` (text)
- **CTA** — `primary_cta_text` (text)
- **Schema** — `schema_type` (select: `LocalBusiness` / `HVACBusiness`)

---

## 3. Elementor Template Wiring (Manual Step)

The approved Elementor Theme Builder template already exists at `https://goblueox.com/minneapolis/service-areas/new-location/`. This phase is manual — no code changes required. Use Elementor's Dynamic Tags UI to connect each widget to its ACF field.

**Steps:**

1. In WP Admin → Templates → Theme Builder, open the approved single template
2. Set the display condition to `Singular → Locations` (the `location` CPT) if not already set
3. For each content widget, click the dynamic tag icon and select **ACF Field**, then choose the matching field:

| Template Section | ACF Field |
|---|---|
| Page H1 | `h1` |
| Hero subheadline | `hero_subheadline` |
| Hero image | `hero_image` |
| Intro paragraph | `intro_paragraph` |
| Section 1 heading | `section_1_h2` |
| Section 1 body | `section_1_body` |
| Section 2 heading | `section_2_h2` |
| Section 2 body | `section_2_body` |
| Section 3 heading | `section_3_h2` |
| Section 3 body | `section_3_body` |
| Section 4 heading | `section_4_h2` |
| Section 4 body | `section_4_body` |
| CTA text | `primary_cta_text` |
| Image 2 | `image_2` |
| Image 3 | `image_3` |

4. **FAQ and Testimonial sections** — Elementor Pro Dynamic Tags cannot bind directly to repeater sub-fields. Two options:
   - **Option A (preferred):** Elementor Pro Loop Grid widget sourced from the `faqs` and `testimonials` ACF repeaters
   - **Option B (fallback):** Custom shortcodes `[blueox_location_faqs]` and `[blueox_location_testimonials]` rendered via a Shortcode widget

   **Decide which approach works with the approved template layout before building the importer.** This decision affects Phase 4.

5. After saving, run **Elementor → Tools → Regenerate CSS & Data**

---

## 4. Schema Markup

**New file:** `wp-content/themes/hello-theme-child-master/inc/location-schema.php`

- Hooks `wp_head` at priority 20
- Runs only on `location` CPT singulars
- Builds JSON-LD using the value of `schema_type` ACF field (`LocalBusiness` or `HVACBusiness`)
- Uses H1 for `name`, site default phone for `telephone`, H1/city for `areaServed`
- Maps `testimonials` repeater to `review` array
- Filters `rank_math/json_ld` to suppress Rank Math's own LocalBusiness output on these pages to avoid duplicates

---

## 5. CSV Importer (WP-CLI + Admin UI)

The importer logic lives in a single shared class. Both the WP-CLI command and the Admin UI page call the same code.

**New files:**
- `wp-content/themes/hello-theme-child-master/inc/location-importer.php` — core importer class
- `wp-content/themes/hello-theme-child-master/inc/cli-location-import.php` — WP-CLI command wrapper
- `wp-content/themes/hello-theme-child-master/inc/admin-location-import.php` — Tools → Import Locations admin page

**WP-CLI is accessed via Local's Site Shell** — click "Site Shell" in Local for the Blue Ox Dev site. No separate installation needed.

**WP-CLI command:**
```
wp blueox import-locations --file=cities.csv [--dry-run] [--only=maple-grove] [--images-dir=/path/to/images]
```

**Images directory for local files:**
```
/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images/
```

**Per-row logic:**

1. Skip rows where Column A is not "Location"
2. Skip rows 1–3 (group headers, column headers, example row)
3. Use Column C as the WordPress post title
4. Extract slug from Column D — take the last path segment and `sanitize_title()` it
5. Skip placeholder rows — cities with no H1 (Column N) are logged as "awaiting content"
6. Validate required fields: H1 (col N), Section 1 heading+body (Q+R), Section 2 (S+T), Section 3 (U+V). Skip and log if any required field is missing
7. Check for slug conflicts with existing WP pages (e.g. `/rochester/`, `/minneapolis/`) — log and skip rather than overwrite
8. Find or create a `location` CPT entry keyed on slug. If exists → update. If not → `wp_insert_post`
9. For each image, check `--images-dir` for local files first:
   - Hero: `{slug}-hero.webp`
   - Image 2: `{slug}-2.webp`
   - Image 3: `{slug}.webp`
   - Sideload via `media_handle_sideload`
   - Set alt text explicitly: `update_post_meta( $att_id, '_wp_attachment_image_alt', $alt )`
   - Cache attachment ID in post meta `_blueox_img_{field}_{slug}` to avoid re-uploading on reruns
10. `update_field()` for all ACF fields including FAQ and testimonial repeaters
11. Write Rank Math meta keys directly via `update_post_meta()`:
    - `rank_math_title` ← Column K
    - `rank_math_description` ← Column L
    - `rank_math_focus_keyword` ← Column E
    - `rank_math_keywords` ← Column F
    - `rank_math_og_title` ← Column AR
    - `rank_math_og_description` ← Column AS
12. Log result per row: created / updated / skipped-placeholder / skipped-conflict / skipped-incomplete / error
13. End with summary table

**Idempotent:** Keyed on slug. Re-running produces zero changes if content hasn't changed.

**Dry-run mode:** Reports planned actions and checks image files exist locally, makes no writes.

---

## 6. functions.php Wiring

Add to `wp-content/themes/hello-theme-child-master/functions.php`:

```php
require_once get_stylesheet_directory() . '/inc/cpt-location.php';
require_once get_stylesheet_directory() . '/inc/acf-location.php';
require_once get_stylesheet_directory() . '/inc/location-schema.php';
require_once get_stylesheet_directory() . '/inc/location-importer.php';
require_once get_stylesheet_directory() . '/inc/admin-location-import.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once get_stylesheet_directory() . '/inc/cli-location-import.php';
}
```

---

## Execution Task List

### Phase 0 — Prep (30 min)

- [ ] Open the approved Elementor template (`https://goblueox.com/minneapolis/service-areas/new-location/`) and document its full section structure — what widgets exist, in what order, and how FAQ and Testimonial sections are built (Loop Grid or shortcode placeholders)
- [ ] Decide on FAQ/Testimonial rendering approach (Loop Grid vs shortcode) — must be decided before Phase 3
- [ ] Confirm all processed images exist in the images folder: `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images/`
- [ ] Confirm Image 3 files (`{slug}.webp`) have been merged into the same Images folder
- [ ] Confirm ACF Pro, Elementor Pro, and Rank Math Pro are active on the dev environment
- [ ] Confirm WP-CLI is accessible via Local's Site Shell (`wp --info` to test)
- [ ] Confirm the CSV header row matches the column mapping table above

**Checkpoint 0:** Template structure documented, FAQ/Testimonial approach decided, all images confirmed in place, environment confirmed.

---

### Phase 1 — CPT + Rewrite Rules (1–2h)

- [ ] Create `inc/cpt-location.php`
- [ ] Register `location` CPT with `rewrite => false`
- [ ] Add custom rewrite rule `^([^/]+)/?$` → CPT with `top` priority
- [ ] Override `post_type_link` to generate `/{slug}/`
- [ ] Add `require_once` in `functions.php`
- [ ] Commit and push to dev
- [ ] Open Local Site Shell and run `wp rewrite flush`

**Checkpoint 1:**
- [ ] "Locations" menu visible in WP Admin
- [ ] Create a test entry with slug `test-location`
- [ ] Visit `/test-location/` — page resolves (404 or blank is fine, rewrite match is what matters)
- [ ] Visit existing pages like `/about-us/` and `/services/` — confirm they still resolve correctly
- [ ] Delete test entry when done

---

### Phase 2 — ACF Field Group (1–2h)

- [ ] Create `inc/acf-location.php`
- [ ] Register all fields per §2 above
- [ ] Mark `h1` as required
- [ ] Add `require_once` in `functions.php`
- [ ] Commit and push to dev

**Checkpoint 2:**
- [ ] Create a new `location` post in admin
- [ ] All field tabs render correctly (Hero, Intro, Body Sections, FAQ, Testimonials, Images, CTA, Schema)
- [ ] Rank Math meta box also visible on the same post edit screen (native Rank Math fields)
- [ ] FAQ repeater adds and removes rows correctly
- [ ] Testimonials repeater adds and removes rows correctly
- [ ] Image fields accept uploads
- [ ] WYSIWYG fields show the editor toolbar

---

### Phase 3 — Template Wiring (2–3h)

- [ ] In Elementor Theme Builder, open the approved template
- [ ] Set display condition to `Singular → Locations`
- [ ] Wire each widget to its ACF field using Dynamic Tags (see wiring table in §3)
- [ ] Implement FAQ and Testimonial rendering (Loop Grid or shortcode) per Phase 0 decision
- [ ] If shortcode route: create `inc/location-shortcodes.php` with `[blueox_location_faqs]` and `[blueox_location_testimonials]`
- [ ] Manually create one test `location` entry (e.g. Maple Grove) — set WP title to "Maple Grove", fill all ACF fields by hand from the CSV row
- [ ] Run Elementor → Tools → Regenerate CSS & Data

**Checkpoint 3 (visual test):**
- [ ] Visit `/maple-grove/` on dev — approved template renders
- [ ] WP title "Maple Grove" used as post title in admin
- [ ] H1 on page pulled from ACF `h1` field
- [ ] Hero subhead, intro, all body sections populated from ACF fields
- [ ] FAQ accordion shows 3 questions and expands/collapses
- [ ] Testimonials section shows 3 reviews
- [ ] Hero image, image 2, image 3 all render
- [ ] Phone number defaults to site default (Rochester)
- [ ] Rank Math snippet preview shows correct meta title and description

---

### Phase 4 — Schema Markup (1–2h)

- [ ] Create `inc/location-schema.php`
- [ ] Hook `wp_head` at priority 20, run only on `location` singulars
- [ ] Build JSON-LD from ACF `schema_type`, `h1`, and `testimonials` fields
- [ ] Filter `rank_math/json_ld` to suppress Rank Math LocalBusiness on these pages
- [ ] Add `require_once` in `functions.php`
- [ ] Commit and push to dev

**Checkpoint 4:**
- [ ] View source on `/maple-grove/` — JSON-LD block present
- [ ] Google Rich Results test → valid, no errors
- [ ] No duplicate LocalBusiness schema from Rank Math

---

### Phase 5 — Importer Core (4–6h)

- [ ] Create `inc/location-importer.php`
- [ ] CSV parsing via `fgetcsv`, column header mapping
- [ ] Column C → WP post title, Column D → slug extraction
- [ ] Row filtering (Location rows only, skip rows 1–3, skip placeholders, skip slug conflicts)
- [ ] Required field validation
- [ ] Find-or-create `location` CPT entry keyed on slug
- [ ] Image sideloading from `--images-dir` local folder with alt text and attachment ID caching
- [ ] `update_field()` for all ACF fields including repeaters
- [ ] Rank Math meta key writes (6 keys as listed in §5)
- [ ] Dry-run mode
- [ ] Result logging and summary table
- [ ] Add `require_once` in `functions.php`

**Checkpoint 5 (pilot — Maple Grove):**
- [ ] Delete the manually-created Maple Grove entry from Phase 3
- [ ] Run via Local Site Shell: `wp blueox import-locations --file=cities.csv --only=maple-grove --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images"`
- [ ] New entry created with WP title "Maple Grove" and all ACF fields populated
- [ ] 3 images in media library with correct alt text
- [ ] Rank Math meta title, description, focus keyword match CSV
- [ ] Page renders identically to Phase 3 manual result
- [ ] Re-run → zero changes (idempotent confirmed)

---

### Phase 6 — WP-CLI Wrapper (1h)

- [ ] Create `inc/cli-location-import.php`
- [ ] Load only if `defined('WP_CLI')`
- [ ] Register `wp blueox import-locations` command
- [ ] Support `--file`, `--dry-run`, `--only`, `--images-dir`
- [ ] Print summary table at end
- [ ] Add `require_once` in `functions.php`

**Checkpoint 6:**
- [ ] `wp blueox import-locations --file=cities.csv --only=maple-grove --dry-run` → prints plan, no writes
- [ ] Full run for maple-grove → correct entry
- [ ] Re-run → zero diffs
- [ ] Missing `--file` → clear error message

---

### Phase 7 — Admin UI (2–3h)

- [ ] Create `inc/admin-location-import.php`
- [ ] Register Tools → Import Locations menu page
- [ ] `manage_options` capability check
- [ ] Form: CSV upload, dry-run checkbox, "only" slug filter, images-dir path field, run button
- [ ] Nonce verification on submit
- [ ] Call core importer, render result table inline
- [ ] Add `require_once` in `functions.php`

**Checkpoint 7:**
- [ ] Tools → Import Locations visible in WP Admin
- [ ] Upload CSV, tick dry-run, filter to `only=maple-grove` → result table shows planned changes
- [ ] Uncheck dry-run, run live → entry updated correctly
- [ ] Non-admin user cannot access the page
- [ ] Missing CSV → clear error message

---

### Phase 8 — Bulk Import on Dev (1–2h)

- [ ] Run full import via WP-CLI Site Shell:
  `wp blueox import-locations --file=cities.csv --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images"`
- [ ] Review summary: created / updated / skipped / errors
- [ ] Resolve any errors and re-run until zero errors

**Checkpoint 8:**
- [ ] All ~70 cities visible in WP Admin → Locations
- [ ] Spot-check 5 random city pages: template renders, images load, schema valid
- [ ] Rank Math sitemap includes all `location` CPT URLs
- [ ] Client sign-off on 2–3 sample pages

---

### Phase 9 — Push to WP Engine Dev Environment (1h)

- [ ] Commit all theme changes and push to GitHub (`feature/location-pages` branch)
- [ ] In Local, push Blue Ox Dev site to WP Engine dev environment using the Push button
- [ ] Run `wp rewrite flush` on WP Engine dev via Site Shell or WP Engine SSH
- [ ] Run importer on WP Engine dev environment
- [ ] Elementor → Tools → Regenerate CSS & Data
- [ ] Purge WP Engine cache via WP Engine User Portal or plugin
- [ ] Test 3–5 pages on the WP Engine dev URL

---

## Pitfalls

1. **Broad rewrite rule** — `^([^/]+)/?$` matches every root-level slug. Register with `top` priority but test carefully. After flushing rewrites, visit existing pages like `/about-us/`, `/services/`, `/rochester/` and confirm they still resolve correctly before proceeding.

2. **Slug conflicts** — `/rochester/` and `/minneapolis/` already exist as standard WP pages. The importer must detect these and log them as conflicts rather than overwriting. Do not delete these pages.

3. **Elementor Theme Builder display condition** — After setting the condition to `Singular → Locations` and saving, always run Elementor Tools → Regenerate CSS & Data. Stale CSS is a common source of rendering issues.

4. **ACF repeaters in Elementor Dynamic Tags** — Elementor Pro Dynamic Tags cannot bind directly to repeater sub-fields. Use either Loop Grid (sourced from the ACF repeater) or a custom shortcode. This must be decided in Phase 0.

5. **Rank Math meta vs ACF** — Rank Math stores its SEO data in its own post meta keys. Writing only to ACF fields has no effect on what Rank Math outputs. The importer must write to the Rank Math meta keys directly using `update_post_meta()`.

6. **`media_handle_sideload` alt text** — Does not reliably set alt text from `post_excerpt`. The importer must explicitly call `update_post_meta( $att_id, '_wp_attachment_image_alt', $alt )` after every sideload.

7. **WP-CLI via Local Site Shell** — WP-CLI commands must be run from inside Local's Site Shell for the correct environment. Do not use the regular Mac Terminal for WP-CLI commands.

8. **WP Engine cache** — After running the importer on the WP Engine dev environment, purge cache via the WP Engine User Portal or the WP Engine plugin in WP Admin.

9. **Image 3 files** — These are named `{slug}.webp` (no suffix like `-hero` or `-2`). Ensure they have been moved into the same Images folder as the other processed images before running the importer.

---

## Critical Files

**New:**
- `wp-content/themes/hello-theme-child-master/inc/cpt-location.php`
- `wp-content/themes/hello-theme-child-master/inc/acf-location.php`
- `wp-content/themes/hello-theme-child-master/inc/location-schema.php`
- `wp-content/themes/hello-theme-child-master/inc/location-importer.php`
- `wp-content/themes/hello-theme-child-master/inc/cli-location-import.php`
- `wp-content/themes/hello-theme-child-master/inc/admin-location-import.php`

**Modified:**
- `wp-content/themes/hello-theme-child-master/functions.php` — add 6 `require_once` lines

**Reused as-is (no changes):**
- `wp-content/themes/hello-theme-child-master/inc/shortcodes-location.php`
- `wp-content/themes/hello-theme-child-master/inc/location-swap.js`

**WordPress admin (manual, not in code):**
- Elementor Theme Builder → approved Single template, display condition set to `Singular → Locations`, all widgets wired to ACF fields

**Local assets:**
- `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/Images/` — all processed images (`{slug}-hero.webp`, `{slug}-2.webp`, `{slug}.webp`)
- `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/BlueOx_SEO_Final(Location).csv` — source data
- `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/download_images.py` — image processing script
- `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Blueox Location Pages/download_log.txt` — image download log

---

## Client Content Guidelines (For Future Reference)

These guidelines are for the client when preparing CSV content for future page builds. A full client-facing guide will be produced separately, but these notes are captured here for reference.

### URL Slugs (Column D)
- Always include a trailing slash: `https://goblueox.com/city-name/` not `https://goblueox.com/city-name`
- Use lowercase, hyphen-separated slugs only (e.g. `north-saint-paul` not `North Saint Paul`)

### Images (Columns AH and AJ — Hero and Image 2)
- **Format:** JPG or PNG accepted (will be converted to WebP during processing)
- **Maximum file size:** No strict limit but ideally under 25MB per file to avoid Google Drive download issues
- **Minimum width:** 1440px wide recommended (images will be resized down, never up)
- **Sharing:** All Google Drive image links must be set to "Anyone with the link can view" — no sign-in required
- Individual Google Drive sharing links are acceptable for columns AH and AJ

### Image 3 (Column AN — Separate Drive Folder)
- **Preferred delivery method:** A single shared Google Drive folder containing all Image 3 files is strongly preferred over individual sharing links
- **Naming convention:** Files must be named using the page slug followed by `.webp` — e.g. `north-saint-paul.webp`, `maple-grove.webp`
- **Format:** WebP preferred, JPG or PNG also accepted
- **Sharing:** The folder must be set to "Anyone with the link can view"
- This approach allows bulk download of all Image 3 files in one zip, which is faster and less error-prone than individual links
