function resetForm(formName, controller) {
    document.getElementById(formName).reset();
    window.location =  window.location.href;//BASE_URL+'/'+controller;
    
    return true;
    //document.getElementById(formName).submit();
}

function toggleVisibility(el,divId, type) {
    var text='';
    
    if(type == 'expand') {
        
        jQuery('#'+divId).show();
        jQuery('#'+el).html('-');
        text = 'collapse';
        jQuery('#'+el).attr('onClick','javascript:toggleVisibility(\''+el+'\',\''+divId+'\', \''+text+'\')');

    }

    if(type == 'collapse') {
        jQuery('#'+divId).hide();
        jQuery('#'+el).html('+');
        text = 'expand';
        //vibhor: check how 'onClick' is used
        jQuery('#'+el).attr('onClick','javascript:toggleVisibility(\''+el+'\',\''+divId+'\', \''+text+'\')');
        
    }
}


function getLocalities(cityId) {
    cleanUpAfterCityChange();

    if(cityId != '' && cityId != 'all') {
        var section = document.getElementById('section').value;
        var queryString = 'section_name='+encodeURI(section)+'&city_id='+encodeURI(cityId);

        jQuery.ajax({
                    type : "post",
                    url  : BASE_URL+'/ajaxrequests/getlocalities',
                    data : queryString,
                    dataType : "text",
                    beforeSend: function () {
                        jQuery.blockUI({message: jQuery('#ajax_msg_div')});
                    },
                    complete: function () {
                            jQuery.unblockUI();
                    },
                    success : function (response) {
                        //alert(response);
                        if(document.getElementById(section+'_filter_localities') != null) {
                            document.getElementById(section+'_filter_localities').innerHTML = '';
                            document.getElementById(section+'_filter_localities').innerHTML = jQuery.trim(response);
                        }
                    }
                });
    }

}

function getMetacategories(cityId) {
    var section = document.getElementById('section').value;
    var queryString = 'section_name='+encodeURI(section)+'&city_id='+encodeURI(cityId);
	
    //clear attributes
    if(document.getElementById(section+'_filter_attributes'))
    {
     document.getElementById(section+'_filter_attributes').innerHTML = '';
    }
    
    
    //clear locality DDL when all is selected
//    if(cityId == 'all' || cityId == '') {
//    	document.getElementById(section+'_filter_localities').innerHTML = '';
//    	document.getElementById(section+'_filter_localities').innerHTML = '<option value="">Select one</option>';
//		
//    	if(cityId == '') {
//    		document.getElementById(section+'_filter_metacat').innerHTML = '';
//    		document.getElementById(section+'_filter_metacat').innerHTML = '<option value="">Select one</option>';
//    	}
//    } 
    
    jQuery.ajax({
                type : "post",
                url  : BASE_URL+'/ajaxrequests/getmetacategories',
                data : queryString,
                dataType : "text",
                beforeSend: function () {
                    jQuery.blockUI({message: jQuery('#ajax_msg_div')});
                },
                complete: function () {
                        jQuery.unblockUI();
                },
                success : function (response) {
                    //alert(response);
                    if(document.getElementById(section+'_filter_metacat') != null) {
                        document.getElementById(section+'_filter_metacat').innerHTML = '';
                        document.getElementById(section+'_filter_metacat').innerHTML = jQuery.trim(response);
                    }
                    
                    if(cityId == 'all' || cityId == '') {
                        //alert(document.getElementById(section+'_filter_localities'));
                        if(document.getElementById(section+'_filter_localities') != null) {
				    	document.getElementById(section+'_filter_localities').innerHTML = '';
				    	document.getElementById(section+'_filter_localities').innerHTML = '<option value="">Select one</option>';
                        }
				    	if(cityId == '') {
				    		document.getElementById(section+'_filter_metacat').innerHTML = '';
				    		document.getElementById(section+'_filter_metacat').innerHTML = '<option value="">Select one</option>';
				    	}
				    } 
                }
            });

}


