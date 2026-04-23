# Paul Bunyan Location Pages — Build Spec

> **Base architecture:** This build follows the same architecture as the Blue Ox Location Pages build. See `blueox-location-pages-spec.md` (in `LSP-paulbunyanplumbing/`) for all shared concepts, patterns, and rationale. This document covers only what is **different or specific to Paul Bunyan**. When in doubt, defer to the Blue Ox spec.

---

## Project Overview

**Client:** Paul Bunyan Plumbing & Drains
**Domain:** paulbunyanplumbing.com
**Total location pages:** 68
**Protected slugs:** `minneapolis`, `rochester` (already exist as standard WP pages — importer must detect and skip)

---

## Environment & Paths

| Item | Value |
|---|---|
| Hosting | WP Engine |
| WPE environment name | `paulbunyans` |
| Local site path | `/Users/tonyst.claire/Local Sites/paul-bunyan-plumbing-drains/app/public/` |
| Cursor project path | `/Users/tonyst.claire/Desktop/Blackhawk/Cursor/Sites/paulbunyan/LSP-paulbunyanplumbing/` |
| WPE server webroot | `/nas/content/live/paulbunyans/` |
| Images directory on WPE server | `/nas/content/live/paulbunyans/wp-content/uploads/location-images/` |

> **Critical:** The WPE webroot is `/nas/content/live/paulbunyans/` — **do not use** `/home/wpe-user/apps/paulbunyans/public/`. That path does not exist on this environment.

---

## SSH & Rsync

**SSH into WP Engine:**
```bash
ssh -i ~/.ssh/rick_wpengine -o IdentitiesOnly=yes paulbunyans@paulbunyans.ssh.wpengine.net
```

**Rsync from Cursor project → Local site (theme files only):**
```bash
rsync -av --delete \
  "/Users/tonyst.claire/Desktop/Blackhawk/Cursor/Sites/paulbunyan/LSP-paulbunyanplumbing/wp-content/themes/hello-theme-child-master/" \
  "/Users/tonyst.claire/Local Sites/paul-bunyan-plumbing-drains/app/public/wp-content/themes/hello-theme-child-master/"
```

---

## Naming Conventions

All function and class prefixes differ from Blue Ox:

| Item | Blue Ox | Paul Bunyan |
|---|---|---|
| PHP function prefix | `blueox_` | `paulbunyan_` |
| PHP class prefix | `BlueOx_` | `PaulBunyan_` |
| ACF field key prefix | `field_blueloc_` | `field_pbloc_` |
| WP-CLI command | `wp blueox import-locations` | `wp paulbunyan import-locations` |
| Importer image cache meta key prefix | `_blueox_img_{field}_{slug}` | `_pb_img_{field}_{slug}` |

---

## Elementor Template

**Template ID:** `8724`

The constant `PAULBUNYAN_LOCATION_ELEMENTOR_TEMPLATE_ID` is set in `inc/elementor-location-override.php`. If the template is ever deleted and recreated, update this constant to the new template ID.

---

## Image Assets

Images are split across **two source folders** (unlike Blue Ox which used one):

### Folder 1 — `Images/`
Contains hero and image 2 files, downloaded and processed via `download_images.py`.

| File | CSV source | Description |
|---|---|---|
| `{slug}-hero.webp` | Column AH | Hero image |
| `{slug}-2.webp` | Column AJ | Image 2 |

### Folder 2 — `Images 3AN/`
Contains image 3 files, provided directly by the client. Files are named without any suffix — just the slug.

| File | CSV source | Description |
|---|---|---|
| `{slug}.webp` | Column AN | Image 3 |

> Note the folder name has a space: `Images 3AN/`. Quote the path when using it in shell commands.

### Image Fallbacks

When a location's image file is not found in the images directory, the importer falls back to the Maple Grove images:

| Field | Fallback file |
|---|---|
| `hero_image` | `maple-grove-hero.webp` |
| `image_2` | `maple-grove-2.webp` |
| `image_3` | `maple-grove.webp` (attachment already sideloaded — attachment ID `8783`) |

The `maple-grove.webp` attachment is already present in the media library and does not need to be re-sideloaded. The importer should use attachment ID `8783` directly when falling back for `image_3`.

