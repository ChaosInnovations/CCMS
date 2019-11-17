CCMS: Chaos Content Management System
=====================================

Website content management system written in PHP.

# Supported Configurations
CCMS Currently supports the following configurations:
* PHP 7.x with MySQL on Apache 2.x

Other configurations may be supported but have not been tested.

# Getting Started
## Prerequisites
1. A web server with a supported configuration (above)
2. The web server must have internet access in order to download the CMS packages
3. Prepare either a MySQL account with full privileges or create a MySQL database and a user with full privileges for that database
## Installation
1. Move `setup.php` to your website root
2. Navigate to `\[yourwebsite\]/setup.php`
3. Click on "Check now" to get an updated package list
4. The `CCMS Index` package should show up. Select your desired version and click `Install`
5. Progress will be shown at the bottom of the page. Wait for the installation to complete - it will automatically direct you to the Database Configuration page
## Configuration
1. Press `Configure`
2. Select the `mysql` database driver. Other databases are not yet supported.
3. Enter the appropriate details for the MySQL account you created earlier and click `Next`
4. Select the correct database from the list, or create a new one if the selected account has permission to do so, then click `Next`
5. Click `Finish`. You will be redirected to your homepage.
6. Click `Secure Access Portal` to access the login page. The default administrator account is `admin` with password `password`
7. It is recommended to change your password (actually, it's best to create a new owner account and delete this one). 