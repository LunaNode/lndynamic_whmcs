<?php

require_once(dirname(__FILE__) . "/common.php");
require_once(dirname(__FILE__) . "/api.php");

function lndynamic_API($api_id, $api_key, $category, $action, $params = array()) {
	try {
		$lndynamic = new LNDynamic($api_id, $api_key);
		return $lndynamic->request($category, $action, $params);
	} catch(LNDAPIException $e) {
		$error = $e->getMessage();
		if(strpos($error, 'API error: ') !== false) {
			$parts = explode('API error: ', $error);
			$error = $parts[1];
		}
		return array('error' => $error);
	}
}

function lndynamic_Plans() {
	return array(
		's.half' => 79,
		's.1' => 80,
		's.2' => 81,
		's.4' => 82,
		's.8' => 83,
		's.16' => 84,
		's.32' => 85,
		'm.1' => 86,
		'm.2' => 87,
		'm.4' => 88,
		'm.8' => 89,
		'm.16' => 90,
		'm.32' => 91,
		'm.64' => 92,
		'c.1' => 93,
		'c.2' => 94,
		'c.4' => 95,
		'c.8' => 96,
		'c.16' => 97,
	);
}

function lndynamic_ParamsToPlanID($params) {
	$plan_name = $params['configoption1'];
	$plan_id = $params['configoption2'];

	if(!$plan_id) {
		$plans = lndynamic_Plans();
		if(array_key_exists($plan_name, $plans)) {
			return $plans[$plan_name];
		} else {
			throw new Exception("Error: product misconfiguration (unknown plan name '$plan_name', and plan_id not set).");
		}
	} else {
		return $plan_id;
	}
}

function lndynamic_ConfigOptions() {
	$planOptions = implode(',', array_keys(lndynamic_Plans()));

	return array(
		"Plan name" => array("Type" => "dropdown", "Options" => $planOptions),
		"plan_id" => array("Type" => "text", "Size" => "32", "Description" => "Only required for special plans; if set, overrides plan name"),
		"API id" => array("Type" => "text", "Size" => "20", "Description" => "Generate from API tab"),
		"API key" => array("Type" => "text", "Size" => "30", "Description" => "Generate from API tab"),
		"Region" => array("Type" => "text", "Size" => "16", "Description" => "The region to provision in (e.g. 'toronto', 'montreal', 'roubaix')"),
		"Startup scripts" => array("Type" => "text", "Size" => "16", "Description" => "Comma-separated list of startup script IDs to run on VMs (optional)"),
		"Security groups" => array("Type" => "text", "Size" => "16", "Description" => "Comma-separated list of security group IDs to associate with VMs (optional)"),
	);
}

function lndynamic_CreateAccount($params) {
	$domain = $params["domain"];
	$configoptions = $params["configoptions"];

	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];
	$region = strtolower($params['configoption5']);
	$startupScripts = $params['configoption6'];
	$securityGroups = $params['configoption7'];

	if(!$region) {
		$region = 'toronto';
	}

	if(!lunanode_customFieldExists($params['pid'], 'vmid')) {
		return 'Custom field vmid has not been configured.';
	}

	if($params['customfields']['vmid']){
		$result = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $params['customfields']['vmid']));
		if(!array_key_exists('error', $result)){
			return 'Virtual machine already exists, please delete and try again.';
		}
	}

	// get image ID
	// if OS config option is integer, then that is the image ID
	// otherwise, it is image name, which we have to change to an ID
	if(!array_key_exists('Operating System', $configoptions)) {
		return "Error: you must select an operating system!";
	}
	$os = $configoptions['Operating System'];
	$image_id = false;

	if(is_numeric($os)) {
		$image_id = intval($os);
	} else {
		$apiImages = lndynamic_API($api_id, $api_key, 'image', 'list', array('region' => $region));

		if(!array_key_exists('images', $apiImages)) {
			return "Error: backend image list call failed.";
		}

		// filter so we only return template images, since we don't support swapping boot order
		$images = array();
		foreach($apiImages['images'] as $apiImage) {
			if(stripos($apiImage['name'], $os) !== false) {
				$image_id = intval($apiImage['image_id']);
				break;
			}
		}

		if($image_id === false) {
			return "Error: could not find template matching the selected operating system!";
		}
	}

	if(!$api_id || !$api_key) {
		return "Error: product misconfiguration (backend interface not set).";
	}

	try {
		$plan_id = lndynamic_ParamsToPlanID($params);
	} catch(Exception $e) {
		return $e->getMessage();
	}
	$args = array(
		'hostname' => $domain,
		'plan_id' => $plan_id,
		'image_id' => $image_id,
		'region' => $region
	);

	if($startupScripts) {
		$args['scripts'] = $startupScripts;
	}
	if($securityGroups) {
		$args['securitygroups'] = $securityGroups;
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'create', $args);

	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	} else {
		lunanode_customFieldSet($params['pid'], 'vmid', $params['serviceid'], $result['vm_id']);
		return "success";
	}
}

function lndynamic_TerminateAccount($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'delete', array('vm_id' => $params['customfields']['vmid']));

	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	} else {
		lunanode_customFieldSet($params['pid'], 'vmid', $params['serviceid'], '');
	}

	return "success";

}

function lndynamic_SuspendAccount($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'stop', array('vm_id' => $params['customfields']['vmid']));

	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	} else {
		return "success";
	}
}

function lndynamic_UnsuspendAccount($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	lndynamic_API($api_id, $api_key, 'vm', 'start', array('vm_id' => $params['customfields']['vmid']));
	return "success";
}

