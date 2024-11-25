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
 * ZnetDK 4 Mobile Audit Trail module view
 *
 * File version: 1.0
 * Last update: 11/18/2024
 */
$color = defined('CFG_MOBILE_W3CSS_THEME_COLOR_SCHEME')
        ? CFG_MOBILE_W3CSS_THEME_COLOR_SCHEME
        : ['content' => 'w3-theme-light', 'modal_content' => 'w3-theme-light',
            'list_border_bottom' => 'w3-border-theme', 'msg_error' => 'w3-red',
            'modal_header' => 'w3-theme-dark', 'btn_hover' => 'w3-hover-theme',
            'modal_footer_border_top' => 'w3-border-theme',
            'modal_footer' => 'w3-theme-l4', 'btn_cancel' => 'w3-red',
            'btn_action' => 'w3-theme-action', 'btn_submit' => 'w3-green'
        ];
?>
<style>
    #z4m-audittrail-list-header {
        position: sticky;
    }
    #z4m-audittrail-list-header li {
        padding-top: 0;
        padding-bottom: 0;
    }
    #z4m-audittrail-list li .operation {
        text-transform: uppercase;
    }
</style>
<!-- Filter by dates -->
<form id="z4m-audittrail-list-filter" class="w3-padding w3-panel <?php echo $color['filter_bar']; ?>">
    <div class="w3-cell w3-mobile w3-margin-bottom">
        <div class="w3-cell no-wrap"><i class="fa fa-calendar"></i>&nbsp;<b><?php echo MOD_Z4M_AUDITTRAIL_FILTER_PERIOD; ?></b>&nbsp;</div>
        <div class="w3-cell w3-mobile">
            <input class="w3-padding" type="date" name="start_filter">
            <input class="w3-padding w3-margin-right" type="date" name="end_filter">
        </div>
    </div>
    <div class="w3-cell">
        <button class="purge w3-button <?php echo $color['btn_action']; ?>" type="button" data-confirmation="<?php echo MOD_Z4M_AUDITTRAIL_PURGE_CONFIRMATION_TEXT; ?>">
            <i class="fa fa-trash fa-lg"></i> <?php echo MOD_Z4M_AUDITTRAIL_PURGE_BUTTON_LABEL; ?>
        </button>
    </div>
</form>
<!-- Header -->
<div id="z4m-audittrail-list-header" class="w3-row <?php echo $color['content']; ?> w3-hide-small w3-border-bottom <?php echo $color['list_border_bottom']; ?>">
    <div class="w3-col m2 l2 w3-padding-small"><b><?php echo MOD_Z4M_AUDITTRAIL_TRANSACTION_ID; ?></b></div>
    <div class="w3-col m2 l2 w3-padding-small"><b><?php echo MOD_Z4M_AUDITTRAIL_DATETIME_LABEL; ?></b></div>
    <div class="w3-col m2 l2 w3-padding-small"><b><?php echo MOD_Z4M_AUDITTRAIL_OPERATION_LABEL; ?></b></div>
    <div class="w3-col m2 l2 w3-padding-small"><b><?php echo MOD_Z4M_AUDITTRAIL_USER_LABEL; ?></b></div>
    <div class="w3-col m2 l2 w3-padding-small"><b><?php echo MOD_Z4M_AUDITTRAIL_TABLE_LABEL; ?></b></div>
    <div class="w3-col m2 l2 w3-padding-small"><b><?php echo MOD_Z4M_AUDITTRAIL_ROW_LABEL; ?></b></div>
