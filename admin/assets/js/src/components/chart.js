Formwork.Chart = function(element, data) {
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

    var chart = new Chartist.Line(element, data, options);

    var isFirefox = navigator.userAgent.indexOf("Firefox") !== -1;

    $(chart.container).on('mouseover', '.ct-point', function() {
        var $this = $(this);
        var tooltipOffset = {x: 0, y: -8};

        if (isFirefox) {
            var strokeWidth = parseFloat($this.css('stroke-width'));
            tooltipOffset.x += strokeWidth / 2;
            tooltipOffset.y += strokeWidth / 2;
        }

        var tooltip = new Formwork.Tooltip($this.attr('ct:value'), {referenceElement: $this, offset: tooltipOffset});
        tooltip.show();
    });
};
