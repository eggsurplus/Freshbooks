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
 
require_once('include/connectors/sources/ext/rest/rest.php');

class ext_rest_freshbooks extends ext_rest {
	protected $_enable_in_wizard = false;
	protected $_enable_in_hover = true;
	protected $_has_testing_enabled = false;
    
	public function __construct(){     
		parent::__construct();
	}

	//implement for Import Wizard support
	public function getItem($args=array(), $module=null){}
	public function getList($args=array(), $module=null){}
	
	//custom function - EAPM not set on a CallConnectorFunc connector call (used in ajax calls) so manually setting when needed
	public function initEAPM() {
		if(empty($this->_eapm)) {
			try {
				require_once('include/externalAPI/ExternalAPIFactory.php');
				$api = ExternalAPIFactory::loadAPI('Freshbooks');
				$this->setEAPM($api);
			} catch(Exception $e) {
				//most likely the current user did not setup a FreshBooks External Account or the connection has not been validated yet
				return false;
			}
		}
		return true;
	}
        
    public function ext_getInvoices() {
    	//ensure record was passed...doing this instead of ID to enforce record security restrictions
    	if(empty($_REQUEST['freshRecord']) || empty($_REQUEST['freshModule'])) {
    		return array('success'=>FALSE,'errorMessage'=>'Both freshRecord and freshModule are required.');
    	}
    	
        if(!$this->initEAPM()) {
    		return array('success'=>FALSE,'errorMessage'=>'Please create a FreshBooks external account under your user setup and verify that the connection works before proceeding.');
        }
        return $this->_eapm->getInvoices($_REQUEST['freshRecord'],$_REQUEST['freshModule']);
    }

}

/**
 * To handle the following possible error which occurs with an EAPM that isn't configured correctly:
<b>Catchable fatal error</b>:  Argument 1 passed to source::setEAPM() must be an instance of ExternalAPIBase, 
boolean given, called in ....and defined in ...
 */
function setEAPMErrorHandler($errno, $errstr, $errfile, $errline) {
	if( E_RECOVERABLE_ERROR === $errno ) {
		//make this a catchable error
		throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
	}
	return false;
}
set_error_handler('setEAPMErrorHandler');


?>
