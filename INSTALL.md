# Requirements

Web server or web hosting package supporting PHP.
One or more MySQL databases.

The Classification Workbench relies on two open source javascript packages, 
check templates/header.php for the currently used versions of this software.
Download the relevant files from:
    https://jquery.com
Check templates/header.php where to put which files from these packages. This
concerns both javascript files (to be placed in the 'scripts' directory and
the jstree style directory to be placed in the 'styles' directory.


# Initial Setup

1. create a directory (and subdomain) on your web server
2. copy the source into the new directory
3. create one or more MySQL database(s) and a user(s)
4. adapt the file auth/config.php with the database names, users, and passwords
5. start auth/setup.php?dbase=<name_of_dbase>
    for each of the databases
6. start ./index.php
        
        
# Usage

After installation the FAQ can be accessed from within the Workbench.