function lndynamic_ChangePackage($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	try {
		$plan_id = lndynamic_ParamsToPlanID($params);
	} catch(Exception $e) {
		return $e->getMessage();
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'resize', array(
		'vm_id' => $params['customfields']['vmid'],
		'plan_id' => $plan_id,
	));
	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	}
	return "success";
}

function lndynamic_ClientArea($params) {
	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$vmid = $params['customfields']['vmid'];
	$info = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $vmid));

	if(!array_key_exists('info', $info)) {
		return "Backend VM info call failed.";
	}

	$apiImages = lndynamic_API($api_id, $api_key, 'image', 'list', array('region' => $info['extra']['region']));

	if(!array_key_exists('images', $apiImages)) {
		return "Backend image list call failed.";
	}

	// filter so we only return template images, since we don't support swapping boot order
	$images = array();
	foreach($apiImages['images'] as $apiImage) {
		if(strpos($apiImage['name'], 'template') !== false) {
			$images[] = $apiImage;
		}
	}

	$extra = $info['extra'];
	$info = $info['info'];

	ob_start();
	include(dirname(__FILE__) . "/clientarea.tmpl.php");
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

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$args = array_merge(array('vm_id' => $params['customfields']['vmid']), $extra);
	$result = lndynamic_API($api_id, $api_key, 'vm', $action, $args);

	if(array_key_exists('error', $result)) {
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
	if(!array_key_exists('os', $_REQUEST) || !$_REQUEST['os']) {
		return 'No operating system specified.';
	}

	if(!lunanode_isActive($params['serviceid'])) {
		return 'Error: service is not currently active.';
	}

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	$info = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $params['customfields']['vmid']));

	if(array_key_exists('error', $result)) {
		return "Error: {$info['error']}.";
	}

	if(!$info['info']['ip']) {
		lndynamic_API($api_id, $api_key, 'vm', 'floatingip-add', array('vm_id' => $params['customfields']['vmid'])); //maybe this failed to acquire IP, so try now
		return "Error: VM does not have an IP address yet!";
	} else if(!array_key_exists('hostname', $info['info']) || !array_key_exists('plan_id', $info['extra']) || !array_key_exists('region', $info['extra'])) {
		return "Error: VM missing hostname and/or plan_id and/or region attribtues.";
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'floatingip-delete', array('vm_id' => $params['customfields']['vmid'], 'keep' => 'yes'));

	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'delete', array('vm_id' => $params['customfields']['vmid']));

	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'create', array('hostname' => $info['info']['hostname'], 'plan_id' => $info['extra']['plan_id'], 'image_id' => $_REQUEST['os'], 'ip' => $info['info']['ip'], 'region' => $info['extra']['region']));

	if(array_key_exists('error', $result)) {
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

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'vnc', array('vm_id' => $params['customfields']['vmid']));

	if(array_key_exists('vnc_url', $result)) {
		$vnc_url = $result['vnc_url'];
		include(dirname(__FILE__) . "/vnc.tmpl.php");
		exit;
	} else {
		return "Error: VNC connection failed.";
	}
}

function lndynamic_diskswap($params) {
	$result = lndynamic_action($params, 'diskswap');
	lndynamic_action($params, 'reboot');
	return $result;
}

function lndynamic_rdns($params) {
	if(!array_key_exists('ip', $_REQUEST) || !$_REQUEST['ip']) {
		return 'No IP specified.';
	} else if(!array_key_exists('reverse', $_REQUEST) || !$_REQUEST['reverse']) {
		return 'No reverse hostname specified.';
	}

	$request_ip = $_REQUEST['ip'];
	$request_reverse = $_REQUEST['reverse'];

	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	// make sure this is a valid IP
	$info = lndynamic_API($api_id, $api_key, 'vm', 'info', array('vm_id' => $params['customfields']['vmid']));
	if(array_key_exists('error', $result)) {
		return "Error: {$info['error']}.";
	}
	$valid = false;
	foreach($info['info']['addresses'] as $address) {
		if($address['external'] && $address['addr'] === $request_ip) {
			$valid = true;
		}
	}
	if(!$valid) {
		return 'Invalid IP specified';
	}

	$result = lndynamic_API($api_id, $api_key, 'dns', 'set', array(
		'ip' => $request_ip,
		'hostname' => $request_reverse,
	));
	if(array_key_exists('error', $result)) {
		return "Error: {$result['error']}.";
	}

	return 'success';
}

function lndynamic_graph($params) {
	if(array_key_exists('key', $_REQUEST)) {
		$graph_key = $_REQUEST['key'];
	} else {
		$graph_key = 'bandwidth';
	}
	if($graph_key !== 'bandwidth' && $graph_key !== 'cpu' && $graph_key !== 'io') {
		return 'Invalid graph key.';
	}

	$api_id = $params['configoption3'];
	$api_key = $params['configoption4'];

	if(!$params['customfields']['vmid']) {
		return 'Virtual machine does not exist.';
	}

	$result = lndynamic_API($api_id, $api_key, 'vm', 'usage', array('vm_id' => $params['customfields']['vmid'], 'key' => $graph_key));
	$b64usage = base64_encode(json_encode($result['usage']));
	$lndynamic_path = 'modules/servers/lndynamic';
	include(dirname(__FILE__) . "/graph.tmpl.php");
	exit;
}

function lndynamic_ClientAreaCustomButtonArray() {
	$buttonarray = array(
		"Reboot Server" => "reboot",
		"Start Server" => "start",
		"Shutdown Server" => "stop",
		"Re-image" => "reimage",
		"Rescue" => "rescue",
		"VNC" => "vnc",
		"Swap boot order" => "diskswap",
		"Graph" => "graph",
		"rDNS" => "rdns",
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
