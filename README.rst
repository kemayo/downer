======
Downer
======

Intro
-----

Downer is a token-based download system. It lets you set up files with
restricted access, and give out tokens that allow people to download
the file.

It's unpolished, but it does work. I wrote it for a friend who wanted
to distribute promotional copies of ebooks and not have to worry about
the link to the file being spread all over the place.

Usage
-----

Run the create table statements in `schema.sql`.

Edit `config.php` to contain appropriate values for your database, and an
actual secure password.

Upload your files to wherever you chose as `file_base` in `config.php`.

Go to `admin.php`, log in, and click `New`. Enter the name of your file.

Click on the `0` in the tokens column next to your new file.

Generate X tokens with Y uses each, expiring whenever you want. Immediately
after generating a set of tokens you can download them as a .csv file if you
want to.

Token usage link is: http://[url-of-downer]/[token]
