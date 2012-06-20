Deployment Information
last updated 2012-19-06

Database hostname, username, and password class constants
should be changed in SyncAccountDAO.class.php.

Database name and table names can be changed 
by changing the class constants in SyncAccountDAO.class.php,
and by changing the database and table names in deploy_database.sql.

Subscription sign up and exception viewing can be done through
account_management.php, which is also where display messages 
(found in figure 3 of the documentation) can be edited.

The following GET parameters should be passed to account_management.php:
    mos_account_key (string)
    mos_api_key (string)
    mos_account_id (int)
The names of these parameters can be changed by editing 
class constants in account_management.php.