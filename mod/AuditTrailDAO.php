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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------
 * ZnetDK 4 Mobile Audit Trail module DAO class
 *
 * File version: 1.0
 * Last update: 11/16/2024
 */

namespace z4m_audittrail\mod;

/**
 * Audit trail on DAO class
 */
 abstract class AuditTrailDAO extends \DAO {
    private $trackingEnabled = FALSE;
    private $includeDetails = FALSE;
    private $includeSignature = FALSE;
    private $followerProfileName = NULL;
    private $isFollowerStored = FALSE;
    private $followerFilterSet = FALSE;

    /**
     * New Audit Trail DAO object
     * @param boolean $trackingEnabled Audit trail enabled if TRUE (set to FALSE
     * by default).
     * @param boolean $includeDetails When set to TRUE, the detail of changed 
     * values is stored in the audit trail (set to FALSE by default).
     * @param boolean $includeSignature When set to TRUE, a signature is added
     * to every inserted row. Specific signature columns (creator_name,
     * operator_name, row_timestamp, tracking_table, previous_id, row_signature,
     * chained_row_signature) must exist in the SQL table to enable this option.
     * @throws \ZDKException arguments are not consistent
     */
    public function __construct($trackingEnabled = FALSE, $includeDetails = FALSE, $includeSignature = FALSE) {
        if ($trackingEnabled === FALSE && $includeDetails === TRUE) {
            $message = "ATD-001: the parameter passed to the '" . get_class($this) .
                    "' class are not consistent!";
            \General::writeErrorLog('z4m_audittrail module error', $message);
            throw new \ZDKException($message);
        }
        $this->trackingEnabled = $trackingEnabled;
        $this->includeDetails = $includeDetails;
        $this->includeSignature = $includeSignature;
        parent::__construct();
    }    

    /**
     * Enables the following of the record by the current authenticated user.
     * The current user must have the specified profile in parameter to be set
     * as the follower of the record.
     * @param string $profileName Name of the profile
     * @return boolean TRUE if the current authenticated user has the specified
     * profile, FALSE otherwise.
     */
    public function setFollowerProfile($profileName) {
        $this->isFollowerStored = TRUE;
        if (\controller\Users::hasProfile($profileName)) {
            $this->followerProfileName = $profileName;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Stores the specified row.
     * @param array $row Same as the DAO::store() method.
     * @param boolean $autocommit Same as the DAO::store() method.
     * @param boolean $emptyValuesToNull Same as the DAO::store() method.
     * @return int The internal identifier of the stored row.
     */
    public function store($row, $autocommit = TRUE, $emptyValuesToNull = FALSE) {
        $userId = $this->getConnectedUserId();
        $operation = array_key_exists($this->IdColumnName, $row) && is_numeric($row[$this->IdColumnName])
                ? 'update' : 'insert';
        if ($this->trackingEnabled) { // Tracking is enabled
            $this->beginTransaction(TRUE);
            $commitAfterStore = FALSE;
        } else {
            $commitAfterStore = $autocommit;
        }
        $this->setCurrentUserAsFollower($userId, $operation, $row);
        $this->addSignatureInfosToRow($operation, $row);
        if ($this->includeDetails) { // Tracking details is enabled
            $originalValues = $operation === 'update'
                ? $this->getOriginalValuesBeforeUpdate($row[$this->IdColumnName]) // The original values are memorized before changing
                : NULL;
        }
        $storedId = parent::store($row, $commitAfterStore, $emptyValuesToNull);
        if (!$this->trackingEnabled) {
            return $storedId; // No tracking enabled, nothing more to do
        }
        if (!$this->doChangesExist($operation, $originalValues, $row)) {
            if ($autocommit) {
                $this->commit();
            }
            return $storedId; // No changes detected, nothing more to do
        }
        $trackingRowId = $this->insertTrackingRow($storedId, $operation, $userId);

        if ($this->includeDetails) { // Details of the changes are stored
            if ($operation === 'insert') {
                $row[$this->IdColumnName] = $storedId;
            }
            $this->insertTrackingDetailRows($trackingRowId, $originalValues, $row);
        }
        if ($autocommit) {
            $this->commit();
        }
        return $storedId;
    }

    /**
     * Removes one or several rows.
     * @param int $rowID Same as the DAO::remove() method.
     * @param boolean $autocommit Same as the DAO::remove() method.
     * @return int The number of rows removed
     */
    public function remove($rowID = NULL, $autocommit = TRUE) {
        if ($this->trackingEnabled) {
            $this->beginTransaction(TRUE);
            $commitAfterRemove = FALSE;
            $idsToRemove = NULL;
            if (is_null($rowID)) { // Case of multi removal
                $idsToRemove = $this->getIdsToRemove();
            }
        } else {
            $commitAfterRemove = $autocommit;
        }
        if ($this->includeDetails) { // Tracking details is enabled
            $originalValues = $this->getOriginalValuesBeforeRemoval($rowID, $idsToRemove); // The original values are memorized before removal
        }
        $rowCount = parent::remove($rowID, $commitAfterRemove);
        if (!$this->trackingEnabled) {
            return $rowCount; // No tracking requested
        }
        if (!is_null($rowID)) { // Only one row to remove
            $trackingRowId = $this->insertTrackingRow($rowID, 'delete');
            if ($this->includeDetails) { // Details of the changes are stored
                $this->insertTrackingDetailRows($trackingRowId, $originalValues, NULL);
            }
        } else { // Case of multi removal
            foreach ($idsToRemove as $key => $id) {
                $trackingRowId = $this->insertTrackingRow($id, 'delete');
                if ($this->includeDetails) { // Details of the changes are stored
                    $this->insertTrackingDetailRows($trackingRowId, $originalValues[$key], NULL);
                }
            }
        }
        if ($autocommit) {
            $this->commit();
        }
        return $rowCount;
    }

    /**
     * Returns the number of data rows.
     * @return int Number of data rows.
     */
    public function getCount() {
        if (!is_null($this->followerProfileName)) {
            $this->addFollowerAsFilter();
        }
        return parent::getCount();
    }

    /**
     * Returns the current data row
     * @return array|boolean Same as the DAO::getResult() method.
     */
    public function getResult() {
        if (!is_null($this->followerProfileName)) {
            $this->addFollowerAsFilter();
        }
        return parent::getResult();
    }

    /**
     * Returns the data row for the specified identifier.
     * @param int $id Same as the DAO::getById() method.
     * @return boolean Same as the DAO::getById() method.
     */
    public function getById($id) {
        $row = parent::getById($id);
        if ($row !== FALSE && !is_null($this->followerProfileName)
                && key_exists('follower_id', $row) && !is_null($row['follower_id'])) {
            $userId = $this->getConnectedUserId(FALSE);
            if ($row['follower_id'] == $userId) {
                return $row;
            } else {
                return FALSE; // The connected user is not the follower of the requested row
            }
        }
        return $row;
    }

    private function addFollowerAsFilter() {
        if ($this->followerFilterSet) {
            return;
        }
        $userId = $this->getConnectedUserId(FALSE);
        $tableAlias = $this->tableAlias ? $this->tableAlias . '.' : '';
        $filterString = $tableAlias . 'follower_id = ?';
        if ($this->filterClause === FALSE || count($this->filterValues) === 0) { // No WHERE clause set...
            $this->filterClause = 'WHERE ' . $filterString;
        } else { // A WHERE clause is set
            $this->filterClause .= ' AND ' . $filterString;
        }
        if (count($this->filterValues) === 0) {
            $this->setFilterCriteria($userId);
        } else {
            $filterValues = $this->filterValues;
            $filterValues[] = $userId;
            call_user_func_array(array($this, 'setFilterCriteria'), $filterValues);
        }
        $this->followerFilterSet = TRUE;
    }

    private function insertTrackingRow($rowId, $operation, $userId = NULL) {
        if (is_null($userId)) {
            $userId = $this->getConnectedUserId();
        }
        $row = array(
            'user_id' => $userId,
            'table_name' => $this->getTableName(),
            'row_id' => $rowId,
            'operation' => $operation,
            'operation_date' => \General::getCurrentW3CDate(TRUE)
        );
        $userRowsDao = new model\UserRowsDAO();
        controller\Z4MAuditTrailCtrl::createModuleSqlTable($userRowsDao);
        try {
            return $userRowsDao->store($row, FALSE);
        } catch (\PDOException $e) {
            $message = "ATD-003: unable to insert a tracking row in the database'" .
                    "': code='" . $e->getCode() . "', message='" . $e->getMessage();
            \General::writeErrorLog('z4m_audittrail module error', $message);
            throw $e;
        }
    }

    private function getConnectedUserId($onlyIfTrackingEnabled = TRUE) {
        if ($onlyIfTrackingEnabled && $this->trackingEnabled === FALSE) {
            return NULL;
        }
        $userId = \UserSession::getUserId();
        if (!is_null($userId)) {
            return $userId;
        }
        // Case of Autoexec and Async User (user ID not stored in session) ?
        $loginName = \UserSession::getLoginName();
        if (!is_null($loginName)) {
            $userInfos = \UserManager::getUserInfos($loginName);
            if (is_array($userInfos) && key_exists('user_id', $userInfos)) {
                return $userInfos['user_id'];
            }
        }
        throw new \ZDKException('ATD-002: unable to get the ID of the connected user');
    }

    private function insertTrackingDetailRows($trackingId, $originalValues, $newValues) {
        if ($newValues === NULL) { // Case of removal
            foreach ($originalValues as $columnName => $oldValue) {
                $row = array(
                    'user_row_id' => $trackingId,
                    'column_name' => $columnName,
                    'old_value' => $oldValue,
                    'new_value' => NULL
                );
                $this->insertTrackingDetailRow($row);
            }
        } else { // Case of insert or update
            foreach ($newValues as $columnName => $newValue) {
                $oldValue = is_array($originalValues) && key_exists($columnName, $originalValues)
                        ? $originalValues[$columnName] : NULL;
                if (is_null($oldValue) || (!is_null($oldValue) && $oldValue != $newValue)) {
                    $row = array(
                        'user_row_id' => $trackingId,
                        'column_name' => $columnName,
                        'old_value' => $oldValue,
                        'new_value' => $newValue
                    );
                    $this->insertTrackingDetailRow($row);
                }
            }
        }
    }

    private function insertTrackingDetailRow($row) {
        $userRowValuesDao = new model\UserRowValuesDAO();
        controller\Z4MAuditTrailCtrl::createModuleSqlTable($userRowValuesDao);
        $userRowValuesDao->store($row, FALSE);
    }

    private function getIdsToRemove() {
        $originalContext = $this->getOriginalDaoContext();
        $this->moneyColumns = FALSE;
        $this->dateColumns = FALSE;
        $this->amountColumns = FALSE;
        $tableAlias = $this->tableAlias ? ' AS ' . $this->tableAlias : '';
        $columnAlias = $this->tableAlias ? $this->tableAlias . '.' : '';
        $this->query = "SELECT " . $columnAlias . $this->IdColumnName . " FROM " . $this->table . $tableAlias;
        $rowIds = array();
        try {
            while ($row = $this->getResult()) {
                $rowIds[] = $row[$this->IdColumnName];
            }
        } catch (\PDOException $e) {
            $message = "ATD-004: unable to retrieve the identifiers of the rows to remove'" .
                    "': code='" . $e->getCode() . "', message='" . $e->getMessage();
            \General::writeErrorLog('z4m_audittrail module error', $message);
            throw $e;
        }
        $this->restoreOriginalDaoContext($originalContext);
        return $rowIds;
    }

    private function getOriginalValuesBeforeUpdate($rowId) {
        $originalContext = $this->getOriginalDaoContext();
        $this->resetDaoContext();
        $this->query = 'SELECT * FROM ' . $this->table;
        try {
            $originalValues = $this->getById($rowId);
        } catch (\PDOException $e) {
            $message = "ATD-005: unable to retrieve the original values of the row to update'" .
                    "': code='" . $e->getCode() . "', message='" . $e->getMessage();
            \General::writeErrorLog('z4m_audittrail module error', $message);
            throw $e;
        }
        $this->restoreOriginalDaoContext($originalContext);
        if ($originalValues === FALSE) {
            throw new \ZDKException("ATD-008: no row found in table '{$this->table}' for ID={$rowId}");
        }
        return $originalValues;
    }

    private function getOriginalValuesBeforeRemoval($rowId, $idsToRemove) {
        if (is_null($rowId)) { // Case of multiple deletion
            $originalValues = array();
            foreach ($idsToRemove as $id) {
                $originalValues[] = $this->getOriginalValuesBeforeUpdate($id);
            }
            return $originalValues;
        } else { // Only one row is to delete
            return $this->getOriginalValuesBeforeUpdate($rowId);
        }
    }

    private function getOriginalDaoContext() {
        return array(
            'query' =>  $this->query,
            'result' => $this->result,
            'filterClause' =>  $this->filterClause,
            'filterValues' => $this->filterValues,
            'groupByClause' => $this->groupByClause,
            'sortClause' => $this->sortClause,
            'tableAlias' => $this->tableAlias,
            'isForUpdate' => $this->isForUpdate(),
            'moneyColumns' => $this->moneyColumns,
            'dateColumns' => $this->dateColumns,
            'amountColumns' => $this->amountColumns,
            'selectedColumns' => $this->selectedColumns,
            'followerProfileName' => $this->followerProfileName
        );
    }

    private function resetDaoContext() {
        $this->result = FALSE;
        $this->filterClause = FALSE;
        $this->filterValues = array();
        $this->setForUpdate(FALSE);
        $this->groupByClause = FALSE;
        $this->sortClause = FALSE;
        $this->tableAlias = FALSE;
        $this->moneyColumns = FALSE;
        $this->dateColumns = FALSE;
        $this->amountColumns = FALSE;
        $this->selectedColumns = FALSE;
        $this->followerProfileName = NULL;
    }

    private function restoreOriginalDaoContext($originalContext) {
        $this->query = $originalContext['query'];
        $this->result = $originalContext['result'];
        $this->filterClause = $originalContext['filterClause'];
        $this->filterValues = $originalContext['filterValues'];
        $this->groupByClause = $originalContext['groupByClause'];
        $this->sortClause = $originalContext['sortClause'];
        $this->tableAlias = $originalContext['tableAlias'];
        $this->setForUpdate($originalContext['isForUpdate']);
        $this->moneyColumns = $originalContext['moneyColumns'];
        $this->dateColumns = $originalContext['dateColumns'];
        $this->amountColumns = $originalContext['amountColumns'];
        $this->selectedColumns = $originalContext['selectedColumns'];
        $this->followerProfileName = $originalContext['followerProfileName'];
    }

    private function doChangesExist($operation, $originalValues, $newValues) {
        if ($operation !== 'update') {
            return TRUE;
        }
        $changesDetected = FALSE;
        foreach ($newValues as $columnName => $newValue) {
            if ($originalValues[$columnName] != $newValue) {
                return TRUE;
            }
        }
        return $changesDetected;
    }

    private function setCurrentUserAsFollower($userId, $operation, &$row) {
        if ($this->trackingEnabled === FALSE || $operation === 'update'
                || $this->isFollowerStored === FALSE) {
            // Tracking is disabled or the follower is not set on update
            // or the profile name is not defined
            return FALSE;
        }
        $row['follower_id'] = $userId;
        return TRUE;
    }

    private function addSignatureInfosToRow($operation, &$row) {
        if ($this->trackingEnabled === FALSE
                || $this->includeSignature === FALSE || $operation !== 'insert') {
            return FALSE;
        }
        $row['creator_name'] = \UserManager::getUserName(\UserSession::getLoginName());
        $row['operator_name'] = MOD_Z4M_AUDITTRAIL_OPERATOR_NAME;
        $row['row_timestamp'] = \General::getCurrentW3CDate(TRUE);
        $row['tracking_table'] = $this->table;
        // Row signatures
        $currentRowSignature = $this->getCurrentRowSignature($row);
        $previousRowId = $this->getPreviousRowId();
        $chainedRowSignature = $this->getChainedRowSignature($previousRowId, $currentRowSignature);
        $row['previous_id'] = $previousRowId;
        $row['row_signature'] = $currentRowSignature;
        $row['chained_row_signature'] = $chainedRowSignature;
        return TRUE;
    }

    private function getSignatureAlgorithm() {
        $algos = hash_algos();
        if (in_array(MOD_Z4M_AUDITTRAIL_SIGNATURE_ALGORITHM, $algos)) {
            return MOD_Z4M_AUDITTRAIL_SIGNATURE_ALGORITHM;
        }
        throw new \ZDKException('ATD-006: the algorithm set as internal parameter is not supported by the PHP engine.');
    }

    private function getPreviousRowId() {
        $originalContext = $this->getOriginalDaoContext();
        $this->resetDaoContext();
        $this->query = 'SELECT id FROM ' . $this->table
                . ' WHERE id = (SELECT MAX(id) FROM ' . $this->table . ')';
        $this->setForUpdate(TRUE);
        try {
            $result = $this->getResult();
        } catch (\PDOException $e) {
            $message = "ATD-005: unable to retrieve the previous row ID of the row to insert'" .
                    "': code='" . $e->getCode() . "', message='" . $e->getMessage();
            \General::writeErrorLog('z4m_audittrail module error', $message);
            throw $e;
        }
        $this->restoreOriginalDaoContext($originalContext);
        return $result === FALSE ? NULL : $result['id'];
    }

    private function getCurrentRowSignature($row) {
        $rowString = '';
        foreach ($row as $value) {
            $rowString .= $value;
        }
        return hash($this->getSignatureAlgorithm(), $rowString);
    }

    private function getPreviousRowSignature($previousRowId) {
        $originalContext = $this->getOriginalDaoContext();
        $this->query = 'SELECT row_signature FROM ' . $this->table;
        $this->groupByClause = FALSE;
        $this->tableAlias = FALSE;
        $this->moneyColumns = FALSE;
        $this->dateColumns = FALSE;
        $this->amountColumns = FALSE;
        $this->selectedColumns = FALSE;
        try {
            $result = $this->getById($previousRowId);
        } catch (\PDOException $e) {
            $message = "ATD-007: unable to retrieve the previous row signature of the row to insert'" .
                    "': code='" . $e->getCode() . "', message='" . $e->getMessage();
            \General::writeErrorLog('z4m_audittrail module error', $message);
            throw $e;
        }
        $this->restoreOriginalDaoContext($originalContext);
        return $result === FALSE ? NULL : $result['row_signature'];
    }

    private function getChainedRowSignature($previousRowId, $currentRowSignature) {
        $previousRowSignature = $this->getPreviousRowSignature($previousRowId);
        $chainedSignaturesBeforeHash = $currentRowSignature . $previousRowSignature;
        return hash($this->getSignatureAlgorithm(), $chainedSignaturesBeforeHash);
    }
}