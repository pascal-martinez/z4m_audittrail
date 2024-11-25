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
 * ZnetDK 4 Mobile Audit Trail module DAO
 *
 * File version: 1.0
 * Last update: 11/18/2024
 */
namespace z4m_audittrail\mod\model;

/**
 * Audit trail DAO (detail infos)
 */
class UserRowValuesDAO extends \DAO {

    protected function initDaoProperties() {
        $this->table = "zdk_user_row_values";        
    }
    
    public function setUserRowIdAsFilter($userRowId) {
        $this->filterClause = "WHERE user_row_id = ?";
        $this->setFilterCriteria($userRowId);
    }
    
    public function setCriteria($filters) {
        if (key_exists('start', $filters)) {
            $this->setStartAsFilter($filters['start']);
        }
        if (key_exists('end', $filters)) {
            $this->setEndAsFilter($filters['end']);
        }
    }
    
    protected function setStartAsFilter($startDate) {
        if ($this->filterClause === FALSE) {
            $this->filterClause = 'WHERE ';
        } else {
            $this->filterClause .= ' AND ';
        }
        $this->filterClause .= "EXISTS (SELECT 1 FROM zdk_user_rows AS ur
              WHERE ur.id = {$this->table}.user_row_id
              AND ur.operation_date >= ?)";
        $this->filterValues []= "{$startDate}T00:00:00Z";
    }
    
    protected function setEndAsFilter($endDate) {
        if ($this->filterClause === FALSE) {
            $this->filterClause = 'WHERE ';
        } else {
            $this->filterClause .= ' AND ';
        }
        $this->filterClause .= "EXISTS (SELECT 1 FROM zdk_user_rows AS ur
              WHERE ur.id = {$this->table}.user_row_id
              AND ur.operation_date <= ?)";
        $this->filterValues []= "{$endDate}T23:59:59Z";
    }
    
}