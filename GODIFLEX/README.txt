GODIFLEX
========

A movie-discovery site built with PHP, MySQL, HTML, CSS, and vanilla JavaScript.
Movie poster images are served from the Pics folder.

Run with XAMPP
--------------
1. Copy this project into C:\xampp\htdocs\GODIFLEX if it is not already there.
2. Open the XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin, select Import, choose this project's
   database.sql file, and run the import.
4. Visit http://localhost/GODIFLEX/.
5. Register a normal account. To make it the first administrator, open the
   godiflex database in phpMyAdmin, select the SQL tab, and run:

   UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';

6. Sign out and back in, then choose Dashboard in the site header. Admins can
   add or remove movies using poster images in Pics and promote or demote users.
   At least one administrator must remain.
7. Signed-in members can save movies to their favorites and view them from the
   My favorites link. Favorites are stored per account and are removed
   automatically if their account or saved movie is deleted.

Database settings
-----------------
The default connection is MySQL at 127.0.0.1 with database "godiflex", user
"root", and an empty password, as commonly configured by XAMPP. Override these
with the GODIFLEX_DB_HOST, GODIFLEX_DB_NAME, GODIFLEX_DB_USER, and
GODIFLEX_DB_PASSWORD environment variables when needed.

Movie playback
--------------
The Pics folder contains poster art only; it does not contain movie video
files. The movie cards link to their poster images. Add video hosting and
licensed playback sources separately if you want playable films.
