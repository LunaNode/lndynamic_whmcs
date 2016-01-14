<?php

require_once("common.php");

function lndynamic_API($api_id, $api_key, $category, $action, $params = array()) {
	$url = "https://dynamic.lunanode.com/api.php";

	$fields = array();
	$fields['api_id'] = $api_id;
	$fields['api_key'] = $api_key;
	$fields['category'] = $category;
	$fields['action'] = $action;

	foreach($params as $key => $value) {
		$fields[urlencode($key)] = urlencode($value);
	}

	$fields_string = "";
	foreach($fields as $key=>$value) {
		$fields_string .= $key . '=' . $value . '&';
	}
	rtrim($fields_string, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if(!empty($fields)) {
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	}

	//execute post
	$raw = curl_exec($ch);

	//close connection
	curl_close($ch);

	$result = json_decode($raw, true);

	if(!is_array($result)) {
		return array('error' => $raw);
	} else {
		return $result;
	}
}

function lndynamic_ConfigOptions() {
	return array(
		"Plan name" => array("Type" => "dropdown", "Options" => "512 MB,1024 MB,2048 MB,4096 MB,8192 MB,16384 MB,Special 2048,Special 1536,Flexible 2048,Flexible 4096,Flexible 8192,Flexible 4096+,SSD 512,SSD 1024,SSD 2048,SSD 4096,SSD 8192,SSD 16384,SSD LL1024,SSD LL2048,SSD LL4096,1024 MB high-memory,1536 MB high-memory,2048 MB high-memory,4096 MB high-memory,8192 MB high-memory,16384 MB high-memory,32768 MB high-memory,SSD 1024 high-memory,SSD 1536 high-memory,SSD 2048 high-memory,SSD 4096 high-memory,SSD 8192 high-memory,SSD 16384 high-memory,SSD 32768 high-memory"),
		"plan_id" => array("Type" => "text", "Size" => "5", "Description" => "Only required for special plans; if set, overrides plan name"),
		"API id" => array("Type" => "text", "Size" => "20", "Description" => "Generate from API tab"),
		"API key" => array("Type" => "text", "Size" => "30", "Description" => "Generate from API tab")
	);
}

function lndynamic_CreateAccount($params) {
	$domain = $params["domain"];
	$configoptions = $params["configoptions"];

	$plan_name = $params['configoption1'];
	$plan_id = $params['configoption2'];
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!lunanode_customFieldExists($params['pid'], 'vmid')) {
		return 'Custom field vmid has not been configured.';
	}

	if(!empty($params['customfields']['vmid'])){
		$result = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $params['customfields']['vmid']));
		if(!isset($result['error'])){
			return 'Virtual machine already exists, please delete and try again.';
		}
	}
	
	if(!isset($configoptions['Operating System'])) {
		return "Error: you must select an operating system!";
	}
	$os = $configoptions['Operating System'];

	if(empty($api_id) || empty($api_key)) {
		return "Error: product misconfiguration (backend interface not set).";
	}

	if(empty($plan_id)) {
		if($plan_name == '1024 MB') {
			$plan_id = 2;
		} else if($plan_name == '2048 MB') {
			$plan_id = 3;
		} else if($plan_name == '4096 MB') {
			$plan_id = 4;
		} else if($plan_name == '8192 MB') {
			$plan_id = 5;
		} else if($plan_name == '16384 MB') {
			$plan_id = 6;
		} else if($plan_name == 'Special 2048') {
			$plan_id = 23;
		} else if($plan_name == 'Special 1536') {
			$plan_id = 31;
		} else if($plan_name == 'Flexible 2048') {
			$plan_id = 35;
		} else if($plan_name == 'Flexible 4096') {
			$plan_id = 36;
		} else if($plan_name == 'Flexible 8192') {
			$plan_id = 37;
		} else if($plan_name == 'Flexible 4096+') {
			$plan_id = 42;
		} else if($plan_name == 'SSD 512') {
			$plan_id = 43;
		} else if($plan_name == 'SSD 1024') {
			$plan_id = 44;
		} else if($plan_name == 'SSD 2048') {
			$plan_id = 45;
		} else if($plan_name == 'SSD 4096') {
			$plan_id = 46;
		} else if($plan_name == 'SSD 8192') {
			$plan_id = 47;
		} else if($plan_name == 'SSD 16384') {
			$plan_id = 48;
		} else if($plan_name == 'SSD LL1024') {
			$plan_id = 49;
		} else if($plan_name == 'SSD LL2048') {
			$plan_id = 50;
		} else if($plan_name == 'SSD LL4096') {
			$plan_id = 51;
		} else if($plan_name == '1024 MB high-memory') {
			$plan_id = 59;
		} else if($plan_name == '1536 MB high-memory') {
			$plan_id = 60;
		} else if($plan_name == '2048 MB high-memory') {
			$plan_id = 61;
		} else if($plan_name == '4096 MB high-memory') {
			$plan_id = 62;
		} else if($plan_name == '8192 MB high-memory') {
			$plan_id = 63;
		} else if($plan_name == '16384 MB high-memory') {
			$plan_id = 64;
		} else if($plan_name == '32768 MB high-memory') {
			$plan_id = 65;
		} else if($plan_name == 'SSD 1024 high-memory') {
			$plan_id = 66;
		} else if($plan_name == 'SSD 1536 high-memory') {
			$plan_id = 67;
		} else if($plan_name == 'SSD 2048 high-memory') {
			$plan_id = 68;
		} else if($plan_name == 'SSD 4096 high-memory') {
			$plan_id = 69;
		} else if($plan_name == 'SSD 8192 high-memory') {
			$plan_id = 70;
		} else if($plan_name == 'SSD 16384 high-memory') {
			$plan_id = 71;
		} else if($plan_name == 'SSD 32768 high-memory') {
			$plan_id = 72;
		} else {
			$plan_id = 1;
		}
	} else {
		$plan_id = intval($plan_id);
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'create', array('hostname' => $domain, 'plan_id' => $plan_id, 'image_id' => $os, 'wait' => 1));

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	} else {
		lunanode_customFieldSet($params['pid'], 'vmid', $params['serviceid'], $result['vm_id']);
		return "success";
	}
}

