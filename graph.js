var Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9+/=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/rn/g,"n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}

function extractSingle(key, usage, limit) {
	var result = [];

	usage.forEach(function(entry) {
		if(new Date().getTime() / 1000 - entry['unix_time'] < limit) {
			result.push([new Date(entry['unix_time'] * 1000), parseFloat(entry[key])]);
		}
	});

	//we don't want to display more than 150 data points on chart
	merge_factor = Math.ceil(result.length / 150);

	if(merge_factor > 1) {
		var result_pre = result;
		result = [];

		for(var i = 0; i <= result_pre.length - merge_factor; i += merge_factor) {
			total = 0;
			for(var j = 0; j < merge_factor; j++) {
				total += result_pre[i + j][1];
			}
			result.push([result_pre[i][0], total / merge_factor]);
		}
	}


	return result;
}

function extractBandwidth(bwusage, limit) {
	return {
		'in': extractSingle('incoming_rate', bwusage, limit),
		'out': extractSingle('outgoing_rate', bwusage, limit),
		'total': extractSingle('total_rate', bwusage, limit)
	};
}

function extractIO(iousage, limit) {
	return {
		'read': extractSingle('read', iousage, limit),
		'write': extractSingle('write', iousage, limit)
	};
}

function processBandwidthUsage(bwusage) {
	if( bwusage.length === 0 ) {
		$('#chart').html('<center><h4>No Bandwidth Data Available</h4></center>');
		return;
	}

	result = extractBandwidth(bwusage, $('#graphduration').val() * 3600);
	if(result.length === 0) {
		$('#chart').html('<center><h4>No Bandwidth Data Available</h4></center>');
		return;
	}

	usage_plot = $.jqplot("chart", [result.in, result.out], {
		cursor: {
			show: true,
			zoom: true,
			looseZoom: true,
			showTooltip: false
		},
		legend: {
			show: true
		},
		series: [
			{
				lineWidth: 2,
				markerOptions: {style: 'square', size: 4.0},
				label: 'Incoming traffic'
			},
			{
				lineWidth: 2,
				markerOptions: {style: 'square', size: 4.0},
				label: 'Outgoing traffic'
			}
		],
		axes: {
			// These options will set up the x axis like a category axis.
			xaxis: {
				renderer: $.jqplot.DateAxisRenderer
			},
			yaxis: {
				min: 0,
				label: 'Bandwidth usage (bytes/sec)',
				labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				tickRenderer: $.jqplot.AxisTickRenderer,
				tickOptions: {
					formatter: function(format, value) {
						if(value > 1000000) return (value / 1000000.0).toFixed(2) + "M";
						else if(value > 1000) return (value / 1000.0).toFixed(2) + "K";
						else return value;
					}
				}
			}
		}
	});

	setTimeout(function() {
		usage_plot.replot( { resetAxes: true } );
	}, 100);
}

function processCPUUsage(cpuusage) {
	if( cpuusage.length === 0 ) {
		$('#chart').html('<center><h4>No CPU Utilization Data Available</h4></center>');
		return;
	}

	result = extractSingle('percent', cpuusage, $('#graphduration').val() * 3600);
	if(result.length === 0) {
		$('#chart').html('<center><h4>No CPU Utilization Data Available</h4></center>');
		return;
	}

	usage_plot = $.jqplot("chart", [result], {
		cursor: {
			show: true,
			zoom: true,
			looseZoom: true,
			showTooltip: false
		},
		series: [
			{
				lineWidth: 2,
				markerOptions: {style: 'square', size: 4.0}
			}
		],
		axes: {
			// These options will set up the x axis like a category axis.
			xaxis: {
				renderer: $.jqplot.DateAxisRenderer
			},
			yaxis: {
				min: 0,
				label: 'CPU percentage',
				labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				tickRenderer: $.jqplot.AxisTickRenderer,
				tickOptions: {
					formatter: function(format, value) {
						return value.toFixed(2) + "%";
					}
				}
			}
		}
	});

	setTimeout(function() {
		usage_plot.replot( { resetAxes: true } );
	}, 300);
}

function processIOUsage(iousage) {
	if( iousage.length === 0 ) {
		$('#chart').html('<center><h4>No IO Utilization Data Available</h4></center>');
		return;
	}

	result = extractIO(iousage, $('#graphduration').val() * 3600);
	if(result.length === 0) {
		$('#chart').html('<center><h4>No IO Utilization Data Available</h4></center>');
		return;
	}

	usage_plot = $.jqplot("chart", [result.read, result.write], {
		cursor: {
			show: true,
			zoom: true,
			looseZoom: true,
			showTooltip: false
		},
		legend: {
			show: true
		},
		series: [
			{
				lineWidth: 2,
				markerOptions: {style: 'square', size: 4.0},
				label: 'Disk read'
			},
			{
				lineWidth: 2,
				markerOptions: {style: 'square', size: 4.0},
				label: 'Disk write'
			}
		],
		axes: {
			// These options will set up the x axis like a category axis.
			xaxis: {
				renderer: $.jqplot.DateAxisRenderer
			},
			yaxis: {
				min: 0,
				label: 'I/O throughput (bytes/sec)',
				labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				tickRenderer: $.jqplot.AxisTickRenderer,
				tickOptions: {
					formatter: function(format, value) {
						if(value > 1000000) return (value / 1000000.0).toFixed(2) + "M";
						else if(value > 1000) return (value / 1000.0).toFixed(2) + "K";
						else return value;
					}
				}
			}
		}
	});

	setTimeout(function() {
		usage_plot.replot( { resetAxes: true } );
	}, 500);
}

function updateUsage() {
	var graphKey = $('#chart').data('graphkey');
	var usageData = JSON.parse(Base64.decode($('#chart').data('usage')));
	if(graphKey == 'bandwidth') {
		processBandwidthUsage(usageData);
	} else if(graphKey == 'cpu') {
		processCPUUsage(usageData);
	} else if(graphKey == 'io') {
		processIOUsage(usageData);
	}
}

$(window).load(function() {
	updateUsage();
});

$(window).resize(function() {
	usage_plot.replot( { resetAxes: true } );
});