</div>
<!-- Data List -->
<ul id="z4m-audittrail-list" class="w3-ul w3-hide w3-margin-bottom" data-zdk-load="Z4MAuditTrailCtrl:all">
    <li class="<?php echo $color['list_border_bottom']; ?> w3-hover-light-grey" data-id="{{id}}">
        <div class="w3-row w3-stretch">
            <a class="edit" href="javascript:void(0)">
                <div class="w3-col s2 m2 l2 w3-padding-small">
                    <span class="w3-tag w3-theme">{{id}} </span>
                </div>
            </a>
            <div class="w3-col s7 m2 l2 w3-padding-small">
                <span class="w3-hide-large w3-hide-medium">
                    <i class="fa fa-clock-o"></i>&nbsp;
                </span><span class="w3-monospace">{{operation_date_locale}}</span>
            </div>
            <div class="w3-col s3 m2 l2 w3-padding-small">
                <span class="w3-hide-large w3-hide-medium">
                    <i class="fa fa-gear"></i>&nbsp;
                </span><b class="operation">{{operation}}</b>
            </div>
            <div class="w3-col s4 m2 l2 w3-padding-small">
                <span class="w3-hide-large w3-hide-medium">
                    <i class="fa fa-user"></i>&nbsp;
                </span>{{user_name}}
            </div>
            <div class="w3-col s5 m2 l2 w3-padding-small">
                <span class="w3-hide-large w3-hide-medium">
                    <i class="fa fa-th"></i>&nbsp;
                </span>{{table_name}}
            </div>
            <div class="w3-col s3 m2 l2 w3-padding-small">
                ID = {{row_id}}
            </div>
        </div>
    </li>
    <li><h3 class="<?php echo $color['msg_error']; ?> w3-center w3-stretch"><i class="fa fa-frown-o"></i>&nbsp;<?php echo LC_MSG_INF_NO_RESULT_FOUND; ?></h3></li>
</ul>
<!-- Modal dialog for adding and editing -->
<div id="z4m-audittrail-modal" class="w3-modal">
    <div class="w3-modal-content w3-card-4">
        <header class="w3-container <?php echo $color['modal_header']; ?>">
            <a class="close w3-button w3-xlarge <?php echo $color['btn_hover']; ?> w3-display-topright" href="javascript:void(0)" aria-label="<?php echo LC_BTN_CLOSE; ?>"><i class="fa fa-times-circle fa-lg" aria-hidden="true" title="<?php echo LC_BTN_CLOSE; ?>"></i></a>
            <h4>
                <i class="fa fa-history fa-lg"></i>
                <span><?php echo MOD_Z4M_AUDITTRAIL_TRANSACTION_ID; ?> <b class="title"></b></span>
            </h4>
        </header>
        <div class="w3-container <?php echo $color['modal_content']; ?>">
            <form data-zdk-load="Z4MAuditTrailCtrl:detail" novalidate="true">
                <input type="hidden" name="id">
                <div class="w3-section">
                    <div class="w3-row-padding w3-stretch">
                        <div class="w3-col w3-twothird">
                            <label>
                                <b><?php echo MOD_Z4M_AUDITTRAIL_DATETIME_LABEL; ?></b>
                                <input class="w3-input w3-border w3-margin-bottom" type="datetime-local" name="operation_date" step="6000" readonly>
                            </label>
                        </div>
                        <div class="w3-col w3-third">
                            <label>
                                <b><?php echo MOD_Z4M_AUDITTRAIL_OPERATION_LABEL; ?></b>
                                <input class="w3-input w3-border w3-margin-bottom" type="text" name="operation" autocomplete="off" readonly>
                            </label>
                        </div>
                    </div>
                    <div class="w3-row-padding w3-stretch">
                        <div class="w3-col w3-third">
                            <label>
                                <b><?php echo MOD_Z4M_AUDITTRAIL_USER_LABEL; ?></b>
                                <input class="w3-input w3-border w3-margin-bottom" type="text" name="user_name" autocomplete="off" readonly>
                            </label>
                        </div>
                        <div class="w3-col w3-third">
                            <label>
                                <b><?php echo MOD_Z4M_AUDITTRAIL_TABLE_LABEL; ?></b>
                                <input class="w3-input w3-border w3-margin-bottom" type="text" name="table_name" readonly>
                            </label>
                        </div>
                        <div class="w3-col w3-third">
                            <label>
                                <b><?php echo MOD_Z4M_AUDITTRAIL_ROW_LABEL; ?></b>
                                <input class="w3-input w3-border w3-margin-bottom" type="number" name="row_id" readonly>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
            <h5 class="w3-medium"><b><?php echo MOD_Z4M_AUDITTRAIL_DATA_LABEL; ?></b></h5>
            <table class="w3-table-all w3-margin-bottom">
                <thead>
                    <tr>
                        <th><?php echo MOD_Z4M_AUDITTRAIL_COLUMN_LABEL; ?></th>
                        <th><?php echo MOD_Z4M_AUDITTRAIL_DATA_BEFORE_LABEL; ?></th>
                        <th><?php echo MOD_Z4M_AUDITTRAIL_DATA_AFTER_LABEL; ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <footer class="w3-container w3-border-top w3-padding-16 <?php echo $color['modal_footer_border_top']; ?> <?php echo $color['modal_footer']; ?>">
            <button type="button" class="cancel w3-button <?php echo $color['btn_cancel']; ?>">
                <i class="fa fa-close fa-lg"></i>&nbsp;
                <?php echo LC_BTN_CLOSE; ?>
            </button>
        </footer>
    </div>
