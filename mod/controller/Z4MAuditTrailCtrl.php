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
 * ZnetDK 4 Mobile Audit Trail module controller
 *
 * File version: 1.0
 * Last update: 11/16/2024
 */
namespace z4m_audittrail\mod\controller;

class Z4MAuditTrailCtrl extends \AppController {
    
    /**
     * Evaluates whether action is allowed or not.
     * When authentication is required, action is allowed if connected user has
     * full menu access or if has a profile allowing access to the  
     * 'z4m_audittrail' view.
     * If no authentication is required, action is allowed if the expected view
     * menu item is declared in the 'menu.php' script of the application.
     * @param string $action Action name
     * @return Boolean TRUE if action is allowed, FALSE otherwise
     */
    static public function isActionAllowed($action) {
        $status = parent::isActionAllowed($action);
        if ($status === FALSE) {
            return FALSE;
        }
        $actionView = [
            'all' => 'z4m_audittrail',
            'detail' => 'z4m_audittrail',
            'purge' => 'z4m_audittrail'
        ];
        $menuItem = key_exists($action, $actionView) ? $actionView[$action] : NULL;
        return CFG_AUTHENT_REQUIRED === TRUE
            ? \controller\Users::hasMenuItem($menuItem) // User has right on menu item
            : \MenuManager::getMenuItem($menuItem) !== NULL; // Menu item declared in 'menu.php'
    }
    /**
     * Returns the audit trail. Expected POST parameters are:
     * - first: the first row number to return (for pagination purpose)
     * - count: the number of rows to return (for pagination purpose)
     * - search_criteria: criteria to apply in JSON format. Expected properties
     * are 'start' (W3C start date) and 'end' (W3C end date).
     * @return \Response The audit trail rows in JSON format.
     * The returned properties are:
     * - total: The total number of existing rows matching the search criteria
     * if specified. This number is generally greater than the number of rows
     * returned.
     * - rows: an array of objects containing audit trail infos.
     * - success: value true on success, false in case of error.
     */
    static protected function action_all() {
        $request = new \Request();
        $first = $request->first;
        $count = $request->count;        
        $sortCriteria = 'id DESC';
        $searchCriteria = is_string($request->search_criteria) ? json_decode($request->search_criteria, TRUE) : NULL;
        $rows = [];
        // Success response returned to the main controller
        $response = new \Response();
        $response->total = self::getRows($first, $count, $searchCriteria, $sortCriteria, $rows);
        $response->rows = $rows;
        $response->success = TRUE;
        return $response;
    }
    static protected function getRows($first, $count, $searchCriteria, $sortCriteria, &$rows) {
        $dao = new \z4m_audittrail\mod\model\UserRowsDAO();
        self::createModuleSqlTable($dao);
        if (is_array($searchCriteria)) {
            $dao->setCriteria($searchCriteria);
        }
        $dao->setSortCriteria($sortCriteria);
        $total = $dao->getCount();
        if (!is_null($first) && !is_null($count)) {
            $dao->setLimit($first, $count);
        }
        while ($row = $dao->getResult()) {
            $rows[] = $row;
        }
        return $total;
    }
    /**
     * Returns the audit trail row matching the specified row ID.
     * Expected POST parameter is:
     * - id: internal identifier of the audit trail row.
     * @return \Response The audit trail row in JSON format or a warning message
     * if no row exists for the specified ID.
     */
    static protected function action_detail() {
        $request = new \Request();
        $dao = new \z4m_audittrail\mod\model\UserRowsDAO();
        $detail = $dao->getById($request->id);
        $response = new \Response();
        if (is_array($detail)) {
            $detail['values'] = [];
            $valueDao = new \z4m_audittrail\mod\model\UserRowValuesDAO();
            $valueDao->setUserRowIdAsFilter($detail['id']);
            while ($row = $valueDao->getResult()) {
                $detail['values'][] = $row;    
            }
            $response->setResponse($detail);
        } else {
            $response->setWarningMessage(NULL, LC_MSG_INF_NO_RESULT_FOUND);
        }
        return $response;
    }
    /**
     * Purges all audit trail or only rows matching the specified filter criteria.
     * Expected POST parameter is:
     * - search_criteria: optional criteria to apply in JSON format. Expected
     * properties are 'start' (W3C start date) and 'end' (W3C end date).
     * @return \Response Success or failed message in JSON format
     */
    static protected function action_purge() {
        $request = new \Request();
        $searchCriteria = is_string($request->search_criteria) ? json_decode($request->search_criteria, TRUE) : NULL;
        $response = new \Response();
        try {
            self::purge($searchCriteria);
            $response->setSuccessMessage(NULL, MOD_Z4M_AUDITTRAIL_PURGE_SUCCESS);
        } catch (Exception $ex) {
            \General::writeErrorLog(__METHOD__, $ex->getMessage());
            $response->setFailedMessage(LC_MSG_CRI_ERR_SUMMARY, LC_MSG_CRI_ERR_GENERIC);
        }
        return $response;
    }
    /**
     * Purge audit trail rows. If search criteria are set, only the matching rows
     * are removed
     * @param array $searchCriteria Filter criteria. Expected keys are
     * 'start_date' and 'end_date'.
     * @return int The number of rows removed
     */
    static protected function purge($searchCriteria) {
        $appliedCriteria = is_array($searchCriteria) ? $searchCriteria : ['start' => '2020-01-01'];
        $daoValues = new \z4m_audittrail\mod\model\UserRowValuesDAO();
        $daoValues->setCriteria($appliedCriteria);
        $daoValues->beginTransaction();
        $daoValues->remove(NULL, FALSE);
        $dao = new \z4m_audittrail\mod\model\UserRowsDAO();
        $dao->setCriteria($appliedCriteria);
        $dao->remove(NULL, FALSE);
        $daoValues->commit();
    }
    
    /**
     * Create the SQL table required for the module.
     * The table is created from the SQL script defined via the
     * MOD_Z4M_AUDITTRAIL_SQL_SCRIPT_PATH constant.
     * @param DAO $dao DAO for which existence is checked
     * @throws \Exception SQL script is missing and SQL table creation failed.
     */
    static public function createModuleSqlTable($dao) {
        if ($dao->doesTableExist()) {
            return;
        }
        if (!file_exists(MOD_Z4M_AUDITTRAIL_SQL_SCRIPT_PATH)) {
            $error = "SQL script '" . MOD_Z4M_AUDITTRAIL_SQL_SCRIPT_PATH . "' is missing.";
            throw new \Exception($error);
        }
        $sqlScript = file_get_contents(MOD_Z4M_AUDITTRAIL_SQL_SCRIPT_PATH);
        $db = \Database::getApplDbConnection();
        try {
            $db->exec($sqlScript);
        } catch (\Exception $ex) {
            \General::writeErrorLog(__METHOD__, $ex->getMessage());
            throw new \Exception("Error executing 'z4m_audittrail' module SQL script.");
        }
    }
}