import Chartist from 'chartist';
import Tooltip from './tooltip';

export default function Chart(element, data) {
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
            labelOffset: {
                x: 0, y: 10
            }
        },
        axisY: {
            onlyInteger: true,
            offset: 15,
            labelOffset: {
                x: 0, y: 5
            }
        }
    };

    var chart = new Chartist.Line(element, data, options);

    chart.container.addEventListener('mouseover', function (event) {
        var tooltipOffset, tooltip, strokeWidth;

        if (event.target.getAttribute('class') === 'ct-point') {
            tooltipOffset = {
                x: 0, y: -8
            };
            if (navigator.userAgent.indexOf('Firefox') !== -1) {
                strokeWidth = parseFloat(getComputedStyle(event.target)['stroke-width']);
                tooltipOffset.x += strokeWidth / 2;
                tooltipOffset.y += strokeWidth / 2;
            }
            tooltip = new Tooltip(event.target.getAttribute('ct:value'), {
                referenceElement: event.target,
                offset: tooltipOffset
            });
            tooltip.show();
        }
    });
}