---

## CSV Differences vs Blue Ox

**File:** `PaulBunyan_SEO_Final(Location).csv` (adjust filename as needed)

The Paul Bunyan CSV has **56 columns** vs Blue Ox's 57. The "Links to 24/7 Text" column present at position AO in Blue Ox (column 39 in zero-indexed terms) is **absent** in the Paul Bunyan CSV. As a result, **all columns from the equivalent of Blue Ox column AO onwards are shifted left by 1**.

Row structure is the same as Blue Ox: Row 2 = headers, Row 3 = example/skip, Row 4 = first real data row.

### Paul Bunyan Column Mapping

| CSV Column | Column Name | Destination | Notes |
|---|---|---|---|
| A | Page Type | — | Skip — filter to "Location" rows only |
| B | Phase | — | Skip |
| C | Location Name | WordPress post title | Used as WP post title, not ACF |
| D | URL Slug | Post slug | Extract last path segment, `sanitize_title()` |
| E | Primary Keyword | `rank_math_focus_keyword` | Write directly to Rank Math |
| F | Secondary Keywords | `rank_math_keywords` | Write directly to Rank Math |
| G | Status | — | Skip |
| H | Assigned To | — | Skip |
| I | Due Date | — | Skip |
| J | Publish Date | — | Skip |
| K | Title Tag | `rank_math_title` | Write directly to Rank Math |
| L | Meta Description | `rank_math_description` | Write directly to Rank Math |
| M | Canonical URL | — | Skip — data outdated |
| N | H1 Heading | `h1` (ACF) | Required — skip row if missing |
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
| AH | Hero Image | `hero_image` (ACF image) | Use `{slug}-hero.webp` from Images/ |
| AI | Hero Image Alt Text | `hero_alt` (ACF text) | Also set as WP attachment alt text |
| AJ | Image 2 | `image_2` (ACF image) | Use `{slug}-2.webp` from Images/ |
| AK | Image 2 Alt Text | `image_2_alt` (ACF text) | Also set as WP attachment alt text |
| AL | Image 3 URL | — | Skip — column is empty |
| AM | Image 3 Alt Text | `image_3_alt` (ACF text) | Alt text for image 3 |
| AN | Image 3 | `image_3` (ACF image) | Use `{slug}.webp` from Images 3AN/ |
| AO | Internal Links TO | — | Skip |
| AP | Internal Links FROM | — | Skip |
| AQ | Schema Type | — | **Ignored** — importer hardcodes `Plumber` regardless of CSV value (see Schema section below) |
| AR | OG Title | `rank_math_og_title` | Write directly to Rank Math |
| AS | OG Description | `rank_math_og_description` | Write directly to Rank Math |
| AT | Review 1 | `testimonials[0][quote]` + `testimonials[0][author]` | Parse author from end of string |
| AU | Review 2 | `testimonials[1][quote]` + `testimonials[1][author]` | Parse author from end of string |
| AV | Review 3 | `testimonials[2][quote]` + `testimonials[2][author]` | Parse author from end of string |
| AW | Notes / Comments | — | Skip — client use only |
| AX | LHS Standard Additions | — | Skip — client use only |

> **Shifted columns:** In Blue Ox, "Links to 24/7 Text" occupied column AO (position 40). Paul Bunyan omits this column entirely. Everything from AO onward in Paul Bunyan maps to what was AP onward in Blue Ox. The mapping table above already reflects the correct Paul Bunyan column letters.

---

## Schema Type — Always Plumber

The Paul Bunyan CSV's Schema Type column (AQ) contains values like `LocalBusiness, Plumber`. The importer **ignores this column entirely** and hardcodes `Plumber` as the schema type for all location pages. There is no `schema_type` ACF select field needed for Paul Bunyan — the schema output always uses `Plumber`.

---

## ACF Field Group

Field key prefix: `field_pbloc_`

All fields are the same as Blue Ox except:
- The `schema_type` ACF select field (`LocalBusiness` / `HVACBusiness`) is **not present** in Paul Bunyan — schema type is hardcoded in the importer and schema output file.

---

## WP-CLI Command

```bash
wp paulbunyan import-locations --file=cities.csv [--dry-run] [--only=maple-grove] [--images-dir=/path/to/images]
```

