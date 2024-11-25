<?php
/**
 * ZnetDK, Starter Web Application for rapid & easy development
 * See official website https://mobile.znetdk.fr
 * Copyright (C) 2024 Pascal MARTINEZ (contact@znetdk.fr)
 * License GNU GPL https://www.gnu.org/licenses/gpl-3.0.html GNU GPL
 * --------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------
 * Parameters of the ZnetDK 4 Mobile Audit Trail module
 *
 * File version: 1.0
 * Last update: 11/24/2024
 */


/**
 * Path of the SQL script to update the database schema
 * @var string Path of the SQL script
 */
define('MOD_Z4M_AUDITTRAIL_SQL_SCRIPT_PATH', ZNETDK_MOD_ROOT
        . DIRECTORY_SEPARATOR . 'z4m_audittrail' . DIRECTORY_SEPARATOR
        . 'mod' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR
        . 'z4m_audittrail.sql');

/** Algorithm used for generating row data signature
 * @return string Value 'sha256' by default
 */
define('MOD_Z4M_AUDITTRAIL_SIGNATURE_ALGORITHM', 'sha256');

/** Name of the operator responsible for the data stored in the application
 * @return string Value NULL by default
 */
define('MOD_Z4M_AUDITTRAIL_OPERATOR_NAME', NULL);

/**
 * Module version number
 * @var string Version
 */

define('MOD_Z4M_AUDITTRAIL_VERSION_NUMBER','1.0');
/**
 * Module version date
 * @var string Date in W3C format
 */
define('MOD_Z4M_AUDITTRAIL_VERSION_DATE','2024-11-24');