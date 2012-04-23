{*
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
 *}
<script type="text/javascript" src="{sugar_getjspath file='include/connectors/formatters/default/company_detail.js'}"></script>
{literal}
<style type="text/css">
#freshbooks_popup_div {
	min-width: 300px;
}
#freshbooks_container {
	padding-right: 20px;
}
#freshbooks_loading {
	text-align: center;
}
#freshbooks_list {
	margin: 0;
	padding: 0;
}
.freshbooks_header {
	background-color: #dddddd;
	border-bottom: 1px solid #c0c0c0;
	font-weight: bold;
	padding: 2px 5px;
	font-size: 1.1em;
	width: 90%;
}
.freshbooks_detail {
	width: 90%;
	padding: 0 5px;
}
.freshbooks_detail label {
	font-weight: bold;
}
.freshbooks_detail div {
	display: inline;
	padding-left: 2px;
}
.freshbooks_bal {
	background-image:url('themes/default/images/colors.green.icon.gif');
	background-repeat:no-repeat;
	background-position:right center; 
}
.freshbooks_due {
	background-image:url('themes/default/images/colors.red.icon.gif');
	background-repeat:no-repeat;
	background-position:right center; 
}
</style>
{/literal}
<script type="text/javascript">
var freshbooksModule = '{{$module}}';
//var freshbooksRecord = '{{$record}}';
var freshbooksHoverActivated = false;
{literal}

var Y_FB = YUI().use('node', function (Y) {});

function show_ext_rest_freshbooks(event)
{
	if(freshbooksHoverActivated == true) return;
	
	freshbooksHoverActivated = true; //prevent multiple requests in a row
	var xCoordinate = event.clientX;
	var yCoordinate = event.clientY;
	var isIE = document.all?true:false;
      
	if(isIE) 
	{
		xCoordinate = xCoordinate + document.body.scrollLeft;
		yCoordinate = yCoordinate + document.body.scrollTop;
	}


	cd = new CompanyDetailsDialog("freshbooks_popup_div", '<div id="freshbooks_container"></div><div id="freshbooks_loading">{/literal}{sugar_image name="loading"}{literal}</div><div id="freshbooks_container"></div>', xCoordinate, yCoordinate);
	cd.setHeader('FreshBooks - Latest Invoices');
	cd.display();

	//assumes form id is 'form'
	var freshbooksRecord = document.getElementById('form').record.value;
	
	//get the list of IM logs
	YAHOO.util.Connect.asyncRequest('GET', 'index.php?module=Connectors&action=CallConnectorFunc&source_id=ext_rest_freshbooks&source_func=getInvoices&freshRecord='+freshbooksRecord+'&freshModule='+freshbooksModule, {
		success: function (o) {
			var data = YAHOO.lang.JSON.parse(o.responseText);

			Y_FB.one("#freshbooks_loading").hide();
			freshbooksHoverActivated = false;

			if(data.success == true) {
				//build out the popup list
				var html = '';
				if(data.invoices) {
					var invoiceLength = data.invoices.length;
					for(var i=0; i < invoiceLength; i++) {
						html += '<li class="freshbooks_header '+(parseFloat(data.invoices[i].outstanding) == 0?'freshbooks_bal':'freshbooks_due')+'">'+data.invoices[i].date+'</li>';						
						html += '<li class="freshbooks_detail"><label for="outstanding_'+data.invoices[i].invoice_id+'">Outstanding:</label>';
						html += '<div id="outstanding_'+data.invoices[i].invoice_id+'">'+data.invoices[i].amount_outstanding+'</div></li>';
						html += '<li class="freshbooks_detail"><label for="status_'+data.invoices[i].invoice_id+'">Status:</label>';
						html += '<div id="status_'+data.invoices[i].invoice_id+'">'+data.invoices[i].status+'</div></li>';
						html += '<li class="freshbooks_detail">';
						html += '<a href="'+data.invoices[i].view+'">View Invoice</a></li>';
					}
				}
				document.getElementById("freshbooks_container").innerHTML = '<ul id="freshbooks_list"></ul>';	
				document.getElementById("freshbooks_list").innerHTML += html;
			} else {
				document.getElementById("freshbooks_container").innerHTML = data.errorMessage;
			}
		},
		failure: function(){
			Y_FB.one("#freshbooks_loading").hide();
			freshbooksHoverActivated = false;
		}
	});
	
}


{/literal}
</script>
