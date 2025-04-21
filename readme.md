# ITF Staff Dashboard Setup

## Prerequisites
- PHP 7.4+ with PDO MySQL
- MySQL 5.7+
- Web server (e.g., Apache)
- Create database `itf_db`

## Setup
1. Run `database.sql` to create `staff` and `posts` tables.
2. Update `php/submit_post.php` and `php/preview_post.php` with your DB credentials (`username`, `password`).
3. Create `uploads/` folder with 777 permissions for image uploads.
4. Place `images/logo.png` and `images/office.jpg` or update paths in `staffdb.html`.
5. Ensure `news.html` sets `$_SESSION['user_id']` and `$_SESSION['staff_name']` on login.

## Notes
- Forms save to `posts` table but don't display in "Recent Posts" yet (connection pending).
- Test forms at `staffdb.html` after logging in via `news.html`.