function getSubcategories(metacatId) {
    
    //use the section name to indentify city.create a hidden element 'section' in you form
    var section = document.getElementById('section').value;
    
    //clear attributes
    if(document.getElementById(section+'_filter_attributes'))
    {
     document.getElementById(section+'_filter_attributes').innerHTML = '';
    }
    //if(document.getElementById('alerts_filter_city'))
    //{
    //var cityId = document.getElementById('alerts_filter_city').value;
    //}
    //else if(document.getElementById('reply_filter_city'))
    //{
    //var cityId = document.getElementById('reply_filter_city').value;    
    //}
    //else if(document.getElementById('ads_filter_city'))
    //{
    //var cityId = document.getElementById('ads_filter_city').value;    
    //}
    var cityId = document.getElementById(section+'_filter_city').value;
    var queryString = 'section_name='+encodeURI(section)+'&metacat_id='+encodeURI(metacatId)+'&city_id='+encodeURI(cityId);
	
    //clear subcat DDL when all is selected
    if(metacatId == 'all') {
    	document.getElementById(section+'_filter_subcat').innerHTML = '';
    	document.getElementById(section+'_filter_subcat').innerHTML = '<option value="">Select one</option>';
    	
    } else {
    
    jQuery.ajax({
                type : "post",
                url  : BASE_URL+'/ajaxrequests/getsubcategories',
                data : queryString,
                dataType : "text",
                beforeSend: function () {
                    jQuery.blockUI({message: jQuery('#ajax_msg_div')});
                },
                complete: function () {
                        jQuery.unblockUI();
                },
                success : function (response) {
                    //alert(response);
                    var chunks = response.split('|');
                    if(chunks[1] == 'FALSE') {
//                        jQuery('#delete_confirm_form_msg').html(chunks[2]);
//                        jQuery('#delete_confirm_form_msg').show();
                        alert(chunks[2]);
                        return false;
                    } else {

                        if(document.getElementById(section+'_filter_subcat') != null) {
                            document.getElementById(section+'_filter_subcat').innerHTML = '';
                            document.getElementById(section+'_filter_subcat').innerHTML = jQuery.trim(chunks[2]);
                        }
                    }
                }
            });
    }

}


function getAttributes(subcatId) {
	var section = document.getElementById('section').value;
	if(subcatId != 'all') {
	    
	    var cityId = document.getElementById(section+'_filter_city').value;
	    var queryString = 'section_name='+encodeURI(section)+'&subcat_id='+encodeURI(subcatId)+'&city_id='+encodeURI(cityId);
	    var subCatName = $("#ads_filter_subcat option:selected").html();
	     // assign the subcatname in index.phtml
	    if(document.getElementById('ads_subcat_name'))
	    {
	     document.getElementById('ads_subcat_name').value = subCatName;
	    }
	    jQuery.ajax({
	                type : "post",
	                url  : BASE_URL+'/ajaxrequests/getattributes',
	                data : queryString,
	                dataType : "text",
	                beforeSend: function () {
	                    jQuery.blockUI({message: jQuery('#ajax_msg_div')});
	                },
	                complete: function () {
	                        jQuery.unblockUI();
	                },
	                success : function (response) {
	                    //alert(response);
	                    if(document.getElementById(section+'_filter_attributes') != null) {
	                        document.getElementById(section+'_filter_attributes').innerHTML = '';
	                        document.getElementById(section+'_filter_attributes').innerHTML = jQuery.trim(response);
	                    }
	                }
	            });
	} else if(subcatId == 'all') {
                if(document.getElementById(section+'_filter_attributes'))
                {
		 document.getElementById(section+'_filter_attributes').innerHTML = '';
                }
	}
	return true;
}


function cleanUpAfterCityChange() {
    var section = document.getElementById('section').value;
    if(document.getElementById(section+'_filter_subcat') != null) {
        document.getElementById(section+'_filter_subcat').innerHTML = '<option value="">Select one</option>';
    }
}


function isColumnSelected() {
    var checkboxes = jQuery('#select_cols input:checkbox:checked'); 
    document.getElementById('is_export_request').value = 'no';
    

    if(jQuery('#show_summarize').attr('checked') != true && jQuery('#show_cashin').attr('checked') != true) {
        if(checkboxes.length > 0) {
            // for user sections ads post number validation
            if(!isNumberCheck('user_filter_no_of_ads_range','user_filter_no_of_ads_text'))
            {
              return false;
            }
            // for user sections reply number validation
            if(!isNumberCheck('user_filter_no_of_replies_range','user_filter_no_of_replies_text'))
            {
              return false;
            }
            // for user sections alerts number validation
            if(!isNumberCheck('user_filter_no_of_alerts_range','user_filter_no_of_alerts_text'))
            {
              return false;
            }
            // email validation
           /* if(!isvalidEmail('user_filter_email_select_ece','user_filter_email_text'))
            {
                return false;
            }*/
            jQuery.blockUI({message: jQuery('#ajax_msg_div')});    
            return true;
        }
        else {
        	
        	//for special case in search
		    //make sure this element is present
		    var section = document.getElementById('section').value;
		    if(section == 'search') {
					return true;
		    }
        	
            alert('Please select at least one column to show in report');
        }
    } else {
        //do validations in php
        return true;
    }
    
    // doing validation for range as numbers
    
    
    
    return false;
}

