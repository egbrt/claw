# Requirements

Web server or web hosting package supporting PHP.
One or more MySQL databases.

The Classification Workbench uses on one jquery, check templates/header.php for the currently used version of this software.
Download the relevant files from:
    https://jquery.com
The file jquery-<version>.js should be placed in the 'scripts' directory.


# Initial Setup

1. create a directory (and subdomain) on your web server
2. copy the source into the new directory
3. create one or more MySQL database(s) and a user(s)
4. copy the file auth/config.php.template to auth/config.php
4. adapt the file auth/config.php with the database names, users, and passwords
5. start auth/setup.php?dbase=<name_of_dbase>
    for each of the databases
6. start ./index.php
        
        
# Usage

After installation the FAQ can be accessed from within the Workbench.

