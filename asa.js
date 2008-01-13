

/****************************************************
 * onload
 * 
 */
Event.observe(window, 'load', function init(event) {
	
	if ($('asa_collections') != null) {
		Object.extend(listMan.prototype, AsaListExtension);
	}	
	
});


function asa_deleteSomething( what, id, message, obj ) {
	if ( !obj )
		obj=theList;
	if ( !message )
		message = obj.delText.replace(/%thing%/g, what);
	if( confirm(message) )
		return obj.asa_ajaxDelete( what, id );
	else return false;
}

function asa_confirm_delete_collection () {
	if (confirm('Do you realy want to delete collection "' + $('select_manage_collection').options[$('select_manage_collection').selectedIndex].innerHTML + '"?')) {
		return true;
	}
	
	return false;
}

function asa_set_latest (id, message) {
	
	if (confirm(message)) {
		return true;
	}
	
	return false;
}


/****************************************************
 * AsaListExtension
 * 
 */
var AsaListExtension = {
	
	asa_ajaxDelete: function( what, id ) {

		var ajaxDel = new WPAjax( this.ajaxHandler, this.ajaxRespEl );
		
		if( ajaxDel.notInitialized() )
			return true;
	
		var tempObj = this;
		var action = 'delete-' + what;
		var actionId = action + '&id=' + id;
		var idName = what + '_' + id;
		ajaxDel.addOnComplete( function(transport) {
			Element.update(ajaxDel.myResponseElement,'');
			tempObj.destore(actionId);
			if( tempObj.delComplete && typeof tempObj.delComplete == 'function' )
				tempObj.delComplete( what, id, transport );
		});
		ajaxDel.addOnWPError( function(transport) { tempObj.restore(actionId, true); });
		ajaxDel.options.parameters = $H(ajaxDel.options.parameters).merge({action: action, id: id}).merge(this.inputData.toQueryParams());
		//ajaxDel.request(ajaxDel.url);
		ajaxDel.request('http://localhost/ichdigital/wp-content/plugins/amazonsimpleadmin/AsaAjax.php');
		this.store(actionId, idName);
		tempObj.removeListItem( idName );
		return false;
	}
	
}
