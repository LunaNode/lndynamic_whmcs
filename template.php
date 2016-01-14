<table width="100%" cellspacing="0" cellpadding="2" border="1" style="text-align: left;">
<tr>
	<th>Status</th>
	<td><?php echo htmlspecialchars_decode($info['status']); ?></td>
</tr>
<? if(isset($info['hostname'])) { ?>
<tr>
	<th>Hostname</th>
	<td><?php echo $info['hostname']; ?></td>
</tr>
<? } ?>
<? if(isset($info['ip'])) { ?>
<tr>
	<th>External IP</th>
	<td><?php echo $info['ip']; ?></td>
</tr>
<? } ?>
<? if(isset($info['privateip'])) { ?>
<tr>
	<th>Private IP</th>
	<td><?php echo $info['privateip']; ?></td>
</tr>
<? } ?>
<? if(isset($info['os'])) { ?>
<tr>
	<th>Operating System</th>
	<td><?php echo $info['os']; ?></td>
</tr>
<? } ?>
<? if(isset($info['login_details'])) { ?>
<tr>
	<th>Login Details</th>
	<td><?php echo $info['login_details']; ?></td>
</tr>
<? } ?>
<? if(isset($info['bandwidthUsedGB']) && isset($extra['bandwidth'])) { ?>
<tr>
	<th>Bandwidth Usage</th>
	<td><?php echo $info['bandwidthUsedGB']; ?> GB / <?php echo $extra['bandwidth']; ?> GB</td>
</tr>
<? } ?>
</table>

<hr>

<div style="text-align:left;">
<h4>Actions</h4>

<form method="POST" action="clientarea.php?action=productdetails&id=<?= $params['serviceid'] ?>">
<input type="hidden" name="serveraction" value="custom" />
<button type="submit" class="searchinput" name="a" value="start">Start</button>
<button type="submit" class="searchinput" name="a" value="reboot">Reboot</button>
<button type="submit" class="searchinput" name="a" value="stop">Stop</button>
<button type="submit" class="searchinput" name="a" value="rescue">Rescue</button>
<a href="clientarea.php?action=productdetails&id=<?= $params['serviceid'] ?>&serveraction=custom&a=vnc" target="_blank"><button type="button" class="searchinput">VNC</button></a>
</form>

<h4>Reinstallation</h4>

<form method="POST" action="clientarea.php?action=productdetails&id=<?= $params['serviceid'] ?>">
<input type="hidden" name="serveraction" value="custom" />
<select name="os">
	<? foreach($images as $image) { ?>
		<option value="<?= $image['image_id'] ?>"><?= $image['name'] ?></option>
	<? } ?>
</select>
<button class="searchinput" name="a" value="reimage">Reinstall</button>
</form>
</div>
