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


require_once('include/MVC/View/views/view.detail.php');

class AccountsViewDetail extends ViewDetail {


 	function AccountsViewDetail(){
 		parent::ViewDetail();
 	}

 	/**
 	 * display
 	 * Override the display method to support customization for the buttons that display
 	 * a popup and allow you to copy the account's address into the selected contacts.
 	 * The custom_code_billing and custom_code_shipping Smarty variables are found in
 	 * include/SugarFields/Fields/Address/DetailView.tpl (default).  If it's a English U.S.
 	 * locale then it'll use file include/SugarFields/Fields/Address/en_us.DetailView.tpl.
 	 */
 	function display(){
				
		if(empty($this->bean->id)){
			global $app_strings;
			sugar_die($app_strings['ERROR_NO_RECORD']);
		}				
		
		$this->dv->process();
		global $mod_strings;
		if(ACLController::checkAccess('Contacts', 'edit', true)) {
			$push_billing = '<span class="id-ff"><button class="button btn_copy" title="' . $mod_strings['LBL_PUSH_CONTACTS_BUTTON_LABEL'] . 
								 '" type="button" onclick=\'open_contact_popup("Contacts", 600, 600, "&account_name=' .
								 urlencode($this->bean->name) . '&html=change_address' .
								 '&primary_address_street=' . str_replace(array("\rn", "\r", "\n"), array('','','<br>'), urlencode($this->bean->billing_address_street)) . 
								 '&primary_address_city=' . $this->bean->billing_address_city . 
								 '&primary_address_state=' . $this->bean->billing_address_state . 
								 '&primary_address_postalcode=' . $this->bean->billing_address_postalcode . 
								 '&primary_address_country=' . $this->bean->billing_address_country .
								 '", true, false);\' value="' . $mod_strings['LBL_PUSH_CONTACTS_BUTTON_TITLE']. '">'.
								 SugarThemeRegistry::current()->getImage("id-ff-copy","",null,null,'.png',$mod_strings["LBL_COPY"]).
								 '</button></span>';
								 
			$push_shipping = '<span class="id-ff"><button class="button btn_copy" title="' . $mod_strings['LBL_PUSH_CONTACTS_BUTTON_LABEL'] . 
								 '" type="button" onclick=\'open_contact_popup("Contacts", 600, 600, "&account_name=' .
								 urlencode($this->bean->name) . '&html=change_address' .
								 '&primary_address_street=' . str_replace(array("\rn", "\r", "\n"), array('','','<br>'), urlencode($this->bean->shipping_address_street)) .
								 '&primary_address_city=' . $this->bean->shipping_address_city .
								 '&primary_address_state=' . $this->bean->shipping_address_state .
								 '&primary_address_postalcode=' . $this->bean->shipping_address_postalcode .
								 '&primary_address_country=' . $this->bean->shipping_address_country .
								 '", true, false);\' value="' . $mod_strings['LBL_PUSH_CONTACTS_BUTTON_TITLE'] . '">'.
								  SugarThemeRegistry::current()->getImage("id-ff-copy",'',null,null,'.png',$mod_strings['LBL_COPY']).
								 '</button></span>';
		} else {
			$push_billing = '';
			$push_shipping = '';
		}

		$this->ss->assign("custom_code_billing", $push_billing);
		$this->ss->assign("custom_code_shipping", $push_shipping);
        
        if(empty($this->bean->id)){
			global $app_strings;
			sugar_die($app_strings['ERROR_NO_RECORD']);
		}				
		echo $this->dv->display();
		
		//FreshBooks bonus footage: add a new panel
		//This is just a proof-of-concept. Best practices should (well...could) be used to cache data on a timed basis depending on needs
		//todo: ensure user has a validated connector. Right now assuming yes. Also need exception handling.
		$smarty = new Sugar_Smarty();
		
		require_once('include/externalAPI/ExternalAPIFactory.php');
        $api = ExternalAPIFactory::loadAPI('Freshbooks');

		$client_result = $api->getClient($this->bean->id,'Accounts');
		if($client_result['success'] == false) {
			return; //for now, just don't display the panel if client not found
		}
		$invoice_result = $api->getLastInvoice($client_result['client']['client_id']);
		if($invoice_result['success'] == true) {
			if($invoice_result['invoice']['amount_outstanding'] > 0) {
				$smarty->assign('invoice_icon','<img border="0" alt="Balance Due" src="themes/default/images/colors.red.icon.gif">');
			} else {
				$smarty->assign('invoice_icon','<img border="0" alt="Paid: No Balance Due" src="themes/default/images/colors.green.icon.gif">');
			}
			$smarty->assign('last_invoice','<a href="'.$invoice_result['invoice']['links']['view'].'">Status: '.ucwords($invoice_result['invoice']['status']).' Outstanding: $'.$invoice_result['invoice']['amount_outstanding'].'</a>');
		}
		$smarty->assign('organization','<a href="'.$client_result['client']['links']['view'].'">'.$client_result['client']['organization'].'</a>');
		$smarty->assign('view_statement','<a href="'.$client_result['client']['links']['statement'].'">View Client Statement</a>');
		$panel_output = $smarty->fetch("custom/modules/Connectors/connectors/formatters/ext/rest/freshbooks/tpls/detailview.tpl");
 		$panel_output = json_encode($panel_output);
 		//now insert before main panel (id=LBL_ACCOUNT_INFORMATION)
	
		$freshbooks_js =<<<EOQ
<script type="text/javascript">
	//YUI().use('node', function (Y) {});
	
	YUI().use('node', 'anim', function(Y) {

		Y.one("#LBL_ACCOUNT_INFORMATION").insert({$panel_output},Y.one("#LBL_ACCOUNT_INFORMATION").get('parentNode')); 
		
		//Y.one('#LBL_PANEL_FRESHBOOKS').animate({height: 'toggle'});	
	});

</script>
EOQ;

		echo $freshbooks_js;
	
 	} 	
}

?>