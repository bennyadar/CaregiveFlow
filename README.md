# CaregiveFlow — Stage 1

This is a PHP 8 + MariaDB + Apache skeleton implementing:
- Login/logout with sessions and roles (admin/staff/viewer)
- CRUD for Employees and Employers
- RTL Bootstrap 5 styling
- Dependent city/street selects via AJAX
- Simple dashboard reading `v_dashboard_stats`

## Requirements
- PHP 8.1+ with PDO MySQL extension
- MariaDB/MySQL server loaded with your schema (`caregiveflow` DB)
- Apache/Nginx (served `/public` as web root)

## Setup
1. Copy the project somewhere under your web server (serve the `public/` folder).
2. Update DB credentials in `src/config.php`.
3. **Create the first admin**:
   - Open `/public/tools/bootstrap_admin.php` in your browser and submit the form.
   - After successful creation, you can delete this file for security (optional).
4. Go to `/public/index.php?r=auth/login` and log in.

## Notes
- Employees table and fields align with your DB (`passport_number`, `country_of_citizenship`, etc.).
- Employers use `employer_id_types`, `cities`, and `streets` as code tables for selects.
- Dashboard pulls figures from `v_dashboard_stats` (make sure the view exists and is accessible).

## Routing
We use a simple query-string router: `index.php?r=controller/action`
- `dashboard/index` — home
- `employees/index`, `employees/create`, `employees/edit&id=ID`, `employees/delete` (POST), `employees/show&id=ID`
- `employers/index`, `employers/create`, `employers/edit&id=ID`, `employers/delete` (POST), `employers/show&id=ID`
- `auth/login`, `auth/logout`

## Security
- Passwords are hashed with `password_hash` (bcrypt/argon2 depending on PHP build).
- Admin role can delete records; staff can create/update; viewer is read-only (no delete buttons shown).
- Consider adding CSRF tokens and more validations in the next stages.

## Styling
- Bootstrap 5.3 RTL CDN.
- See `public/assets/css/style.css` to tweak spacing/typography.
