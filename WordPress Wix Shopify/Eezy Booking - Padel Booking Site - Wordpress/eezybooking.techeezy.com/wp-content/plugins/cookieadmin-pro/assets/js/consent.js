
function cookieadmin_pro_set_consent(prefrenc, days){
	
	const xhttp = new XMLHttpRequest();
	var data = 'action=cookieadmin_pro_ajax_handler&cookieadmin_act=save_consent&cookieadmin_pro_security=' + cookieadmin_pro_vars.nonce + '&cookieadmin_preference=' + encodeURIComponent(JSON.stringify(Object.keys(prefrenc)));
	if(cookieadmin_is_obj(cookieadmin_is_consent) && !!cookieadmin_is_consent.consent){
		data += '&cookieadmin_consent_id=' + cookieadmin_is_consent.consent;
	}
	
	var consent_id = "";
	
	xhttp.open("POST", cookieadmin_pro_vars.ajax_url, false);
	xhttp.onload = function() {
		parsed = JSON.parse(this.responseText);
		if(parsed.success){
			consent_id = parsed.data.response;
		}
		
	}
	
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
	xhttp.send(data);
	
	if(consent_id){
		return consent_id;
	}
	
	return false;
	
}