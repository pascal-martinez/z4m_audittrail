# ZnetDK 4 Mobile module: Audit Trail (z4m_audittrail)
This module allows you to record all changes made to the application's SQL tables.

![Screenshot of the Audit trail view provided by the ZnetDK 4 Mobile 'z4m_audittrail' module](https://mobile.znetdk.fr/applications/default/public/images/modules/z4m_audittrail/screenshot1.png?v1.0)

![Screenshot of the Audit trail modal dialog provided by the ZnetDK 4 Mobile 'z4m_audittrail' module](https://mobile.znetdk.fr/applications/default/public/images/modules/z4m_audittrail/screenshot2.png?v1.0)

## FEATURES
- Changes made through a DAO derivated from the `\z4m_audittrail\mod\AuditTrailDAO` class are automatically recorded in the Audit trail.
- The history of data changes can be viewed for a given date range.
- The audit trail can be purged partially for a given period or purged completely.

## LICENCE
This module is published under the version 3 of GPL General Public Licence.

## REQUIREMENTS
- [ZnetDK 4 Mobile](/../../../znetdk4mobile) version 2.0 or higher,
- A **MySQL** database is configured to store the application data,
- Authentication is enabled
([`CFG_AUTHENT_REQUIRED`](https://mobile.znetdk.fr/settings#z4m-settings-auth-required)
is `TRUE` in the App's
[`config.php`](/../../../znetdk4mobile/blob/master/applications/default/app/config.php)).

## INSTALLATION
1. Add a new subdirectory named `z4m_audittrail` within the
[`./engine/modules/`](/../../../znetdk4mobile/tree/master/engine/modules/) subdirectory of your
ZnetDK 4 Mobile starter App,
2. Copy module's code in the new `./engine/modules/z4m_audittrail/` subdirectory,
or from your IDE, pull the code from this module's GitHub repository,
3. Edit the App's [`menu.php`](/../../../znetdk4mobile/blob/master/applications/default/app/menu.php)
located in the [`./applications/default/app/`](/../../../znetdk4mobile/tree/master/applications/default/app/)
subfolder and include the [`menu.inc`](mod/menu.inc) script to add a menu item definition for the `z4m_audittrail` view.
```php
require ZNETDK_MOD_ROOT . '/z4m_audittrail/mod/menu.inc';
```
4. Go to the **Audit trail** menu to check if the audit trail view is correctly installed. 

## USERS GRANTED TO MODULE FEATURES
Once the **Audit trail** menu item is added to the application, you can restrict 
its access via a [user profile](https://mobile.znetdk.fr/settings#z4m-settings-user-rights).  
For example:
1. Create a user profile named `Admin` from the **Authorizations | Profiles** menu,
2. Select for this new profile, the **Audit trail** menu item,
3. Finally for each allowed user, add them the `Admin` profile from the
**Authorizations | Users** menu. 

## TRANSLATIONS
This module is translated in **French**, **English** and **Spanish** languages.  
To translate this module in another language or change the standard
translations:
1. Copy in the clipboard the PHP constants declared within the 
[`locale_en.php`](mod/lang/locale_en.php) script of the module,
2. Paste them from the clipboard within the
[`locale.php`](/../../../znetdk4mobile/blob/master/applications/default/app/lang/locale.php) script of your application,   
3. Finally, translate each text associated with these PHP constants into your own language.

## USAGE
### Enabling audit trail for your custom DAO classes
To enable the audit trail for your **custom DAO class**,
you just have to extends your class from the [`\z4m_audittrail\mod\AuditTrailDAO`](mod/AuditTrailDAO.php) class instead of the [`\DAO`](https://mobile.znetdk.fr/php-api#z4m-phpapi-dao) class.

Here is below the example of the `\app\model\BookDAO` class which extends the `\z4m_audittrail\mod\AuditTrailDAO` class.
```php
<?php
namespace app\model;

use \z4m_audittrail\mod\AuditTrailDAO;
class BookDAO extends AuditTrailDAO {

    protected function initDaoProperties() {
        $this->table = 'book';
    }
}
```

Next, when you instantiate your custom `\app\model\BookDAO` class to store or delete a row, set `TRUE` to the first and second argument of the class constructor as shown below.
```php
function saveBook($row) {
    $dao = \app\model\BookDAO(TRUE, TRUE);
    return $dao->store($row);
}

function deleteBook($bookId) {
    $dao = \app\model\BookDAO(TRUE, TRUE);
    return $dao->remove($bookId);
}
```

> [!TIP]
> If you set `FALSE` to the second argument of the DAO constructor, the detail of the values changed is not recorded in the audit trail.

### Visualizing data changes in SQL tables
Once you have inserted, updated or deleted rows in SQL tables traced in the audit trail, go to **Audit trail** menu to visualize the history of the SQL transactions that have occurred.

To display the audit trail for a specific date or a specific range of dates, enter a begin and end date to the filter bar located above the datalist.

By clicking the **Transaction ID** in the datalist, a modal dialog is open to show the detail of the changed values for each table's column.

### Purging audit trail
To purge the audit trail, go to the **Audit trail** menu and click the **Purge...** button.
If you entered a period in the filter bar above the datalist, the audit trail is purged only for the specified period.

## INSTALLATION ISSUES
The `zdk_user_rows` and `zdk_user_row_values` SQL tables
are created automatically by the module when they don't yet exist.   
If the MySQL user declared through the
[`CFG_SQL_APPL_USR`](https://mobile.znetdk.fr/settings#z4m-settings-db-user)
PHP constant does not have `CREATE` privilege, the module can't create the
required SQL tables.   
In this case, you can create the module's SQL tables by importing in MySQL or
phpMyAdmin the script [`z4m_audittrail.sql`](mod/sql/z4m_audittrail.sql)
provided by the module.

## CHANGE LOG
See [CHANGELOG.md](CHANGELOG.md) file.

## CONTRIBUTING
Your contribution to the **ZnetDK 4 Mobile** project is welcome. Please refer to the [CONTRIBUTING.md](https://github.com/pascal-martinez/znetdk4mobile/blob/master/CONTRIBUTING.md) file.
