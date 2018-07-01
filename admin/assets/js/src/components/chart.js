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

    $(chart.container).on('mouseover', '.ct-point', function() {
        var $this = $(this);
        var tooltip = new Formwork.Tooltip($this.attr('ct:value'), {referenceElement: $this, offset: {x: 0, y: -8}});
        tooltip.show();
    });
};