</div>
<script>
<?php if (CFG_DEV_JS_ENABLED) : ?>
    console.log("'z4m_audittrail.php' ** For debug purpose **");
<?php endif; ?>
    $(function(){
        var dataList = z4m.list.make('#z4m-audittrail-list', false, false);
        dataList.setModal('#z4m-audittrail-modal', false, undefined, function(innerForm, formData) {
            // EDIT
            if (formData.hasOwnProperty('warning')) {
                // This row no longer exists in database
                z4m.messages.showSnackbar(formData.msg, true);
                return false;
            }
            this.setTitle(formData.id);
            // The modified values are displayed in the table element
            const tbodyEl = $('#z4m-audittrail-modal tbody');
            tbodyEl.empty();
            for (const row of formData.values) {
                tbodyEl.append('<tr>'
                    + '<td>' + row.column_name + '</td>'
                    + '<td>' + row.old_value + '</td>'
                    + '<td>' + row.new_value + '</td>'
                    + '</tr>');
            }
        });
        // Filters applied before list loading
        dataList.beforeSearchRequestCallback = function(requestData) {
            const JSONFilters = getFilterCriteria();
            if (JSONFilters !== null) {
                requestData.search_criteria = JSONFilters;
            }
        };
        dataList.loadedCallback = function(rowCount, pageNumber) {
            const purgeBtn = $('#z4m-audittrail-list-filter button.purge');
            purgeBtn.prop('disabled', rowCount === 0 && pageNumber === 1);
        };
        function getFilterCriteria() {
            const filterForm = z4m.form.make('#z4m-audittrail-list-filter'),
                startDate = filterForm.getInputValue('start_filter'),
                endDate = filterForm.getInputValue('end_filter'),
                filters = {};
            if (startDate !== '') {
                filters.start = startDate;
            }
            if (endDate !== '') {
                filters.end = endDate;
            }
            if (Object.keys(filters).length > 0) {
                return JSON.stringify(filters);
            }
            return null;
        }
        // Filter change events
        $('#z4m-audittrail-list-filter input').on('change.z4m_audittrail', function(){
            if ($(this).attr('name') === 'start_filter') {
                const startDate = new Date($(this).val()),
                    endDateEl = $('#z4m-login-history-list-filter input[name=end_filter]'),
                    endDate = new Date(endDateEl.val());
                if (startDate > endDate) {
                    endDateEl.val($(this).val());
                }
            } else if ($(this).attr('name') === 'end_filter') {
                const endDate = new Date($(this).val()),
                    startDateEl = $('#z4m-login-history-list-filter input[name=start_filter]'),
                    startDate = new Date(startDateEl.val());
                if (startDate > endDate) {
                    startDateEl.val($(this).val());
                }
            }
            dataList.refresh();
        });
        // Purge button click events
        $('#z4m-audittrail-list-filter button.purge').on('click.z4m_audittrail', function(){
            z4m.messages.ask($(this).text(), $(this).data('confirmation'), null, function(isOK){
                if(!isOK) {
                    return;
                }
                const requestObj = {
                    controller: 'Z4MAuditTrailCtrl',
                    action: 'purge',
                    callback(response) {
                        if (response.success) {
                            dataList.refresh();
                            z4m.messages.showSnackbar(response.msg);
                        }
                    }
                };
                const JSONFilters = getFilterCriteria();
                if (JSONFilters !== null) {
                    requestObj.data = {search_criteria: JSONFilters};
                }
                z4m.ajax.request(requestObj);
            });
        });
    });
</script>