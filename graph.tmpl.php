<!DOCTYPE html>
<html>
	<head>
		<title>Graph</title>
		<link href="<?= $lndynamic_path ?>/jqplot/jquery.jqplot.min.css" rel="stylesheet">
	</head>
	<body>
		<select id="graphduration" onchange="updateUsage();">
			<option value="6">Six hours</option>
			<option value="12">Twelve hours</option>
			<option value="24">Day</option>
			<option value="168">Week</option>
			<option value="720">Month</option>
		</select>
		<div id="chart" class="jqplot-target" style="width: 100%" data-usage="<?php echo $b64usage; ?>" data-graphkey="<?php echo $graph_key ?>"></div>

		<script src="<?= $lndynamic_path ?>/jquery.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/jquery.jqplot.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.barRenderer.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.highlighter.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.cursor.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.pointLabels.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
		<script src="<?= $lndynamic_path ?>/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
		<script src="<?= $lndynamic_path ?>/graph.js"></script>
	</body>
</html>
