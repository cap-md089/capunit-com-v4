# Database export

First, use MySQL workbench to export the database to a single file (`DumpYYYYMMDD-Num.sql`) where `Num` is the number of dumps done in that day. This file is saved to `mysql-dumps`.

# SFTP transfer

Using FileZilla, transfer files to remote server (https://capunit.com/ should work). These are the important directories:

1. data
2. images
3. lib
4. logs
5. mailer
6. mysql-dumps
7. nstyles
8. pages
9. pluggables
10. scripts
11. templates
12. unittests

These are important files:

1. .htaccess
2. 404.php
3. 500.php
4. config.php
5. favicon.ico
6. filemanager.php
7. index.php
8. manifest.appcache
9. rss
10. tables.ini

If nothing else, make sure that index.php and .htaccess get uploaded.

# MySQL import

Log into the remote server, and run the following command on the mysql dump file from above:

 > mysql -u root -p < DumpYYYYMMDD-Num.sql

Username is root, password is `alongpassword2016`

# Notes

## Event Files

To speed things up one can delete all files from the database with the comment 'Old Event File'

 > DELETE FROM FileData WHERE Comments = 'Old Event File';

Then, upload the data/EventDocuments folder.

To reimport the files, run the PHP command line on the remote server. Require config.php, lib/Account.php, and lib/ImportGS.php. Create the variable `$_ACCOUNT`, instantiating an `Account` object with the parameter 'md089'

 > $_ACCOUNT = new Account('md089');

Then, run `ImportGSFiles()`