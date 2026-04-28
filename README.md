# SuperShows Trade Shows Directory

Custom WordPress plugin for SuperPath (`superpath.com`) that stores trade show directory records in a dedicated custom table.

## Current scope (initial scaffolding)

- Registers a WordPress plugin with activation hook.
- Creates/updates table: `{$wpdb->prefix}supershows_tradeshows`.
- Adds a WordPress admin page (`SuperShows`) with a create form tab for trade show data entry.
- Stores raw JSON blobs for structured fields (`address`, `imagery`, `dates`, `industries`) while also storing searchable/sortable columns for key query paths:
  - `address_city`, `address_state`, `address_zip`
  - `start_datetime`, `start_month`, `start_year`
  - `logo_wordpress_image_id`
  - `industries_search`

## Notes

- This is the foundational database layer only.
- Admin CRUD UI, validation/sanitization workflows, and frontend search views will be added in subsequent iterations.