Replace `wp blueox` with `wp paulbunyan` everywhere.

---

## Image Cache — SQL Clear for image_3

If image_3 files need to be re-sideloaded (e.g., after replacing source files), the attachment ID cache must be cleared first. The importer caches attachment IDs in post meta to avoid re-uploading on reruns. For `image_3`, clear with:

```sql
DELETE FROM wp_postmeta WHERE meta_key LIKE '_pb_img_3_%';
```

Run this in WP Admin → Tools → phpMyAdmin, or via WP-CLI:
```bash
wp db query "DELETE FROM wp_postmeta WHERE meta_key LIKE '_pb_img_3_%';"
```

After clearing, re-run the importer. It will re-sideload image_3 for all locations.

---

## Critical Files

**New (Paul Bunyan equivalents):**
- `wp-content/themes/hello-theme-child-master/inc/cpt-location.php`
- `wp-content/themes/hello-theme-child-master/inc/acf-location.php`
- `wp-content/themes/hello-theme-child-master/inc/location-schema.php`
- `wp-content/themes/hello-theme-child-master/inc/location-importer.php`
- `wp-content/themes/hello-theme-child-master/inc/cli-location-import.php`
- `wp-content/themes/hello-theme-child-master/inc/admin-location-import.php`
- `wp-content/themes/hello-theme-child-master/inc/elementor-location-override.php` — sets `PAULBUNYAN_LOCATION_ELEMENTOR_TEMPLATE_ID = 8724`

**Modified:**
- `wp-content/themes/hello-theme-child-master/functions.php` — add `require_once` lines for all inc files above

**Local assets:**
- `Images/` — processed hero (`{slug}-hero.webp`) and image 2 (`{slug}-2.webp`) files
- `Images 3AN/` — image 3 files (`{slug}.webp`), provided by client
- CSV source file — `PaulBunyan_SEO_Final(Location).csv`
- `download_images.py` — image download/processing script for hero and image 2

---

## Critical Lessons Learned

1. **WPE webroot path** — The server webroot is `/nas/content/live/paulbunyans/`. The path `/home/wpe-user/apps/paulbunyans/public/` does **not exist** on this environment. Any SSH, rsync, or WP-CLI path references must use the correct `/nas/content/live/paulbunyans/` root.

2. **image_3 cache must be cleared before re-sideloading** — The importer caches attachment IDs in `_pb_img_3_{slug}` post meta. If image_3 files are replaced or the sideload needs to be redone, run the SQL delete above before re-running the importer, or the old (stale) attachment IDs will be reused.

3. **maple-grove.webp fallback for image_3 is pre-sideloaded** — Attachment ID `8783` is already in the media library. The importer should reference this ID directly for the `image_3` fallback rather than attempting to sideload it again.

4. **Admin importer images dir on WPE** — When running the Admin UI importer (Tools → Import Locations) on WP Engine, the images directory path is `/nas/content/live/paulbunyans/wp-content/uploads/location-images/`.

5. **Two image source folders** — Unlike Blue Ox (one folder), Paul Bunyan has `Images/` for hero and image 2, and `Images 3AN/` for image 3. The `--images-dir` flag (or admin UI field) must either accept two paths or the files must be merged into a single folder before running the importer.

---

## Pitfalls (Paul Bunyan–Specific Additions)

All pitfalls from the Blue Ox spec apply. Additional items specific to Paul Bunyan:

1. **WPE path** — See lesson 1 above. Using the wrong webroot path in SSH or rsync commands will silently fail or target a non-existent directory.

2. **56-column CSV** — The Paul Bunyan CSV has one fewer column than Blue Ox. If adapting the Blue Ox importer code, the column index for every field from AO onward must be decremented by 1. Double-check the column mapping table above against the actual CSV header row before running.

3. **Schema type hardcoded** — Do not read `schema_type` from the CSV. The JSON-LD output always uses `"@type": "Plumber"`.

4. **image_3 folder name has a space** — `Images 3AN/` — always quote this path in shell commands and PHP code.

5. **image_3 fallback uses a pre-existing attachment** — Do not attempt to re-sideload `maple-grove.webp` for image_3 fallback. Use attachment ID `8783` directly.
