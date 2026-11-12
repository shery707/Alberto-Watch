# Alberto Watch Company — Aptech eProject

A responsive single-page website aligned with the supplied Alberto Watch Company project brief.

## Run the website (XAMPP required for PHP backend)
1. Install and open XAMPP.
2. Extract the `alberto-watch-2-main` folder into `C:/xampp/htdocs/`.
3. Start **Apache** from the XAMPP Control Panel.
4. Open `http://localhost/alberto-watch-2-main/` in your browser.
5. Keep an internet connection active for Bootstrap, Bootstrap Icons, Google Fonts, jQuery and remote product photography.
6. Make sure the `data` folder is writable by Apache.
7. Open `http://localhost/alberto-watch-2-main/admin/setup.php` and create the first admin account (minimum 10-character password). This setup locks after the first admin is created.
8. Admin login: `http://localhost/alberto-watch-2-main/admin/`.
9. Do not open `index.html` directly with `file://`; PHP APIs require localhost/Apache.

## Features mapped to the Aptech brief
- Brand logo, responsive navigation, hover and active menu states.
- Single-page sections: Products, Technology, Store Locator, Support, About, Contact, Gallery, Price List and Sitemap.
- Product line-up filters: Luxury, Vintage, Smart, Classic and Clocks.
- Sample brand catalogue featuring Michael Kors, Rolex, Citizen Eco-Drive and Bulova.
- Product search, price/name sorting, and product details in Bootstrap popups.
- Consolidated price list and click-to-enlarge gallery.
- Browser-local visitor counter beside the brand logo.
- Customer registration/login/logout using PHP sessions and JSON storage (`data/users.json`); passwords use PHP `password_hash()`.
- Shopping cart with quantity controls, server-validated checkout, delivery details and customer order history.
- Footer contact form stores enquiries in `data/messages.json`, visible in the Admin Messages inbox.
- Admin setup/login, dashboard statistics, customer list, order management and order status updates.
- Admin product create/edit/delete; live shop catalogue is loaded from `data/products.json`.
- Orders are stored in `data/orders.json`; administrator accounts are stored separately in `data/admins.json`.
- Continuous bottom ticker with live date/time and visitor-initiated HTML5 geolocation.
- Responsive layout, keyboard-accessible product cards and reduced-motion support.

## Project files
- `index.html` — website structure and page sections.
- `assets/css/style.css` — theme, layout, animations and responsive styles.
- `assets/js/products.js` — JSON-style sample watch and clock catalogue.
- `assets/js/script.js` — filters, search, sorting, popups, price list, ticker, visitor count and authentication UI/API calls.
- `api/auth.php` — PHP JSON API for customer/admin login, registration, session status and logout.
- `api/store.php` — PHP JSON API for catalogue, checkout, customer orders, contact messages and admin management.
- `admin/setup.php` — one-time first-admin account setup.
- `admin/index.php` / `admin/admin.js` — protected administrator dashboard.
- `data/products.json`, `orders.json`, `messages.json`, `admins.json`, `users.json` — JSON data files.
- `data/users.json` — JSON file containing registered accounts (password hashes, not plain-text passwords).
- `PROJECT_REPORT.md` — project report, flow and data flow, test cases and assumptions.
- `TEST_DATA.csv` — sample test data.

## Important assumptions and limitations
- The brief does not supply verified Alberto company contact details or a showroom address. Contact details shown in the page are placeholders and must be replaced before real-world use.
- Product names, references, photos and prices are demonstration data, not verified brand inventory or official quotations.
- The visitor counter uses LocalStorage in the current browser only; it is not a global visitor counter.
- Customer accounts and orders are stored server-side in JSON files and can be used across browsers on the same hosted site. Use a strong admin password and keep the `data` directory protected.
- This eProject uses JSON files as its data store, not MySQL. It does not require a MySQL database. JSON file storage is suitable for a small student demonstration; for production/high traffic use MySQL, CSRF protection, rate limiting, HTTPS and scheduled backups.
- Location is requested only after a visitor clicks the ticker label. Coordinates are shown locally and are not stored by the website.
- Remote images and CDN libraries require internet access.


## Authentication storage
- The server stores accounts in `data/users.json`; passwords are hashed with PHP's `password_hash()` and checked with `password_verify()`.
- The `data/.htaccess` file blocks direct HTTP access to the JSON file on Apache 2.4.
- This is a small learning-project JSON store. For a high-traffic production site, use MySQL or another database, add CSRF protection, rate limiting, HTTPS and backups.

## Admin first-time setup
1. Start Apache in XAMPP.
2. Visit `/alberto-watch-2-main/admin/setup.php`.
3. Create your own admin name, email and strong password (10+ characters).
4. After setup, open `/alberto-watch-2-main/admin/` to log in.
5. Do not share admin credentials. The setup page refuses to create another admin once one exists.

## Storage files
- `users.json`: customer accounts and password hashes.
- `admins.json`: administrator accounts and password hashes.
- `products.json`: editable product catalogue.
- `orders.json`: customer orders and delivery details.
- `messages.json`: footer contact enquiries and their statuses.

All JSON data files are protected from direct Apache web access by `data/.htaccess`. Back up the complete `data` folder before final submission.
