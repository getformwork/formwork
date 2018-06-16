var Chart = (function(element, data) {
	var options = {
		showArea: true,
		fullWidth: true,
		scaleMinSpace: 20,
		divisor: 5,
		chartPadding: 20,
		lineSmooth: false,
		low: 0,
		axisX: {
			showGrid: false,
			labelOffset: {x: 0, y: 10}
		},
		axisY: {
			onlyInteger: true,
			offset: 15,
			labelOffset: {x: 0, y: 5}
		}
	};

	new Chartist.Line(element, data, options);
});

$(function() {
	$('[data-chart-data]').each(function() {
		new Chart(this, $(this).data('chart-data'));
	});
	$('.ct-chart').on('mouseover', '.ct-point', function() {
		new Tooltip($(this), $(this).attr('ct:value'), {offset: {x: 0, y: -8}});
	});
});
