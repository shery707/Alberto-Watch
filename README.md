Alberto Watch Company — Aptech eProject

A responsive single-page website and e-commerce storefront aligned with the Alberto Watch Company project brief.

Run the website (XAMPP required for PHP backend)

Install and open XAMPP.

Extract the albert folder into C:/xampp/htdocs/.

Start Apache from the XAMPP Control Panel (MySQL is not required).

Open http://localhost/albert/ in your browser.

Keep an internet connection active for Bootstrap, Bootstrap Icons, Google Fonts, jQuery and remote product photography.

Make sure the data folder is writable by Apache.

Open http://localhost/albert/admin/setup.php and create the first admin account (minimum 10-character password). This setup locks automatically after the first admin is created.

Admin login: http://localhost/albert/admin/.

Do not open index.html directly with file://; PHP APIs require localhost/Apache.

Features mapped to the Aptech brief

Brand logo, responsive navigation, hover and active menu states.

Single-page sections: Home, Products, Technology, Price List, Gallery, Store Locator, About, Team, Support, Contact and Sitemap.

Product line-up filters: Luxury, Vintage, Smart, Classic and Clocks.

Sample brand catalogue featuring Michael Kors, Rolex, Citizen Eco-Drive, Bulova and Alberto.

Product search, price/name sorting, and product details in Bootstrap popups.

Consolidated indicative USD price list and click-to-enlarge gallery.

Browser-local visitor counter in the footer (Visits in this browser).

Customer registration/login/logout using PHP sessions and JSON storage (data/users.json); passwords use PHP password_hash().

Shopping bag with quantity controls, server-validated checkout, delivery details and customer order history.

Contact form stores enquiries in data/messages.json, visible in the Admin Messages inbox.

Admin setup/login, dashboard statistics, customer list, order management and order status updates.

Admin product create/edit/delete; live shop catalogue is loaded from data/products.json.

Orders are stored in data/orders.json; administrator accounts are stored separately in data/admins.json.

Responsive layout, keyboard-accessible product cards and reduced-motion support.

Project files

index.html — website structure, page sections, modals and team credits.

assets/css/style.css — bench-ledger theme, layout, animations and responsive styles.

assets/js/products.js — fallback sample watch and clock catalogue.

assets/js/script.js — filters, search, sorting, popups, price list, visitor count and authentication UI/API calls.

assets/js/shop.js — shopping bag, checkout, customer order history, contact submission and live JSON catalogue loading.

api/auth.php — PHP JSON API for customer/admin login, registration, session status and logout.

api/store.php — PHP JSON API for catalogue, checkout, customer orders, contact messages and admin management.

admin/setup.php — one-time first-admin account setup.

admin/index.php / admin/admin.js — protected administrator dashboard.

data/products.json, orders.json, messages.json, admins.json, users.json — JSON data files.

PROJECT_REPORT.md — project report, flow and data flow, test cases and assumptions.

TEST_DATA.csv — sample test data.

Important assumptions and limitations

The brief does not supply verified Alberto company contact details or a showroom address. Contact details shown in the page (123 Luxury Avenue, Karachi) are placeholders and must be replaced before real-world use.

Product names, references, photos and prices are demonstration data, not verified brand inventory or official quotations.

The visitor counter uses LocalStorage in the current browser only; it is not a global visitor counter.

Customer accounts and orders are stored server-side in JSON files and can be used across browsers on the same hosted site. Use a strong admin password and keep the data directory protected.

This eProject uses JSON files as its data store, not MySQL. It does not require a MySQL database. JSON file storage is suitable for a small student demonstration; for production/high traffic use MySQL, CSRF protection, rate limiting, HTTPS and scheduled backups.

Remote images and CDN libraries require internet access.

Authentication storage

The server stores customer accounts in data/users.json and admin accounts in data/admins.json; passwords are hashed with PHP's password_hash() and checked with password_verify().

The data/.htaccess file blocks direct HTTP access to the JSON files on Apache 2.4.

This is a small learning-project JSON store. For a high-traffic production site, use MySQL or another database, add CSRF protection, rate limiting, HTTPS and backups.

Admin first-time setup

Start Apache in XAMPP.

Visit http://localhost/albert/admin/setup.php.

Create your own admin name, email and strong password (10+ characters).

After setup, open http://localhost/albert/admin/ to log in.

Do not share admin credentials. The setup page refuses to create another admin once one exists.

Storage files

users.json — customer accounts and password hashes.

admins.json — administrator accounts and password hashes.

products.json — editable product catalogue.

orders.json — customer orders and delivery details.

messages.json — contact enquiries and their statuses.

All JSON data files are protected from direct Apache web access by data/.htaccess. Back up the complete data folder before final submission.
