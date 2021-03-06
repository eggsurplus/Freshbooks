<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2012 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

/*********************************************************************************

 * Description:
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): Jason Eggers (www.eggsurplus.com)
 ********************************************************************************/

require_once('include/connectors/formatters/default/formatter.php');

class ext_rest_freshbooks_formatter extends default_formatter {
    
	public function __construct(){     
		parent::__construct();
	}
	
	//this is just called once when initially building DetailView.tpl cache
	public function getDetailViewFormat() {
		$source = $this->_component->getSource();
		$config = $source->getConfig();

		//make sure that the module does have a yahoo_id field mapped
		$mapping = $this->getSourceMapping();
		

		$name_mapping = !empty($mapping['beans'][$this->_module]['name']) ? $mapping['beans'][$this->_module]['name'] : '';
		if(empty($name_mapping)) {
			$GLOBALS['log']->error('FreshBooks Connector: '.$GLOBALS['app_strings']['ERR_MISSING_MAPPING_ENTRY_FORM_MODULE']);
			return '';
		}

		//Beware: this is maintained/cached and will not change even if you change the record you are looking at...
		//So this should only be used for data that should persist
		//$this->_ss->assign('module', $_REQUEST['module']);
		//$this->_ss->assign('record', $_REQUEST['record']);

		//fetch our default.tpl template
		return $this->fetchSmarty();
	}	


	public function getIconFilePath() {
		return 'custom/modules/Connectors/connectors/formatters/ext/rest/freshbooks/tpls/freshbooks.png';
	}   

}
?>
