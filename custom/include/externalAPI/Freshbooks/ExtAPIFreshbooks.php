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

//require_once('include/externalAPI/Base/OAuthPluginBase.php');
require_once('include/externalAPI/Base/ExternalAPIBase.php');
require_once('FreshBooksRequest.php');

//Using Auth Token for now...they want the token passed in the username...no password passed
class ExtAPIFreshbooks extends ExternalAPIBase { //OAuthPluginBase {
	//FreshBooks supports both OAuth and Token-Based Auth (for now...).
    public $authMethod = 'password'; //'oauth';
    public $useAuth = true;
    public $requireAuth = true;
    protected $authData;
    public $needsUrl = false; 
    public $supportedModules = array('Contacts','Import'); //support the Import Wizard for person modules
    public $connector = 'ext_rest_freshbooks';
    public $url = '';


	//Required for OAuth:
	protected $oauthReq = '/oauth/oauth_request.php';
    protected $oauthAuth = '/oauth/oauth_authorize.php';
    protected $oauthAccess = '/oauth/oauth_access.php';
    protected $oauthParams = array(
    	'signatureMethod' => 'PLAINTEXT', //only PLAINTEXT is supported by FreshBooks API: http://developers.freshbooks.com/authentication-2/
    );

    function __construct() 
	{
        $this->url = $this->getConnectorParam('freshbooks_api_url');
        $this->oauthReq = $this->url.$this->oauthReq;
        $this->oauthAuth = $this->url.$this->oauthAuth;
        $this->oauthAccess = $this->url.$this->oauthAccess;

        //parent::__construct(); //uncomment if using OAuth
    }
    
    //OVERRIDE
    //FreshBooks only passes the username which contains the auth token
    public function loadEAPM($eapmBean)
    {
    	parent::loadEAPM($eapmBean);
        //if using deprecated auth token method
        if ($this->authMethod == 'password') {
            $this->account_name = $eapmBean->password;
            $this->account_password = '';
        }
        //if Auth Token
        FreshBooksRequest::init($this->url, $this->account_name); //want to see if this works? Enter dummy info and watch the Status blank out on the External Account setup after hitting Connect
        
        return true;
    }

	//OVERRIDE
	//Make a simple call to FreshBooks to ensure auth params are set correctly
	//External Account setup for a user will call this to validate and display "Connected" for the EAPM Status
    public function checkLogin($eapmBean = null)
    {
		//make call to system. As of 2012/04/15 this was in beta and could change
		//http://developers.freshbooks.com/docs/system/
		$fb = new FreshBooksRequest('system.current');
		$fb->request();
		if($fb->success())
		{
			return array('success' => true);
		}
		else
		{
			$GLOBALS['log']->error($fb->getError());
				return array('success' => false);
		}

        return array('success' => true);
    }

	public function getInvoices($freshRecord, $freshModule)
	{
		//first load the parent bean
		global $beanList;
		//Generic method of loading a bean
		$parent_bean = new $beanList[$freshModule];
		$parent_bean->retrieve($freshRecord);
		
		if(empty($parent_bean->id)) {
			return array('success'=>FALSE,'errorMessage'=>'Unable to retrieve the requested record in Sugar.');
		}
		if(empty($parent_bean->email1)) {
			return array('success'=>FALSE,'errorMessage'=>'No email found for this record.');
		}
		
		$client_id = null;
		//get client by email address (Client ID could be cached as a custom field in the future)
		//http://developers.freshbooks.com/docs/clients/
		$fb = new FreshBooksRequest('client.list');
		$fb->post(array(
			'email' => $parent_bean->email1
		));
		$fb->request();
		if($fb->success())
		{
			$result = $fb->getResponse();
		
			if($result['clients']['@attributes']['total'] == 0) {
				return array('success'=>FALSE,'errorMessage'=>'No client in FreshBooks found with this email address.');
			} else if($result['clients']['@attributes']['total'] > 1) {
				return array('success'=>FALSE,'errorMessage'=>'More than 1 client was found in FreshBooks with this email address. This connector cannot currently handle this scenario.');
			}
		
			$client_id = $result['clients']['client']['client_id'];
		}
		else
		{
			return array('success'=>FALSE,'errorMessage'=>'No client in FreshBooks found with this email address.');
		}
	
		//now get invoices for the client
		//http://developers.freshbooks.com/docs/invoices/
		$invoices = array();
		$fb = new FreshBooksRequest('invoice.list');
		$fb->post(array(
			'client_id' => $client_id
		));
		$fb->request();
		if($fb->success())
		{
			$result = $fb->getResponse();
			if($result['invoices']['@attributes']['total'] == 0) {
				return array('success'=>FALSE,'errorMessage'=>'This client does not have any invoices yet.');
			}
			
			//force standard indexed arrays
			$result['invoices']['invoice'] = $this->cleanResult($result['invoices']['invoice']);
		}
		else
		{
			return array('success'=>FALSE,'errorMessage'=>'Unable to connect to FreshBooks at this time.');
		}		
		
		foreach($result['invoices']['invoice'] as $invoice) {
			//do data scrubbing here
			$inv = array();
			
			$date = date_parse($invoice['date']);
			$date_string = $date['month'].'-'.$date['day'].'-'.$date['year'];
			
			$inv['invoice_id'] = $invoice['invoice_id'];
			$inv['status'] = ucwords($invoice['status']);
			$inv['amount'] = '$'.$invoice['amount'];
			$inv['amount_outstanding'] = '$'.$invoice['amount_outstanding'];
			$inv['outstanding'] = $invoice['amount_outstanding'];
			$inv['paid'] = '$'.$invoice['paid'];
			$inv['date'] = $date_string;
			$inv['updated'] = $invoice['updated'];
			$inv['view'] = $invoice['links']['view'];
			$invoices[] = $inv;
		}
        
		return array('success'=>TRUE,'invoices'=> $invoices);
	}

