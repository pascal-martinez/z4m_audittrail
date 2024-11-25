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
 * ZnetDK 4 Mobile Audit trail module SQL script
 *
 * File version: 1.0
 * Last update: 11/18/2024
 */

CREATE TABLE IF NOT EXISTS `zdk_user_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `table_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `row_id` int(11) NOT NULL,
  `operation` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `operation_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `table_name` (`table_name`),
  KEY `operation` (`operation`),
  KEY `operation_date` (`operation_date`),
  KEY `user_rows_multi_1` (`row_id`,`table_name`,`operation`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `zdk_user_row_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_row_id` int(11) NOT NULL,
  `column_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8_unicode_ci,
  `new_value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `user_row_id` (`user_row_id`),
  KEY `column_name` (`column_name`),
  KEY `old_value` (`old_value`(200)),
  KEY `new_value` (`new_value`(200)),
  KEY `user_row_values_multi_1` (`user_row_id`,`column_name`,`new_value`(200))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `zdk_user_row_values`
  ADD CONSTRAINT `zdk_user_row_values_ibfk_1` FOREIGN KEY (`user_row_id`) REFERENCES `zdk_user_rows` (`id`);