function lndynamic_TerminateAccount($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'delete', array('vm_id' => $params['customfields']['vmid']));

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	} else {
		lunanode_customFieldSet($params['pid'], 'vmid', $params['serviceid'], '');
	}
	
	return "success";

}

function lndynamic_SuspendAccount($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'stop', array('vm_id' => $params['customfields']['vmid']));

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	} else {
		return "success";
	}
}

function lndynamic_UnsuspendAccount($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}

	lndynamic_API($api_id, $api_key, 'vm', 'start', array('vm_id' => $params['customfields']['vmid']));
	return "success";
}

function lndynamic_ChangePackage($params) {
	return "Error: operation not supported.";
}

function lndynamic_ClientArea($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}

	$vmid = $params['customfields']['vmid'];
	$info = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $vmid));
	$images = lndynamic_API($api_id, $api_key, 'image', 'list', array('vm_id' => $vmid));

	if(!isset($info['info']) || !isset($images['images'])) {
		return "Backend call failed.";
	}

	$extra = $info['extra'];
	$info = $info['info'];
	$images = $images['images'];

	ob_start();
	include(dirname(__FILE__) . "/template.php");
	return ob_get_clean();
}

function lndynamic_AdminLink($params) {

	$code = '<form action=\"http://'.$params["serverip"].'/controlpanel" method="post" target="_blank">
<input type="hidden" name="user" value="'.$params["serverusername"].'" />
<input type="hidden" name="pass" value="'.$params["serverpassword"].'" />
<input type="submit" value="Login to Control Panel" />
</form>';
	return $code;

}

function lndynamic_LoginLink($params) {

	echo "<a href=\"http://".$params["serverip"]."/controlpanel?gotousername=".$params["username"]."\" target=\"_blank\" style=\"color:#cc0000\">login to control panel</a>";

}

function lndynamic_action($params, $action, $extra = array()) {
	if(!lunanode_isActive($params['serviceid'])) {
		return 'Error: service is not currently active.';
	}
	
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}

	$args = array_merge(array('vm_id' => $params['customfields']['vmid']), $extra);
	$result = lndynamic_API($api_id, $api_key, 'vm', $action, $args);

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	} else {
		return "success";
	}
}

function lndynamic_start($params) {
	return lndynamic_action($params, 'start');
}

function lndynamic_reboot($params) {
	return lndynamic_action($params, 'reboot');
}

function lndynamic_stop($params) {
	return lndynamic_action($params, 'stop');
}

function lndynamic_rescue($params) {
	return lndynamic_action($params, 'rescue');
}

function lndynamic_reimage($params) {
	if(!isset($_REQUEST['os'])) {
		return 'No operating system specified.';
	}

	if(!lunanode_isActive($params['serviceid'])) {
		return 'Error: service is not currently active.';
	}

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}
	
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];
	
	$info = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $params['customfields']['vmid']));

	if(isset($info['error'])) {
		return "Error: {$info['error']}.";
	}

	if(empty($info['info']['ip'])) {
		lndynamic_API($api_id, $api_key, 'vm', 'floatingip-add', array('vm_id' => $params['customfields']['vmid'])); //maybe this failed to acquire IP, so try now
		return "Error: VM does not have an IP address yet!";
	} else if(empty($info['info']['hostname']) || empty($info['extra']['plan_id'])) {
		return "Error: VM missing hostname attribtue.";
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'floatingip-delete', array('vm_id' => $params['customfields']['vmid'], 'keep' => 'yes'));

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'delete', array('vm_id' => $params['customfields']['vmid']));

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'create', array('hostname' => $info['info']['hostname'], 'plan_id' => $info['extra']['plan_id'], 'image_id' => $_REQUEST['os'], 'ip' => $info['info']['ip']));

	if(isset($result['error'])) {
		return "Error: {$result['error']}.";
	}

	lunanode_customFieldSet($params['pid'], 'vmid', $params['serviceid'], $result['vm_id']);
	lunanode_redirect('clientarea.php', array('action' => 'productdetails', 'id' => $params['serviceid']));
}

function lndynamic_vnc($params) {
	if(!lunanode_isActive($params['serviceid'])) {
		return 'Error: service is not currently active.';
	}
	
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(empty($params['customfields']['vmid'])) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'vnc', array('vm_id' => $params['customfields']['vmid']));

	if(isset($result['vnc_url'])) {
		lunanode_redirect($result['vnc_url']);
	} else {
		return "Error: VNC connection failed.";
	}
}

function lndynamic_ClientAreaCustomButtonArray() {
	$buttonarray = array(
	 "Reboot Server" => "reboot",
	 "Start Server" => "start",
	 "Shutdown Server" => "stop",
	 "Re-image" => "reimage",
	 "Rescue" => "rescue",
	 "VNC" => "vnc"
	);
	return $buttonarray;
}

function lndynamic_AdminCustomButtonArray() {
	$buttonarray = array(
	 "Reboot Server" => "reboot",
	 "Start Server" => "start",
	 "Shutdown Server" => "stop"
	);
	return $buttonarray;
}

function lndynamic_UsageUpdate($params) {
	return;
}

function lndynamic_AdminServicesTabFields($params) {
	return array();
}

function lndynamic_AdminServicesTabFieldsSave($params) {
	return;
}

?>