function resetSummarize(force) {

    if(force || jQuery('#show_summarize').attr('checked') != true) {

        jQuery('#summarize').hide();
        
        if(force){
        	jQuery('#show_summarize').attr('checked', false);
        }
    } else {
    	resetCashin(true);
        jQuery('#summarize input:radio').each(function () {
            jQuery(this).attr('checked',false);
        });

        jQuery('#summarize').show();
    }
}

function resetCashin(force) {

    if(force || jQuery('#show_cashin').attr('checked') != true) {

        jQuery('#cashin').hide();
        if(force){
        	jQuery('#show_cashin').attr('checked', false);
        }
    } else {
    	
    	resetSummarize(true);
        jQuery('#cashin input:radio').each(function () {
            jQuery(this).attr('checked',false);
        });

        jQuery('#cashin').show();
    }
}

function submitForPagination(startRows, formName) {
    var checkboxes = jQuery('#select_cols input:checkbox:checked');
    
    if(checkboxes.length > 0) {
        jQuery.blockUI({message: jQuery('#ajax_msg_div')});

        //create hidden element inside the form
        jQuery('#pagination_submit').val('yes');
        jQuery('#start_rows').val(startRows);
        jQuery('#'+formName).submit();
        return true;
    }
    else {
    	//for special case in search
	    //make sure this element is present
	    var section = document.getElementById('section').value;
	    if(section == 'search') {
			jQuery.blockUI({message: jQuery('#ajax_msg_div')});
	        //create hidden element inside the form
	        jQuery('#pagination_submit').val('yes');
	        jQuery('#start_rows').val(startRows);
	        jQuery('#'+formName).submit();
	        return true;
	    }
    	
    	
    	
        alert('Please select at least one column to show in report');
    }
    return false;
}

function showFlagReason(val,id)
{
    if(val == 'flag_and_delay' && id=='ads_filter_status')
    {
        document.getElementById('ads_filter_flag_reason').disabled = false;
    }
    else if(id=='ads_filter_status')
    {
        document.getElementById('ads_filter_flag_reason').disabled = true;
    }
    
    if(val == 'flag_and_delay' && id=='reply_filter_status')
    {
        document.getElementById('reply_filter_flag_reason').disabled = false;
    }
    else if(id=='reply_filter_status')
    {
        document.getElementById('reply_filter_flag_reason').disabled = true;
    }
}


function isNumberCheck(ele,val)
{
     if(document.getElementById(ele) && document.getElementById(ele).value != '')
     {
         if(isNaN(document.getElementById(val).value))
         {
            alert('Please select a valid number!');
            return false;
         }
         else
         {
            return true;
         }
     }
     else
     {
        return true;
     }
}

// function to check the validation of email
function isvalidEmail(ele,emailTxt)
{
    if(document.getElementById(ele) && document.getElementById(ele).value!='none')
    {
        email = document.getElementById(emailTxt).value
        AtPos = email.indexOf("@")
        StopPos = email.lastIndexOf(".")
        if (AtPos == -1 || StopPos == -1) {
        alert("Not a valid email address");
        return false;
        }
    } // OE if
    return true;
}

function getExcelDownload(frm)
{
    document.getElementById('is_export_request').value = 'yes';
    document.getElementById(frm).submit();
}

function getSummaryExcelDownload(frm)
{
    document.getElementById('is_export_request').value = 'summary';
    document.getElementById(frm).submit();
}

/**
 * a generic function for disabling the column selection when a certain filter is selected/changed
 */
function disableColumnSelection(ddvalue,input) {
    
    //console.log(ddElement);

    //var ddvalue = document.getElementById(ddElement).value;
    var section = document.getElementById('section').value;
    var checkboxes = jQuery('#select_cols input:checkbox');
    if(input != '' && ddvalue != '') {
	checkboxes.each(function (index) {
	    jQuery(this).attr('disabled','disabled');
	});
	//this element should be present in the form
	jQuery('#special_checkbox_for_count').val('dummy');
	
	alert('Selection of columns has been disabled.');
    } else {
	checkboxes.each(function (index) {
	    jQuery(this).attr('disabled','');
	});
	
	alert('Selection of columns has been enabled.');
	//this element should be present in the form
	jQuery('#special_checkbox_for_count').val('');
    }
    
    
    //console.log(checkboxes);
    
}

/**
 * function to select/unselect all columns to display
 */
  function checkUncheckAllColumns(status) {
      
      if(status == "Select All") {
          jQuery('#select_cols input:checkbox').each(function (index) {
                jQuery(this).attr("checked",true);
          });
          jQuery('#check_uncheck').val('Unselect All');
      } else {
          jQuery('#select_cols input:checkbox').each(function (index) {
                jQuery(this).attr("checked",false);
                jQuery('#check_uncheck').val('Select All');
          });
      }
  }  