	public function getClient($freshRecord, $freshModule)
	{
		//first load the parent bean
		global $beanList;
		//Generic method of loading a bean
		$parent_bean = new $beanList[$freshModule];
		$parent_bean->retrieve($freshRecord);
		
		if(empty($parent_bean->id)) {
			return array('success'=>FALSE,'errorMessage'=>'Unable to retrieve the requested record in Sugar.');
		}
		if(empty($parent_bean->email1)) {
			return array('success'=>FALSE,'errorMessage'=>'No email found for this record.');
		}
		
		$client_id = null;
		//get client by email address (Client ID could be cached as a custom field in the future)
		//http://developers.freshbooks.com/docs/clients/
		$fb = new FreshBooksRequest('client.list');
		$fb->post(array(
			'email' => $parent_bean->email1
		));
		$fb->request();
		if($fb->success())
		{
			$result = $fb->getResponse();
		
			if($result['clients']['@attributes']['total'] == 0) {
				return array('success'=>FALSE,'errorMessage'=>'No client in FreshBooks found with this email address.');
			} else if($result['clients']['@attributes']['total'] > 1) {
				return array('success'=>FALSE,'errorMessage'=>'More than 1 client was found in FreshBooks with this email address. This connector cannot currently handle this scenario.');
			}
		
			$client = $result['clients']['client'];
		}
		else
		{
			return array('success'=>FALSE,'errorMessage'=>'No client in FreshBooks found with this email address.');
		}
        
		return array('success'=>TRUE,'client'=> $client);
	}
	
	public function getLastInvoice($client_id)
	{
	
		//now get invoices for the client
		//http://developers.freshbooks.com/docs/invoices/
		$invoices = array();
		$fb = new FreshBooksRequest('invoice.list');
		$fb->post(array(
			'client_id' => $client_id,
			'per_page' => 1
		));
		$fb->request();
		if($fb->success())
		{
			$result = $fb->getResponse();
			if($result['invoices']['@attributes']['total'] == 0) {
				return array('success'=>FALSE,'errorMessage'=>'This client does not have any invoices yet.');
			}
			
			$invoice = $result['invoices']['invoice'];
		}
		else
		{
			return array('success'=>FALSE,'errorMessage'=>'Unable to connect to FreshBooks at this time.');
		}		
        
		return array('success'=>TRUE,'invoice'=> $invoice);	
	}
	
	// simplexml_load_string returns an object array if 1 result...an array of objects if many
	// so we will do our best to clean it up and make it standard
	function cleanResult($arr)
	{
		//make into an array
		if($this->isAssoc($arr) == 1) {
			$arrObject = $arr;
			$arr = array();
			$arr[0] = $arrObject;
		}
		
		return $arr;
	}
	
	function isAssoc($arr)
	{
		return (array_keys($arr) != array_keys(array_keys($arr)));

	}



}
