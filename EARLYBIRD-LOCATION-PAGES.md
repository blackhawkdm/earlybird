# EarlyBird Electricians — Location Pages Build Spec

> **Base architecture:** Same as Blue Ox and Paul Bunyan. See `blueox-location-pages-spec.md` and `blueox-location-pages-build-notes.md` for all shared concepts, patterns, lessons learned, and the repeating-on-a-new-site checklist. This document covers only what is **different or specific to EarlyBird**.

---

## Project Overview

| Item | Value |
|---|---|
| Client | EarlyBird Electricians |
| Domain | earlybirdelectricians.com |
| Total location pages | 68 |
| Protected slugs | TBD — check WP Admin for any existing pages that conflict |

---

## Environment & Paths

| Item | Value |
|---|---|
| Hosting | WP Engine |
| WPE environment name | TBD — check with client or WPE dashboard |
| Local site path | `/Users/tonyst.claire/Local Sites/{earlybird-local-site-name}/app/public/` |
| Cursor project path | `/Users/tonyst.claire/Desktop/Blackhawk/Cursor/Sites/earlybird/LSP-earlybirdelectricians/` (create this) |
| WPE server webroot | `/nas/content/live/{wpe-env-name}/` |
| Images directory on WPE server | `/nas/content/live/{wpe-env-name}/wp-content/uploads/location-images/` |

> **Critical:** WPE webroot is always `/nas/content/live/{env}/` — do NOT use `/home/wpe-user/apps/{env}/public/`. That path does not exist.

---

## Naming Conventions

All function and class prefixes differ from Blue Ox and Paul Bunyan:

| Item | Blue Ox | Paul Bunyan | EarlyBird |
|---|---|---|---|
| PHP function prefix | `blueox_` | `paulbunyan_` | `earlybird_` |
| PHP class prefix | `BlueOx_` | `PaulBunyan_` | `EarlyBird_` |
| ACF field key prefix | `field_blueloc_` | `field_pbloc_` | `field_ebloc_` |
| WP-CLI command | `wp blueox import-locations` | `wp paulbunyan import-locations` | `wp earlybird import-locations` |
| Importer image cache meta key prefix | `_blueox_img_` | `_pb_img_` | `_eb_img_` |

---

## Elementor Template

The constant name in `elementor-location-override.php` should be:

```php
define( 'EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID', XXXX );
```

**Find the template ID:** WP Admin → Templates → Theme Builder → open "Location Page Template" → read `post=XXXX` from the URL.

---

## CSV Column Mapping

**File:** `EarlyBird_SEO_Final_Location_Pages_.csv`
**Total columns:** 57 | **Location rows:** 68

Row 2 = headers, Row 3 = example/skip, Row 4 = first real data row.

> **Key difference vs Blue Ox/Paul Bunyan:** EarlyBird has a "CTA Image" column at AG (col 32) that does not exist in Blue Ox. This shifts all image columns right by 1 starting at AG. The importer uses dynamic header detection, so this is handled automatically — but the column mapping table below reflects the correct EarlyBird positions.

