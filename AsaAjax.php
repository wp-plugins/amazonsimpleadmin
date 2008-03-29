<?php
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	die('0');
}

require_once(dirname(__FILE__) . '/../../../wp-config.php');
require_once(dirname(__FILE__) . '/../../../wp-admin/includes/admin.php');

define('DOING_AJAX', true);

if ( !is_user_logged_in() ) {
	die('-1');
}

$action = strip_tags($_POST['action']);
$id 	= (int) strip_tags($_POST['id']);



switch ($action) {
	
	case 'delete-collection_item':
		
		require_once(dirname(__FILE__) . '/AsaCollection.php');
		$AsaCollection = new AsaCollection($wpdb);
		if ($AsaCollection->deleteAsin($id)) {
			die('1');
		}
		break;
		
	default:
		die('-1');
}

die('-1');
?>