| CSV Column | Col # | Column Name | Destination | Notes |
|---|---|---|---|---|
| A | 0 | Page Type | — | Filter to "Location" rows only |
| B | 1 | Phase | — | Skip |
| C | 2 | Location Name | WordPress post title | Used as WP post title |
| D | 3 | URL Slug | Post slug | Extract last path segment |
| E | 4 | Primary Keyword | `rank_math_focus_keyword` | |
| F | 5 | Secondary Keywords | `rank_math_keywords` | |
| G | 6 | Status | — | Skip |
| H | 7 | Assigned To | — | Skip |
| I | 8 | Due Date | — | Skip |
| J | 9 | Publish Date | — | Skip |
| K | 10 | Title Tag | `rank_math_title` | |
| L | 11 | Meta Description | `rank_math_description` | |
| M | 12 | Canonical URL | — | Skip |
| N | 13 | H1 Heading | `h1` (ACF) | Required — skip row if missing |
| O | 14 | Hero Subheadline | `hero_subheadline` (ACF) | |
| P | 15 | Intro Paragraph | `intro_paragraph` (ACF) | |
| Q | 16 | Section 1 Heading | `section_1_h2` (ACF) | Required |
| R | 17 | Section 1 Body | `section_1_body` (ACF) | Required |
| S | 18 | Section 2 Heading | `section_2_h2` (ACF) | Required |
| T | 19 | Section 2 Body | `section_2_body` (ACF) | Required |
| U | 20 | Section 3 Heading | `section_3_h2` (ACF) | Required |
| V | 21 | Section 3 Body | `section_3_body` (ACF) | Required |
| W | 22 | Section 4 Heading | `section_4_h2` (ACF) | Optional |
| X | 23 | Section 4 Body | `section_4_body` (ACF) | Optional |
| Y | 24 | FAQ Q1 | `faqs[0][question]` (ACF repeater) | |
| Z | 25 | FAQ A1 | `faqs[0][answer]` (ACF repeater) | |
| AA | 26 | FAQ Q2 | `faqs[1][question]` (ACF repeater) | |
| AB | 27 | FAQ A2 | `faqs[1][answer]` (ACF repeater) | |
| AC | 28 | FAQ Q3 | `faqs[2][question]` (ACF repeater) | |
| AD | 29 | FAQ A3 | `faqs[2][answer]` (ACF repeater) | |
| AE | 30 | Primary CTA Text | `primary_cta_text` (ACF) | |
| AF | 31 | CTA Button Text | — | Skip |
| AG | 32 | CTA Image | — | **NEW vs Blue Ox** — Skip (Drive URL, not used by importer) |
| AH | 33 | Phone # Display | — | Skip |
| AI | 34 | Hero Image | `hero_image` (ACF image) | Use `{slug}-hero.webp` from Images/ |
| AJ | 35 | Hero Image Alt Text | `hero_alt` (ACF text) | Also set as WP attachment alt |
| AK | 36 | Image 2 | `image_2` (ACF image) | Use `{slug}-2.webp` from Images/ |
| AL | 37 | Image 2 Alt Text | `image_2_alt` (ACF text) | Also set as WP attachment alt |
| AM | 38 | Image 3 | — | **Empty for all rows — skip** |
| AN | 39 | Image 3 Alt Text | `image_3_alt` (ACF text) | Only populate if image_3 exists |
| AO | 40 | Internal Links TO | — | Skip |
| AP | 41 | Internal Links FROM | — | Skip |
| AQ | 42 | Schema Type | — | Hardcode `Electrician` (see below) |
| AR | 43 | OG Title | `rank_math_og_title` | |
| AS | 44 | OG Description | `rank_math_og_description` | |
| AT | 45 | Review 1 | `testimonials[0][quote]` + `testimonials[0][author]` | Parse author from end |
| AU | 46 | Review 2 | `testimonials[1][quote]` + `testimonials[1][author]` | |
| AV | 47 | Review 3 | `testimonials[2][quote]` + `testimonials[2][author]` | |
| AW | 48 | Notes / Comments | — | Skip |
| AX–A_ | 49–56 | Various internal fields | — | Skip |

---

## Schema Type — Always Electrician

The CSV Schema Type column (AQ) contains values like `LocalBusiness, Electrician`. The importer **ignores this and hardcodes `Electrician`** as the schema type for all location pages. No `schema_type` ACF select field needed.

---

## Image Assets

Only **two image types** per location (no image 3):

| File | CSV column | Col # | Description |
|---|---|---|---|
| `{slug}-hero.webp` | AI | 34 | Hero image |
| `{slug}-2.webp` | AK | 36 | Image 2 |

All images live in a single `Images/` folder — no split folders like Paul Bunyan.

### Image Fallbacks

Upload these to the media library on the EarlyBird local site before running the importer. Use an EarlyBird-branded image (or temporarily reuse the Blue Ox ones to test):

| Field | Fallback file |
|---|---|
| `hero_image` | `{any-slug}-hero.webp` sideloaded to media library — note the attachment ID |
| `image_2` | `{any-slug}-2.webp` sideloaded to media library — note the attachment ID |

Update the fallback attachment IDs in `location-importer.php` (look for the `FALLBACK_*` constants or array).

---

## Setup Checklist (Run in Order)

### Step 1 — Copy theme files from Blue Ox (or Paul Bunyan) to EarlyBird local site

```bash
rsync -av --delete \
  "/Users/tonyst.claire/Desktop/Blackhawk/Cursor/Sites/paulbunyan/LSP-paulbunyanplumbing/wp-content/themes/hello-theme-child-master/" \
  "/Users/tonyst.claire/Local Sites/{earlybird-local-site-name}/app/public/wp-content/themes/hello-theme-child-master/"
```

### Step 2 — Rename all prefixes in Cursor (find & replace across project)

Open the copied theme in Cursor. Do project-wide find & replace:

| Find | Replace |
|---|---|
| `paulbunyan_` | `earlybird_` |
| `PaulBunyan_` | `EarlyBird_` |
| `field_pbloc_` | `field_ebloc_` |
| `wp paulbunyan` | `wp earlybird` |
| `_pb_img_` | `_eb_img_` |
| `PAULBUNYAN_LOCATION_` | `EARLYBIRD_LOCATION_` |

Also update any string literals referencing "Paul Bunyan" or "paulbunyans" (WPE env name, SSH host, etc.).

### Step 3 — Update column mapping in location-importer.php

The importer uses dynamic header detection, so it should pick up column positions automatically from the CSV header row. However, confirm these in `location-importer.php` after the find/replace:

- Hero image column header: should match `"Hero Image"` (in AI / col 34)
- Image 2 column header: should match `"Image 2"` (in AK / col 36)
- Schema type: hardcode `Electrician` (replace `HVACBusiness` or `Plumber`)

### Step 4 — Update WPE environment name and SSH details

In any file referencing the WPE environment (SSH commands, WP-CLI paths, admin importer):

```bash
# SSH command
ssh -i ~/.ssh/rick_wpengine -o IdentitiesOnly=yes {earlybird-env}@{earlybird-env}.ssh.wpengine.net

# WPE webroot
/nas/content/live/{earlybird-env}/
```

### Step 5 — Rsync Cursor → Local after all edits

```bash
rsync -av --delete \
  "/Users/tonyst.claire/Desktop/Blackhawk/Cursor/Sites/earlybird/LSP-earlybirdelectricians/wp-content/themes/hello-theme-child-master/" \
  "/Users/tonyst.claire/Local Sites/{earlybird-local-site-name}/app/public/wp-content/themes/hello-theme-child-master/"
```

### Step 6 — Find the Elementor template ID on the local site

WP Admin → Templates → Theme Builder → open "Location Page Template" → URL shows `post=XXXX`.

Update `inc/elementor-location-override.php`:

```php
define( 'EARLYBIRD_LOCATION_ELEMENTOR_TEMPLATE_ID', XXXX );
```

Rsync again after saving.

### Step 7 — Confirm Elementor template is Published

Elementor → Theme Builder → "Location Page Template" must be **Published**. Unpublished = silent render failure.

### Step 8 — Upload fallback images to local media library

Upload one hero and one image 2 file to the WP media library. Note their attachment IDs (check URL in media library). Update fallback IDs in `location-importer.php`.

### Step 9 — Download images

Run the updated `download_images.py` to pull all hero and image 2 files from Google Drive:

```bash
cd "/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages"
python3 download_images.py
```

Images save to: `/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images/`

### Step 10 — Dry run

```bash
wp earlybird import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/EarlyBird_SEO_Final_Location_Pages_.csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images" \
  --dry-run
```

Check for: skipped-incomplete rows, image warn lines, correct count (~68 Location rows).

### Step 11 — Pilot import (single city)

Pick a city you have images for:

```bash
wp earlybird import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/EarlyBird_SEO_Final_Location_Pages_.csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images" \
  --only=saint-paul
```

Visit the page and confirm: Elementor template body renders, ACF fields show content, hero image is visible, Rank Math shows title/description.

### Step 12 — Full import

```bash
wp earlybird import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/EarlyBird_SEO_Final_Location_Pages_.csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images"
```

### Step 13 — Post-import checks

```bash
# Confirm page count
wp post list --post_type=page --meta_key=_wp_page_template \
  --meta_value=template-location.php --format=count

# Spot-check a page
wp post meta get {POST_ID} h1
wp post meta get {POST_ID} rank_math_title
```

---

## Critical Pitfalls

1. **WPE webroot path** — Always `/nas/content/live/{env}/`. Never `/home/wpe-user/apps/{env}/public/`.

2. **Elementor template ID** — Must be updated per environment. Wrong ID = blank page body. First thing to check if frontend doesn't render.

3. **Column shift at AG** — EarlyBird has "CTA Image" at AG (col 32) which Blue Ox does not. Hero image is at AI (col 34) not AH (col 33). The dynamic header detection handles this, but double-check if image fields aren't populating.

4. **No image 3** — AM column is empty for all EarlyBird rows. Do not expect image_3 files. Remove any image_3 logic from the importer or leave it as a graceful no-op.

5. **`rsync --delete`** — Always use this, never `cp -r`. Without `--delete`, stale files from the source site (Blue Ox/Paul Bunyan references) won't be removed.

6. **Fallback images** — Must be uploaded to the media library before running the importer. If attachment IDs aren't set, image fields are simply skipped on fallback.

7. **Schema type** — Hardcode `Electrician`. Do not read from CSV column AQ.

---

## WP-CLI Import Commands (Reference)

```bash
# Dry run
wp earlybird import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/EarlyBird_SEO_Final_Location_Pages_.csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images" \
  --dry-run

# Single city pilot
wp earlybird import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/EarlyBird_SEO_Final_Location_Pages_.csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images" \
  --only=saint-paul

# Full import
wp earlybird import-locations \
  --file="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/EarlyBird_SEO_Final_Location_Pages_.csv" \
  --images-dir="/Users/tonyst.claire/Desktop/Blackhawk/Projects/Earlybid Location Pages/Images"
```

---

## Image Cache — SQL Clear (if re-sideloading needed)

```sql
DELETE FROM wp_postmeta WHERE meta_key LIKE '_eb_img_%';
```

Or via WP-CLI:
```bash
wp db query "DELETE FROM wp_postmeta WHERE meta_key LIKE '_eb_img_%';"
```
