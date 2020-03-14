var Formwork = (function () {
	'use strict';

	var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

	function createCommonjsModule(fn, module) {
		return module = { exports: {} }, fn(module, module.exports), module.exports;
	}

	var chartist = createCommonjsModule(function (module) {
	(function (root, factory) {
	  if ( module.exports) {
	    // Node. Does not work with strict CommonJS, but
	    // only CommonJS-like environments that support module.exports,
	    // like Node.
	    module.exports = factory();
	  } else {
	    root['Chartist'] = factory();
	  }
	}(commonjsGlobal, function () {

	/* Chartist.js 0.11.4
	 * Copyright © 2019 Gion Kunz
	 * Free to use under either the WTFPL license or the MIT license.
	 * https://raw.githubusercontent.com/gionkunz/chartist-js/master/LICENSE-WTFPL
	 * https://raw.githubusercontent.com/gionkunz/chartist-js/master/LICENSE-MIT
	 */
	/**
	 * The core module of Chartist that is mainly providing static functions and higher level functions for chart modules.
	 *
	 * @module Chartist.Core
	 */
	var Chartist = {
	  version: '0.11.4'
	};

	(function (globalRoot, Chartist) {

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  /**
	   * This object contains all namespaces used within Chartist.
	   *
	   * @memberof Chartist.Core
	   * @type {{svg: string, xmlns: string, xhtml: string, xlink: string, ct: string}}
	   */
	  Chartist.namespaces = {
	    svg: 'http://www.w3.org/2000/svg',
	    xmlns: 'http://www.w3.org/2000/xmlns/',
	    xhtml: 'http://www.w3.org/1999/xhtml',
	    xlink: 'http://www.w3.org/1999/xlink',
	    ct: 'http://gionkunz.github.com/chartist-js/ct'
	  };

	  /**
	   * Helps to simplify functional style code
	   *
	   * @memberof Chartist.Core
	   * @param {*} n This exact value will be returned by the noop function
	   * @return {*} The same value that was provided to the n parameter
	   */
	  Chartist.noop = function (n) {
	    return n;
	  };

	  /**
	   * Generates a-z from a number 0 to 26
	   *
	   * @memberof Chartist.Core
	   * @param {Number} n A number from 0 to 26 that will result in a letter a-z
	   * @return {String} A character from a-z based on the input number n
	   */
	  Chartist.alphaNumerate = function (n) {
	    // Limit to a-z
	    return String.fromCharCode(97 + n % 26);
	  };

	  /**
	   * Simple recursive object extend
	   *
	   * @memberof Chartist.Core
	   * @param {Object} target Target object where the source will be merged into
	   * @param {Object...} sources This object (objects) will be merged into target and then target is returned
	   * @return {Object} An object that has the same reference as target but is extended and merged with the properties of source
	   */
	  Chartist.extend = function (target) {
	    var i, source, sourceProp;
	    target = target || {};

	    for (i = 1; i < arguments.length; i++) {
	      source = arguments[i];
	      for (var prop in source) {
	        sourceProp = source[prop];
	        if (typeof sourceProp === 'object' && sourceProp !== null && !(sourceProp instanceof Array)) {
	          target[prop] = Chartist.extend(target[prop], sourceProp);
	        } else {
	          target[prop] = sourceProp;
	        }
	      }
	    }

	    return target;
	  };

	  /**
	   * Replaces all occurrences of subStr in str with newSubStr and returns a new string.
	   *
	   * @memberof Chartist.Core
	   * @param {String} str
	   * @param {String} subStr
	   * @param {String} newSubStr
	   * @return {String}
	   */
	  Chartist.replaceAll = function(str, subStr, newSubStr) {
	    return str.replace(new RegExp(subStr, 'g'), newSubStr);
	  };

	  /**
	   * Converts a number to a string with a unit. If a string is passed then this will be returned unmodified.
	   *
	   * @memberof Chartist.Core
	   * @param {Number} value
	   * @param {String} unit
	   * @return {String} Returns the passed number value with unit.
	   */
	  Chartist.ensureUnit = function(value, unit) {
	    if(typeof value === 'number') {
	      value = value + unit;
	    }

	    return value;
	  };

	  /**
	   * Converts a number or string to a quantity object.
	   *
	   * @memberof Chartist.Core
	   * @param {String|Number} input
	   * @return {Object} Returns an object containing the value as number and the unit as string.
	   */
	  Chartist.quantity = function(input) {
	    if (typeof input === 'string') {
	      var match = (/^(\d+)\s*(.*)$/g).exec(input);
	      return {
	        value : +match[1],
	        unit: match[2] || undefined
	      };
	    }
	    return { value: input };
	  };

	  /**
	   * This is a wrapper around document.querySelector that will return the query if it's already of type Node
	   *
	   * @memberof Chartist.Core
	   * @param {String|Node} query The query to use for selecting a Node or a DOM node that will be returned directly
	   * @return {Node}
	   */
	  Chartist.querySelector = function(query) {
	    return query instanceof Node ? query : document.querySelector(query);
	  };

	  /**
	   * Functional style helper to produce array with given length initialized with undefined values
	   *
	   * @memberof Chartist.Core
	   * @param length
	   * @return {Array}
	   */
	  Chartist.times = function(length) {
	    return Array.apply(null, new Array(length));
	  };

	  /**
	   * Sum helper to be used in reduce functions
	   *
	   * @memberof Chartist.Core
	   * @param previous
	   * @param current
	   * @return {*}
	   */
	  Chartist.sum = function(previous, current) {
	    return previous + (current ? current : 0);
	  };

	  /**
	   * Multiply helper to be used in `Array.map` for multiplying each value of an array with a factor.
	   *
	   * @memberof Chartist.Core
	   * @param {Number} factor
	   * @returns {Function} Function that can be used in `Array.map` to multiply each value in an array
	   */
	  Chartist.mapMultiply = function(factor) {
	    return function(num) {
	      return num * factor;
	    };
	  };

	  /**
	   * Add helper to be used in `Array.map` for adding a addend to each value of an array.
	   *
	   * @memberof Chartist.Core
	   * @param {Number} addend
	   * @returns {Function} Function that can be used in `Array.map` to add a addend to each value in an array
	   */
	  Chartist.mapAdd = function(addend) {
	    return function(num) {
	      return num + addend;
	    };
	  };

	  /**
	   * Map for multi dimensional arrays where their nested arrays will be mapped in serial. The output array will have the length of the largest nested array. The callback function is called with variable arguments where each argument is the nested array value (or undefined if there are no more values).
	   *
	   * @memberof Chartist.Core
	   * @param arr
	   * @param cb
	   * @return {Array}
	   */
	  Chartist.serialMap = function(arr, cb) {
	    var result = [],
	        length = Math.max.apply(null, arr.map(function(e) {
	          return e.length;
	        }));

	    Chartist.times(length).forEach(function(e, index) {
	      var args = arr.map(function(e) {
	        return e[index];
	      });

	      result[index] = cb.apply(null, args);
	    });

	    return result;
	  };

	  /**
	   * This helper function can be used to round values with certain precision level after decimal. This is used to prevent rounding errors near float point precision limit.
	   *
	   * @memberof Chartist.Core
	   * @param {Number} value The value that should be rounded with precision
	   * @param {Number} [digits] The number of digits after decimal used to do the rounding
	   * @returns {number} Rounded value
	   */
	  Chartist.roundWithPrecision = function(value, digits) {
	    var precision = Math.pow(10, digits || Chartist.precision);
	    return Math.round(value * precision) / precision;
	  };

	  /**
	   * Precision level used internally in Chartist for rounding. If you require more decimal places you can increase this number.
	   *
	   * @memberof Chartist.Core
	   * @type {number}
	   */
	  Chartist.precision = 8;

	  /**
	   * A map with characters to escape for strings to be safely used as attribute values.
	   *
	   * @memberof Chartist.Core
	   * @type {Object}
	   */
	  Chartist.escapingMap = {
	    '&': '&amp;',
	    '<': '&lt;',
	    '>': '&gt;',
	    '"': '&quot;',
	    '\'': '&#039;'
	  };

	  /**
	   * This function serializes arbitrary data to a string. In case of data that can't be easily converted to a string, this function will create a wrapper object and serialize the data using JSON.stringify. The outcoming string will always be escaped using Chartist.escapingMap.
	   * If called with null or undefined the function will return immediately with null or undefined.
	   *
	   * @memberof Chartist.Core
	   * @param {Number|String|Object} data
	   * @return {String}
	   */
	  Chartist.serialize = function(data) {
	    if(data === null || data === undefined) {
	      return data;
	    } else if(typeof data === 'number') {
	      data = ''+data;
	    } else if(typeof data === 'object') {
	      data = JSON.stringify({data: data});
	    }

	    return Object.keys(Chartist.escapingMap).reduce(function(result, key) {
	      return Chartist.replaceAll(result, key, Chartist.escapingMap[key]);
	    }, data);
	  };

	  /**
	   * This function de-serializes a string previously serialized with Chartist.serialize. The string will always be unescaped using Chartist.escapingMap before it's returned. Based on the input value the return type can be Number, String or Object. JSON.parse is used with try / catch to see if the unescaped string can be parsed into an Object and this Object will be returned on success.
	   *
	   * @memberof Chartist.Core
	   * @param {String} data
	   * @return {String|Number|Object}
	   */
	  Chartist.deserialize = function(data) {
	    if(typeof data !== 'string') {
	      return data;
	    }

	    data = Object.keys(Chartist.escapingMap).reduce(function(result, key) {
	      return Chartist.replaceAll(result, Chartist.escapingMap[key], key);
	    }, data);

	    try {
	      data = JSON.parse(data);
	      data = data.data !== undefined ? data.data : data;
	    } catch(e) {}

	    return data;
	  };

	  /**
	   * Create or reinitialize the SVG element for the chart
	   *
	   * @memberof Chartist.Core
	   * @param {Node} container The containing DOM Node object that will be used to plant the SVG element
	   * @param {String} width Set the width of the SVG element. Default is 100%
	   * @param {String} height Set the height of the SVG element. Default is 100%
	   * @param {String} className Specify a class to be added to the SVG element
	   * @return {Object} The created/reinitialized SVG element
	   */
	  Chartist.createSvg = function (container, width, height, className) {
	    var svg;

	    width = width || '100%';
	    height = height || '100%';

	    // Check if there is a previous SVG element in the container that contains the Chartist XML namespace and remove it
	    // Since the DOM API does not support namespaces we need to manually search the returned list http://www.w3.org/TR/selectors-api/
	    Array.prototype.slice.call(container.querySelectorAll('svg')).filter(function filterChartistSvgObjects(svg) {
	      return svg.getAttributeNS(Chartist.namespaces.xmlns, 'ct');
	    }).forEach(function removePreviousElement(svg) {
	      container.removeChild(svg);
	    });

	    // Create svg object with width and height or use 100% as default
	    svg = new Chartist.Svg('svg').attr({
	      width: width,
	      height: height
	    }).addClass(className);

	    svg._node.style.width = width;
	    svg._node.style.height = height;

	    // Add the DOM node to our container
	    container.appendChild(svg._node);

	    return svg;
	  };

	  /**
	   * Ensures that the data object passed as second argument to the charts is present and correctly initialized.
	   *
	   * @param  {Object} data The data object that is passed as second argument to the charts
	   * @return {Object} The normalized data object
	   */
	  Chartist.normalizeData = function(data, reverse, multi) {
	    var labelCount;
	    var output = {
	      raw: data,
	      normalized: {}
	    };

	    // Check if we should generate some labels based on existing series data
	    output.normalized.series = Chartist.getDataArray({
	      series: data.series || []
	    }, reverse, multi);

	    // If all elements of the normalized data array are arrays we're dealing with
	    // multi series data and we need to find the largest series if they are un-even
	    if (output.normalized.series.every(function(value) {
	        return value instanceof Array;
	      })) {
	      // Getting the series with the the most elements
	      labelCount = Math.max.apply(null, output.normalized.series.map(function(series) {
	        return series.length;
	      }));
	    } else {
	      // We're dealing with Pie data so we just take the normalized array length
	      labelCount = output.normalized.series.length;
	    }

	    output.normalized.labels = (data.labels || []).slice();
	    // Padding the labels to labelCount with empty strings
	    Array.prototype.push.apply(
	      output.normalized.labels,
	      Chartist.times(Math.max(0, labelCount - output.normalized.labels.length)).map(function() {
	        return '';
	      })
	    );

	    if(reverse) {
	      Chartist.reverseData(output.normalized);
	    }

	    return output;
	  };

	  /**
	   * This function safely checks if an objects has an owned property.
	   *
	   * @param {Object} object The object where to check for a property
	   * @param {string} property The property name
	   * @returns {boolean} Returns true if the object owns the specified property
	   */
	  Chartist.safeHasProperty = function(object, property) {
	    return object !== null &&
	      typeof object === 'object' &&
	      object.hasOwnProperty(property);
	  };

	  /**
	   * Checks if a value is considered a hole in the data series.
	   *
	   * @param {*} value
	   * @returns {boolean} True if the value is considered a data hole
	   */
	  Chartist.isDataHoleValue = function(value) {
	    return value === null ||
	      value === undefined ||
	      (typeof value === 'number' && isNaN(value));
	  };

	  /**
	   * Reverses the series, labels and series data arrays.
	   *
	   * @memberof Chartist.Core
	   * @param data
	   */
	  Chartist.reverseData = function(data) {
	    data.labels.reverse();
	    data.series.reverse();
	    for (var i = 0; i < data.series.length; i++) {
	      if(typeof(data.series[i]) === 'object' && data.series[i].data !== undefined) {
	        data.series[i].data.reverse();
	      } else if(data.series[i] instanceof Array) {
	        data.series[i].reverse();
	      }
	    }
	  };

	  /**
	   * Convert data series into plain array
	   *
	   * @memberof Chartist.Core
	   * @param {Object} data The series object that contains the data to be visualized in the chart
	   * @param {Boolean} [reverse] If true the whole data is reversed by the getDataArray call. This will modify the data object passed as first parameter. The labels as well as the series order is reversed. The whole series data arrays are reversed too.
	   * @param {Boolean} [multi] Create a multi dimensional array from a series data array where a value object with `x` and `y` values will be created.
	   * @return {Array} A plain array that contains the data to be visualized in the chart
	   */
	  Chartist.getDataArray = function(data, reverse, multi) {
	    // Recursively walks through nested arrays and convert string values to numbers and objects with value properties
	    // to values. Check the tests in data core -> data normalization for a detailed specification of expected values
	    function recursiveConvert(value) {
	      if(Chartist.safeHasProperty(value, 'value')) {
	        // We are dealing with value object notation so we need to recurse on value property
	        return recursiveConvert(value.value);
	      } else if(Chartist.safeHasProperty(value, 'data')) {
	        // We are dealing with series object notation so we need to recurse on data property
	        return recursiveConvert(value.data);
	      } else if(value instanceof Array) {
	        // Data is of type array so we need to recurse on the series
	        return value.map(recursiveConvert);
	      } else if(Chartist.isDataHoleValue(value)) {
	        // We're dealing with a hole in the data and therefore need to return undefined
	        // We're also returning undefined for multi value output
	        return undefined;
	      } else {
	        // We need to prepare multi value output (x and y data)
	        if(multi) {
	          var multiValue = {};

	          // Single series value arrays are assumed to specify the Y-Axis value
	          // For example: [1, 2] => [{x: undefined, y: 1}, {x: undefined, y: 2}]
	          // If multi is a string then it's assumed that it specified which dimension should be filled as default
	          if(typeof multi === 'string') {
	            multiValue[multi] = Chartist.getNumberOrUndefined(value);
	          } else {
	            multiValue.y = Chartist.getNumberOrUndefined(value);
	          }

	          multiValue.x = value.hasOwnProperty('x') ? Chartist.getNumberOrUndefined(value.x) : multiValue.x;
	          multiValue.y = value.hasOwnProperty('y') ? Chartist.getNumberOrUndefined(value.y) : multiValue.y;

	          return multiValue;

	        } else {
	          // We can return simple data
	          return Chartist.getNumberOrUndefined(value);
	        }
	      }
	    }

	    return data.series.map(recursiveConvert);
	  };

	  /**
	   * Converts a number into a padding object.
	   *
	   * @memberof Chartist.Core
	   * @param {Object|Number} padding
	   * @param {Number} [fallback] This value is used to fill missing values if a incomplete padding object was passed
	   * @returns {Object} Returns a padding object containing top, right, bottom, left properties filled with the padding number passed in as argument. If the argument is something else than a number (presumably already a correct padding object) then this argument is directly returned.
	   */
	  Chartist.normalizePadding = function(padding, fallback) {
	    fallback = fallback || 0;

	    return typeof padding === 'number' ? {
	      top: padding,
	      right: padding,
	      bottom: padding,
	      left: padding
	    } : {
	      top: typeof padding.top === 'number' ? padding.top : fallback,
	      right: typeof padding.right === 'number' ? padding.right : fallback,
	      bottom: typeof padding.bottom === 'number' ? padding.bottom : fallback,
	      left: typeof padding.left === 'number' ? padding.left : fallback
	    };
	  };

	  Chartist.getMetaData = function(series, index) {
	    var value = series.data ? series.data[index] : series[index];
	    return value ? value.meta : undefined;
	  };

	  /**
	   * Calculate the order of magnitude for the chart scale
	   *
	   * @memberof Chartist.Core
	   * @param {Number} value The value Range of the chart
	   * @return {Number} The order of magnitude
	   */
	  Chartist.orderOfMagnitude = function (value) {
	    return Math.floor(Math.log(Math.abs(value)) / Math.LN10);
	  };

	  /**
	   * Project a data length into screen coordinates (pixels)
	   *
	   * @memberof Chartist.Core
	   * @param {Object} axisLength The svg element for the chart
	   * @param {Number} length Single data value from a series array
	   * @param {Object} bounds All the values to set the bounds of the chart
	   * @return {Number} The projected data length in pixels
	   */
	  Chartist.projectLength = function (axisLength, length, bounds) {
	    return length / bounds.range * axisLength;
	  };

	  /**
	   * Get the height of the area in the chart for the data series
	   *
	   * @memberof Chartist.Core
	   * @param {Object} svg The svg element for the chart
	   * @param {Object} options The Object that contains all the optional values for the chart
	   * @return {Number} The height of the area in the chart for the data series
	   */
	  Chartist.getAvailableHeight = function (svg, options) {
	    return Math.max((Chartist.quantity(options.height).value || svg.height()) - (options.chartPadding.top +  options.chartPadding.bottom) - options.axisX.offset, 0);
	  };

	  /**
	   * Get highest and lowest value of data array. This Array contains the data that will be visualized in the chart.
	   *
	   * @memberof Chartist.Core
	   * @param {Array} data The array that contains the data to be visualized in the chart
	   * @param {Object} options The Object that contains the chart options
	   * @param {String} dimension Axis dimension 'x' or 'y' used to access the correct value and high / low configuration
	   * @return {Object} An object that contains the highest and lowest value that will be visualized on the chart.
	   */
	  Chartist.getHighLow = function (data, options, dimension) {
	    // TODO: Remove workaround for deprecated global high / low config. Axis high / low configuration is preferred
	    options = Chartist.extend({}, options, dimension ? options['axis' + dimension.toUpperCase()] : {});

	    var highLow = {
	        high: options.high === undefined ? -Number.MAX_VALUE : +options.high,
	        low: options.low === undefined ? Number.MAX_VALUE : +options.low
	      };
	    var findHigh = options.high === undefined;
	    var findLow = options.low === undefined;

	    // Function to recursively walk through arrays and find highest and lowest number
	    function recursiveHighLow(data) {
	      if(data === undefined) {
	        return undefined;
	      } else if(data instanceof Array) {
	        for (var i = 0; i < data.length; i++) {
	          recursiveHighLow(data[i]);
	        }
	      } else {
	        var value = dimension ? +data[dimension] : +data;

	        if (findHigh && value > highLow.high) {
	          highLow.high = value;
	        }

	        if (findLow && value < highLow.low) {
	          highLow.low = value;
	        }
	      }
	    }

	    // Start to find highest and lowest number recursively
	    if(findHigh || findLow) {
	      recursiveHighLow(data);
	    }

	    // Overrides of high / low based on reference value, it will make sure that the invisible reference value is
	    // used to generate the chart. This is useful when the chart always needs to contain the position of the
	    // invisible reference value in the view i.e. for bipolar scales.
	    if (options.referenceValue || options.referenceValue === 0) {
	      highLow.high = Math.max(options.referenceValue, highLow.high);
	      highLow.low = Math.min(options.referenceValue, highLow.low);
	    }

	    // If high and low are the same because of misconfiguration or flat data (only the same value) we need
	    // to set the high or low to 0 depending on the polarity
	    if (highLow.high <= highLow.low) {
	      // If both values are 0 we set high to 1
	      if (highLow.low === 0) {
	        highLow.high = 1;
	      } else if (highLow.low < 0) {
	        // If we have the same negative value for the bounds we set bounds.high to 0
	        highLow.high = 0;
	      } else if (highLow.high > 0) {
	        // If we have the same positive value for the bounds we set bounds.low to 0
	        highLow.low = 0;
	      } else {
	        // If data array was empty, values are Number.MAX_VALUE and -Number.MAX_VALUE. Set bounds to prevent errors
	        highLow.high = 1;
	        highLow.low = 0;
	      }
	    }

	    return highLow;
	  };

	  /**
	   * Checks if a value can be safely coerced to a number. This includes all values except null which result in finite numbers when coerced. This excludes NaN, since it's not finite.
	   *
	   * @memberof Chartist.Core
	   * @param value
	   * @returns {Boolean}
	   */
	  Chartist.isNumeric = function(value) {
	    return value === null ? false : isFinite(value);
	  };

	  /**
	   * Returns true on all falsey values except the numeric value 0.
	   *
	   * @memberof Chartist.Core
	   * @param value
	   * @returns {boolean}
	   */
	  Chartist.isFalseyButZero = function(value) {
	    return !value && value !== 0;
	  };

	  /**
	   * Returns a number if the passed parameter is a valid number or the function will return undefined. On all other values than a valid number, this function will return undefined.
	   *
	   * @memberof Chartist.Core
	   * @param value
	   * @returns {*}
	   */
	  Chartist.getNumberOrUndefined = function(value) {
	    return Chartist.isNumeric(value) ? +value : undefined;
	  };

	  /**
	   * Checks if provided value object is multi value (contains x or y properties)
	   *
	   * @memberof Chartist.Core
	   * @param value
	   */
	  Chartist.isMultiValue = function(value) {
	    return typeof value === 'object' && ('x' in value || 'y' in value);
	  };

	  /**
	   * Gets a value from a dimension `value.x` or `value.y` while returning value directly if it's a valid numeric value. If the value is not numeric and it's falsey this function will return `defaultValue`.
	   *
	   * @memberof Chartist.Core
	   * @param value
	   * @param dimension
	   * @param defaultValue
	   * @returns {*}
	   */
	  Chartist.getMultiValue = function(value, dimension) {
	    if(Chartist.isMultiValue(value)) {
	      return Chartist.getNumberOrUndefined(value[dimension || 'y']);
	    } else {
	      return Chartist.getNumberOrUndefined(value);
	    }
	  };

	  /**
	   * Pollard Rho Algorithm to find smallest factor of an integer value. There are more efficient algorithms for factorization, but this one is quite efficient and not so complex.
	   *
	   * @memberof Chartist.Core
	   * @param {Number} num An integer number where the smallest factor should be searched for
	   * @returns {Number} The smallest integer factor of the parameter num.
	   */
	  Chartist.rho = function(num) {
	    if(num === 1) {
	      return num;
	    }

	    function gcd(p, q) {
	      if (p % q === 0) {
	        return q;
	      } else {
	        return gcd(q, p % q);
	      }
	    }

	    function f(x) {
	      return x * x + 1;
	    }

	    var x1 = 2, x2 = 2, divisor;
	    if (num % 2 === 0) {
	      return 2;
	    }

	    do {
	      x1 = f(x1) % num;
	      x2 = f(f(x2)) % num;
	      divisor = gcd(Math.abs(x1 - x2), num);
	    } while (divisor === 1);

	    return divisor;
	  };

	  /**
	   * Calculate and retrieve all the bounds for the chart and return them in one array
	   *
	   * @memberof Chartist.Core
	   * @param {Number} axisLength The length of the Axis used for
	   * @param {Object} highLow An object containing a high and low property indicating the value range of the chart.
	   * @param {Number} scaleMinSpace The minimum projected length a step should result in
	   * @param {Boolean} onlyInteger
	   * @return {Object} All the values to set the bounds of the chart
	   */
	  Chartist.getBounds = function (axisLength, highLow, scaleMinSpace, onlyInteger) {
	    var i,
	      optimizationCounter = 0,
	      newMin,
	      newMax,
	      bounds = {
	        high: highLow.high,
	        low: highLow.low
	      };

	    bounds.valueRange = bounds.high - bounds.low;
	    bounds.oom = Chartist.orderOfMagnitude(bounds.valueRange);
	    bounds.step = Math.pow(10, bounds.oom);
	    bounds.min = Math.floor(bounds.low / bounds.step) * bounds.step;
	    bounds.max = Math.ceil(bounds.high / bounds.step) * bounds.step;
	    bounds.range = bounds.max - bounds.min;
	    bounds.numberOfSteps = Math.round(bounds.range / bounds.step);

	    // Optimize scale step by checking if subdivision is possible based on horizontalGridMinSpace
	    // If we are already below the scaleMinSpace value we will scale up
	    var length = Chartist.projectLength(axisLength, bounds.step, bounds);
	    var scaleUp = length < scaleMinSpace;
	    var smallestFactor = onlyInteger ? Chartist.rho(bounds.range) : 0;

	    // First check if we should only use integer steps and if step 1 is still larger than scaleMinSpace so we can use 1
	    if(onlyInteger && Chartist.projectLength(axisLength, 1, bounds) >= scaleMinSpace) {
	      bounds.step = 1;
	    } else if(onlyInteger && smallestFactor < bounds.step && Chartist.projectLength(axisLength, smallestFactor, bounds) >= scaleMinSpace) {
	      // If step 1 was too small, we can try the smallest factor of range
	      // If the smallest factor is smaller than the current bounds.step and the projected length of smallest factor
	      // is larger than the scaleMinSpace we should go for it.
	      bounds.step = smallestFactor;
	    } else {
	      // Trying to divide or multiply by 2 and find the best step value
	      while (true) {
	        if (scaleUp && Chartist.projectLength(axisLength, bounds.step, bounds) <= scaleMinSpace) {
	          bounds.step *= 2;
	        } else if (!scaleUp && Chartist.projectLength(axisLength, bounds.step / 2, bounds) >= scaleMinSpace) {
	          bounds.step /= 2;
	          if(onlyInteger && bounds.step % 1 !== 0) {
	            bounds.step *= 2;
	            break;
	          }
	        } else {
	          break;
	        }

	        if(optimizationCounter++ > 1000) {
	          throw new Error('Exceeded maximum number of iterations while optimizing scale step!');
	        }
	      }
	    }

	    var EPSILON = 2.221E-16;
	    bounds.step = Math.max(bounds.step, EPSILON);
	    function safeIncrement(value, increment) {
	      // If increment is too small use *= (1+EPSILON) as a simple nextafter
	      if (value === (value += increment)) {
	      	value *= (1 + (increment > 0 ? EPSILON : -EPSILON));
	      }
	      return value;
	    }

	    // Narrow min and max based on new step
	    newMin = bounds.min;
	    newMax = bounds.max;
	    while (newMin + bounds.step <= bounds.low) {
	    	newMin = safeIncrement(newMin, bounds.step);
	    }
	    while (newMax - bounds.step >= bounds.high) {
	    	newMax = safeIncrement(newMax, -bounds.step);
	    }
	    bounds.min = newMin;
	    bounds.max = newMax;
	    bounds.range = bounds.max - bounds.min;

	    var values = [];
	    for (i = bounds.min; i <= bounds.max; i = safeIncrement(i, bounds.step)) {
	      var value = Chartist.roundWithPrecision(i);
	      if (value !== values[values.length - 1]) {
	        values.push(value);
	      }
	    }
	    bounds.values = values;
	    return bounds;
	  };

	  /**
	   * Calculate cartesian coordinates of polar coordinates
	   *
	   * @memberof Chartist.Core
	   * @param {Number} centerX X-axis coordinates of center point of circle segment
	   * @param {Number} centerY X-axis coordinates of center point of circle segment
	   * @param {Number} radius Radius of circle segment
	   * @param {Number} angleInDegrees Angle of circle segment in degrees
	   * @return {{x:Number, y:Number}} Coordinates of point on circumference
	   */
	  Chartist.polarToCartesian = function (centerX, centerY, radius, angleInDegrees) {
	    var angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;

	    return {
	      x: centerX + (radius * Math.cos(angleInRadians)),
	      y: centerY + (radius * Math.sin(angleInRadians))
	    };
	  };

	  /**
	   * Initialize chart drawing rectangle (area where chart is drawn) x1,y1 = bottom left / x2,y2 = top right
	   *
	   * @memberof Chartist.Core
	   * @param {Object} svg The svg element for the chart
	   * @param {Object} options The Object that contains all the optional values for the chart
	   * @param {Number} [fallbackPadding] The fallback padding if partial padding objects are used
	   * @return {Object} The chart rectangles coordinates inside the svg element plus the rectangles measurements
	   */
	  Chartist.createChartRect = function (svg, options, fallbackPadding) {
	    var hasAxis = !!(options.axisX || options.axisY);
	    var yAxisOffset = hasAxis ? options.axisY.offset : 0;
	    var xAxisOffset = hasAxis ? options.axisX.offset : 0;
	    // If width or height results in invalid value (including 0) we fallback to the unitless settings or even 0
	    var width = svg.width() || Chartist.quantity(options.width).value || 0;
	    var height = svg.height() || Chartist.quantity(options.height).value || 0;
	    var normalizedPadding = Chartist.normalizePadding(options.chartPadding, fallbackPadding);

	    // If settings were to small to cope with offset (legacy) and padding, we'll adjust
	    width = Math.max(width, yAxisOffset + normalizedPadding.left + normalizedPadding.right);
	    height = Math.max(height, xAxisOffset + normalizedPadding.top + normalizedPadding.bottom);

	    var chartRect = {
	      padding: normalizedPadding,
	      width: function () {
	        return this.x2 - this.x1;
	      },
	      height: function () {
	        return this.y1 - this.y2;
	      }
	    };

	    if(hasAxis) {
	      if (options.axisX.position === 'start') {
	        chartRect.y2 = normalizedPadding.top + xAxisOffset;
	        chartRect.y1 = Math.max(height - normalizedPadding.bottom, chartRect.y2 + 1);
	      } else {
	        chartRect.y2 = normalizedPadding.top;
	        chartRect.y1 = Math.max(height - normalizedPadding.bottom - xAxisOffset, chartRect.y2 + 1);
	      }

	      if (options.axisY.position === 'start') {
	        chartRect.x1 = normalizedPadding.left + yAxisOffset;
	        chartRect.x2 = Math.max(width - normalizedPadding.right, chartRect.x1 + 1);
	      } else {
	        chartRect.x1 = normalizedPadding.left;
	        chartRect.x2 = Math.max(width - normalizedPadding.right - yAxisOffset, chartRect.x1 + 1);
	      }
	    } else {
	      chartRect.x1 = normalizedPadding.left;
	      chartRect.x2 = Math.max(width - normalizedPadding.right, chartRect.x1 + 1);
	      chartRect.y2 = normalizedPadding.top;
	      chartRect.y1 = Math.max(height - normalizedPadding.bottom, chartRect.y2 + 1);
	    }

	    return chartRect;
	  };

	  /**
	   * Creates a grid line based on a projected value.
	   *
	   * @memberof Chartist.Core
	   * @param position
	   * @param index
	   * @param axis
	   * @param offset
	   * @param length
	   * @param group
	   * @param classes
	   * @param eventEmitter
	   */
	  Chartist.createGrid = function(position, index, axis, offset, length, group, classes, eventEmitter) {
	    var positionalData = {};
	    positionalData[axis.units.pos + '1'] = position;
	    positionalData[axis.units.pos + '2'] = position;
	    positionalData[axis.counterUnits.pos + '1'] = offset;
	    positionalData[axis.counterUnits.pos + '2'] = offset + length;

	    var gridElement = group.elem('line', positionalData, classes.join(' '));

	    // Event for grid draw
	    eventEmitter.emit('draw',
	      Chartist.extend({
	        type: 'grid',
	        axis: axis,
	        index: index,
	        group: group,
	        element: gridElement
	      }, positionalData)
	    );
	  };

	  /**
	   * Creates a grid background rect and emits the draw event.
	   *
	   * @memberof Chartist.Core
	   * @param gridGroup
	   * @param chartRect
	   * @param className
	   * @param eventEmitter
	   */
	  Chartist.createGridBackground = function (gridGroup, chartRect, className, eventEmitter) {
	    var gridBackground = gridGroup.elem('rect', {
	        x: chartRect.x1,
	        y: chartRect.y2,
	        width: chartRect.width(),
	        height: chartRect.height(),
	      }, className, true);

	      // Event for grid background draw
	      eventEmitter.emit('draw', {
	        type: 'gridBackground',
	        group: gridGroup,
	        element: gridBackground
	      });
	  };

	  /**
	   * Creates a label based on a projected value and an axis.
	   *
	   * @memberof Chartist.Core
	   * @param position
	   * @param length
	   * @param index
	   * @param labels
	   * @param axis
	   * @param axisOffset
	   * @param labelOffset
	   * @param group
	   * @param classes
	   * @param useForeignObject
	   * @param eventEmitter
	   */
	  Chartist.createLabel = function(position, length, index, labels, axis, axisOffset, labelOffset, group, classes, useForeignObject, eventEmitter) {
	    var labelElement;
	    var positionalData = {};

	    positionalData[axis.units.pos] = position + labelOffset[axis.units.pos];
	    positionalData[axis.counterUnits.pos] = labelOffset[axis.counterUnits.pos];
	    positionalData[axis.units.len] = length;
	    positionalData[axis.counterUnits.len] = Math.max(0, axisOffset - 10);

	    if(useForeignObject) {
	      // We need to set width and height explicitly to px as span will not expand with width and height being
	      // 100% in all browsers
	      var content = document.createElement('span');
	      content.className = classes.join(' ');
	      content.setAttribute('xmlns', Chartist.namespaces.xhtml);
	      content.innerText = labels[index];
	      content.style[axis.units.len] = Math.round(positionalData[axis.units.len]) + 'px';
	      content.style[axis.counterUnits.len] = Math.round(positionalData[axis.counterUnits.len]) + 'px';

	      labelElement = group.foreignObject(content, Chartist.extend({
	        style: 'overflow: visible;'
	      }, positionalData));
	    } else {
	      labelElement = group.elem('text', positionalData, classes.join(' ')).text(labels[index]);
	    }

	    eventEmitter.emit('draw', Chartist.extend({
	      type: 'label',
	      axis: axis,
	      index: index,
	      group: group,
	      element: labelElement,
	      text: labels[index]
	    }, positionalData));
	  };

	  /**
	   * Helper to read series specific options from options object. It automatically falls back to the global option if
	   * there is no option in the series options.
	   *
	   * @param {Object} series Series object
	   * @param {Object} options Chartist options object
	   * @param {string} key The options key that should be used to obtain the options
	   * @returns {*}
	   */
	  Chartist.getSeriesOption = function(series, options, key) {
	    if(series.name && options.series && options.series[series.name]) {
	      var seriesOptions = options.series[series.name];
	      return seriesOptions.hasOwnProperty(key) ? seriesOptions[key] : options[key];
	    } else {
	      return options[key];
	    }
	  };

	  /**
	   * Provides options handling functionality with callback for options changes triggered by responsive options and media query matches
	   *
	   * @memberof Chartist.Core
	   * @param {Object} options Options set by user
	   * @param {Array} responsiveOptions Optional functions to add responsive behavior to chart
	   * @param {Object} eventEmitter The event emitter that will be used to emit the options changed events
	   * @return {Object} The consolidated options object from the defaults, base and matching responsive options
	   */
	  Chartist.optionsProvider = function (options, responsiveOptions, eventEmitter) {
	    var baseOptions = Chartist.extend({}, options),
	      currentOptions,
	      mediaQueryListeners = [],
	      i;

	    function updateCurrentOptions(mediaEvent) {
	      var previousOptions = currentOptions;
	      currentOptions = Chartist.extend({}, baseOptions);

	      if (responsiveOptions) {
	        for (i = 0; i < responsiveOptions.length; i++) {
	          var mql = window.matchMedia(responsiveOptions[i][0]);
	          if (mql.matches) {
	            currentOptions = Chartist.extend(currentOptions, responsiveOptions[i][1]);
	          }
	        }
	      }

	      if(eventEmitter && mediaEvent) {
	        eventEmitter.emit('optionsChanged', {
	          previousOptions: previousOptions,
	          currentOptions: currentOptions
	        });
	      }
	    }

	    function removeMediaQueryListeners() {
	      mediaQueryListeners.forEach(function(mql) {
	        mql.removeListener(updateCurrentOptions);
	      });
	    }

	    if (!window.matchMedia) {
	      throw 'window.matchMedia not found! Make sure you\'re using a polyfill.';
	    } else if (responsiveOptions) {

	      for (i = 0; i < responsiveOptions.length; i++) {
	        var mql = window.matchMedia(responsiveOptions[i][0]);
	        mql.addListener(updateCurrentOptions);
	        mediaQueryListeners.push(mql);
	      }
	    }
	    // Execute initially without an event argument so we get the correct options
	    updateCurrentOptions();

	    return {
	      removeMediaQueryListeners: removeMediaQueryListeners,
	      getCurrentOptions: function getCurrentOptions() {
	        return Chartist.extend({}, currentOptions);
	      }
	    };
	  };


	  /**
	   * Splits a list of coordinates and associated values into segments. Each returned segment contains a pathCoordinates
	   * valueData property describing the segment.
	   *
	   * With the default options, segments consist of contiguous sets of points that do not have an undefined value. Any
	   * points with undefined values are discarded.
	   *
	   * **Options**
	   * The following options are used to determine how segments are formed
	   * ```javascript
	   * var options = {
	   *   // If fillHoles is true, undefined values are simply discarded without creating a new segment. Assuming other options are default, this returns single segment.
	   *   fillHoles: false,
	   *   // If increasingX is true, the coordinates in all segments have strictly increasing x-values.
	   *   increasingX: false
	   * };
	   * ```
	   *
	   * @memberof Chartist.Core
	   * @param {Array} pathCoordinates List of point coordinates to be split in the form [x1, y1, x2, y2 ... xn, yn]
	   * @param {Array} values List of associated point values in the form [v1, v2 .. vn]
	   * @param {Object} options Options set by user
	   * @return {Array} List of segments, each containing a pathCoordinates and valueData property.
	   */
	  Chartist.splitIntoSegments = function(pathCoordinates, valueData, options) {
	    var defaultOptions = {
	      increasingX: false,
	      fillHoles: false
	    };

	    options = Chartist.extend({}, defaultOptions, options);

	    var segments = [];
	    var hole = true;

	    for(var i = 0; i < pathCoordinates.length; i += 2) {
	      // If this value is a "hole" we set the hole flag
	      if(Chartist.getMultiValue(valueData[i / 2].value) === undefined) {
	      // if(valueData[i / 2].value === undefined) {
	        if(!options.fillHoles) {
	          hole = true;
	        }
	      } else {
	        if(options.increasingX && i >= 2 && pathCoordinates[i] <= pathCoordinates[i-2]) {
	          // X is not increasing, so we need to make sure we start a new segment
	          hole = true;
	        }


	        // If it's a valid value we need to check if we're coming out of a hole and create a new empty segment
	        if(hole) {
	          segments.push({
	            pathCoordinates: [],
	            valueData: []
	          });
	          // As we have a valid value now, we are not in a "hole" anymore
	          hole = false;
	        }

	        // Add to the segment pathCoordinates and valueData
	        segments[segments.length - 1].pathCoordinates.push(pathCoordinates[i], pathCoordinates[i + 1]);
	        segments[segments.length - 1].valueData.push(valueData[i / 2]);
	      }
	    }

	    return segments;
	  };
	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist) {

	  Chartist.Interpolation = {};

	  /**
	   * This interpolation function does not smooth the path and the result is only containing lines and no curves.
	   *
	   * @example
	   * var chart = new Chartist.Line('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5],
	   *   series: [[1, 2, 8, 1, 7]]
	   * }, {
	   *   lineSmooth: Chartist.Interpolation.none({
	   *     fillHoles: false
	   *   })
	   * });
	   *
	   *
	   * @memberof Chartist.Interpolation
	   * @return {Function}
	   */
	  Chartist.Interpolation.none = function(options) {
	    var defaultOptions = {
	      fillHoles: false
	    };
	    options = Chartist.extend({}, defaultOptions, options);
	    return function none(pathCoordinates, valueData) {
	      var path = new Chartist.Svg.Path();
	      var hole = true;

	      for(var i = 0; i < pathCoordinates.length; i += 2) {
	        var currX = pathCoordinates[i];
	        var currY = pathCoordinates[i + 1];
	        var currData = valueData[i / 2];

	        if(Chartist.getMultiValue(currData.value) !== undefined) {

	          if(hole) {
	            path.move(currX, currY, false, currData);
	          } else {
	            path.line(currX, currY, false, currData);
	          }

	          hole = false;
	        } else if(!options.fillHoles) {
	          hole = true;
	        }
	      }

	      return path;
	    };
	  };

	  /**
	   * Simple smoothing creates horizontal handles that are positioned with a fraction of the length between two data points. You can use the divisor option to specify the amount of smoothing.
	   *
	   * Simple smoothing can be used instead of `Chartist.Smoothing.cardinal` if you'd like to get rid of the artifacts it produces sometimes. Simple smoothing produces less flowing lines but is accurate by hitting the points and it also doesn't swing below or above the given data point.
	   *
	   * All smoothing functions within Chartist are factory functions that accept an options parameter. The simple interpolation function accepts one configuration parameter `divisor`, between 1 and ∞, which controls the smoothing characteristics.
	   *
	   * @example
	   * var chart = new Chartist.Line('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5],
	   *   series: [[1, 2, 8, 1, 7]]
	   * }, {
	   *   lineSmooth: Chartist.Interpolation.simple({
	   *     divisor: 2,
	   *     fillHoles: false
	   *   })
	   * });
	   *
	   *
	   * @memberof Chartist.Interpolation
	   * @param {Object} options The options of the simple interpolation factory function.
	   * @return {Function}
	   */
	  Chartist.Interpolation.simple = function(options) {
	    var defaultOptions = {
	      divisor: 2,
	      fillHoles: false
	    };
	    options = Chartist.extend({}, defaultOptions, options);

	    var d = 1 / Math.max(1, options.divisor);

	    return function simple(pathCoordinates, valueData) {
	      var path = new Chartist.Svg.Path();
	      var prevX, prevY, prevData;

	      for(var i = 0; i < pathCoordinates.length; i += 2) {
	        var currX = pathCoordinates[i];
	        var currY = pathCoordinates[i + 1];
	        var length = (currX - prevX) * d;
	        var currData = valueData[i / 2];

	        if(currData.value !== undefined) {

	          if(prevData === undefined) {
	            path.move(currX, currY, false, currData);
	          } else {
	            path.curve(
	              prevX + length,
	              prevY,
	              currX - length,
	              currY,
	              currX,
	              currY,
	              false,
	              currData
	            );
	          }

	          prevX = currX;
	          prevY = currY;
	          prevData = currData;
	        } else if(!options.fillHoles) {
	          prevX = currX = prevData = undefined;
	        }
	      }

	      return path;
	    };
	  };

	  /**
	   * Cardinal / Catmull-Rome spline interpolation is the default smoothing function in Chartist. It produces nice results where the splines will always meet the points. It produces some artifacts though when data values are increased or decreased rapidly. The line may not follow a very accurate path and if the line should be accurate this smoothing function does not produce the best results.
	   *
	   * Cardinal splines can only be created if there are more than two data points. If this is not the case this smoothing will fallback to `Chartist.Smoothing.none`.
	   *
	   * All smoothing functions within Chartist are factory functions that accept an options parameter. The cardinal interpolation function accepts one configuration parameter `tension`, between 0 and 1, which controls the smoothing intensity.
	   *
	   * @example
	   * var chart = new Chartist.Line('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5],
	   *   series: [[1, 2, 8, 1, 7]]
	   * }, {
	   *   lineSmooth: Chartist.Interpolation.cardinal({
	   *     tension: 1,
	   *     fillHoles: false
	   *   })
	   * });
	   *
	   * @memberof Chartist.Interpolation
	   * @param {Object} options The options of the cardinal factory function.
	   * @return {Function}
	   */
	  Chartist.Interpolation.cardinal = function(options) {
	    var defaultOptions = {
	      tension: 1,
	      fillHoles: false
	    };

	    options = Chartist.extend({}, defaultOptions, options);

	    var t = Math.min(1, Math.max(0, options.tension)),
	      c = 1 - t;

	    return function cardinal(pathCoordinates, valueData) {
	      // First we try to split the coordinates into segments
	      // This is necessary to treat "holes" in line charts
	      var segments = Chartist.splitIntoSegments(pathCoordinates, valueData, {
	        fillHoles: options.fillHoles
	      });

	      if(!segments.length) {
	        // If there were no segments return 'Chartist.Interpolation.none'
	        return Chartist.Interpolation.none()([]);
	      } else if(segments.length > 1) {
	        // If the split resulted in more that one segment we need to interpolate each segment individually and join them
	        // afterwards together into a single path.
	          var paths = [];
	        // For each segment we will recurse the cardinal function
	        segments.forEach(function(segment) {
	          paths.push(cardinal(segment.pathCoordinates, segment.valueData));
	        });
	        // Join the segment path data into a single path and return
	        return Chartist.Svg.Path.join(paths);
	      } else {
	        // If there was only one segment we can proceed regularly by using pathCoordinates and valueData from the first
	        // segment
	        pathCoordinates = segments[0].pathCoordinates;
	        valueData = segments[0].valueData;

	        // If less than two points we need to fallback to no smoothing
	        if(pathCoordinates.length <= 4) {
	          return Chartist.Interpolation.none()(pathCoordinates, valueData);
	        }

	        var path = new Chartist.Svg.Path().move(pathCoordinates[0], pathCoordinates[1], false, valueData[0]),
	          z;

	        for (var i = 0, iLen = pathCoordinates.length; iLen - 2 * !z > i; i += 2) {
	          var p = [
	            {x: +pathCoordinates[i - 2], y: +pathCoordinates[i - 1]},
	            {x: +pathCoordinates[i], y: +pathCoordinates[i + 1]},
	            {x: +pathCoordinates[i + 2], y: +pathCoordinates[i + 3]},
	            {x: +pathCoordinates[i + 4], y: +pathCoordinates[i + 5]}
	          ];
	          if (z) {
	            if (!i) {
	              p[0] = {x: +pathCoordinates[iLen - 2], y: +pathCoordinates[iLen - 1]};
	            } else if (iLen - 4 === i) {
	              p[3] = {x: +pathCoordinates[0], y: +pathCoordinates[1]};
	            } else if (iLen - 2 === i) {
	              p[2] = {x: +pathCoordinates[0], y: +pathCoordinates[1]};
	              p[3] = {x: +pathCoordinates[2], y: +pathCoordinates[3]};
	            }
	          } else {
	            if (iLen - 4 === i) {
	              p[3] = p[2];
	            } else if (!i) {
	              p[0] = {x: +pathCoordinates[i], y: +pathCoordinates[i + 1]};
	            }
	          }

	          path.curve(
	            (t * (-p[0].x + 6 * p[1].x + p[2].x) / 6) + (c * p[2].x),
	            (t * (-p[0].y + 6 * p[1].y + p[2].y) / 6) + (c * p[2].y),
	            (t * (p[1].x + 6 * p[2].x - p[3].x) / 6) + (c * p[2].x),
	            (t * (p[1].y + 6 * p[2].y - p[3].y) / 6) + (c * p[2].y),
	            p[2].x,
	            p[2].y,
	            false,
	            valueData[(i + 2) / 2]
	          );
	        }

	        return path;
	      }
	    };
	  };

	  /**
	   * Monotone Cubic spline interpolation produces a smooth curve which preserves monotonicity. Unlike cardinal splines, the curve will not extend beyond the range of y-values of the original data points.
	   *
	   * Monotone Cubic splines can only be created if there are more than two data points. If this is not the case this smoothing will fallback to `Chartist.Smoothing.none`.
	   *
	   * The x-values of subsequent points must be increasing to fit a Monotone Cubic spline. If this condition is not met for a pair of adjacent points, then there will be a break in the curve between those data points.
	   *
	   * All smoothing functions within Chartist are factory functions that accept an options parameter.
	   *
	   * @example
	   * var chart = new Chartist.Line('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5],
	   *   series: [[1, 2, 8, 1, 7]]
	   * }, {
	   *   lineSmooth: Chartist.Interpolation.monotoneCubic({
	   *     fillHoles: false
	   *   })
	   * });
	   *
	   * @memberof Chartist.Interpolation
	   * @param {Object} options The options of the monotoneCubic factory function.
	   * @return {Function}
	   */
	  Chartist.Interpolation.monotoneCubic = function(options) {
	    var defaultOptions = {
	      fillHoles: false
	    };

	    options = Chartist.extend({}, defaultOptions, options);

	    return function monotoneCubic(pathCoordinates, valueData) {
	      // First we try to split the coordinates into segments
	      // This is necessary to treat "holes" in line charts
	      var segments = Chartist.splitIntoSegments(pathCoordinates, valueData, {
	        fillHoles: options.fillHoles,
	        increasingX: true
	      });

	      if(!segments.length) {
	        // If there were no segments return 'Chartist.Interpolation.none'
	        return Chartist.Interpolation.none()([]);
	      } else if(segments.length > 1) {
	        // If the split resulted in more that one segment we need to interpolate each segment individually and join them
	        // afterwards together into a single path.
	          var paths = [];
	        // For each segment we will recurse the monotoneCubic fn function
	        segments.forEach(function(segment) {
	          paths.push(monotoneCubic(segment.pathCoordinates, segment.valueData));
	        });
	        // Join the segment path data into a single path and return
	        return Chartist.Svg.Path.join(paths);
	      } else {
	        // If there was only one segment we can proceed regularly by using pathCoordinates and valueData from the first
	        // segment
	        pathCoordinates = segments[0].pathCoordinates;
	        valueData = segments[0].valueData;

	        // If less than three points we need to fallback to no smoothing
	        if(pathCoordinates.length <= 4) {
	          return Chartist.Interpolation.none()(pathCoordinates, valueData);
	        }

	        var xs = [],
	          ys = [],
	          i,
	          n = pathCoordinates.length / 2,
	          ms = [],
	          ds = [], dys = [], dxs = [],
	          path;

	        // Populate x and y coordinates into separate arrays, for readability

	        for(i = 0; i < n; i++) {
	          xs[i] = pathCoordinates[i * 2];
	          ys[i] = pathCoordinates[i * 2 + 1];
	        }

	        // Calculate deltas and derivative

	        for(i = 0; i < n - 1; i++) {
	          dys[i] = ys[i + 1] - ys[i];
	          dxs[i] = xs[i + 1] - xs[i];
	          ds[i] = dys[i] / dxs[i];
	        }

	        // Determine desired slope (m) at each point using Fritsch-Carlson method
	        // See: http://math.stackexchange.com/questions/45218/implementation-of-monotone-cubic-interpolation

	        ms[0] = ds[0];
	        ms[n - 1] = ds[n - 2];

	        for(i = 1; i < n - 1; i++) {
	          if(ds[i] === 0 || ds[i - 1] === 0 || (ds[i - 1] > 0) !== (ds[i] > 0)) {
	            ms[i] = 0;
	          } else {
	            ms[i] = 3 * (dxs[i - 1] + dxs[i]) / (
	              (2 * dxs[i] + dxs[i - 1]) / ds[i - 1] +
	              (dxs[i] + 2 * dxs[i - 1]) / ds[i]);

	            if(!isFinite(ms[i])) {
	              ms[i] = 0;
	            }
	          }
	        }

	        // Now build a path from the slopes

	        path = new Chartist.Svg.Path().move(xs[0], ys[0], false, valueData[0]);

	        for(i = 0; i < n - 1; i++) {
	          path.curve(
	            // First control point
	            xs[i] + dxs[i] / 3,
	            ys[i] + ms[i] * dxs[i] / 3,
	            // Second control point
	            xs[i + 1] - dxs[i] / 3,
	            ys[i + 1] - ms[i + 1] * dxs[i] / 3,
	            // End point
	            xs[i + 1],
	            ys[i + 1],

	            false,
	            valueData[i + 1]
	          );
	        }

	        return path;
	      }
	    };
	  };

	  /**
	   * Step interpolation will cause the line chart to move in steps rather than diagonal or smoothed lines. This interpolation will create additional points that will also be drawn when the `showPoint` option is enabled.
	   *
	   * All smoothing functions within Chartist are factory functions that accept an options parameter. The step interpolation function accepts one configuration parameter `postpone`, that can be `true` or `false`. The default value is `true` and will cause the step to occur where the value actually changes. If a different behaviour is needed where the step is shifted to the left and happens before the actual value, this option can be set to `false`.
	   *
	   * @example
	   * var chart = new Chartist.Line('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5],
	   *   series: [[1, 2, 8, 1, 7]]
	   * }, {
	   *   lineSmooth: Chartist.Interpolation.step({
	   *     postpone: true,
	   *     fillHoles: false
	   *   })
	   * });
	   *
	   * @memberof Chartist.Interpolation
	   * @param options
	   * @returns {Function}
	   */
	  Chartist.Interpolation.step = function(options) {
	    var defaultOptions = {
	      postpone: true,
	      fillHoles: false
	    };

	    options = Chartist.extend({}, defaultOptions, options);

	    return function step(pathCoordinates, valueData) {
	      var path = new Chartist.Svg.Path();

	      var prevX, prevY, prevData;

	      for (var i = 0; i < pathCoordinates.length; i += 2) {
	        var currX = pathCoordinates[i];
	        var currY = pathCoordinates[i + 1];
	        var currData = valueData[i / 2];

	        // If the current point is also not a hole we can draw the step lines
	        if(currData.value !== undefined) {
	          if(prevData === undefined) {
	            path.move(currX, currY, false, currData);
	          } else {
	            if(options.postpone) {
	              // If postponed we should draw the step line with the value of the previous value
	              path.line(currX, prevY, false, prevData);
	            } else {
	              // If not postponed we should draw the step line with the value of the current value
	              path.line(prevX, currY, false, currData);
	            }
	            // Line to the actual point (this should only be a Y-Axis movement
	            path.line(currX, currY, false, currData);
	          }

	          prevX = currX;
	          prevY = currY;
	          prevData = currData;
	        } else if(!options.fillHoles) {
	          prevX = prevY = prevData = undefined;
	        }
	      }

	      return path;
	    };
	  };

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function (globalRoot, Chartist) {

	  Chartist.EventEmitter = function () {
	    var handlers = [];

	    /**
	     * Add an event handler for a specific event
	     *
	     * @memberof Chartist.Event
	     * @param {String} event The event name
	     * @param {Function} handler A event handler function
	     */
	    function addEventHandler(event, handler) {
	      handlers[event] = handlers[event] || [];
	      handlers[event].push(handler);
	    }

	    /**
	     * Remove an event handler of a specific event name or remove all event handlers for a specific event.
	     *
	     * @memberof Chartist.Event
	     * @param {String} event The event name where a specific or all handlers should be removed
	     * @param {Function} [handler] An optional event handler function. If specified only this specific handler will be removed and otherwise all handlers are removed.
	     */
	    function removeEventHandler(event, handler) {
	      // Only do something if there are event handlers with this name existing
	      if(handlers[event]) {
	        // If handler is set we will look for a specific handler and only remove this
	        if(handler) {
	          handlers[event].splice(handlers[event].indexOf(handler), 1);
	          if(handlers[event].length === 0) {
	            delete handlers[event];
	          }
	        } else {
	          // If no handler is specified we remove all handlers for this event
	          delete handlers[event];
	        }
	      }
	    }

	    /**
	     * Use this function to emit an event. All handlers that are listening for this event will be triggered with the data parameter.
	     *
	     * @memberof Chartist.Event
	     * @param {String} event The event name that should be triggered
	     * @param {*} data Arbitrary data that will be passed to the event handler callback functions
	     */
	    function emit(event, data) {
	      // Only do something if there are event handlers with this name existing
	      if(handlers[event]) {
	        handlers[event].forEach(function(handler) {
	          handler(data);
	        });
	      }

	      // Emit event to star event handlers
	      if(handlers['*']) {
	        handlers['*'].forEach(function(starHandler) {
	          starHandler(event, data);
	        });
	      }
	    }

	    return {
	      addEventHandler: addEventHandler,
	      removeEventHandler: removeEventHandler,
	      emit: emit
	    };
	  };

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist) {

	  function listToArray(list) {
	    var arr = [];
	    if (list.length) {
	      for (var i = 0; i < list.length; i++) {
	        arr.push(list[i]);
	      }
	    }
	    return arr;
	  }

	  /**
	   * Method to extend from current prototype.
	   *
	   * @memberof Chartist.Class
	   * @param {Object} properties The object that serves as definition for the prototype that gets created for the new class. This object should always contain a constructor property that is the desired constructor for the newly created class.
	   * @param {Object} [superProtoOverride] By default extens will use the current class prototype or Chartist.class. With this parameter you can specify any super prototype that will be used.
	   * @return {Function} Constructor function of the new class
	   *
	   * @example
	   * var Fruit = Class.extend({
	     * color: undefined,
	     *   sugar: undefined,
	     *
	     *   constructor: function(color, sugar) {
	     *     this.color = color;
	     *     this.sugar = sugar;
	     *   },
	     *
	     *   eat: function() {
	     *     this.sugar = 0;
	     *     return this;
	     *   }
	     * });
	   *
	   * var Banana = Fruit.extend({
	     *   length: undefined,
	     *
	     *   constructor: function(length, sugar) {
	     *     Banana.super.constructor.call(this, 'Yellow', sugar);
	     *     this.length = length;
	     *   }
	     * });
	   *
	   * var banana = new Banana(20, 40);
	   * console.log('banana instanceof Fruit', banana instanceof Fruit);
	   * console.log('Fruit is prototype of banana', Fruit.prototype.isPrototypeOf(banana));
	   * console.log('bananas prototype is Fruit', Object.getPrototypeOf(banana) === Fruit.prototype);
	   * console.log(banana.sugar);
	   * console.log(banana.eat().sugar);
	   * console.log(banana.color);
	   */
	  function extend(properties, superProtoOverride) {
	    var superProto = superProtoOverride || this.prototype || Chartist.Class;
	    var proto = Object.create(superProto);

	    Chartist.Class.cloneDefinitions(proto, properties);

	    var constr = function() {
	      var fn = proto.constructor || function () {},
	        instance;

	      // If this is linked to the Chartist namespace the constructor was not called with new
	      // To provide a fallback we will instantiate here and return the instance
	      instance = this === Chartist ? Object.create(proto) : this;
	      fn.apply(instance, Array.prototype.slice.call(arguments, 0));

	      // If this constructor was not called with new we need to return the instance
	      // This will not harm when the constructor has been called with new as the returned value is ignored
	      return instance;
	    };

	    constr.prototype = proto;
	    constr.super = superProto;
	    constr.extend = this.extend;

	    return constr;
	  }

	  // Variable argument list clones args > 0 into args[0] and retruns modified args[0]
	  function cloneDefinitions() {
	    var args = listToArray(arguments);
	    var target = args[0];

	    args.splice(1, args.length - 1).forEach(function (source) {
	      Object.getOwnPropertyNames(source).forEach(function (propName) {
	        // If this property already exist in target we delete it first
	        delete target[propName];
	        // Define the property with the descriptor from source
	        Object.defineProperty(target, propName,
	          Object.getOwnPropertyDescriptor(source, propName));
	      });
	    });

	    return target;
	  }

	  Chartist.Class = {
	    extend: extend,
	    cloneDefinitions: cloneDefinitions
	  };

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist) {

	  var window = globalRoot.window;

	  // TODO: Currently we need to re-draw the chart on window resize. This is usually very bad and will affect performance.
	  // This is done because we can't work with relative coordinates when drawing the chart because SVG Path does not
	  // work with relative positions yet. We need to check if we can do a viewBox hack to switch to percentage.
	  // See http://mozilla.6506.n7.nabble.com/Specyfing-paths-with-percentages-unit-td247474.html
	  // Update: can be done using the above method tested here: http://codepen.io/gionkunz/pen/KDvLj
	  // The problem is with the label offsets that can't be converted into percentage and affecting the chart container
	  /**
	   * Updates the chart which currently does a full reconstruction of the SVG DOM
	   *
	   * @param {Object} [data] Optional data you'd like to set for the chart before it will update. If not specified the update method will use the data that is already configured with the chart.
	   * @param {Object} [options] Optional options you'd like to add to the previous options for the chart before it will update. If not specified the update method will use the options that have been already configured with the chart.
	   * @param {Boolean} [override] If set to true, the passed options will be used to extend the options that have been configured already. Otherwise the chart default options will be used as the base
	   * @memberof Chartist.Base
	   */
	  function update(data, options, override) {
	    if(data) {
	      this.data = data || {};
	      this.data.labels = this.data.labels || [];
	      this.data.series = this.data.series || [];
	      // Event for data transformation that allows to manipulate the data before it gets rendered in the charts
	      this.eventEmitter.emit('data', {
	        type: 'update',
	        data: this.data
	      });
	    }

	    if(options) {
	      this.options = Chartist.extend({}, override ? this.options : this.defaultOptions, options);

	      // If chartist was not initialized yet, we just set the options and leave the rest to the initialization
	      // Otherwise we re-create the optionsProvider at this point
	      if(!this.initializeTimeoutId) {
	        this.optionsProvider.removeMediaQueryListeners();
	        this.optionsProvider = Chartist.optionsProvider(this.options, this.responsiveOptions, this.eventEmitter);
	      }
	    }

	    // Only re-created the chart if it has been initialized yet
	    if(!this.initializeTimeoutId) {
	      this.createChart(this.optionsProvider.getCurrentOptions());
	    }

	    // Return a reference to the chart object to chain up calls
	    return this;
	  }

	  /**
	   * This method can be called on the API object of each chart and will un-register all event listeners that were added to other components. This currently includes a window.resize listener as well as media query listeners if any responsive options have been provided. Use this function if you need to destroy and recreate Chartist charts dynamically.
	   *
	   * @memberof Chartist.Base
	   */
	  function detach() {
	    // Only detach if initialization already occurred on this chart. If this chart still hasn't initialized (therefore
	    // the initializationTimeoutId is still a valid timeout reference, we will clear the timeout
	    if(!this.initializeTimeoutId) {
	      window.removeEventListener('resize', this.resizeListener);
	      this.optionsProvider.removeMediaQueryListeners();
	    } else {
	      window.clearTimeout(this.initializeTimeoutId);
	    }

	    return this;
	  }

	  /**
	   * Use this function to register event handlers. The handler callbacks are synchronous and will run in the main thread rather than the event loop.
	   *
	   * @memberof Chartist.Base
	   * @param {String} event Name of the event. Check the examples for supported events.
	   * @param {Function} handler The handler function that will be called when an event with the given name was emitted. This function will receive a data argument which contains event data. See the example for more details.
	   */
	  function on(event, handler) {
	    this.eventEmitter.addEventHandler(event, handler);
	    return this;
	  }

	  /**
	   * Use this function to un-register event handlers. If the handler function parameter is omitted all handlers for the given event will be un-registered.
	   *
	   * @memberof Chartist.Base
	   * @param {String} event Name of the event for which a handler should be removed
	   * @param {Function} [handler] The handler function that that was previously used to register a new event handler. This handler will be removed from the event handler list. If this parameter is omitted then all event handlers for the given event are removed from the list.
	   */
	  function off(event, handler) {
	    this.eventEmitter.removeEventHandler(event, handler);
	    return this;
	  }

	  function initialize() {
	    // Add window resize listener that re-creates the chart
	    window.addEventListener('resize', this.resizeListener);

	    // Obtain current options based on matching media queries (if responsive options are given)
	    // This will also register a listener that is re-creating the chart based on media changes
	    this.optionsProvider = Chartist.optionsProvider(this.options, this.responsiveOptions, this.eventEmitter);
	    // Register options change listener that will trigger a chart update
	    this.eventEmitter.addEventHandler('optionsChanged', function() {
	      this.update();
	    }.bind(this));

	    // Before the first chart creation we need to register us with all plugins that are configured
	    // Initialize all relevant plugins with our chart object and the plugin options specified in the config
	    if(this.options.plugins) {
	      this.options.plugins.forEach(function(plugin) {
	        if(plugin instanceof Array) {
	          plugin[0](this, plugin[1]);
	        } else {
	          plugin(this);
	        }
	      }.bind(this));
	    }

	    // Event for data transformation that allows to manipulate the data before it gets rendered in the charts
	    this.eventEmitter.emit('data', {
	      type: 'initial',
	      data: this.data
	    });

	    // Create the first chart
	    this.createChart(this.optionsProvider.getCurrentOptions());

	    // As chart is initialized from the event loop now we can reset our timeout reference
	    // This is important if the chart gets initialized on the same element twice
	    this.initializeTimeoutId = undefined;
	  }

	  /**
	   * Constructor of chart base class.
	   *
	   * @param query
	   * @param data
	   * @param defaultOptions
	   * @param options
	   * @param responsiveOptions
	   * @constructor
	   */
	  function Base(query, data, defaultOptions, options, responsiveOptions) {
	    this.container = Chartist.querySelector(query);
	    this.data = data || {};
	    this.data.labels = this.data.labels || [];
	    this.data.series = this.data.series || [];
	    this.defaultOptions = defaultOptions;
	    this.options = options;
	    this.responsiveOptions = responsiveOptions;
	    this.eventEmitter = Chartist.EventEmitter();
	    this.supportsForeignObject = Chartist.Svg.isSupported('Extensibility');
	    this.supportsAnimations = Chartist.Svg.isSupported('AnimationEventsAttribute');
	    this.resizeListener = function resizeListener(){
	      this.update();
	    }.bind(this);

	    if(this.container) {
	      // If chartist was already initialized in this container we are detaching all event listeners first
	      if(this.container.__chartist__) {
	        this.container.__chartist__.detach();
	      }

	      this.container.__chartist__ = this;
	    }

	    // Using event loop for first draw to make it possible to register event listeners in the same call stack where
	    // the chart was created.
	    this.initializeTimeoutId = setTimeout(initialize.bind(this), 0);
	  }

	  // Creating the chart base class
	  Chartist.Base = Chartist.Class.extend({
	    constructor: Base,
	    optionsProvider: undefined,
	    container: undefined,
	    svg: undefined,
	    eventEmitter: undefined,
	    createChart: function() {
	      throw new Error('Base chart type can\'t be instantiated!');
	    },
	    update: update,
	    detach: detach,
	    on: on,
	    off: off,
	    version: Chartist.version,
	    supportsForeignObject: false
	  });

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist) {

	  var document = globalRoot.document;

	  /**
	   * Chartist.Svg creates a new SVG object wrapper with a starting element. You can use the wrapper to fluently create sub-elements and modify them.
	   *
	   * @memberof Chartist.Svg
	   * @constructor
	   * @param {String|Element} name The name of the SVG element to create or an SVG dom element which should be wrapped into Chartist.Svg
	   * @param {Object} attributes An object with properties that will be added as attributes to the SVG element that is created. Attributes with undefined values will not be added.
	   * @param {String} className This class or class list will be added to the SVG element
	   * @param {Object} parent The parent SVG wrapper object where this newly created wrapper and it's element will be attached to as child
	   * @param {Boolean} insertFirst If this param is set to true in conjunction with a parent element the newly created element will be added as first child element in the parent element
	   */
	  function Svg(name, attributes, className, parent, insertFirst) {
	    // If Svg is getting called with an SVG element we just return the wrapper
	    if(name instanceof Element) {
	      this._node = name;
	    } else {
	      this._node = document.createElementNS(Chartist.namespaces.svg, name);

	      // If this is an SVG element created then custom namespace
	      if(name === 'svg') {
	        this.attr({
	          'xmlns:ct': Chartist.namespaces.ct
	        });
	      }
	    }

	    if(attributes) {
	      this.attr(attributes);
	    }

	    if(className) {
	      this.addClass(className);
	    }

	    if(parent) {
	      if (insertFirst && parent._node.firstChild) {
	        parent._node.insertBefore(this._node, parent._node.firstChild);
	      } else {
	        parent._node.appendChild(this._node);
	      }
	    }
	  }

	  /**
	   * Set attributes on the current SVG element of the wrapper you're currently working on.
	   *
	   * @memberof Chartist.Svg
	   * @param {Object|String} attributes An object with properties that will be added as attributes to the SVG element that is created. Attributes with undefined values will not be added. If this parameter is a String then the function is used as a getter and will return the attribute value.
	   * @param {String} [ns] If specified, the attribute will be obtained using getAttributeNs. In order to write namepsaced attributes you can use the namespace:attribute notation within the attributes object.
	   * @return {Object|String} The current wrapper object will be returned so it can be used for chaining or the attribute value if used as getter function.
	   */
	  function attr(attributes, ns) {
	    if(typeof attributes === 'string') {
	      if(ns) {
	        return this._node.getAttributeNS(ns, attributes);
	      } else {
	        return this._node.getAttribute(attributes);
	      }
	    }

	    Object.keys(attributes).forEach(function(key) {
	      // If the attribute value is undefined we can skip this one
	      if(attributes[key] === undefined) {
	        return;
	      }

	      if (key.indexOf(':') !== -1) {
	        var namespacedAttribute = key.split(':');
	        this._node.setAttributeNS(Chartist.namespaces[namespacedAttribute[0]], key, attributes[key]);
	      } else {
	        this._node.setAttribute(key, attributes[key]);
	      }
	    }.bind(this));

	    return this;
	  }

	  /**
	   * Create a new SVG element whose wrapper object will be selected for further operations. This way you can also create nested groups easily.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} name The name of the SVG element that should be created as child element of the currently selected element wrapper
	   * @param {Object} [attributes] An object with properties that will be added as attributes to the SVG element that is created. Attributes with undefined values will not be added.
	   * @param {String} [className] This class or class list will be added to the SVG element
	   * @param {Boolean} [insertFirst] If this param is set to true in conjunction with a parent element the newly created element will be added as first child element in the parent element
	   * @return {Chartist.Svg} Returns a Chartist.Svg wrapper object that can be used to modify the containing SVG data
	   */
	  function elem(name, attributes, className, insertFirst) {
	    return new Chartist.Svg(name, attributes, className, this, insertFirst);
	  }

	  /**
	   * Returns the parent Chartist.SVG wrapper object
	   *
	   * @memberof Chartist.Svg
	   * @return {Chartist.Svg} Returns a Chartist.Svg wrapper around the parent node of the current node. If the parent node is not existing or it's not an SVG node then this function will return null.
	   */
	  function parent() {
	    return this._node.parentNode instanceof SVGElement ? new Chartist.Svg(this._node.parentNode) : null;
	  }

	  /**
	   * This method returns a Chartist.Svg wrapper around the root SVG element of the current tree.
	   *
	   * @memberof Chartist.Svg
	   * @return {Chartist.Svg} The root SVG element wrapped in a Chartist.Svg element
	   */
	  function root() {
	    var node = this._node;
	    while(node.nodeName !== 'svg') {
	      node = node.parentNode;
	    }
	    return new Chartist.Svg(node);
	  }

	  /**
	   * Find the first child SVG element of the current element that matches a CSS selector. The returned object is a Chartist.Svg wrapper.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} selector A CSS selector that is used to query for child SVG elements
	   * @return {Chartist.Svg} The SVG wrapper for the element found or null if no element was found
	   */
	  function querySelector(selector) {
	    var foundNode = this._node.querySelector(selector);
	    return foundNode ? new Chartist.Svg(foundNode) : null;
	  }

	  /**
	   * Find the all child SVG elements of the current element that match a CSS selector. The returned object is a Chartist.Svg.List wrapper.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} selector A CSS selector that is used to query for child SVG elements
	   * @return {Chartist.Svg.List} The SVG wrapper list for the element found or null if no element was found
	   */
	  function querySelectorAll(selector) {
	    var foundNodes = this._node.querySelectorAll(selector);
	    return foundNodes.length ? new Chartist.Svg.List(foundNodes) : null;
	  }

	  /**
	   * Returns the underlying SVG node for the current element.
	   *
	   * @memberof Chartist.Svg
	   * @returns {Node}
	   */
	  function getNode() {
	    return this._node;
	  }

	  /**
	   * This method creates a foreignObject (see https://developer.mozilla.org/en-US/docs/Web/SVG/Element/foreignObject) that allows to embed HTML content into a SVG graphic. With the help of foreignObjects you can enable the usage of regular HTML elements inside of SVG where they are subject for SVG positioning and transformation but the Browser will use the HTML rendering capabilities for the containing DOM.
	   *
	   * @memberof Chartist.Svg
	   * @param {Node|String} content The DOM Node, or HTML string that will be converted to a DOM Node, that is then placed into and wrapped by the foreignObject
	   * @param {String} [attributes] An object with properties that will be added as attributes to the foreignObject element that is created. Attributes with undefined values will not be added.
	   * @param {String} [className] This class or class list will be added to the SVG element
	   * @param {Boolean} [insertFirst] Specifies if the foreignObject should be inserted as first child
	   * @return {Chartist.Svg} New wrapper object that wraps the foreignObject element
	   */
	  function foreignObject(content, attributes, className, insertFirst) {
	    // If content is string then we convert it to DOM
	    // TODO: Handle case where content is not a string nor a DOM Node
	    if(typeof content === 'string') {
	      var container = document.createElement('div');
	      container.innerHTML = content;
	      content = container.firstChild;
	    }

	    // Adding namespace to content element
	    content.setAttribute('xmlns', Chartist.namespaces.xmlns);

	    // Creating the foreignObject without required extension attribute (as described here
	    // http://www.w3.org/TR/SVG/extend.html#ForeignObjectElement)
	    var fnObj = this.elem('foreignObject', attributes, className, insertFirst);

	    // Add content to foreignObjectElement
	    fnObj._node.appendChild(content);

	    return fnObj;
	  }

	  /**
	   * This method adds a new text element to the current Chartist.Svg wrapper.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} t The text that should be added to the text element that is created
	   * @return {Chartist.Svg} The same wrapper object that was used to add the newly created element
	   */
	  function text(t) {
	    this._node.appendChild(document.createTextNode(t));
	    return this;
	  }

	  /**
	   * This method will clear all child nodes of the current wrapper object.
	   *
	   * @memberof Chartist.Svg
	   * @return {Chartist.Svg} The same wrapper object that got emptied
	   */
	  function empty() {
	    while (this._node.firstChild) {
	      this._node.removeChild(this._node.firstChild);
	    }

	    return this;
	  }

	  /**
	   * This method will cause the current wrapper to remove itself from its parent wrapper. Use this method if you'd like to get rid of an element in a given DOM structure.
	   *
	   * @memberof Chartist.Svg
	   * @return {Chartist.Svg} The parent wrapper object of the element that got removed
	   */
	  function remove() {
	    this._node.parentNode.removeChild(this._node);
	    return this.parent();
	  }

	  /**
	   * This method will replace the element with a new element that can be created outside of the current DOM.
	   *
	   * @memberof Chartist.Svg
	   * @param {Chartist.Svg} newElement The new Chartist.Svg object that will be used to replace the current wrapper object
	   * @return {Chartist.Svg} The wrapper of the new element
	   */
	  function replace(newElement) {
	    this._node.parentNode.replaceChild(newElement._node, this._node);
	    return newElement;
	  }

	  /**
	   * This method will append an element to the current element as a child.
	   *
	   * @memberof Chartist.Svg
	   * @param {Chartist.Svg} element The Chartist.Svg element that should be added as a child
	   * @param {Boolean} [insertFirst] Specifies if the element should be inserted as first child
	   * @return {Chartist.Svg} The wrapper of the appended object
	   */
	  function append(element, insertFirst) {
	    if(insertFirst && this._node.firstChild) {
	      this._node.insertBefore(element._node, this._node.firstChild);
	    } else {
	      this._node.appendChild(element._node);
	    }

	    return this;
	  }

	  /**
	   * Returns an array of class names that are attached to the current wrapper element. This method can not be chained further.
	   *
	   * @memberof Chartist.Svg
	   * @return {Array} A list of classes or an empty array if there are no classes on the current element
	   */
	  function classes() {
	    return this._node.getAttribute('class') ? this._node.getAttribute('class').trim().split(/\s+/) : [];
	  }

	  /**
	   * Adds one or a space separated list of classes to the current element and ensures the classes are only existing once.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} names A white space separated list of class names
	   * @return {Chartist.Svg} The wrapper of the current element
	   */
	  function addClass(names) {
	    this._node.setAttribute('class',
	      this.classes(this._node)
	        .concat(names.trim().split(/\s+/))
	        .filter(function(elem, pos, self) {
	          return self.indexOf(elem) === pos;
	        }).join(' ')
	    );

	    return this;
	  }

	  /**
	   * Removes one or a space separated list of classes from the current element.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} names A white space separated list of class names
	   * @return {Chartist.Svg} The wrapper of the current element
	   */
	  function removeClass(names) {
	    var removedClasses = names.trim().split(/\s+/);

	    this._node.setAttribute('class', this.classes(this._node).filter(function(name) {
	      return removedClasses.indexOf(name) === -1;
	    }).join(' '));

	    return this;
	  }

	  /**
	   * Removes all classes from the current element.
	   *
	   * @memberof Chartist.Svg
	   * @return {Chartist.Svg} The wrapper of the current element
	   */
	  function removeAllClasses() {
	    this._node.setAttribute('class', '');

	    return this;
	  }

	  /**
	   * Get element height using `getBoundingClientRect`
	   *
	   * @memberof Chartist.Svg
	   * @return {Number} The elements height in pixels
	   */
	  function height() {
	    return this._node.getBoundingClientRect().height;
	  }

	  /**
	   * Get element width using `getBoundingClientRect`
	   *
	   * @memberof Chartist.Core
	   * @return {Number} The elements width in pixels
	   */
	  function width() {
	    return this._node.getBoundingClientRect().width;
	  }

	  /**
	   * The animate function lets you animate the current element with SMIL animations. You can add animations for multiple attributes at the same time by using an animation definition object. This object should contain SMIL animation attributes. Please refer to http://www.w3.org/TR/SVG/animate.html for a detailed specification about the available animation attributes. Additionally an easing property can be passed in the animation definition object. This can be a string with a name of an easing function in `Chartist.Svg.Easing` or an array with four numbers specifying a cubic Bézier curve.
	   * **An animations object could look like this:**
	   * ```javascript
	   * element.animate({
	   *   opacity: {
	   *     dur: 1000,
	   *     from: 0,
	   *     to: 1
	   *   },
	   *   x1: {
	   *     dur: '1000ms',
	   *     from: 100,
	   *     to: 200,
	   *     easing: 'easeOutQuart'
	   *   },
	   *   y1: {
	   *     dur: '2s',
	   *     from: 0,
	   *     to: 100
	   *   }
	   * });
	   * ```
	   * **Automatic unit conversion**
	   * For the `dur` and the `begin` animate attribute you can also omit a unit by passing a number. The number will automatically be converted to milli seconds.
	   * **Guided mode**
	   * The default behavior of SMIL animations with offset using the `begin` attribute is that the attribute will keep it's original value until the animation starts. Mostly this behavior is not desired as you'd like to have your element attributes already initialized with the animation `from` value even before the animation starts. Also if you don't specify `fill="freeze"` on an animate element or if you delete the animation after it's done (which is done in guided mode) the attribute will switch back to the initial value. This behavior is also not desired when performing simple one-time animations. For one-time animations you'd want to trigger animations immediately instead of relative to the document begin time. That's why in guided mode Chartist.Svg will also use the `begin` property to schedule a timeout and manually start the animation after the timeout. If you're using multiple SMIL definition objects for an attribute (in an array), guided mode will be disabled for this attribute, even if you explicitly enabled it.
	   * If guided mode is enabled the following behavior is added:
	   * - Before the animation starts (even when delayed with `begin`) the animated attribute will be set already to the `from` value of the animation
	   * - `begin` is explicitly set to `indefinite` so it can be started manually without relying on document begin time (creation)
	   * - The animate element will be forced to use `fill="freeze"`
	   * - The animation will be triggered with `beginElement()` in a timeout where `begin` of the definition object is interpreted in milli seconds. If no `begin` was specified the timeout is triggered immediately.
	   * - After the animation the element attribute value will be set to the `to` value of the animation
	   * - The animate element is deleted from the DOM
	   *
	   * @memberof Chartist.Svg
	   * @param {Object} animations An animations object where the property keys are the attributes you'd like to animate. The properties should be objects again that contain the SMIL animation attributes (usually begin, dur, from, and to). The property begin and dur is auto converted (see Automatic unit conversion). You can also schedule multiple animations for the same attribute by passing an Array of SMIL definition objects. Attributes that contain an array of SMIL definition objects will not be executed in guided mode.
	   * @param {Boolean} guided Specify if guided mode should be activated for this animation (see Guided mode). If not otherwise specified, guided mode will be activated.
	   * @param {Object} eventEmitter If specified, this event emitter will be notified when an animation starts or ends.
	   * @return {Chartist.Svg} The current element where the animation was added
	   */
	  function animate(animations, guided, eventEmitter) {
	    if(guided === undefined) {
	      guided = true;
	    }

	    Object.keys(animations).forEach(function createAnimateForAttributes(attribute) {

	      function createAnimate(animationDefinition, guided) {
	        var attributeProperties = {},
	          animate,
	          timeout,
	          easing;

	        // Check if an easing is specified in the definition object and delete it from the object as it will not
	        // be part of the animate element attributes.
	        if(animationDefinition.easing) {
	          // If already an easing Bézier curve array we take it or we lookup a easing array in the Easing object
	          easing = animationDefinition.easing instanceof Array ?
	            animationDefinition.easing :
	            Chartist.Svg.Easing[animationDefinition.easing];
	          delete animationDefinition.easing;
	        }

	        // If numeric dur or begin was provided we assume milli seconds
	        animationDefinition.begin = Chartist.ensureUnit(animationDefinition.begin, 'ms');
	        animationDefinition.dur = Chartist.ensureUnit(animationDefinition.dur, 'ms');

	        if(easing) {
	          animationDefinition.calcMode = 'spline';
	          animationDefinition.keySplines = easing.join(' ');
	          animationDefinition.keyTimes = '0;1';
	        }

	        // Adding "fill: freeze" if we are in guided mode and set initial attribute values
	        if(guided) {
	          animationDefinition.fill = 'freeze';
	          // Animated property on our element should already be set to the animation from value in guided mode
	          attributeProperties[attribute] = animationDefinition.from;
	          this.attr(attributeProperties);

	          // In guided mode we also set begin to indefinite so we can trigger the start manually and put the begin
	          // which needs to be in ms aside
	          timeout = Chartist.quantity(animationDefinition.begin || 0).value;
	          animationDefinition.begin = 'indefinite';
	        }

	        animate = this.elem('animate', Chartist.extend({
	          attributeName: attribute
	        }, animationDefinition));

	        if(guided) {
	          // If guided we take the value that was put aside in timeout and trigger the animation manually with a timeout
	          setTimeout(function() {
	            // If beginElement fails we set the animated attribute to the end position and remove the animate element
	            // This happens if the SMIL ElementTimeControl interface is not supported or any other problems occured in
	            // the browser. (Currently FF 34 does not support animate elements in foreignObjects)
	            try {
	              animate._node.beginElement();
	            } catch(err) {
	              // Set animated attribute to current animated value
	              attributeProperties[attribute] = animationDefinition.to;
	              this.attr(attributeProperties);
	              // Remove the animate element as it's no longer required
	              animate.remove();
	            }
	          }.bind(this), timeout);
	        }

	        if(eventEmitter) {
	          animate._node.addEventListener('beginEvent', function handleBeginEvent() {
	            eventEmitter.emit('animationBegin', {
	              element: this,
	              animate: animate._node,
	              params: animationDefinition
	            });
	          }.bind(this));
	        }

	        animate._node.addEventListener('endEvent', function handleEndEvent() {
	          if(eventEmitter) {
	            eventEmitter.emit('animationEnd', {
	              element: this,
	              animate: animate._node,
	              params: animationDefinition
	            });
	          }

	          if(guided) {
	            // Set animated attribute to current animated value
	            attributeProperties[attribute] = animationDefinition.to;
	            this.attr(attributeProperties);
	            // Remove the animate element as it's no longer required
	            animate.remove();
	          }
	        }.bind(this));
	      }

	      // If current attribute is an array of definition objects we create an animate for each and disable guided mode
	      if(animations[attribute] instanceof Array) {
	        animations[attribute].forEach(function(animationDefinition) {
	          createAnimate.bind(this)(animationDefinition, false);
	        }.bind(this));
	      } else {
	        createAnimate.bind(this)(animations[attribute], guided);
	      }

	    }.bind(this));

	    return this;
	  }

	  Chartist.Svg = Chartist.Class.extend({
	    constructor: Svg,
	    attr: attr,
	    elem: elem,
	    parent: parent,
	    root: root,
	    querySelector: querySelector,
	    querySelectorAll: querySelectorAll,
	    getNode: getNode,
	    foreignObject: foreignObject,
	    text: text,
	    empty: empty,
	    remove: remove,
	    replace: replace,
	    append: append,
	    classes: classes,
	    addClass: addClass,
	    removeClass: removeClass,
	    removeAllClasses: removeAllClasses,
	    height: height,
	    width: width,
	    animate: animate
	  });

	  /**
	   * This method checks for support of a given SVG feature like Extensibility, SVG-animation or the like. Check http://www.w3.org/TR/SVG11/feature for a detailed list.
	   *
	   * @memberof Chartist.Svg
	   * @param {String} feature The SVG 1.1 feature that should be checked for support.
	   * @return {Boolean} True of false if the feature is supported or not
	   */
	  Chartist.Svg.isSupported = function(feature) {
	    return document.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#' + feature, '1.1');
	  };

	  /**
	   * This Object contains some standard easing cubic bezier curves. Then can be used with their name in the `Chartist.Svg.animate`. You can also extend the list and use your own name in the `animate` function. Click the show code button to see the available bezier functions.
	   *
	   * @memberof Chartist.Svg
	   */
	  var easingCubicBeziers = {
	    easeInSine: [0.47, 0, 0.745, 0.715],
	    easeOutSine: [0.39, 0.575, 0.565, 1],
	    easeInOutSine: [0.445, 0.05, 0.55, 0.95],
	    easeInQuad: [0.55, 0.085, 0.68, 0.53],
	    easeOutQuad: [0.25, 0.46, 0.45, 0.94],
	    easeInOutQuad: [0.455, 0.03, 0.515, 0.955],
	    easeInCubic: [0.55, 0.055, 0.675, 0.19],
	    easeOutCubic: [0.215, 0.61, 0.355, 1],
	    easeInOutCubic: [0.645, 0.045, 0.355, 1],
	    easeInQuart: [0.895, 0.03, 0.685, 0.22],
	    easeOutQuart: [0.165, 0.84, 0.44, 1],
	    easeInOutQuart: [0.77, 0, 0.175, 1],
	    easeInQuint: [0.755, 0.05, 0.855, 0.06],
	    easeOutQuint: [0.23, 1, 0.32, 1],
	    easeInOutQuint: [0.86, 0, 0.07, 1],
	    easeInExpo: [0.95, 0.05, 0.795, 0.035],
	    easeOutExpo: [0.19, 1, 0.22, 1],
	    easeInOutExpo: [1, 0, 0, 1],
	    easeInCirc: [0.6, 0.04, 0.98, 0.335],
	    easeOutCirc: [0.075, 0.82, 0.165, 1],
	    easeInOutCirc: [0.785, 0.135, 0.15, 0.86],
	    easeInBack: [0.6, -0.28, 0.735, 0.045],
	    easeOutBack: [0.175, 0.885, 0.32, 1.275],
	    easeInOutBack: [0.68, -0.55, 0.265, 1.55]
	  };

	  Chartist.Svg.Easing = easingCubicBeziers;

	  /**
	   * This helper class is to wrap multiple `Chartist.Svg` elements into a list where you can call the `Chartist.Svg` functions on all elements in the list with one call. This is helpful when you'd like to perform calls with `Chartist.Svg` on multiple elements.
	   * An instance of this class is also returned by `Chartist.Svg.querySelectorAll`.
	   *
	   * @memberof Chartist.Svg
	   * @param {Array<Node>|NodeList} nodeList An Array of SVG DOM nodes or a SVG DOM NodeList (as returned by document.querySelectorAll)
	   * @constructor
	   */
	  function SvgList(nodeList) {
	    var list = this;

	    this.svgElements = [];
	    for(var i = 0; i < nodeList.length; i++) {
	      this.svgElements.push(new Chartist.Svg(nodeList[i]));
	    }

	    // Add delegation methods for Chartist.Svg
	    Object.keys(Chartist.Svg.prototype).filter(function(prototypeProperty) {
	      return ['constructor',
	          'parent',
	          'querySelector',
	          'querySelectorAll',
	          'replace',
	          'append',
	          'classes',
	          'height',
	          'width'].indexOf(prototypeProperty) === -1;
	    }).forEach(function(prototypeProperty) {
	      list[prototypeProperty] = function() {
	        var args = Array.prototype.slice.call(arguments, 0);
	        list.svgElements.forEach(function(element) {
	          Chartist.Svg.prototype[prototypeProperty].apply(element, args);
	        });
	        return list;
	      };
	    });
	  }

	  Chartist.Svg.List = Chartist.Class.extend({
	    constructor: SvgList
	  });
	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist) {

	  /**
	   * Contains the descriptors of supported element types in a SVG path. Currently only move, line and curve are supported.
	   *
	   * @memberof Chartist.Svg.Path
	   * @type {Object}
	   */
	  var elementDescriptions = {
	    m: ['x', 'y'],
	    l: ['x', 'y'],
	    c: ['x1', 'y1', 'x2', 'y2', 'x', 'y'],
	    a: ['rx', 'ry', 'xAr', 'lAf', 'sf', 'x', 'y']
	  };

	  /**
	   * Default options for newly created SVG path objects.
	   *
	   * @memberof Chartist.Svg.Path
	   * @type {Object}
	   */
	  var defaultOptions = {
	    // The accuracy in digit count after the decimal point. This will be used to round numbers in the SVG path. If this option is set to false then no rounding will be performed.
	    accuracy: 3
	  };

	  function element(command, params, pathElements, pos, relative, data) {
	    var pathElement = Chartist.extend({
	      command: relative ? command.toLowerCase() : command.toUpperCase()
	    }, params, data ? { data: data } : {} );

	    pathElements.splice(pos, 0, pathElement);
	  }

	  function forEachParam(pathElements, cb) {
	    pathElements.forEach(function(pathElement, pathElementIndex) {
	      elementDescriptions[pathElement.command.toLowerCase()].forEach(function(paramName, paramIndex) {
	        cb(pathElement, paramName, pathElementIndex, paramIndex, pathElements);
	      });
	    });
	  }

	  /**
	   * Used to construct a new path object.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Boolean} close If set to true then this path will be closed when stringified (with a Z at the end)
	   * @param {Object} options Options object that overrides the default objects. See default options for more details.
	   * @constructor
	   */
	  function SvgPath(close, options) {
	    this.pathElements = [];
	    this.pos = 0;
	    this.close = close;
	    this.options = Chartist.extend({}, defaultOptions, options);
	  }

	  /**
	   * Gets or sets the current position (cursor) inside of the path. You can move around the cursor freely but limited to 0 or the count of existing elements. All modifications with element functions will insert new elements at the position of this cursor.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} [pos] If a number is passed then the cursor is set to this position in the path element array.
	   * @return {Chartist.Svg.Path|Number} If the position parameter was passed then the return value will be the path object for easy call chaining. If no position parameter was passed then the current position is returned.
	   */
	  function position(pos) {
	    if(pos !== undefined) {
	      this.pos = Math.max(0, Math.min(this.pathElements.length, pos));
	      return this;
	    } else {
	      return this.pos;
	    }
	  }

	  /**
	   * Removes elements from the path starting at the current position.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} count Number of path elements that should be removed from the current position.
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function remove(count) {
	    this.pathElements.splice(this.pos, count);
	    return this;
	  }

	  /**
	   * Use this function to add a new move SVG path element.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} x The x coordinate for the move element.
	   * @param {Number} y The y coordinate for the move element.
	   * @param {Boolean} [relative] If set to true the move element will be created with relative coordinates (lowercase letter)
	   * @param {*} [data] Any data that should be stored with the element object that will be accessible in pathElement
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function move(x, y, relative, data) {
	    element('M', {
	      x: +x,
	      y: +y
	    }, this.pathElements, this.pos++, relative, data);
	    return this;
	  }

	  /**
	   * Use this function to add a new line SVG path element.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} x The x coordinate for the line element.
	   * @param {Number} y The y coordinate for the line element.
	   * @param {Boolean} [relative] If set to true the line element will be created with relative coordinates (lowercase letter)
	   * @param {*} [data] Any data that should be stored with the element object that will be accessible in pathElement
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function line(x, y, relative, data) {
	    element('L', {
	      x: +x,
	      y: +y
	    }, this.pathElements, this.pos++, relative, data);
	    return this;
	  }

	  /**
	   * Use this function to add a new curve SVG path element.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} x1 The x coordinate for the first control point of the bezier curve.
	   * @param {Number} y1 The y coordinate for the first control point of the bezier curve.
	   * @param {Number} x2 The x coordinate for the second control point of the bezier curve.
	   * @param {Number} y2 The y coordinate for the second control point of the bezier curve.
	   * @param {Number} x The x coordinate for the target point of the curve element.
	   * @param {Number} y The y coordinate for the target point of the curve element.
	   * @param {Boolean} [relative] If set to true the curve element will be created with relative coordinates (lowercase letter)
	   * @param {*} [data] Any data that should be stored with the element object that will be accessible in pathElement
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function curve(x1, y1, x2, y2, x, y, relative, data) {
	    element('C', {
	      x1: +x1,
	      y1: +y1,
	      x2: +x2,
	      y2: +y2,
	      x: +x,
	      y: +y
	    }, this.pathElements, this.pos++, relative, data);
	    return this;
	  }

	  /**
	   * Use this function to add a new non-bezier curve SVG path element.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} rx The radius to be used for the x-axis of the arc.
	   * @param {Number} ry The radius to be used for the y-axis of the arc.
	   * @param {Number} xAr Defines the orientation of the arc
	   * @param {Number} lAf Large arc flag
	   * @param {Number} sf Sweep flag
	   * @param {Number} x The x coordinate for the target point of the curve element.
	   * @param {Number} y The y coordinate for the target point of the curve element.
	   * @param {Boolean} [relative] If set to true the curve element will be created with relative coordinates (lowercase letter)
	   * @param {*} [data] Any data that should be stored with the element object that will be accessible in pathElement
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function arc(rx, ry, xAr, lAf, sf, x, y, relative, data) {
	    element('A', {
	      rx: +rx,
	      ry: +ry,
	      xAr: +xAr,
	      lAf: +lAf,
	      sf: +sf,
	      x: +x,
	      y: +y
	    }, this.pathElements, this.pos++, relative, data);
	    return this;
	  }

	  /**
	   * Parses an SVG path seen in the d attribute of path elements, and inserts the parsed elements into the existing path object at the current cursor position. Any closing path indicators (Z at the end of the path) will be ignored by the parser as this is provided by the close option in the options of the path object.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {String} path Any SVG path that contains move (m), line (l) or curve (c) components.
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function parse(path) {
	    // Parsing the SVG path string into an array of arrays [['M', '10', '10'], ['L', '100', '100']]
	    var chunks = path.replace(/([A-Za-z])([0-9])/g, '$1 $2')
	      .replace(/([0-9])([A-Za-z])/g, '$1 $2')
	      .split(/[\s,]+/)
	      .reduce(function(result, element) {
	        if(element.match(/[A-Za-z]/)) {
	          result.push([]);
	        }

	        result[result.length - 1].push(element);
	        return result;
	      }, []);

	    // If this is a closed path we remove the Z at the end because this is determined by the close option
	    if(chunks[chunks.length - 1][0].toUpperCase() === 'Z') {
	      chunks.pop();
	    }

	    // Using svgPathElementDescriptions to map raw path arrays into objects that contain the command and the parameters
	    // For example {command: 'M', x: '10', y: '10'}
	    var elements = chunks.map(function(chunk) {
	        var command = chunk.shift(),
	          description = elementDescriptions[command.toLowerCase()];

	        return Chartist.extend({
	          command: command
	        }, description.reduce(function(result, paramName, index) {
	          result[paramName] = +chunk[index];
	          return result;
	        }, {}));
	      });

	    // Preparing a splice call with the elements array as var arg params and insert the parsed elements at the current position
	    var spliceArgs = [this.pos, 0];
	    Array.prototype.push.apply(spliceArgs, elements);
	    Array.prototype.splice.apply(this.pathElements, spliceArgs);
	    // Increase the internal position by the element count
	    this.pos += elements.length;

	    return this;
	  }

	  /**
	   * This function renders to current SVG path object into a final SVG string that can be used in the d attribute of SVG path elements. It uses the accuracy option to round big decimals. If the close parameter was set in the constructor of this path object then a path closing Z will be appended to the output string.
	   *
	   * @memberof Chartist.Svg.Path
	   * @return {String}
	   */
	  function stringify() {
	    var accuracyMultiplier = Math.pow(10, this.options.accuracy);

	    return this.pathElements.reduce(function(path, pathElement) {
	        var params = elementDescriptions[pathElement.command.toLowerCase()].map(function(paramName) {
	          return this.options.accuracy ?
	            (Math.round(pathElement[paramName] * accuracyMultiplier) / accuracyMultiplier) :
	            pathElement[paramName];
	        }.bind(this));

	        return path + pathElement.command + params.join(',');
	      }.bind(this), '') + (this.close ? 'Z' : '');
	  }

	  /**
	   * Scales all elements in the current SVG path object. There is an individual parameter for each coordinate. Scaling will also be done for control points of curves, affecting the given coordinate.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} x The number which will be used to scale the x, x1 and x2 of all path elements.
	   * @param {Number} y The number which will be used to scale the y, y1 and y2 of all path elements.
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function scale(x, y) {
	    forEachParam(this.pathElements, function(pathElement, paramName) {
	      pathElement[paramName] *= paramName[0] === 'x' ? x : y;
	    });
	    return this;
	  }

	  /**
	   * Translates all elements in the current SVG path object. The translation is relative and there is an individual parameter for each coordinate. Translation will also be done for control points of curves, affecting the given coordinate.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Number} x The number which will be used to translate the x, x1 and x2 of all path elements.
	   * @param {Number} y The number which will be used to translate the y, y1 and y2 of all path elements.
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function translate(x, y) {
	    forEachParam(this.pathElements, function(pathElement, paramName) {
	      pathElement[paramName] += paramName[0] === 'x' ? x : y;
	    });
	    return this;
	  }

	  /**
	   * This function will run over all existing path elements and then loop over their attributes. The callback function will be called for every path element attribute that exists in the current path.
	   * The method signature of the callback function looks like this:
	   * ```javascript
	   * function(pathElement, paramName, pathElementIndex, paramIndex, pathElements)
	   * ```
	   * If something else than undefined is returned by the callback function, this value will be used to replace the old value. This allows you to build custom transformations of path objects that can't be achieved using the basic transformation functions scale and translate.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Function} transformFnc The callback function for the transformation. Check the signature in the function description.
	   * @return {Chartist.Svg.Path} The current path object for easy call chaining.
	   */
	  function transform(transformFnc) {
	    forEachParam(this.pathElements, function(pathElement, paramName, pathElementIndex, paramIndex, pathElements) {
	      var transformed = transformFnc(pathElement, paramName, pathElementIndex, paramIndex, pathElements);
	      if(transformed || transformed === 0) {
	        pathElement[paramName] = transformed;
	      }
	    });
	    return this;
	  }

	  /**
	   * This function clones a whole path object with all its properties. This is a deep clone and path element objects will also be cloned.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Boolean} [close] Optional option to set the new cloned path to closed. If not specified or false, the original path close option will be used.
	   * @return {Chartist.Svg.Path}
	   */
	  function clone(close) {
	    var c = new Chartist.Svg.Path(close || this.close);
	    c.pos = this.pos;
	    c.pathElements = this.pathElements.slice().map(function cloneElements(pathElement) {
	      return Chartist.extend({}, pathElement);
	    });
	    c.options = Chartist.extend({}, this.options);
	    return c;
	  }

	  /**
	   * Split a Svg.Path object by a specific command in the path chain. The path chain will be split and an array of newly created paths objects will be returned. This is useful if you'd like to split an SVG path by it's move commands, for example, in order to isolate chunks of drawings.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {String} command The command you'd like to use to split the path
	   * @return {Array<Chartist.Svg.Path>}
	   */
	  function splitByCommand(command) {
	    var split = [
	      new Chartist.Svg.Path()
	    ];

	    this.pathElements.forEach(function(pathElement) {
	      if(pathElement.command === command.toUpperCase() && split[split.length - 1].pathElements.length !== 0) {
	        split.push(new Chartist.Svg.Path());
	      }

	      split[split.length - 1].pathElements.push(pathElement);
	    });

	    return split;
	  }

	  /**
	   * This static function on `Chartist.Svg.Path` is joining multiple paths together into one paths.
	   *
	   * @memberof Chartist.Svg.Path
	   * @param {Array<Chartist.Svg.Path>} paths A list of paths to be joined together. The order is important.
	   * @param {boolean} close If the newly created path should be a closed path
	   * @param {Object} options Path options for the newly created path.
	   * @return {Chartist.Svg.Path}
	   */

	  function join(paths, close, options) {
	    var joinedPath = new Chartist.Svg.Path(close, options);
	    for(var i = 0; i < paths.length; i++) {
	      var path = paths[i];
	      for(var j = 0; j < path.pathElements.length; j++) {
	        joinedPath.pathElements.push(path.pathElements[j]);
	      }
	    }
	    return joinedPath;
	  }

	  Chartist.Svg.Path = Chartist.Class.extend({
	    constructor: SvgPath,
	    position: position,
	    remove: remove,
	    move: move,
	    line: line,
	    curve: curve,
	    arc: arc,
	    scale: scale,
	    translate: translate,
	    transform: transform,
	    parse: parse,
	    stringify: stringify,
	    clone: clone,
	    splitByCommand: splitByCommand
	  });

	  Chartist.Svg.Path.elementDescriptions = elementDescriptions;
	  Chartist.Svg.Path.join = join;
	}(this || commonjsGlobal, Chartist));
	(function (globalRoot, Chartist) {

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  var axisUnits = {
	    x: {
	      pos: 'x',
	      len: 'width',
	      dir: 'horizontal',
	      rectStart: 'x1',
	      rectEnd: 'x2',
	      rectOffset: 'y2'
	    },
	    y: {
	      pos: 'y',
	      len: 'height',
	      dir: 'vertical',
	      rectStart: 'y2',
	      rectEnd: 'y1',
	      rectOffset: 'x1'
	    }
	  };

	  function Axis(units, chartRect, ticks, options) {
	    this.units = units;
	    this.counterUnits = units === axisUnits.x ? axisUnits.y : axisUnits.x;
	    this.chartRect = chartRect;
	    this.axisLength = chartRect[units.rectEnd] - chartRect[units.rectStart];
	    this.gridOffset = chartRect[units.rectOffset];
	    this.ticks = ticks;
	    this.options = options;
	  }

	  function createGridAndLabels(gridGroup, labelGroup, useForeignObject, chartOptions, eventEmitter) {
	    var axisOptions = chartOptions['axis' + this.units.pos.toUpperCase()];
	    var projectedValues = this.ticks.map(this.projectValue.bind(this));
	    var labelValues = this.ticks.map(axisOptions.labelInterpolationFnc);

	    projectedValues.forEach(function(projectedValue, index) {
	      var labelOffset = {
	        x: 0,
	        y: 0
	      };

	      // TODO: Find better solution for solving this problem
	      // Calculate how much space we have available for the label
	      var labelLength;
	      if(projectedValues[index + 1]) {
	        // If we still have one label ahead, we can calculate the distance to the next tick / label
	        labelLength = projectedValues[index + 1] - projectedValue;
	      } else {
	        // If we don't have a label ahead and we have only two labels in total, we just take the remaining distance to
	        // on the whole axis length. We limit that to a minimum of 30 pixel, so that labels close to the border will
	        // still be visible inside of the chart padding.
	        labelLength = Math.max(this.axisLength - projectedValue, 30);
	      }

	      // Skip grid lines and labels where interpolated label values are falsey (execpt for 0)
	      if(Chartist.isFalseyButZero(labelValues[index]) && labelValues[index] !== '') {
	        return;
	      }

	      // Transform to global coordinates using the chartRect
	      // We also need to set the label offset for the createLabel function
	      if(this.units.pos === 'x') {
	        projectedValue = this.chartRect.x1 + projectedValue;
	        labelOffset.x = chartOptions.axisX.labelOffset.x;

	        // If the labels should be positioned in start position (top side for vertical axis) we need to set a
	        // different offset as for positioned with end (bottom)
	        if(chartOptions.axisX.position === 'start') {
	          labelOffset.y = this.chartRect.padding.top + chartOptions.axisX.labelOffset.y + (useForeignObject ? 5 : 20);
	        } else {
	          labelOffset.y = this.chartRect.y1 + chartOptions.axisX.labelOffset.y + (useForeignObject ? 5 : 20);
	        }
	      } else {
	        projectedValue = this.chartRect.y1 - projectedValue;
	        labelOffset.y = chartOptions.axisY.labelOffset.y - (useForeignObject ? labelLength : 0);

	        // If the labels should be positioned in start position (left side for horizontal axis) we need to set a
	        // different offset as for positioned with end (right side)
	        if(chartOptions.axisY.position === 'start') {
	          labelOffset.x = useForeignObject ? this.chartRect.padding.left + chartOptions.axisY.labelOffset.x : this.chartRect.x1 - 10;
	        } else {
	          labelOffset.x = this.chartRect.x2 + chartOptions.axisY.labelOffset.x + 10;
	        }
	      }

	      if(axisOptions.showGrid) {
	        Chartist.createGrid(projectedValue, index, this, this.gridOffset, this.chartRect[this.counterUnits.len](), gridGroup, [
	          chartOptions.classNames.grid,
	          chartOptions.classNames[this.units.dir]
	        ], eventEmitter);
	      }

	      if(axisOptions.showLabel) {
	        Chartist.createLabel(projectedValue, labelLength, index, labelValues, this, axisOptions.offset, labelOffset, labelGroup, [
	          chartOptions.classNames.label,
	          chartOptions.classNames[this.units.dir],
	          (axisOptions.position === 'start' ? chartOptions.classNames[axisOptions.position] : chartOptions.classNames['end'])
	        ], useForeignObject, eventEmitter);
	      }
	    }.bind(this));
	  }

	  Chartist.Axis = Chartist.Class.extend({
	    constructor: Axis,
	    createGridAndLabels: createGridAndLabels,
	    projectValue: function(value, index, data) {
	      throw new Error('Base axis can\'t be instantiated!');
	    }
	  });

	  Chartist.Axis.units = axisUnits;

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function (globalRoot, Chartist) {

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  function AutoScaleAxis(axisUnit, data, chartRect, options) {
	    // Usually we calculate highLow based on the data but this can be overriden by a highLow object in the options
	    var highLow = options.highLow || Chartist.getHighLow(data, options, axisUnit.pos);
	    this.bounds = Chartist.getBounds(chartRect[axisUnit.rectEnd] - chartRect[axisUnit.rectStart], highLow, options.scaleMinSpace || 20, options.onlyInteger);
	    this.range = {
	      min: this.bounds.min,
	      max: this.bounds.max
	    };

	    Chartist.AutoScaleAxis.super.constructor.call(this,
	      axisUnit,
	      chartRect,
	      this.bounds.values,
	      options);
	  }

	  function projectValue(value) {
	    return this.axisLength * (+Chartist.getMultiValue(value, this.units.pos) - this.bounds.min) / this.bounds.range;
	  }

	  Chartist.AutoScaleAxis = Chartist.Axis.extend({
	    constructor: AutoScaleAxis,
	    projectValue: projectValue
	  });

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function (globalRoot, Chartist) {

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  function FixedScaleAxis(axisUnit, data, chartRect, options) {
	    var highLow = options.highLow || Chartist.getHighLow(data, options, axisUnit.pos);
	    this.divisor = options.divisor || 1;
	    this.ticks = options.ticks || Chartist.times(this.divisor).map(function(value, index) {
	      return highLow.low + (highLow.high - highLow.low) / this.divisor * index;
	    }.bind(this));
	    this.ticks.sort(function(a, b) {
	      return a - b;
	    });
	    this.range = {
	      min: highLow.low,
	      max: highLow.high
	    };

	    Chartist.FixedScaleAxis.super.constructor.call(this,
	      axisUnit,
	      chartRect,
	      this.ticks,
	      options);

	    this.stepLength = this.axisLength / this.divisor;
	  }

	  function projectValue(value) {
	    return this.axisLength * (+Chartist.getMultiValue(value, this.units.pos) - this.range.min) / (this.range.max - this.range.min);
	  }

	  Chartist.FixedScaleAxis = Chartist.Axis.extend({
	    constructor: FixedScaleAxis,
	    projectValue: projectValue
	  });

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function (globalRoot, Chartist) {

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  function StepAxis(axisUnit, data, chartRect, options) {
	    Chartist.StepAxis.super.constructor.call(this,
	      axisUnit,
	      chartRect,
	      options.ticks,
	      options);

	    var calc = Math.max(1, options.ticks.length - (options.stretch ? 1 : 0));
	    this.stepLength = this.axisLength / calc;
	  }

	  function projectValue(value, index) {
	    return this.stepLength * index;
	  }

	  Chartist.StepAxis = Chartist.Axis.extend({
	    constructor: StepAxis,
	    projectValue: projectValue
	  });

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist){

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  /**
	   * Default options in line charts. Expand the code view to see a detailed list of options with comments.
	   *
	   * @memberof Chartist.Line
	   */
	  var defaultOptions = {
	    // Options for X-Axis
	    axisX: {
	      // The offset of the labels to the chart area
	      offset: 30,
	      // Position where labels are placed. Can be set to `start` or `end` where `start` is equivalent to left or top on vertical axis and `end` is equivalent to right or bottom on horizontal axis.
	      position: 'end',
	      // Allows you to correct label positioning on this axis by positive or negative x and y offset.
	      labelOffset: {
	        x: 0,
	        y: 0
	      },
	      // If labels should be shown or not
	      showLabel: true,
	      // If the axis grid should be drawn or not
	      showGrid: true,
	      // Interpolation function that allows you to intercept the value from the axis label
	      labelInterpolationFnc: Chartist.noop,
	      // Set the axis type to be used to project values on this axis. If not defined, Chartist.StepAxis will be used for the X-Axis, where the ticks option will be set to the labels in the data and the stretch option will be set to the global fullWidth option. This type can be changed to any axis constructor available (e.g. Chartist.FixedScaleAxis), where all axis options should be present here.
	      type: undefined
	    },
	    // Options for Y-Axis
	    axisY: {
	      // The offset of the labels to the chart area
	      offset: 40,
	      // Position where labels are placed. Can be set to `start` or `end` where `start` is equivalent to left or top on vertical axis and `end` is equivalent to right or bottom on horizontal axis.
	      position: 'start',
	      // Allows you to correct label positioning on this axis by positive or negative x and y offset.
	      labelOffset: {
	        x: 0,
	        y: 0
	      },
	      // If labels should be shown or not
	      showLabel: true,
	      // If the axis grid should be drawn or not
	      showGrid: true,
	      // Interpolation function that allows you to intercept the value from the axis label
	      labelInterpolationFnc: Chartist.noop,
	      // Set the axis type to be used to project values on this axis. If not defined, Chartist.AutoScaleAxis will be used for the Y-Axis, where the high and low options will be set to the global high and low options. This type can be changed to any axis constructor available (e.g. Chartist.FixedScaleAxis), where all axis options should be present here.
	      type: undefined,
	      // This value specifies the minimum height in pixel of the scale steps
	      scaleMinSpace: 20,
	      // Use only integer values (whole numbers) for the scale steps
	      onlyInteger: false
	    },
	    // Specify a fixed width for the chart as a string (i.e. '100px' or '50%')
	    width: undefined,
	    // Specify a fixed height for the chart as a string (i.e. '100px' or '50%')
	    height: undefined,
	    // If the line should be drawn or not
	    showLine: true,
	    // If dots should be drawn or not
	    showPoint: true,
	    // If the line chart should draw an area
	    showArea: false,
	    // The base for the area chart that will be used to close the area shape (is normally 0)
	    areaBase: 0,
	    // Specify if the lines should be smoothed. This value can be true or false where true will result in smoothing using the default smoothing interpolation function Chartist.Interpolation.cardinal and false results in Chartist.Interpolation.none. You can also choose other smoothing / interpolation functions available in the Chartist.Interpolation module, or write your own interpolation function. Check the examples for a brief description.
	    lineSmooth: true,
	    // If the line chart should add a background fill to the .ct-grids group.
	    showGridBackground: false,
	    // Overriding the natural low of the chart allows you to zoom in or limit the charts lowest displayed value
	    low: undefined,
	    // Overriding the natural high of the chart allows you to zoom in or limit the charts highest displayed value
	    high: undefined,
	    // Padding of the chart drawing area to the container element and labels as a number or padding object {top: 5, right: 5, bottom: 5, left: 5}
	    chartPadding: {
	      top: 15,
	      right: 15,
	      bottom: 5,
	      left: 10
	    },
	    // When set to true, the last grid line on the x-axis is not drawn and the chart elements will expand to the full available width of the chart. For the last label to be drawn correctly you might need to add chart padding or offset the last label with a draw event handler.
	    fullWidth: false,
	    // If true the whole data is reversed including labels, the series order as well as the whole series data arrays.
	    reverseData: false,
	    // Override the class names that get used to generate the SVG structure of the chart
	    classNames: {
	      chart: 'ct-chart-line',
	      label: 'ct-label',
	      labelGroup: 'ct-labels',
	      series: 'ct-series',
	      line: 'ct-line',
	      point: 'ct-point',
	      area: 'ct-area',
	      grid: 'ct-grid',
	      gridGroup: 'ct-grids',
	      gridBackground: 'ct-grid-background',
	      vertical: 'ct-vertical',
	      horizontal: 'ct-horizontal',
	      start: 'ct-start',
	      end: 'ct-end'
	    }
	  };

	  /**
	   * Creates a new chart
	   *
	   */
	  function createChart(options) {
	    var data = Chartist.normalizeData(this.data, options.reverseData, true);

	    // Create new svg object
	    this.svg = Chartist.createSvg(this.container, options.width, options.height, options.classNames.chart);
	    // Create groups for labels, grid and series
	    var gridGroup = this.svg.elem('g').addClass(options.classNames.gridGroup);
	    var seriesGroup = this.svg.elem('g');
	    var labelGroup = this.svg.elem('g').addClass(options.classNames.labelGroup);

	    var chartRect = Chartist.createChartRect(this.svg, options, defaultOptions.padding);
	    var axisX, axisY;

	    if(options.axisX.type === undefined) {
	      axisX = new Chartist.StepAxis(Chartist.Axis.units.x, data.normalized.series, chartRect, Chartist.extend({}, options.axisX, {
	        ticks: data.normalized.labels,
	        stretch: options.fullWidth
	      }));
	    } else {
	      axisX = options.axisX.type.call(Chartist, Chartist.Axis.units.x, data.normalized.series, chartRect, options.axisX);
	    }

	    if(options.axisY.type === undefined) {
	      axisY = new Chartist.AutoScaleAxis(Chartist.Axis.units.y, data.normalized.series, chartRect, Chartist.extend({}, options.axisY, {
	        high: Chartist.isNumeric(options.high) ? options.high : options.axisY.high,
	        low: Chartist.isNumeric(options.low) ? options.low : options.axisY.low
	      }));
	    } else {
	      axisY = options.axisY.type.call(Chartist, Chartist.Axis.units.y, data.normalized.series, chartRect, options.axisY);
	    }

	    axisX.createGridAndLabels(gridGroup, labelGroup, this.supportsForeignObject, options, this.eventEmitter);
	    axisY.createGridAndLabels(gridGroup, labelGroup, this.supportsForeignObject, options, this.eventEmitter);

	    if (options.showGridBackground) {
	      Chartist.createGridBackground(gridGroup, chartRect, options.classNames.gridBackground, this.eventEmitter);
	    }

	    // Draw the series
	    data.raw.series.forEach(function(series, seriesIndex) {
	      var seriesElement = seriesGroup.elem('g');

	      // Write attributes to series group element. If series name or meta is undefined the attributes will not be written
	      seriesElement.attr({
	        'ct:series-name': series.name,
	        'ct:meta': Chartist.serialize(series.meta)
	      });

	      // Use series class from series data or if not set generate one
	      seriesElement.addClass([
	        options.classNames.series,
	        (series.className || options.classNames.series + '-' + Chartist.alphaNumerate(seriesIndex))
	      ].join(' '));

	      var pathCoordinates = [],
	        pathData = [];

	      data.normalized.series[seriesIndex].forEach(function(value, valueIndex) {
	        var p = {
	          x: chartRect.x1 + axisX.projectValue(value, valueIndex, data.normalized.series[seriesIndex]),
	          y: chartRect.y1 - axisY.projectValue(value, valueIndex, data.normalized.series[seriesIndex])
	        };
	        pathCoordinates.push(p.x, p.y);
	        pathData.push({
	          value: value,
	          valueIndex: valueIndex,
	          meta: Chartist.getMetaData(series, valueIndex)
	        });
	      }.bind(this));

	      var seriesOptions = {
	        lineSmooth: Chartist.getSeriesOption(series, options, 'lineSmooth'),
	        showPoint: Chartist.getSeriesOption(series, options, 'showPoint'),
	        showLine: Chartist.getSeriesOption(series, options, 'showLine'),
	        showArea: Chartist.getSeriesOption(series, options, 'showArea'),
	        areaBase: Chartist.getSeriesOption(series, options, 'areaBase')
	      };

	      var smoothing = typeof seriesOptions.lineSmooth === 'function' ?
	        seriesOptions.lineSmooth : (seriesOptions.lineSmooth ? Chartist.Interpolation.monotoneCubic() : Chartist.Interpolation.none());
	      // Interpolating path where pathData will be used to annotate each path element so we can trace back the original
	      // index, value and meta data
	      var path = smoothing(pathCoordinates, pathData);

	      // If we should show points we need to create them now to avoid secondary loop
	      // Points are drawn from the pathElements returned by the interpolation function
	      // Small offset for Firefox to render squares correctly
	      if (seriesOptions.showPoint) {

	        path.pathElements.forEach(function(pathElement) {
	          var point = seriesElement.elem('line', {
	            x1: pathElement.x,
	            y1: pathElement.y,
	            x2: pathElement.x + 0.01,
	            y2: pathElement.y
	          }, options.classNames.point).attr({
	            'ct:value': [pathElement.data.value.x, pathElement.data.value.y].filter(Chartist.isNumeric).join(','),
	            'ct:meta': Chartist.serialize(pathElement.data.meta)
	          });

	          this.eventEmitter.emit('draw', {
	            type: 'point',
	            value: pathElement.data.value,
	            index: pathElement.data.valueIndex,
	            meta: pathElement.data.meta,
	            series: series,
	            seriesIndex: seriesIndex,
	            axisX: axisX,
	            axisY: axisY,
	            group: seriesElement,
	            element: point,
	            x: pathElement.x,
	            y: pathElement.y
	          });
	        }.bind(this));
	      }

	      if(seriesOptions.showLine) {
	        var line = seriesElement.elem('path', {
	          d: path.stringify()
	        }, options.classNames.line, true);

	        this.eventEmitter.emit('draw', {
	          type: 'line',
	          values: data.normalized.series[seriesIndex],
	          path: path.clone(),
	          chartRect: chartRect,
	          index: seriesIndex,
	          series: series,
	          seriesIndex: seriesIndex,
	          seriesMeta: series.meta,
	          axisX: axisX,
	          axisY: axisY,
	          group: seriesElement,
	          element: line
	        });
	      }

	      // Area currently only works with axes that support a range!
	      if(seriesOptions.showArea && axisY.range) {
	        // If areaBase is outside the chart area (< min or > max) we need to set it respectively so that
	        // the area is not drawn outside the chart area.
	        var areaBase = Math.max(Math.min(seriesOptions.areaBase, axisY.range.max), axisY.range.min);

	        // We project the areaBase value into screen coordinates
	        var areaBaseProjected = chartRect.y1 - axisY.projectValue(areaBase);

	        // In order to form the area we'll first split the path by move commands so we can chunk it up into segments
	        path.splitByCommand('M').filter(function onlySolidSegments(pathSegment) {
	          // We filter only "solid" segments that contain more than one point. Otherwise there's no need for an area
	          return pathSegment.pathElements.length > 1;
	        }).map(function convertToArea(solidPathSegments) {
	          // Receiving the filtered solid path segments we can now convert those segments into fill areas
	          var firstElement = solidPathSegments.pathElements[0];
	          var lastElement = solidPathSegments.pathElements[solidPathSegments.pathElements.length - 1];

	          // Cloning the solid path segment with closing option and removing the first move command from the clone
	          // We then insert a new move that should start at the area base and draw a straight line up or down
	          // at the end of the path we add an additional straight line to the projected area base value
	          // As the closing option is set our path will be automatically closed
	          return solidPathSegments.clone(true)
	            .position(0)
	            .remove(1)
	            .move(firstElement.x, areaBaseProjected)
	            .line(firstElement.x, firstElement.y)
	            .position(solidPathSegments.pathElements.length + 1)
	            .line(lastElement.x, areaBaseProjected);

	        }).forEach(function createArea(areaPath) {
	          // For each of our newly created area paths, we'll now create path elements by stringifying our path objects
	          // and adding the created DOM elements to the correct series group
	          var area = seriesElement.elem('path', {
	            d: areaPath.stringify()
	          }, options.classNames.area, true);

	          // Emit an event for each area that was drawn
	          this.eventEmitter.emit('draw', {
	            type: 'area',
	            values: data.normalized.series[seriesIndex],
	            path: areaPath.clone(),
	            series: series,
	            seriesIndex: seriesIndex,
	            axisX: axisX,
	            axisY: axisY,
	            chartRect: chartRect,
	            index: seriesIndex,
	            group: seriesElement,
	            element: area
	          });
	        }.bind(this));
	      }
	    }.bind(this));

	    this.eventEmitter.emit('created', {
	      bounds: axisY.bounds,
	      chartRect: chartRect,
	      axisX: axisX,
	      axisY: axisY,
	      svg: this.svg,
	      options: options
	    });
	  }

	  /**
	   * This method creates a new line chart.
	   *
	   * @memberof Chartist.Line
	   * @param {String|Node} query A selector query string or directly a DOM element
	   * @param {Object} data The data object that needs to consist of a labels and a series array
	   * @param {Object} [options] The options object with options that override the default options. Check the examples for a detailed list.
	   * @param {Array} [responsiveOptions] Specify an array of responsive option arrays which are a media query and options object pair => [[mediaQueryString, optionsObject],[more...]]
	   * @return {Object} An object which exposes the API for the created chart
	   *
	   * @example
	   * // Create a simple line chart
	   * var data = {
	   *   // A labels array that can contain any sort of values
	   *   labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
	   *   // Our series array that contains series objects or in this case series data arrays
	   *   series: [
	   *     [5, 2, 4, 2, 0]
	   *   ]
	   * };
	   *
	   * // As options we currently only set a static size of 300x200 px
	   * var options = {
	   *   width: '300px',
	   *   height: '200px'
	   * };
	   *
	   * // In the global name space Chartist we call the Line function to initialize a line chart. As a first parameter we pass in a selector where we would like to get our chart created. Second parameter is the actual data object and as a third parameter we pass in our options
	   * new Chartist.Line('.ct-chart', data, options);
	   *
	   * @example
	   * // Use specific interpolation function with configuration from the Chartist.Interpolation module
	   *
	   * var chart = new Chartist.Line('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5],
	   *   series: [
	   *     [1, 1, 8, 1, 7]
	   *   ]
	   * }, {
	   *   lineSmooth: Chartist.Interpolation.cardinal({
	   *     tension: 0.2
	   *   })
	   * });
	   *
	   * @example
	   * // Create a line chart with responsive options
	   *
	   * var data = {
	   *   // A labels array that can contain any sort of values
	   *   labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
	   *   // Our series array that contains series objects or in this case series data arrays
	   *   series: [
	   *     [5, 2, 4, 2, 0]
	   *   ]
	   * };
	   *
	   * // In addition to the regular options we specify responsive option overrides that will override the default configutation based on the matching media queries.
	   * var responsiveOptions = [
	   *   ['screen and (min-width: 641px) and (max-width: 1024px)', {
	   *     showPoint: false,
	   *     axisX: {
	   *       labelInterpolationFnc: function(value) {
	   *         // Will return Mon, Tue, Wed etc. on medium screens
	   *         return value.slice(0, 3);
	   *       }
	   *     }
	   *   }],
	   *   ['screen and (max-width: 640px)', {
	   *     showLine: false,
	   *     axisX: {
	   *       labelInterpolationFnc: function(value) {
	   *         // Will return M, T, W etc. on small screens
	   *         return value[0];
	   *       }
	   *     }
	   *   }]
	   * ];
	   *
	   * new Chartist.Line('.ct-chart', data, null, responsiveOptions);
	   *
	   */
	  function Line(query, data, options, responsiveOptions) {
	    Chartist.Line.super.constructor.call(this,
	      query,
	      data,
	      defaultOptions,
	      Chartist.extend({}, defaultOptions, options),
	      responsiveOptions);
	  }

	  // Creating line chart type in Chartist namespace
	  Chartist.Line = Chartist.Base.extend({
	    constructor: Line,
	    createChart: createChart
	  });

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist){

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  /**
	   * Default options in bar charts. Expand the code view to see a detailed list of options with comments.
	   *
	   * @memberof Chartist.Bar
	   */
	  var defaultOptions = {
	    // Options for X-Axis
	    axisX: {
	      // The offset of the chart drawing area to the border of the container
	      offset: 30,
	      // Position where labels are placed. Can be set to `start` or `end` where `start` is equivalent to left or top on vertical axis and `end` is equivalent to right or bottom on horizontal axis.
	      position: 'end',
	      // Allows you to correct label positioning on this axis by positive or negative x and y offset.
	      labelOffset: {
	        x: 0,
	        y: 0
	      },
	      // If labels should be shown or not
	      showLabel: true,
	      // If the axis grid should be drawn or not
	      showGrid: true,
	      // Interpolation function that allows you to intercept the value from the axis label
	      labelInterpolationFnc: Chartist.noop,
	      // This value specifies the minimum width in pixel of the scale steps
	      scaleMinSpace: 30,
	      // Use only integer values (whole numbers) for the scale steps
	      onlyInteger: false
	    },
	    // Options for Y-Axis
	    axisY: {
	      // The offset of the chart drawing area to the border of the container
	      offset: 40,
	      // Position where labels are placed. Can be set to `start` or `end` where `start` is equivalent to left or top on vertical axis and `end` is equivalent to right or bottom on horizontal axis.
	      position: 'start',
	      // Allows you to correct label positioning on this axis by positive or negative x and y offset.
	      labelOffset: {
	        x: 0,
	        y: 0
	      },
	      // If labels should be shown or not
	      showLabel: true,
	      // If the axis grid should be drawn or not
	      showGrid: true,
	      // Interpolation function that allows you to intercept the value from the axis label
	      labelInterpolationFnc: Chartist.noop,
	      // This value specifies the minimum height in pixel of the scale steps
	      scaleMinSpace: 20,
	      // Use only integer values (whole numbers) for the scale steps
	      onlyInteger: false
	    },
	    // Specify a fixed width for the chart as a string (i.e. '100px' or '50%')
	    width: undefined,
	    // Specify a fixed height for the chart as a string (i.e. '100px' or '50%')
	    height: undefined,
	    // Overriding the natural high of the chart allows you to zoom in or limit the charts highest displayed value
	    high: undefined,
	    // Overriding the natural low of the chart allows you to zoom in or limit the charts lowest displayed value
	    low: undefined,
	    // Unless low/high are explicitly set, bar chart will be centered at zero by default. Set referenceValue to null to auto scale.
	    referenceValue: 0,
	    // Padding of the chart drawing area to the container element and labels as a number or padding object {top: 5, right: 5, bottom: 5, left: 5}
	    chartPadding: {
	      top: 15,
	      right: 15,
	      bottom: 5,
	      left: 10
	    },
	    // Specify the distance in pixel of bars in a group
	    seriesBarDistance: 15,
	    // If set to true this property will cause the series bars to be stacked. Check the `stackMode` option for further stacking options.
	    stackBars: false,
	    // If set to 'overlap' this property will force the stacked bars to draw from the zero line.
	    // If set to 'accumulate' this property will form a total for each series point. This will also influence the y-axis and the overall bounds of the chart. In stacked mode the seriesBarDistance property will have no effect.
	    stackMode: 'accumulate',
	    // Inverts the axes of the bar chart in order to draw a horizontal bar chart. Be aware that you also need to invert your axis settings as the Y Axis will now display the labels and the X Axis the values.
	    horizontalBars: false,
	    // If set to true then each bar will represent a series and the data array is expected to be a one dimensional array of data values rather than a series array of series. This is useful if the bar chart should represent a profile rather than some data over time.
	    distributeSeries: false,
	    // If true the whole data is reversed including labels, the series order as well as the whole series data arrays.
	    reverseData: false,
	    // If the bar chart should add a background fill to the .ct-grids group.
	    showGridBackground: false,
	    // Override the class names that get used to generate the SVG structure of the chart
	    classNames: {
	      chart: 'ct-chart-bar',
	      horizontalBars: 'ct-horizontal-bars',
	      label: 'ct-label',
	      labelGroup: 'ct-labels',
	      series: 'ct-series',
	      bar: 'ct-bar',
	      grid: 'ct-grid',
	      gridGroup: 'ct-grids',
	      gridBackground: 'ct-grid-background',
	      vertical: 'ct-vertical',
	      horizontal: 'ct-horizontal',
	      start: 'ct-start',
	      end: 'ct-end'
	    }
	  };

	  /**
	   * Creates a new chart
	   *
	   */
	  function createChart(options) {
	    var data;
	    var highLow;

	    if(options.distributeSeries) {
	      data = Chartist.normalizeData(this.data, options.reverseData, options.horizontalBars ? 'x' : 'y');
	      data.normalized.series = data.normalized.series.map(function(value) {
	        return [value];
	      });
	    } else {
	      data = Chartist.normalizeData(this.data, options.reverseData, options.horizontalBars ? 'x' : 'y');
	    }

	    // Create new svg element
	    this.svg = Chartist.createSvg(
	      this.container,
	      options.width,
	      options.height,
	      options.classNames.chart + (options.horizontalBars ? ' ' + options.classNames.horizontalBars : '')
	    );

	    // Drawing groups in correct order
	    var gridGroup = this.svg.elem('g').addClass(options.classNames.gridGroup);
	    var seriesGroup = this.svg.elem('g');
	    var labelGroup = this.svg.elem('g').addClass(options.classNames.labelGroup);

	    if(options.stackBars && data.normalized.series.length !== 0) {

	      // If stacked bars we need to calculate the high low from stacked values from each series
	      var serialSums = Chartist.serialMap(data.normalized.series, function serialSums() {
	        return Array.prototype.slice.call(arguments).map(function(value) {
	          return value;
	        }).reduce(function(prev, curr) {
	          return {
	            x: prev.x + (curr && curr.x) || 0,
	            y: prev.y + (curr && curr.y) || 0
	          };
	        }, {x: 0, y: 0});
	      });

	      highLow = Chartist.getHighLow([serialSums], options, options.horizontalBars ? 'x' : 'y');

	    } else {

	      highLow = Chartist.getHighLow(data.normalized.series, options, options.horizontalBars ? 'x' : 'y');
	    }

	    // Overrides of high / low from settings
	    highLow.high = +options.high || (options.high === 0 ? 0 : highLow.high);
	    highLow.low = +options.low || (options.low === 0 ? 0 : highLow.low);

	    var chartRect = Chartist.createChartRect(this.svg, options, defaultOptions.padding);

	    var valueAxis,
	      labelAxisTicks,
	      labelAxis,
	      axisX,
	      axisY;

	    // We need to set step count based on some options combinations
	    if(options.distributeSeries && options.stackBars) {
	      // If distributed series are enabled and bars need to be stacked, we'll only have one bar and therefore should
	      // use only the first label for the step axis
	      labelAxisTicks = data.normalized.labels.slice(0, 1);
	    } else {
	      // If distributed series are enabled but stacked bars aren't, we should use the series labels
	      // If we are drawing a regular bar chart with two dimensional series data, we just use the labels array
	      // as the bars are normalized
	      labelAxisTicks = data.normalized.labels;
	    }

	    // Set labelAxis and valueAxis based on the horizontalBars setting. This setting will flip the axes if necessary.
	    if(options.horizontalBars) {
	      if(options.axisX.type === undefined) {
	        valueAxis = axisX = new Chartist.AutoScaleAxis(Chartist.Axis.units.x, data.normalized.series, chartRect, Chartist.extend({}, options.axisX, {
	          highLow: highLow,
	          referenceValue: 0
	        }));
	      } else {
	        valueAxis = axisX = options.axisX.type.call(Chartist, Chartist.Axis.units.x, data.normalized.series, chartRect, Chartist.extend({}, options.axisX, {
	          highLow: highLow,
	          referenceValue: 0
	        }));
	      }

	      if(options.axisY.type === undefined) {
	        labelAxis = axisY = new Chartist.StepAxis(Chartist.Axis.units.y, data.normalized.series, chartRect, {
	          ticks: labelAxisTicks
	        });
	      } else {
	        labelAxis = axisY = options.axisY.type.call(Chartist, Chartist.Axis.units.y, data.normalized.series, chartRect, options.axisY);
	      }
	    } else {
	      if(options.axisX.type === undefined) {
	        labelAxis = axisX = new Chartist.StepAxis(Chartist.Axis.units.x, data.normalized.series, chartRect, {
	          ticks: labelAxisTicks
	        });
	      } else {
	        labelAxis = axisX = options.axisX.type.call(Chartist, Chartist.Axis.units.x, data.normalized.series, chartRect, options.axisX);
	      }

	      if(options.axisY.type === undefined) {
	        valueAxis = axisY = new Chartist.AutoScaleAxis(Chartist.Axis.units.y, data.normalized.series, chartRect, Chartist.extend({}, options.axisY, {
	          highLow: highLow,
	          referenceValue: 0
	        }));
	      } else {
	        valueAxis = axisY = options.axisY.type.call(Chartist, Chartist.Axis.units.y, data.normalized.series, chartRect, Chartist.extend({}, options.axisY, {
	          highLow: highLow,
	          referenceValue: 0
	        }));
	      }
	    }

	    // Projected 0 point
	    var zeroPoint = options.horizontalBars ? (chartRect.x1 + valueAxis.projectValue(0)) : (chartRect.y1 - valueAxis.projectValue(0));
	    // Used to track the screen coordinates of stacked bars
	    var stackedBarValues = [];

	    labelAxis.createGridAndLabels(gridGroup, labelGroup, this.supportsForeignObject, options, this.eventEmitter);
	    valueAxis.createGridAndLabels(gridGroup, labelGroup, this.supportsForeignObject, options, this.eventEmitter);

	    if (options.showGridBackground) {
	      Chartist.createGridBackground(gridGroup, chartRect, options.classNames.gridBackground, this.eventEmitter);
	    }

	    // Draw the series
	    data.raw.series.forEach(function(series, seriesIndex) {
	      // Calculating bi-polar value of index for seriesOffset. For i = 0..4 biPol will be -1.5, -0.5, 0.5, 1.5 etc.
	      var biPol = seriesIndex - (data.raw.series.length - 1) / 2;
	      // Half of the period width between vertical grid lines used to position bars
	      var periodHalfLength;
	      // Current series SVG element
	      var seriesElement;

	      // We need to set periodHalfLength based on some options combinations
	      if(options.distributeSeries && !options.stackBars) {
	        // If distributed series are enabled but stacked bars aren't, we need to use the length of the normaizedData array
	        // which is the series count and divide by 2
	        periodHalfLength = labelAxis.axisLength / data.normalized.series.length / 2;
	      } else if(options.distributeSeries && options.stackBars) {
	        // If distributed series and stacked bars are enabled we'll only get one bar so we should just divide the axis
	        // length by 2
	        periodHalfLength = labelAxis.axisLength / 2;
	      } else {
	        // On regular bar charts we should just use the series length
	        periodHalfLength = labelAxis.axisLength / data.normalized.series[seriesIndex].length / 2;
	      }

	      // Adding the series group to the series element
	      seriesElement = seriesGroup.elem('g');

	      // Write attributes to series group element. If series name or meta is undefined the attributes will not be written
	      seriesElement.attr({
	        'ct:series-name': series.name,
	        'ct:meta': Chartist.serialize(series.meta)
	      });

	      // Use series class from series data or if not set generate one
	      seriesElement.addClass([
	        options.classNames.series,
	        (series.className || options.classNames.series + '-' + Chartist.alphaNumerate(seriesIndex))
	      ].join(' '));

	      data.normalized.series[seriesIndex].forEach(function(value, valueIndex) {
	        var projected,
	          bar,
	          previousStack,
	          labelAxisValueIndex;

	        // We need to set labelAxisValueIndex based on some options combinations
	        if(options.distributeSeries && !options.stackBars) {
	          // If distributed series are enabled but stacked bars aren't, we can use the seriesIndex for later projection
	          // on the step axis for label positioning
	          labelAxisValueIndex = seriesIndex;
	        } else if(options.distributeSeries && options.stackBars) {
	          // If distributed series and stacked bars are enabled, we will only get one bar and therefore always use
	          // 0 for projection on the label step axis
	          labelAxisValueIndex = 0;
	        } else {
	          // On regular bar charts we just use the value index to project on the label step axis
	          labelAxisValueIndex = valueIndex;
	        }

	        // We need to transform coordinates differently based on the chart layout
	        if(options.horizontalBars) {
	          projected = {
	            x: chartRect.x1 + valueAxis.projectValue(value && value.x ? value.x : 0, valueIndex, data.normalized.series[seriesIndex]),
	            y: chartRect.y1 - labelAxis.projectValue(value && value.y ? value.y : 0, labelAxisValueIndex, data.normalized.series[seriesIndex])
	          };
	        } else {
	          projected = {
	            x: chartRect.x1 + labelAxis.projectValue(value && value.x ? value.x : 0, labelAxisValueIndex, data.normalized.series[seriesIndex]),
	            y: chartRect.y1 - valueAxis.projectValue(value && value.y ? value.y : 0, valueIndex, data.normalized.series[seriesIndex])
	          };
	        }

	        // If the label axis is a step based axis we will offset the bar into the middle of between two steps using
	        // the periodHalfLength value. Also we do arrange the different series so that they align up to each other using
	        // the seriesBarDistance. If we don't have a step axis, the bar positions can be chosen freely so we should not
	        // add any automated positioning.
	        if(labelAxis instanceof Chartist.StepAxis) {
	          // Offset to center bar between grid lines, but only if the step axis is not stretched
	          if(!labelAxis.options.stretch) {
	            projected[labelAxis.units.pos] += periodHalfLength * (options.horizontalBars ? -1 : 1);
	          }
	          // Using bi-polar offset for multiple series if no stacked bars or series distribution is used
	          projected[labelAxis.units.pos] += (options.stackBars || options.distributeSeries) ? 0 : biPol * options.seriesBarDistance * (options.horizontalBars ? -1 : 1);
	        }

	        // Enter value in stacked bar values used to remember previous screen value for stacking up bars
	        previousStack = stackedBarValues[valueIndex] || zeroPoint;
	        stackedBarValues[valueIndex] = previousStack - (zeroPoint - projected[labelAxis.counterUnits.pos]);

	        // Skip if value is undefined
	        if(value === undefined) {
	          return;
	        }

	        var positions = {};
	        positions[labelAxis.units.pos + '1'] = projected[labelAxis.units.pos];
	        positions[labelAxis.units.pos + '2'] = projected[labelAxis.units.pos];

	        if(options.stackBars && (options.stackMode === 'accumulate' || !options.stackMode)) {
	          // Stack mode: accumulate (default)
	          // If bars are stacked we use the stackedBarValues reference and otherwise base all bars off the zero line
	          // We want backwards compatibility, so the expected fallback without the 'stackMode' option
	          // to be the original behaviour (accumulate)
	          positions[labelAxis.counterUnits.pos + '1'] = previousStack;
	          positions[labelAxis.counterUnits.pos + '2'] = stackedBarValues[valueIndex];
	        } else {
	          // Draw from the zero line normally
	          // This is also the same code for Stack mode: overlap
	          positions[labelAxis.counterUnits.pos + '1'] = zeroPoint;
	          positions[labelAxis.counterUnits.pos + '2'] = projected[labelAxis.counterUnits.pos];
	        }

	        // Limit x and y so that they are within the chart rect
	        positions.x1 = Math.min(Math.max(positions.x1, chartRect.x1), chartRect.x2);
	        positions.x2 = Math.min(Math.max(positions.x2, chartRect.x1), chartRect.x2);
	        positions.y1 = Math.min(Math.max(positions.y1, chartRect.y2), chartRect.y1);
	        positions.y2 = Math.min(Math.max(positions.y2, chartRect.y2), chartRect.y1);

	        var metaData = Chartist.getMetaData(series, valueIndex);

	        // Create bar element
	        bar = seriesElement.elem('line', positions, options.classNames.bar).attr({
	          'ct:value': [value.x, value.y].filter(Chartist.isNumeric).join(','),
	          'ct:meta': Chartist.serialize(metaData)
	        });

	        this.eventEmitter.emit('draw', Chartist.extend({
	          type: 'bar',
	          value: value,
	          index: valueIndex,
	          meta: metaData,
	          series: series,
	          seriesIndex: seriesIndex,
	          axisX: axisX,
	          axisY: axisY,
	          chartRect: chartRect,
	          group: seriesElement,
	          element: bar
	        }, positions));
	      }.bind(this));
	    }.bind(this));

	    this.eventEmitter.emit('created', {
	      bounds: valueAxis.bounds,
	      chartRect: chartRect,
	      axisX: axisX,
	      axisY: axisY,
	      svg: this.svg,
	      options: options
	    });
	  }

	  /**
	   * This method creates a new bar chart and returns API object that you can use for later changes.
	   *
	   * @memberof Chartist.Bar
	   * @param {String|Node} query A selector query string or directly a DOM element
	   * @param {Object} data The data object that needs to consist of a labels and a series array
	   * @param {Object} [options] The options object with options that override the default options. Check the examples for a detailed list.
	   * @param {Array} [responsiveOptions] Specify an array of responsive option arrays which are a media query and options object pair => [[mediaQueryString, optionsObject],[more...]]
	   * @return {Object} An object which exposes the API for the created chart
	   *
	   * @example
	   * // Create a simple bar chart
	   * var data = {
	   *   labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
	   *   series: [
	   *     [5, 2, 4, 2, 0]
	   *   ]
	   * };
	   *
	   * // In the global name space Chartist we call the Bar function to initialize a bar chart. As a first parameter we pass in a selector where we would like to get our chart created and as a second parameter we pass our data object.
	   * new Chartist.Bar('.ct-chart', data);
	   *
	   * @example
	   * // This example creates a bipolar grouped bar chart where the boundaries are limitted to -10 and 10
	   * new Chartist.Bar('.ct-chart', {
	   *   labels: [1, 2, 3, 4, 5, 6, 7],
	   *   series: [
	   *     [1, 3, 2, -5, -3, 1, -6],
	   *     [-5, -2, -4, -1, 2, -3, 1]
	   *   ]
	   * }, {
	   *   seriesBarDistance: 12,
	   *   low: -10,
	   *   high: 10
	   * });
	   *
	   */
	  function Bar(query, data, options, responsiveOptions) {
	    Chartist.Bar.super.constructor.call(this,
	      query,
	      data,
	      defaultOptions,
	      Chartist.extend({}, defaultOptions, options),
	      responsiveOptions);
	  }

	  // Creating bar chart type in Chartist namespace
	  Chartist.Bar = Chartist.Base.extend({
	    constructor: Bar,
	    createChart: createChart
	  });

	}(this || commonjsGlobal, Chartist));
	/* global Chartist */
	(function(globalRoot, Chartist) {

	  var window = globalRoot.window;
	  var document = globalRoot.document;

	  /**
	   * Default options in line charts. Expand the code view to see a detailed list of options with comments.
	   *
	   * @memberof Chartist.Pie
	   */
	  var defaultOptions = {
	    // Specify a fixed width for the chart as a string (i.e. '100px' or '50%')
	    width: undefined,
	    // Specify a fixed height for the chart as a string (i.e. '100px' or '50%')
	    height: undefined,
	    // Padding of the chart drawing area to the container element and labels as a number or padding object {top: 5, right: 5, bottom: 5, left: 5}
	    chartPadding: 5,
	    // Override the class names that are used to generate the SVG structure of the chart
	    classNames: {
	      chartPie: 'ct-chart-pie',
	      chartDonut: 'ct-chart-donut',
	      series: 'ct-series',
	      slicePie: 'ct-slice-pie',
	      sliceDonut: 'ct-slice-donut',
	      sliceDonutSolid: 'ct-slice-donut-solid',
	      label: 'ct-label'
	    },
	    // The start angle of the pie chart in degrees where 0 points north. A higher value offsets the start angle clockwise.
	    startAngle: 0,
	    // An optional total you can specify. By specifying a total value, the sum of the values in the series must be this total in order to draw a full pie. You can use this parameter to draw only parts of a pie or gauge charts.
	    total: undefined,
	    // If specified the donut CSS classes will be used and strokes will be drawn instead of pie slices.
	    donut: false,
	    // If specified the donut segments will be drawn as shapes instead of strokes.
	    donutSolid: false,
	    // Specify the donut stroke width, currently done in javascript for convenience. May move to CSS styles in the future.
	    // This option can be set as number or string to specify a relative width (i.e. 100 or '30%').
	    donutWidth: 60,
	    // If a label should be shown or not
	    showLabel: true,
	    // Label position offset from the standard position which is half distance of the radius. This value can be either positive or negative. Positive values will position the label away from the center.
	    labelOffset: 0,
	    // This option can be set to 'inside', 'outside' or 'center'. Positioned with 'inside' the labels will be placed on half the distance of the radius to the border of the Pie by respecting the 'labelOffset'. The 'outside' option will place the labels at the border of the pie and 'center' will place the labels in the absolute center point of the chart. The 'center' option only makes sense in conjunction with the 'labelOffset' option.
	    labelPosition: 'inside',
	    // An interpolation function for the label value
	    labelInterpolationFnc: Chartist.noop,
	    // Label direction can be 'neutral', 'explode' or 'implode'. The labels anchor will be positioned based on those settings as well as the fact if the labels are on the right or left side of the center of the chart. Usually explode is useful when labels are positioned far away from the center.
	    labelDirection: 'neutral',
	    // If true the whole data is reversed including labels, the series order as well as the whole series data arrays.
	    reverseData: false,
	    // If true empty values will be ignored to avoid drawing unncessary slices and labels
	    ignoreEmptyValues: false
	  };

	  /**
	   * Determines SVG anchor position based on direction and center parameter
	   *
	   * @param center
	   * @param label
	   * @param direction
	   * @return {string}
	   */
	  function determineAnchorPosition(center, label, direction) {
	    var toTheRight = label.x > center.x;

	    if(toTheRight && direction === 'explode' ||
	      !toTheRight && direction === 'implode') {
	      return 'start';
	    } else if(toTheRight && direction === 'implode' ||
	      !toTheRight && direction === 'explode') {
	      return 'end';
	    } else {
	      return 'middle';
	    }
	  }

	  /**
	   * Creates the pie chart
	   *
	   * @param options
	   */
	  function createChart(options) {
	    var data = Chartist.normalizeData(this.data);
	    var seriesGroups = [],
	      labelsGroup,
	      chartRect,
	      radius,
	      labelRadius,
	      totalDataSum,
	      startAngle = options.startAngle;

	    // Create SVG.js draw
	    this.svg = Chartist.createSvg(this.container, options.width, options.height,options.donut ? options.classNames.chartDonut : options.classNames.chartPie);
	    // Calculate charting rect
	    chartRect = Chartist.createChartRect(this.svg, options, defaultOptions.padding);
	    // Get biggest circle radius possible within chartRect
	    radius = Math.min(chartRect.width() / 2, chartRect.height() / 2);
	    // Calculate total of all series to get reference value or use total reference from optional options
	    totalDataSum = options.total || data.normalized.series.reduce(function(previousValue, currentValue) {
	      return previousValue + currentValue;
	    }, 0);

	    var donutWidth = Chartist.quantity(options.donutWidth);
	    if (donutWidth.unit === '%') {
	      donutWidth.value *= radius / 100;
	    }

	    // If this is a donut chart we need to adjust our radius to enable strokes to be drawn inside
	    // Unfortunately this is not possible with the current SVG Spec
	    // See this proposal for more details: http://lists.w3.org/Archives/Public/www-svg/2003Oct/0000.html
	    radius -= options.donut && !options.donutSolid ? donutWidth.value / 2  : 0;

	    // If labelPosition is set to `outside` or a donut chart is drawn then the label position is at the radius,
	    // if regular pie chart it's half of the radius
	    if(options.labelPosition === 'outside' || options.donut && !options.donutSolid) {
	      labelRadius = radius;
	    } else if(options.labelPosition === 'center') {
	      // If labelPosition is center we start with 0 and will later wait for the labelOffset
	      labelRadius = 0;
	    } else if(options.donutSolid) {
	      labelRadius = radius - donutWidth.value / 2;
	    } else {
	      // Default option is 'inside' where we use half the radius so the label will be placed in the center of the pie
	      // slice
	      labelRadius = radius / 2;
	    }
	    // Add the offset to the labelRadius where a negative offset means closed to the center of the chart
	    labelRadius += options.labelOffset;

	    // Calculate end angle based on total sum and current data value and offset with padding
	    var center = {
	      x: chartRect.x1 + chartRect.width() / 2,
	      y: chartRect.y2 + chartRect.height() / 2
	    };

	    // Check if there is only one non-zero value in the series array.
	    var hasSingleValInSeries = data.raw.series.filter(function(val) {
	      return val.hasOwnProperty('value') ? val.value !== 0 : val !== 0;
	    }).length === 1;

	    // Creating the series groups
	    data.raw.series.forEach(function(series, index) {
	      seriesGroups[index] = this.svg.elem('g', null, null);
	    }.bind(this));
	    //if we need to show labels we create the label group now
	    if(options.showLabel) {
	      labelsGroup = this.svg.elem('g', null, null);
	    }

	    // Draw the series
	    // initialize series groups
	    data.raw.series.forEach(function(series, index) {
	      // If current value is zero and we are ignoring empty values then skip to next value
	      if (data.normalized.series[index] === 0 && options.ignoreEmptyValues) return;

	      // If the series is an object and contains a name or meta data we add a custom attribute
	      seriesGroups[index].attr({
	        'ct:series-name': series.name
	      });

	      // Use series class from series data or if not set generate one
	      seriesGroups[index].addClass([
	        options.classNames.series,
	        (series.className || options.classNames.series + '-' + Chartist.alphaNumerate(index))
	      ].join(' '));

	      // If the whole dataset is 0 endAngle should be zero. Can't divide by 0.
	      var endAngle = (totalDataSum > 0 ? startAngle + data.normalized.series[index] / totalDataSum * 360 : 0);

	      // Use slight offset so there are no transparent hairline issues
	      var overlappigStartAngle = Math.max(0, startAngle - (index === 0 || hasSingleValInSeries ? 0 : 0.2));

	      // If we need to draw the arc for all 360 degrees we need to add a hack where we close the circle
	      // with Z and use 359.99 degrees
	      if(endAngle - overlappigStartAngle >= 359.99) {
	        endAngle = overlappigStartAngle + 359.99;
	      }

	      var start = Chartist.polarToCartesian(center.x, center.y, radius, overlappigStartAngle),
	        end = Chartist.polarToCartesian(center.x, center.y, radius, endAngle);

	      var innerStart,
	        innerEnd,
	        donutSolidRadius;

	      // Create a new path element for the pie chart. If this isn't a donut chart we should close the path for a correct stroke
	      var path = new Chartist.Svg.Path(!options.donut || options.donutSolid)
	        .move(end.x, end.y)
	        .arc(radius, radius, 0, endAngle - startAngle > 180, 0, start.x, start.y);

	      // If regular pie chart (no donut) we add a line to the center of the circle for completing the pie
	      if(!options.donut) {
	        path.line(center.x, center.y);
	      } else if (options.donutSolid) {
	        donutSolidRadius = radius - donutWidth.value;
	        innerStart = Chartist.polarToCartesian(center.x, center.y, donutSolidRadius, startAngle - (index === 0 || hasSingleValInSeries ? 0 : 0.2));
	        innerEnd = Chartist.polarToCartesian(center.x, center.y, donutSolidRadius, endAngle);
	        path.line(innerStart.x, innerStart.y);
	        path.arc(donutSolidRadius, donutSolidRadius, 0, endAngle - startAngle  > 180, 1, innerEnd.x, innerEnd.y);
	      }

	      // Create the SVG path
	      // If this is a donut chart we add the donut class, otherwise just a regular slice
	      var pathClassName = options.classNames.slicePie;
	      if (options.donut) {
	        pathClassName = options.classNames.sliceDonut;
	        if (options.donutSolid) {
	          pathClassName = options.classNames.sliceDonutSolid;
	        }
	      }
	      var pathElement = seriesGroups[index].elem('path', {
	        d: path.stringify()
	      }, pathClassName);

	      // Adding the pie series value to the path
	      pathElement.attr({
	        'ct:value': data.normalized.series[index],
	        'ct:meta': Chartist.serialize(series.meta)
	      });

	      // If this is a donut, we add the stroke-width as style attribute
	      if(options.donut && !options.donutSolid) {
	        pathElement._node.style.strokeWidth = donutWidth.value + 'px';
	      }

	      // Fire off draw event
	      this.eventEmitter.emit('draw', {
	        type: 'slice',
	        value: data.normalized.series[index],
	        totalDataSum: totalDataSum,
	        index: index,
	        meta: series.meta,
	        series: series,
	        group: seriesGroups[index],
	        element: pathElement,
	        path: path.clone(),
	        center: center,
	        radius: radius,
	        startAngle: startAngle,
	        endAngle: endAngle
	      });

	      // If we need to show labels we need to add the label for this slice now
	      if(options.showLabel) {
	        var labelPosition;
	        if(data.raw.series.length === 1) {
	          // If we have only 1 series, we can position the label in the center of the pie
	          labelPosition = {
	            x: center.x,
	            y: center.y
	          };
	        } else {
	          // Position at the labelRadius distance from center and between start and end angle
	          labelPosition = Chartist.polarToCartesian(
	            center.x,
	            center.y,
	            labelRadius,
	            startAngle + (endAngle - startAngle) / 2
	          );
	        }

	        var rawValue;
	        if(data.normalized.labels && !Chartist.isFalseyButZero(data.normalized.labels[index])) {
	          rawValue = data.normalized.labels[index];
	        } else {
	          rawValue = data.normalized.series[index];
	        }

	        var interpolatedValue = options.labelInterpolationFnc(rawValue, index);

	        if(interpolatedValue || interpolatedValue === 0) {
	          var labelElement = labelsGroup.elem('text', {
	            dx: labelPosition.x,
	            dy: labelPosition.y,
	            'text-anchor': determineAnchorPosition(center, labelPosition, options.labelDirection)
	          }, options.classNames.label).text('' + interpolatedValue);

	          // Fire off draw event
	          this.eventEmitter.emit('draw', {
	            type: 'label',
	            index: index,
	            group: labelsGroup,
	            element: labelElement,
	            text: '' + interpolatedValue,
	            x: labelPosition.x,
	            y: labelPosition.y
	          });
	        }
	      }

	      // Set next startAngle to current endAngle.
	      // (except for last slice)
	      startAngle = endAngle;
	    }.bind(this));

	    this.eventEmitter.emit('created', {
	      chartRect: chartRect,
	      svg: this.svg,
	      options: options
	    });
	  }

	  /**
	   * This method creates a new pie chart and returns an object that can be used to redraw the chart.
	   *
	   * @memberof Chartist.Pie
	   * @param {String|Node} query A selector query string or directly a DOM element
	   * @param {Object} data The data object in the pie chart needs to have a series property with a one dimensional data array. The values will be normalized against each other and don't necessarily need to be in percentage. The series property can also be an array of value objects that contain a value property and a className property to override the CSS class name for the series group.
	   * @param {Object} [options] The options object with options that override the default options. Check the examples for a detailed list.
	   * @param {Array} [responsiveOptions] Specify an array of responsive option arrays which are a media query and options object pair => [[mediaQueryString, optionsObject],[more...]]
	   * @return {Object} An object with a version and an update method to manually redraw the chart
	   *
	   * @example
	   * // Simple pie chart example with four series
	   * new Chartist.Pie('.ct-chart', {
	   *   series: [10, 2, 4, 3]
	   * });
	   *
	   * @example
	   * // Drawing a donut chart
	   * new Chartist.Pie('.ct-chart', {
	   *   series: [10, 2, 4, 3]
	   * }, {
	   *   donut: true
	   * });
	   *
	   * @example
	   * // Using donut, startAngle and total to draw a gauge chart
	   * new Chartist.Pie('.ct-chart', {
	   *   series: [20, 10, 30, 40]
	   * }, {
	   *   donut: true,
	   *   donutWidth: 20,
	   *   startAngle: 270,
	   *   total: 200
	   * });
	   *
	   * @example
	   * // Drawing a pie chart with padding and labels that are outside the pie
	   * new Chartist.Pie('.ct-chart', {
	   *   series: [20, 10, 30, 40]
	   * }, {
	   *   chartPadding: 30,
	   *   labelOffset: 50,
	   *   labelDirection: 'explode'
	   * });
	   *
	   * @example
	   * // Overriding the class names for individual series as well as a name and meta data.
	   * // The name will be written as ct:series-name attribute and the meta data will be serialized and written
	   * // to a ct:meta attribute.
	   * new Chartist.Pie('.ct-chart', {
	   *   series: [{
	   *     value: 20,
	   *     name: 'Series 1',
	   *     className: 'my-custom-class-one',
	   *     meta: 'Meta One'
	   *   }, {
	   *     value: 10,
	   *     name: 'Series 2',
	   *     className: 'my-custom-class-two',
	   *     meta: 'Meta Two'
	   *   }, {
	   *     value: 70,
	   *     name: 'Series 3',
	   *     className: 'my-custom-class-three',
	   *     meta: 'Meta Three'
	   *   }]
	   * });
	   */
	  function Pie(query, data, options, responsiveOptions) {
	    Chartist.Pie.super.constructor.call(this,
	      query,
	      data,
	      defaultOptions,
	      Chartist.extend({}, defaultOptions, options),
	      responsiveOptions);
	  }

	  // Creating pie chart type in Chartist namespace
	  Chartist.Pie = Chartist.Base.extend({
	    constructor: Pie,
	    createChart: createChart,
	    determineAnchorPosition: determineAnchorPosition
	  });

	}(this || commonjsGlobal, Chartist));

	return Chartist;

	}));
	});

	var Utils = {
	    escapeRegExp: function (string) {
	        return string.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&');
	    },

	    makeDiacriticsRegExp: function (string) {
	        var char;
	        var diacritics = {
	            'a': '[aáàăâǎåäãȧąāảȁạ]',
	            'b': '[bḃḅ]',
	            'c': '[cćĉčċç]',
	            'd': '[dďḋḑḍ]',
	            'e': '[eéèĕêěëẽėȩęēẻȅẹ]',
	            'g': '[gǵğĝǧġģḡ]',
	            'h': '[hĥȟḧḣḩḥ]',
	            'i': '[iiíìĭîǐïĩįīỉȉịı]',
	            'j': '[jĵǰ]',
	            'k': '[kḱǩķḳ]',
	            'l': '[lĺľļḷ]',
	            'm': '[mḿṁṃ]',
	            'n': '[nńǹňñṅņṇ]',
	            'o': '[oóòŏôǒöőõȯǿǫōỏȍơọ]',
	            'p': '[pṕṗ]',
	            'r': '[rŕřṙŗȑṛ]',
	            's': '[sśŝšṡşṣș]',
	            't': '[tťẗṫţṭț]',
	            'u': '[uúùŭûǔůüűũųūủȕưụ]',
	            'v': '[vṽṿ]',
	            'w': '[wẃẁŵẘẅẇẉ]',
	            'x': '[xẍẋ]',
	            'y': '[yýỳŷẙÿỹẏȳỷỵ]',
	            'z': '[zźẑžżẓ]'
	        };
	        for (char in diacritics) {
	            if (diacritics.hasOwnProperty(char)) {
	                string = string.split(char).join(diacritics[char]);
	                string = string.split(char.toUpperCase()).join(diacritics[char].toUpperCase());
	            }
	        }
	        return string;
	    },

	    slug: function (string) {
	        var char;
	        var translate = {
	            '\t': '', '\r': '', '!': '', '"': '', '#': '', '$': '', '%': '', '\'': '-', '(': '', ')': '', '*': '', '+': '', ',': '', '.': '', ':': '', ';': '', '<': '', '=': '', '>': '', '?': '', '@': '', '[': '', ']': '', '^': '', '`': '', '{': '', '|': '', '}': '', '¡': '', '£': '', '¤': '', '¥': '', '¦': '', '§': '', '«': '', '°': '', '»': '', '‘': '', '’': '', '“': '', '”': '', '\n': '-', ' ': '-', '-': '-', '–': '-', '—': '-', '/': '-', '\\': '-', '_': '-', '~': '-', 'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A', 'Å': 'A', 'Æ': 'Ae', 'Ç': 'C', 'Ð': 'D', 'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E', 'Ì': 'I', 'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ñ': 'N', 'Ò': 'O', 'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'O', 'Ø': 'O', 'Œ': 'Oe', 'Š': 'S', 'Þ': 'Th', 'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'U', 'Ý': 'Y', 'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'ae', 'å': 'a', 'æ': 'ae', '¢': 'c', 'ç': 'c', 'ð': 'd', 'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e', 'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i', 'ñ': 'n', 'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'oe', 'ø': 'o', 'œ': 'oe', 'š': 's', 'ß': 'ss', 'þ': 'th', 'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'ue', 'ý': 'y', 'ÿ': 'y', 'Ÿ': 'y'
	        };
	        string = string.toLowerCase();
	        for (char in translate) {
	            if (translate.hasOwnProperty(char)) {
	                string = string.split(char).join(translate[char]);
	            }
	        }
	        return string.replace(/[^a-z0-9-]/g, '').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
	    },

	    validateSlug: function (slug) {
	        return slug.toLowerCase().replace(' ', '-').replace(/[^a-z0-9-]/g, '');
	    },

	    debounce: function (callback, delay, leading) {
	        var context, args, result;
	        var timer = null;

	        function wrapper() {
	            context = this;
	            args = arguments;
	            if (timer) {
	                clearTimeout(timer);
	            }
	            if (leading && !timer) {
	                result = callback.apply(context, args);
	            }
	            timer = setTimeout(function () {
	                if (!leading) {
	                    result = callback.apply(context, args);
	                }
	                timer = null;
	            }, delay);
	            return result;
	        }

	        return wrapper;
	    },

	    throttle: function (callback, delay) {
	        var context, args, result;
	        var previous = 0;
	        var timer = null;

	        function wrapper() {
	            var now = Date.now();
	            var remaining;
	            if (previous === 0) {
	                previous = now;
	            }
	            remaining = (previous + delay) - now;
	            context = this;
	            args = arguments;
	            if (remaining <= 0 || remaining > delay) {
	                if (timer) {
	                    clearTimeout(timer);
	                    timer = null;
	                }
	                previous = now;
	                result = callback.apply(context, args);
	            } else if (!timer){
	                timer = setTimeout(function () {
	                    previous = Date.now();
	                    result = callback.apply(context, args);
	                    timer = null;
	                }, remaining);
	            }
	            return result;
	        }

	        return wrapper;
	    },

	    outerWidth: function (element) {
	        var width = element.offsetWidth;
	        var style = getComputedStyle(element);
	        width += parseInt(style.marginLeft) + parseInt(style.marginRight);
	        return width;
	    },

	    outerHeight: function (element) {
	        var height = element.offsetHeight;
	        var style = getComputedStyle(element);
	        height += parseInt(style.marginTop) + parseInt(style.marginBottom);
	        return height;
	    },

	    toggleElement: function (element, type) {
	        var display = element.style.display || getComputedStyle(element).display;
	        if (typeof type === 'undefined') {
	            type = 'block';
	        }
	        if (display === 'none') {
	            element.style.display = type;
	        } else {
	            element.style.display = 'none';
	        }
	    },

	    extendObject: function (target) {
	        var i, source, property;
	        target = target || {};
	        for (i = 1; i < arguments.length; i++) {
	            source = arguments[i];
	            for (property in source) {
	                target[property] = source[property];
	            }
	        }
	        return target;
	    },

	    serializeObject: function (object) {
	        var property;
	        var serialized = [];
	        for (property in object) {
	            if (object.hasOwnProperty(property)) {
	                serialized.push(encodeURIComponent(property) + '=' + encodeURIComponent(object[property]));
	            }
	        }
	        return serialized.join('&');
	    },

	    serializeForm: function (form) {
	        var field, i, j;
	        var serialized = [];
	        for (i = 0; i < form.elements.length; i++) {
	            field = form.elements[i];
	            if (field.name && !field.disabled && field.type !== 'file' && field.type !== 'reset' && field.type !== 'submit' && field.type !== 'button') {
	                if (field.type === 'select-multiple') {
	                    for (j = form.elements[i].options.length - 1; j >= 0; j--) {
	                        if (field.options[j].selected) {
	                            serialized.push(encodeURIComponent(field.name) + '=' + encodeURIComponent(field.options[j].value));
	                        }
	                    }
	                } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
	                    serialized.push(encodeURIComponent(field.name) + '=' + encodeURIComponent(field.value));
	                }
	            }
	        }
	        return serialized.join('&');
	    },

	    triggerEvent: function (target, type) {
	        var event;
	        try {
	            event = new Event(type);
	        } catch (error) {
	            // The browser doesn't support Event constructor
	            event = document.createEvent('HTMLEvents');
	            event.initEvent(type, true, true);
	        }
	        target.dispatchEvent(event);
	    },

	    triggerDownload: function (uri, csrfToken) {
	        var form = document.createElement('form');
	        var input = document.createElement('input');
	        form.action = uri;
	        form.method = 'post';
	        input.type = 'hidden';
	        input.name = 'csrf-token';
	        input.value = csrfToken;
	        form.appendChild(input);
	        document.body.appendChild(form);
	        form.submit();
	        document.body.removeChild(form);
	    },

	    longClick: function (element, callback, timeout, interval) {
	        var timer;
	        function clear() {
	            clearTimeout(timer);
	        }
	        element.addEventListener('mousedown', function (event) {
	            var context = this;
	            if (event.which !== 1) {
	                clear();
	            } else {
	                callback.call(context, event);
	                timer = setTimeout(function () {
	                    timer = setInterval(callback.bind(context, event), interval);
	                }, timeout);
	            }
	        });
	        element.addEventListener('mouseout', clear);
	        window.addEventListener('mouseup', clear);
	    }
	};

	function Tooltip(text, options) {
	    var defaults = {
	        container: document.body,
	        referenceElement: document.body,
	        position: 'top',
	        offset: {
	            x: 0, y: 0
	        },
	        delay: 500
	    };

	    var referenceElement = options.referenceElement;
	    var tooltip, timer;

	    options = Utils.extendObject({}, defaults, options);

	    // IE 10-11 support classList only on HTMLElement
	    if (referenceElement instanceof HTMLElement) {
	        // Remove tooltip when clicking on buttons
	        if (referenceElement.tagName.toLowerCase() === 'button' || referenceElement.classList.contains('button')) {
	            referenceElement.addEventListener('click', remove);
	        }
	    }

	    referenceElement.addEventListener('mouseout', remove);

	    function show() {
	        timer = setTimeout(function () {
	            var position;
	            tooltip = document.createElement('div');
	            tooltip.className = 'tooltip';
	            tooltip.setAttribute('role', 'tooltip');
	            tooltip.style.display = 'block';
	            tooltip.innerHTML = text;

	            options.container.appendChild(tooltip);

	            position = getTooltipPosition(tooltip);
	            tooltip.style.top = position.top + 'px';
	            tooltip.style.left = position.left + 'px';
	        }, options.delay);
	    }

	    function remove() {
	        clearTimeout(timer);
	        if (tooltip !== undefined && options.container.contains(tooltip)) {
	            options.container.removeChild(tooltip);
	        }
	    }

	    function getTooltipPosition(tooltip) {
	        var rect = referenceElement.getBoundingClientRect();
	        var top = rect.top + window.pageYOffset;
	        var left = rect.left + window.pageXOffset;

	        var hw = (rect.width - tooltip.offsetWidth) / 2;
	        var hh = (rect.height - tooltip.offsetHeight) / 2;

	        switch (options.position) {
	        case 'top':
	            return {
	                top: Math.round(top - tooltip.offsetHeight + options.offset.y),
	                left: Math.round(left + hw + options.offset.x)
	            };
	        case 'right':
	            return {
	                top: Math.round(top + hh + options.offset.y),
	                left: Math.round(left + referenceElement.offsetWidth + options.offset.x)
	            };
	        case 'bottom':
	            return {
	                top: Math.round(top + referenceElement.offsetHeight + options.offset.y),
	                left: Math.round(left + hw + options.offset.x)
	            };
	        case 'left':
	            return {
	                top: Math.round(top + hh + options.offset.y),
	                left: Math.round(left - tooltip.offsetWidth + options.offset.x)
	            };
	        }
	    }

	    return {
	        show: show,
	        remove: remove
	    };
	}

	function Chart(element, data) {
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

	    var chart = new chartist.Line(element, data, options);

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

	function Notification(text, type, interval, options) {
	    var defaults = {
	        newestOnTop: true,
	        fadeOutDelay: 300,
	        mouseleaveDelay: 1000
	    };

	    var container = $('.notification-container');

	    var notification, timer;

	    options = Utils.extendObject({}, defaults, options);

	    function show() {
	        if (!container) {
	            container = document.createElement('div');
	            container.className = 'notification-container';
	            document.body.appendChild(container);
	        }

	        notification = document.createElement('div');
	        notification.className = 'notification notification-' + type;
	        notification.innerHTML = text;

	        if (options.newestOnTop && container.childNodes.length > 0) {
	            container.insertBefore(notification, container.childNodes[0]);
	        } else {
	            container.appendChild(notification);
	        }

	        timer = setTimeout(remove, interval);

	        notification.addEventListener('click', remove);

	        notification.addEventListener('mouseenter', function () {
	            clearTimeout(timer);
	        });

	        notification.addEventListener('mouseleave', function () {
	            timer = setTimeout(remove, options.mouseleaveDelay);
	        });
	    }

	    function remove() {
	        notification.classList.add('fadeout');

	        setTimeout(function () {
	            if (notification.parentNode) {
	                container.removeChild(notification);
	            }
	            if (container.childNodes.length < 1) {
	                if (container.parentNode) {
	                    document.body.removeChild(container);
	                }
	                container = null;
	            }
	        }, options.fadeOutDelay);
	    }

	    return {
	        show: show,
	        remove: remove
	    };
	}

	function Request(options, callback) {

	    var request = new XMLHttpRequest();

	    var handler, response, code;

	    request.open(options.method, options.url, true);
	    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	    request.send(Utils.serializeObject(options.data));

	    if (typeof callback === 'function') {
	        handler = function () {
	            response = JSON.parse(this.response);
	            code = response.code || this.status;
	            if (parseInt(code) === 400) {
	                location.reload();
	            } else {
	                callback(response, request);
	            }
	        };
	        request.onload = handler;
	        request.onerror = handler;
	    }

	    return request;
	}

	var Dashboard = {
	    init: function () {
	        var clearCacheCommand = $('[data-command=clear-cache]');
	        var makeBackupCommand = $('[data-command=make-backup]');

	        if (clearCacheCommand) {
	            clearCacheCommand.addEventListener('click', function () {
	                Request({
	                    method: 'POST',
	                    url: Formwork.config.baseUri + 'cache/clear/',
	                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
	                }, function (response) {
	                    var notification = new Notification(response.message, response.status, 5000);
	                    notification.show();
	                });
	            });
	        }

	        if (makeBackupCommand) {
	            makeBackupCommand.addEventListener('click', function () {
	                var button = this;
	                button.setAttribute('disabled', '');
	                Request({
	                    method: 'POST',
	                    url: Formwork.config.baseUri + 'backup/make/',
	                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
	                }, function (response) {
	                    var notification = new Notification(response.message, response.status, 5000);
	                    notification.show();
	                    setTimeout(function () {
	                        if (response.status === 'success') {
	                            Utils.triggerDownload(response.data.uri, $('meta[name=csrf-token]').getAttribute('content'));
	                        }
	                        button.removeAttribute('disabled');
	                    }, 1000);
	                });
	            });
	        }
	    }
	};

	var Dropdowns = {
	    init: function () {
	        if ($('.dropdown')) {
	            document.addEventListener('click', function (event) {
	                var button = event.target.closest('.dropdown-button');
	                var dropdown, isVisible;
	                if (button) {
	                    dropdown = document.getElementById(button.getAttribute('data-dropdown'));
	                    isVisible = getComputedStyle(dropdown).display !== 'none';
	                    event.preventDefault();
	                }
	                $$('.dropdown-menu').forEach(function (element) {
	                    element.style.display = '';
	                });
	                if (dropdown && !isVisible) {
	                    dropdown.style.display = 'block';
	                }
	            });
	        }
	    }
	};

	/**!
	 * Sortable 1.10.2
	 * @author	RubaXa   <trash@rubaxa.org>
	 * @author	owenm    <owen23355@gmail.com>
	 * @license MIT
	 */
	function _typeof(obj) {
	  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
	    _typeof = function (obj) {
	      return typeof obj;
	    };
	  } else {
	    _typeof = function (obj) {
	      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
	    };
	  }

	  return _typeof(obj);
	}

	function _defineProperty(obj, key, value) {
	  if (key in obj) {
	    Object.defineProperty(obj, key, {
	      value: value,
	      enumerable: true,
	      configurable: true,
	      writable: true
	    });
	  } else {
	    obj[key] = value;
	  }

	  return obj;
	}

	function _extends() {
	  _extends = Object.assign || function (target) {
	    for (var i = 1; i < arguments.length; i++) {
	      var source = arguments[i];

	      for (var key in source) {
	        if (Object.prototype.hasOwnProperty.call(source, key)) {
	          target[key] = source[key];
	        }
	      }
	    }

	    return target;
	  };

	  return _extends.apply(this, arguments);
	}

	function _objectSpread(target) {
	  for (var i = 1; i < arguments.length; i++) {
	    var source = arguments[i] != null ? arguments[i] : {};
	    var ownKeys = Object.keys(source);

	    if (typeof Object.getOwnPropertySymbols === 'function') {
	      ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) {
	        return Object.getOwnPropertyDescriptor(source, sym).enumerable;
	      }));
	    }

	    ownKeys.forEach(function (key) {
	      _defineProperty(target, key, source[key]);
	    });
	  }

	  return target;
	}

	function _objectWithoutPropertiesLoose(source, excluded) {
	  if (source == null) return {};
	  var target = {};
	  var sourceKeys = Object.keys(source);
	  var key, i;

	  for (i = 0; i < sourceKeys.length; i++) {
	    key = sourceKeys[i];
	    if (excluded.indexOf(key) >= 0) continue;
	    target[key] = source[key];
	  }

	  return target;
	}

	function _objectWithoutProperties(source, excluded) {
	  if (source == null) return {};

	  var target = _objectWithoutPropertiesLoose(source, excluded);

	  var key, i;

	  if (Object.getOwnPropertySymbols) {
	    var sourceSymbolKeys = Object.getOwnPropertySymbols(source);

	    for (i = 0; i < sourceSymbolKeys.length; i++) {
	      key = sourceSymbolKeys[i];
	      if (excluded.indexOf(key) >= 0) continue;
	      if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
	      target[key] = source[key];
	    }
	  }

	  return target;
	}

	var version = "1.10.2";

	function userAgent(pattern) {
	  if (typeof window !== 'undefined' && window.navigator) {
	    return !!
	    /*@__PURE__*/
	    navigator.userAgent.match(pattern);
	  }
	}

	var IE11OrLess = userAgent(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i);
	var Edge = userAgent(/Edge/i);
	var FireFox = userAgent(/firefox/i);
	var Safari = userAgent(/safari/i) && !userAgent(/chrome/i) && !userAgent(/android/i);
	var IOS = userAgent(/iP(ad|od|hone)/i);
	var ChromeForAndroid = userAgent(/chrome/i) && userAgent(/android/i);

	var captureMode = {
	  capture: false,
	  passive: false
	};

	function on(el, event, fn) {
	  el.addEventListener(event, fn, !IE11OrLess && captureMode);
	}

	function off(el, event, fn) {
	  el.removeEventListener(event, fn, !IE11OrLess && captureMode);
	}

	function matches(
	/**HTMLElement*/
	el,
	/**String*/
	selector) {
	  if (!selector) return;
	  selector[0] === '>' && (selector = selector.substring(1));

	  if (el) {
	    try {
	      if (el.matches) {
	        return el.matches(selector);
	      } else if (el.msMatchesSelector) {
	        return el.msMatchesSelector(selector);
	      } else if (el.webkitMatchesSelector) {
	        return el.webkitMatchesSelector(selector);
	      }
	    } catch (_) {
	      return false;
	    }
	  }

	  return false;
	}

	function getParentOrHost(el) {
	  return el.host && el !== document && el.host.nodeType ? el.host : el.parentNode;
	}

	function closest(
	/**HTMLElement*/
	el,
	/**String*/
	selector,
	/**HTMLElement*/
	ctx, includeCTX) {
	  if (el) {
	    ctx = ctx || document;

	    do {
	      if (selector != null && (selector[0] === '>' ? el.parentNode === ctx && matches(el, selector) : matches(el, selector)) || includeCTX && el === ctx) {
	        return el;
	      }

	      if (el === ctx) break;
	      /* jshint boss:true */
	    } while (el = getParentOrHost(el));
	  }

	  return null;
	}

	var R_SPACE = /\s+/g;

	function toggleClass(el, name, state) {
	  if (el && name) {
	    if (el.classList) {
	      el.classList[state ? 'add' : 'remove'](name);
	    } else {
	      var className = (' ' + el.className + ' ').replace(R_SPACE, ' ').replace(' ' + name + ' ', ' ');
	      el.className = (className + (state ? ' ' + name : '')).replace(R_SPACE, ' ');
	    }
	  }
	}

	function css(el, prop, val) {
	  var style = el && el.style;

	  if (style) {
	    if (val === void 0) {
	      if (document.defaultView && document.defaultView.getComputedStyle) {
	        val = document.defaultView.getComputedStyle(el, '');
	      } else if (el.currentStyle) {
	        val = el.currentStyle;
	      }

	      return prop === void 0 ? val : val[prop];
	    } else {
	      if (!(prop in style) && prop.indexOf('webkit') === -1) {
	        prop = '-webkit-' + prop;
	      }

	      style[prop] = val + (typeof val === 'string' ? '' : 'px');
	    }
	  }
	}

	function matrix(el, selfOnly) {
	  var appliedTransforms = '';

	  if (typeof el === 'string') {
	    appliedTransforms = el;
	  } else {
	    do {
	      var transform = css(el, 'transform');

	      if (transform && transform !== 'none') {
	        appliedTransforms = transform + ' ' + appliedTransforms;
	      }
	      /* jshint boss:true */

	    } while (!selfOnly && (el = el.parentNode));
	  }

	  var matrixFn = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
	  /*jshint -W056 */

	  return matrixFn && new matrixFn(appliedTransforms);
	}

	function find(ctx, tagName, iterator) {
	  if (ctx) {
	    var list = ctx.getElementsByTagName(tagName),
	        i = 0,
	        n = list.length;

	    if (iterator) {
	      for (; i < n; i++) {
	        iterator(list[i], i);
	      }
	    }

	    return list;
	  }

	  return [];
	}

	function getWindowScrollingElement() {
	  var scrollingElement = document.scrollingElement;

	  if (scrollingElement) {
	    return scrollingElement;
	  } else {
	    return document.documentElement;
	  }
	}
	/**
	 * Returns the "bounding client rect" of given element
	 * @param  {HTMLElement} el                       The element whose boundingClientRect is wanted
	 * @param  {[Boolean]} relativeToContainingBlock  Whether the rect should be relative to the containing block of (including) the container
	 * @param  {[Boolean]} relativeToNonStaticParent  Whether the rect should be relative to the relative parent of (including) the contaienr
	 * @param  {[Boolean]} undoScale                  Whether the container's scale() should be undone
	 * @param  {[HTMLElement]} container              The parent the element will be placed in
	 * @return {Object}                               The boundingClientRect of el, with specified adjustments
	 */


	function getRect(el, relativeToContainingBlock, relativeToNonStaticParent, undoScale, container) {
	  if (!el.getBoundingClientRect && el !== window) return;
	  var elRect, top, left, bottom, right, height, width;

	  if (el !== window && el !== getWindowScrollingElement()) {
	    elRect = el.getBoundingClientRect();
	    top = elRect.top;
	    left = elRect.left;
	    bottom = elRect.bottom;
	    right = elRect.right;
	    height = elRect.height;
	    width = elRect.width;
	  } else {
	    top = 0;
	    left = 0;
	    bottom = window.innerHeight;
	    right = window.innerWidth;
	    height = window.innerHeight;
	    width = window.innerWidth;
	  }

	  if ((relativeToContainingBlock || relativeToNonStaticParent) && el !== window) {
	    // Adjust for translate()
	    container = container || el.parentNode; // solves #1123 (see: https://stackoverflow.com/a/37953806/6088312)
	    // Not needed on <= IE11

	    if (!IE11OrLess) {
	      do {
	        if (container && container.getBoundingClientRect && (css(container, 'transform') !== 'none' || relativeToNonStaticParent && css(container, 'position') !== 'static')) {
	          var containerRect = container.getBoundingClientRect(); // Set relative to edges of padding box of container

	          top -= containerRect.top + parseInt(css(container, 'border-top-width'));
	          left -= containerRect.left + parseInt(css(container, 'border-left-width'));
	          bottom = top + elRect.height;
	          right = left + elRect.width;
	          break;
	        }
	        /* jshint boss:true */

	      } while (container = container.parentNode);
	    }
	  }

	  if (undoScale && el !== window) {
	    // Adjust for scale()
	    var elMatrix = matrix(container || el),
	        scaleX = elMatrix && elMatrix.a,
	        scaleY = elMatrix && elMatrix.d;

	    if (elMatrix) {
	      top /= scaleY;
	      left /= scaleX;
	      width /= scaleX;
	      height /= scaleY;
	      bottom = top + height;
	      right = left + width;
	    }
	  }

	  return {
	    top: top,
	    left: left,
	    bottom: bottom,
	    right: right,
	    width: width,
	    height: height
	  };
	}
	/**
	 * Checks if a side of an element is scrolled past a side of its parents
	 * @param  {HTMLElement}  el           The element who's side being scrolled out of view is in question
	 * @param  {String}       elSide       Side of the element in question ('top', 'left', 'right', 'bottom')
	 * @param  {String}       parentSide   Side of the parent in question ('top', 'left', 'right', 'bottom')
	 * @return {HTMLElement}               The parent scroll element that the el's side is scrolled past, or null if there is no such element
	 */


	function isScrolledPast(el, elSide, parentSide) {
	  var parent = getParentAutoScrollElement(el, true),
	      elSideVal = getRect(el)[elSide];
	  /* jshint boss:true */

	  while (parent) {
	    var parentSideVal = getRect(parent)[parentSide],
	        visible = void 0;

	    if (parentSide === 'top' || parentSide === 'left') {
	      visible = elSideVal >= parentSideVal;
	    } else {
	      visible = elSideVal <= parentSideVal;
	    }

	    if (!visible) return parent;
	    if (parent === getWindowScrollingElement()) break;
	    parent = getParentAutoScrollElement(parent, false);
	  }

	  return false;
	}
	/**
	 * Gets nth child of el, ignoring hidden children, sortable's elements (does not ignore clone if it's visible)
	 * and non-draggable elements
	 * @param  {HTMLElement} el       The parent element
	 * @param  {Number} childNum      The index of the child
	 * @param  {Object} options       Parent Sortable's options
	 * @return {HTMLElement}          The child at index childNum, or null if not found
	 */


	function getChild(el, childNum, options) {
	  var currentChild = 0,
	      i = 0,
	      children = el.children;

	  while (i < children.length) {
	    if (children[i].style.display !== 'none' && children[i] !== Sortable.ghost && children[i] !== Sortable.dragged && closest(children[i], options.draggable, el, false)) {
	      if (currentChild === childNum) {
	        return children[i];
	      }

	      currentChild++;
	    }

	    i++;
	  }

	  return null;
	}
	/**
	 * Gets the last child in the el, ignoring ghostEl or invisible elements (clones)
	 * @param  {HTMLElement} el       Parent element
	 * @param  {selector} selector    Any other elements that should be ignored
	 * @return {HTMLElement}          The last child, ignoring ghostEl
	 */


	function lastChild(el, selector) {
	  var last = el.lastElementChild;

	  while (last && (last === Sortable.ghost || css(last, 'display') === 'none' || selector && !matches(last, selector))) {
	    last = last.previousElementSibling;
	  }

	  return last || null;
	}
	/**
	 * Returns the index of an element within its parent for a selected set of
	 * elements
	 * @param  {HTMLElement} el
	 * @param  {selector} selector
	 * @return {number}
	 */


	function index(el, selector) {
	  var index = 0;

	  if (!el || !el.parentNode) {
	    return -1;
	  }
	  /* jshint boss:true */


	  while (el = el.previousElementSibling) {
	    if (el.nodeName.toUpperCase() !== 'TEMPLATE' && el !== Sortable.clone && (!selector || matches(el, selector))) {
	      index++;
	    }
	  }

	  return index;
	}
	/**
	 * Returns the scroll offset of the given element, added with all the scroll offsets of parent elements.
	 * The value is returned in real pixels.
	 * @param  {HTMLElement} el
	 * @return {Array}             Offsets in the format of [left, top]
	 */


	function getRelativeScrollOffset(el) {
	  var offsetLeft = 0,
	      offsetTop = 0,
	      winScroller = getWindowScrollingElement();

	  if (el) {
	    do {
	      var elMatrix = matrix(el),
	          scaleX = elMatrix.a,
	          scaleY = elMatrix.d;
	      offsetLeft += el.scrollLeft * scaleX;
	      offsetTop += el.scrollTop * scaleY;
	    } while (el !== winScroller && (el = el.parentNode));
	  }

	  return [offsetLeft, offsetTop];
	}
	/**
	 * Returns the index of the object within the given array
	 * @param  {Array} arr   Array that may or may not hold the object
	 * @param  {Object} obj  An object that has a key-value pair unique to and identical to a key-value pair in the object you want to find
	 * @return {Number}      The index of the object in the array, or -1
	 */


	function indexOfObject(arr, obj) {
	  for (var i in arr) {
	    if (!arr.hasOwnProperty(i)) continue;

	    for (var key in obj) {
	      if (obj.hasOwnProperty(key) && obj[key] === arr[i][key]) return Number(i);
	    }
	  }

	  return -1;
	}

	function getParentAutoScrollElement(el, includeSelf) {
	  // skip to window
	  if (!el || !el.getBoundingClientRect) return getWindowScrollingElement();
	  var elem = el;
	  var gotSelf = false;

	  do {
	    // we don't need to get elem css if it isn't even overflowing in the first place (performance)
	    if (elem.clientWidth < elem.scrollWidth || elem.clientHeight < elem.scrollHeight) {
	      var elemCSS = css(elem);

	      if (elem.clientWidth < elem.scrollWidth && (elemCSS.overflowX == 'auto' || elemCSS.overflowX == 'scroll') || elem.clientHeight < elem.scrollHeight && (elemCSS.overflowY == 'auto' || elemCSS.overflowY == 'scroll')) {
	        if (!elem.getBoundingClientRect || elem === document.body) return getWindowScrollingElement();
	        if (gotSelf || includeSelf) return elem;
	        gotSelf = true;
	      }
	    }
	    /* jshint boss:true */

	  } while (elem = elem.parentNode);

	  return getWindowScrollingElement();
	}

	function extend(dst, src) {
	  if (dst && src) {
	    for (var key in src) {
	      if (src.hasOwnProperty(key)) {
	        dst[key] = src[key];
	      }
	    }
	  }

	  return dst;
	}

	function isRectEqual(rect1, rect2) {
	  return Math.round(rect1.top) === Math.round(rect2.top) && Math.round(rect1.left) === Math.round(rect2.left) && Math.round(rect1.height) === Math.round(rect2.height) && Math.round(rect1.width) === Math.round(rect2.width);
	}

	var _throttleTimeout;

	function throttle(callback, ms) {
	  return function () {
	    if (!_throttleTimeout) {
	      var args = arguments,
	          _this = this;

	      if (args.length === 1) {
	        callback.call(_this, args[0]);
	      } else {
	        callback.apply(_this, args);
	      }

	      _throttleTimeout = setTimeout(function () {
	        _throttleTimeout = void 0;
	      }, ms);
	    }
	  };
	}

	function cancelThrottle() {
	  clearTimeout(_throttleTimeout);
	  _throttleTimeout = void 0;
	}

	function scrollBy(el, x, y) {
	  el.scrollLeft += x;
	  el.scrollTop += y;
	}

	function clone(el) {
	  var Polymer = window.Polymer;
	  var $ = window.jQuery || window.Zepto;

	  if (Polymer && Polymer.dom) {
	    return Polymer.dom(el).cloneNode(true);
	  } else if ($) {
	    return $(el).clone(true)[0];
	  } else {
	    return el.cloneNode(true);
	  }
	}

	var expando = 'Sortable' + new Date().getTime();

	function AnimationStateManager() {
	  var animationStates = [],
	      animationCallbackId;
	  return {
	    captureAnimationState: function captureAnimationState() {
	      animationStates = [];
	      if (!this.options.animation) return;
	      var children = [].slice.call(this.el.children);
	      children.forEach(function (child) {
	        if (css(child, 'display') === 'none' || child === Sortable.ghost) return;
	        animationStates.push({
	          target: child,
	          rect: getRect(child)
	        });

	        var fromRect = _objectSpread({}, animationStates[animationStates.length - 1].rect); // If animating: compensate for current animation


	        if (child.thisAnimationDuration) {
	          var childMatrix = matrix(child, true);

	          if (childMatrix) {
	            fromRect.top -= childMatrix.f;
	            fromRect.left -= childMatrix.e;
	          }
	        }

	        child.fromRect = fromRect;
	      });
	    },
	    addAnimationState: function addAnimationState(state) {
	      animationStates.push(state);
	    },
	    removeAnimationState: function removeAnimationState(target) {
	      animationStates.splice(indexOfObject(animationStates, {
	        target: target
	      }), 1);
	    },
	    animateAll: function animateAll(callback) {
	      var _this = this;

	      if (!this.options.animation) {
	        clearTimeout(animationCallbackId);
	        if (typeof callback === 'function') callback();
	        return;
	      }

	      var animating = false,
	          animationTime = 0;
	      animationStates.forEach(function (state) {
	        var time = 0,
	            target = state.target,
	            fromRect = target.fromRect,
	            toRect = getRect(target),
	            prevFromRect = target.prevFromRect,
	            prevToRect = target.prevToRect,
	            animatingRect = state.rect,
	            targetMatrix = matrix(target, true);

	        if (targetMatrix) {
	          // Compensate for current animation
	          toRect.top -= targetMatrix.f;
	          toRect.left -= targetMatrix.e;
	        }

	        target.toRect = toRect;

	        if (target.thisAnimationDuration) {
	          // Could also check if animatingRect is between fromRect and toRect
	          if (isRectEqual(prevFromRect, toRect) && !isRectEqual(fromRect, toRect) && // Make sure animatingRect is on line between toRect & fromRect
	          (animatingRect.top - toRect.top) / (animatingRect.left - toRect.left) === (fromRect.top - toRect.top) / (fromRect.left - toRect.left)) {
	            // If returning to same place as started from animation and on same axis
	            time = calculateRealTime(animatingRect, prevFromRect, prevToRect, _this.options);
	          }
	        } // if fromRect != toRect: animate


	        if (!isRectEqual(toRect, fromRect)) {
	          target.prevFromRect = fromRect;
	          target.prevToRect = toRect;

	          if (!time) {
	            time = _this.options.animation;
	          }

	          _this.animate(target, animatingRect, toRect, time);
	        }

	        if (time) {
	          animating = true;
	          animationTime = Math.max(animationTime, time);
	          clearTimeout(target.animationResetTimer);
	          target.animationResetTimer = setTimeout(function () {
	            target.animationTime = 0;
	            target.prevFromRect = null;
	            target.fromRect = null;
	            target.prevToRect = null;
	            target.thisAnimationDuration = null;
	          }, time);
	          target.thisAnimationDuration = time;
	        }
	      });
	      clearTimeout(animationCallbackId);

	      if (!animating) {
	        if (typeof callback === 'function') callback();
	      } else {
	        animationCallbackId = setTimeout(function () {
	          if (typeof callback === 'function') callback();
	        }, animationTime);
	      }

	      animationStates = [];
	    },
	    animate: function animate(target, currentRect, toRect, duration) {
	      if (duration) {
	        css(target, 'transition', '');
	        css(target, 'transform', '');
	        var elMatrix = matrix(this.el),
	            scaleX = elMatrix && elMatrix.a,
	            scaleY = elMatrix && elMatrix.d,
	            translateX = (currentRect.left - toRect.left) / (scaleX || 1),
	            translateY = (currentRect.top - toRect.top) / (scaleY || 1);
	        target.animatingX = !!translateX;
	        target.animatingY = !!translateY;
	        css(target, 'transform', 'translate3d(' + translateX + 'px,' + translateY + 'px,0)');
	        repaint(target); // repaint

	        css(target, 'transition', 'transform ' + duration + 'ms' + (this.options.easing ? ' ' + this.options.easing : ''));
	        css(target, 'transform', 'translate3d(0,0,0)');
	        typeof target.animated === 'number' && clearTimeout(target.animated);
	        target.animated = setTimeout(function () {
	          css(target, 'transition', '');
	          css(target, 'transform', '');
	          target.animated = false;
	          target.animatingX = false;
	          target.animatingY = false;
	        }, duration);
	      }
	    }
	  };
	}

	function repaint(target) {
	  return target.offsetWidth;
	}

	function calculateRealTime(animatingRect, fromRect, toRect, options) {
	  return Math.sqrt(Math.pow(fromRect.top - animatingRect.top, 2) + Math.pow(fromRect.left - animatingRect.left, 2)) / Math.sqrt(Math.pow(fromRect.top - toRect.top, 2) + Math.pow(fromRect.left - toRect.left, 2)) * options.animation;
	}

	var plugins = [];
	var defaults = {
	  initializeByDefault: true
	};
	var PluginManager = {
	  mount: function mount(plugin) {
	    // Set default static properties
	    for (var option in defaults) {
	      if (defaults.hasOwnProperty(option) && !(option in plugin)) {
	        plugin[option] = defaults[option];
	      }
	    }

	    plugins.push(plugin);
	  },
	  pluginEvent: function pluginEvent(eventName, sortable, evt) {
	    var _this = this;

	    this.eventCanceled = false;

	    evt.cancel = function () {
	      _this.eventCanceled = true;
	    };

	    var eventNameGlobal = eventName + 'Global';
	    plugins.forEach(function (plugin) {
	      if (!sortable[plugin.pluginName]) return; // Fire global events if it exists in this sortable

	      if (sortable[plugin.pluginName][eventNameGlobal]) {
	        sortable[plugin.pluginName][eventNameGlobal](_objectSpread({
	          sortable: sortable
	        }, evt));
	      } // Only fire plugin event if plugin is enabled in this sortable,
	      // and plugin has event defined


	      if (sortable.options[plugin.pluginName] && sortable[plugin.pluginName][eventName]) {
	        sortable[plugin.pluginName][eventName](_objectSpread({
	          sortable: sortable
	        }, evt));
	      }
	    });
	  },
	  initializePlugins: function initializePlugins(sortable, el, defaults, options) {
	    plugins.forEach(function (plugin) {
	      var pluginName = plugin.pluginName;
	      if (!sortable.options[pluginName] && !plugin.initializeByDefault) return;
	      var initialized = new plugin(sortable, el, sortable.options);
	      initialized.sortable = sortable;
	      initialized.options = sortable.options;
	      sortable[pluginName] = initialized; // Add default options from plugin

	      _extends(defaults, initialized.defaults);
	    });

	    for (var option in sortable.options) {
	      if (!sortable.options.hasOwnProperty(option)) continue;
	      var modified = this.modifyOption(sortable, option, sortable.options[option]);

	      if (typeof modified !== 'undefined') {
	        sortable.options[option] = modified;
	      }
	    }
	  },
	  getEventProperties: function getEventProperties(name, sortable) {
	    var eventProperties = {};
	    plugins.forEach(function (plugin) {
	      if (typeof plugin.eventProperties !== 'function') return;

	      _extends(eventProperties, plugin.eventProperties.call(sortable[plugin.pluginName], name));
	    });
	    return eventProperties;
	  },
	  modifyOption: function modifyOption(sortable, name, value) {
	    var modifiedValue;
	    plugins.forEach(function (plugin) {
	      // Plugin must exist on the Sortable
	      if (!sortable[plugin.pluginName]) return; // If static option listener exists for this option, call in the context of the Sortable's instance of this plugin

	      if (plugin.optionListeners && typeof plugin.optionListeners[name] === 'function') {
	        modifiedValue = plugin.optionListeners[name].call(sortable[plugin.pluginName], value);
	      }
	    });
	    return modifiedValue;
	  }
	};

	function dispatchEvent(_ref) {
	  var sortable = _ref.sortable,
	      rootEl = _ref.rootEl,
	      name = _ref.name,
	      targetEl = _ref.targetEl,
	      cloneEl = _ref.cloneEl,
	      toEl = _ref.toEl,
	      fromEl = _ref.fromEl,
	      oldIndex = _ref.oldIndex,
	      newIndex = _ref.newIndex,
	      oldDraggableIndex = _ref.oldDraggableIndex,
	      newDraggableIndex = _ref.newDraggableIndex,
	      originalEvent = _ref.originalEvent,
	      putSortable = _ref.putSortable,
	      extraEventProperties = _ref.extraEventProperties;
	  sortable = sortable || rootEl && rootEl[expando];
	  if (!sortable) return;
	  var evt,
	      options = sortable.options,
	      onName = 'on' + name.charAt(0).toUpperCase() + name.substr(1); // Support for new CustomEvent feature

	  if (window.CustomEvent && !IE11OrLess && !Edge) {
	    evt = new CustomEvent(name, {
	      bubbles: true,
	      cancelable: true
	    });
	  } else {
	    evt = document.createEvent('Event');
	    evt.initEvent(name, true, true);
	  }

	  evt.to = toEl || rootEl;
	  evt.from = fromEl || rootEl;
	  evt.item = targetEl || rootEl;
	  evt.clone = cloneEl;
	  evt.oldIndex = oldIndex;
	  evt.newIndex = newIndex;
	  evt.oldDraggableIndex = oldDraggableIndex;
	  evt.newDraggableIndex = newDraggableIndex;
	  evt.originalEvent = originalEvent;
	  evt.pullMode = putSortable ? putSortable.lastPutMode : undefined;

	  var allEventProperties = _objectSpread({}, extraEventProperties, PluginManager.getEventProperties(name, sortable));

	  for (var option in allEventProperties) {
	    evt[option] = allEventProperties[option];
	  }

	  if (rootEl) {
	    rootEl.dispatchEvent(evt);
	  }

	  if (options[onName]) {
	    options[onName].call(sortable, evt);
	  }
	}

	var pluginEvent = function pluginEvent(eventName, sortable) {
	  var _ref = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {},
	      originalEvent = _ref.evt,
	      data = _objectWithoutProperties(_ref, ["evt"]);

	  PluginManager.pluginEvent.bind(Sortable)(eventName, sortable, _objectSpread({
	    dragEl: dragEl,
	    parentEl: parentEl,
	    ghostEl: ghostEl,
	    rootEl: rootEl,
	    nextEl: nextEl,
	    lastDownEl: lastDownEl,
	    cloneEl: cloneEl,
	    cloneHidden: cloneHidden,
	    dragStarted: moved,
	    putSortable: putSortable,
	    activeSortable: Sortable.active,
	    originalEvent: originalEvent,
	    oldIndex: oldIndex,
	    oldDraggableIndex: oldDraggableIndex,
	    newIndex: newIndex,
	    newDraggableIndex: newDraggableIndex,
	    hideGhostForTarget: _hideGhostForTarget,
	    unhideGhostForTarget: _unhideGhostForTarget,
	    cloneNowHidden: function cloneNowHidden() {
	      cloneHidden = true;
	    },
	    cloneNowShown: function cloneNowShown() {
	      cloneHidden = false;
	    },
	    dispatchSortableEvent: function dispatchSortableEvent(name) {
	      _dispatchEvent({
	        sortable: sortable,
	        name: name,
	        originalEvent: originalEvent
	      });
	    }
	  }, data));
	};

	function _dispatchEvent(info) {
	  dispatchEvent(_objectSpread({
	    putSortable: putSortable,
	    cloneEl: cloneEl,
	    targetEl: dragEl,
	    rootEl: rootEl,
	    oldIndex: oldIndex,
	    oldDraggableIndex: oldDraggableIndex,
	    newIndex: newIndex,
	    newDraggableIndex: newDraggableIndex
	  }, info));
	}

	var dragEl,
	    parentEl,
	    ghostEl,
	    rootEl,
	    nextEl,
	    lastDownEl,
	    cloneEl,
	    cloneHidden,
	    oldIndex,
	    newIndex,
	    oldDraggableIndex,
	    newDraggableIndex,
	    activeGroup,
	    putSortable,
	    awaitingDragStarted = false,
	    ignoreNextClick = false,
	    sortables = [],
	    tapEvt,
	    touchEvt,
	    lastDx,
	    lastDy,
	    tapDistanceLeft,
	    tapDistanceTop,
	    moved,
	    lastTarget,
	    lastDirection,
	    pastFirstInvertThresh = false,
	    isCircumstantialInvert = false,
	    targetMoveDistance,
	    // For positioning ghost absolutely
	ghostRelativeParent,
	    ghostRelativeParentInitialScroll = [],
	    // (left, top)
	_silent = false,
	    savedInputChecked = [];
	/** @const */

	var documentExists = typeof document !== 'undefined',
	    PositionGhostAbsolutely = IOS,
	    CSSFloatProperty = Edge || IE11OrLess ? 'cssFloat' : 'float',
	    // This will not pass for IE9, because IE9 DnD only works on anchors
	supportDraggable = documentExists && !ChromeForAndroid && !IOS && 'draggable' in document.createElement('div'),
	    supportCssPointerEvents = function () {
	  if (!documentExists) return; // false when <= IE11

	  if (IE11OrLess) {
	    return false;
	  }

	  var el = document.createElement('x');
	  el.style.cssText = 'pointer-events:auto';
	  return el.style.pointerEvents === 'auto';
	}(),
	    _detectDirection = function _detectDirection(el, options) {
	  var elCSS = css(el),
	      elWidth = parseInt(elCSS.width) - parseInt(elCSS.paddingLeft) - parseInt(elCSS.paddingRight) - parseInt(elCSS.borderLeftWidth) - parseInt(elCSS.borderRightWidth),
	      child1 = getChild(el, 0, options),
	      child2 = getChild(el, 1, options),
	      firstChildCSS = child1 && css(child1),
	      secondChildCSS = child2 && css(child2),
	      firstChildWidth = firstChildCSS && parseInt(firstChildCSS.marginLeft) + parseInt(firstChildCSS.marginRight) + getRect(child1).width,
	      secondChildWidth = secondChildCSS && parseInt(secondChildCSS.marginLeft) + parseInt(secondChildCSS.marginRight) + getRect(child2).width;

	  if (elCSS.display === 'flex') {
	    return elCSS.flexDirection === 'column' || elCSS.flexDirection === 'column-reverse' ? 'vertical' : 'horizontal';
	  }

	  if (elCSS.display === 'grid') {
	    return elCSS.gridTemplateColumns.split(' ').length <= 1 ? 'vertical' : 'horizontal';
	  }

	  if (child1 && firstChildCSS["float"] && firstChildCSS["float"] !== 'none') {
	    var touchingSideChild2 = firstChildCSS["float"] === 'left' ? 'left' : 'right';
	    return child2 && (secondChildCSS.clear === 'both' || secondChildCSS.clear === touchingSideChild2) ? 'vertical' : 'horizontal';
	  }

	  return child1 && (firstChildCSS.display === 'block' || firstChildCSS.display === 'flex' || firstChildCSS.display === 'table' || firstChildCSS.display === 'grid' || firstChildWidth >= elWidth && elCSS[CSSFloatProperty] === 'none' || child2 && elCSS[CSSFloatProperty] === 'none' && firstChildWidth + secondChildWidth > elWidth) ? 'vertical' : 'horizontal';
	},
	    _dragElInRowColumn = function _dragElInRowColumn(dragRect, targetRect, vertical) {
	  var dragElS1Opp = vertical ? dragRect.left : dragRect.top,
	      dragElS2Opp = vertical ? dragRect.right : dragRect.bottom,
	      dragElOppLength = vertical ? dragRect.width : dragRect.height,
	      targetS1Opp = vertical ? targetRect.left : targetRect.top,
	      targetS2Opp = vertical ? targetRect.right : targetRect.bottom,
	      targetOppLength = vertical ? targetRect.width : targetRect.height;
	  return dragElS1Opp === targetS1Opp || dragElS2Opp === targetS2Opp || dragElS1Opp + dragElOppLength / 2 === targetS1Opp + targetOppLength / 2;
	},

	/**
	 * Detects first nearest empty sortable to X and Y position using emptyInsertThreshold.
	 * @param  {Number} x      X position
	 * @param  {Number} y      Y position
	 * @return {HTMLElement}   Element of the first found nearest Sortable
	 */
	_detectNearestEmptySortable = function _detectNearestEmptySortable(x, y) {
	  var ret;
	  sortables.some(function (sortable) {
	    if (lastChild(sortable)) return;
	    var rect = getRect(sortable),
	        threshold = sortable[expando].options.emptyInsertThreshold,
	        insideHorizontally = x >= rect.left - threshold && x <= rect.right + threshold,
	        insideVertically = y >= rect.top - threshold && y <= rect.bottom + threshold;

	    if (threshold && insideHorizontally && insideVertically) {
	      return ret = sortable;
	    }
	  });
	  return ret;
	},
	    _prepareGroup = function _prepareGroup(options) {
	  function toFn(value, pull) {
	    return function (to, from, dragEl, evt) {
	      var sameGroup = to.options.group.name && from.options.group.name && to.options.group.name === from.options.group.name;

	      if (value == null && (pull || sameGroup)) {
	        // Default pull value
	        // Default pull and put value if same group
	        return true;
	      } else if (value == null || value === false) {
	        return false;
	      } else if (pull && value === 'clone') {
	        return value;
	      } else if (typeof value === 'function') {
	        return toFn(value(to, from, dragEl, evt), pull)(to, from, dragEl, evt);
	      } else {
	        var otherGroup = (pull ? to : from).options.group.name;
	        return value === true || typeof value === 'string' && value === otherGroup || value.join && value.indexOf(otherGroup) > -1;
	      }
	    };
	  }

	  var group = {};
	  var originalGroup = options.group;

	  if (!originalGroup || _typeof(originalGroup) != 'object') {
	    originalGroup = {
	      name: originalGroup
	    };
	  }

	  group.name = originalGroup.name;
	  group.checkPull = toFn(originalGroup.pull, true);
	  group.checkPut = toFn(originalGroup.put);
	  group.revertClone = originalGroup.revertClone;
	  options.group = group;
	},
	    _hideGhostForTarget = function _hideGhostForTarget() {
	  if (!supportCssPointerEvents && ghostEl) {
	    css(ghostEl, 'display', 'none');
	  }
	},
	    _unhideGhostForTarget = function _unhideGhostForTarget() {
	  if (!supportCssPointerEvents && ghostEl) {
	    css(ghostEl, 'display', '');
	  }
	}; // #1184 fix - Prevent click event on fallback if dragged but item not changed position


	if (documentExists) {
	  document.addEventListener('click', function (evt) {
	    if (ignoreNextClick) {
	      evt.preventDefault();
	      evt.stopPropagation && evt.stopPropagation();
	      evt.stopImmediatePropagation && evt.stopImmediatePropagation();
	      ignoreNextClick = false;
	      return false;
	    }
	  }, true);
	}

	var nearestEmptyInsertDetectEvent = function nearestEmptyInsertDetectEvent(evt) {
	  if (dragEl) {
	    evt = evt.touches ? evt.touches[0] : evt;

	    var nearest = _detectNearestEmptySortable(evt.clientX, evt.clientY);

	    if (nearest) {
	      // Create imitation event
	      var event = {};

	      for (var i in evt) {
	        if (evt.hasOwnProperty(i)) {
	          event[i] = evt[i];
	        }
	      }

	      event.target = event.rootEl = nearest;
	      event.preventDefault = void 0;
	      event.stopPropagation = void 0;

	      nearest[expando]._onDragOver(event);
	    }
	  }
	};

	var _checkOutsideTargetEl = function _checkOutsideTargetEl(evt) {
	  if (dragEl) {
	    dragEl.parentNode[expando]._isOutsideThisEl(evt.target);
	  }
	};
	/**
	 * @class  Sortable
	 * @param  {HTMLElement}  el
	 * @param  {Object}       [options]
	 */


	function Sortable(el, options) {
	  if (!(el && el.nodeType && el.nodeType === 1)) {
	    throw "Sortable: `el` must be an HTMLElement, not ".concat({}.toString.call(el));
	  }

	  this.el = el; // root element

	  this.options = options = _extends({}, options); // Export instance

	  el[expando] = this;
	  var defaults = {
	    group: null,
	    sort: true,
	    disabled: false,
	    store: null,
	    handle: null,
	    draggable: /^[uo]l$/i.test(el.nodeName) ? '>li' : '>*',
	    swapThreshold: 1,
	    // percentage; 0 <= x <= 1
	    invertSwap: false,
	    // invert always
	    invertedSwapThreshold: null,
	    // will be set to same as swapThreshold if default
	    removeCloneOnHide: true,
	    direction: function direction() {
	      return _detectDirection(el, this.options);
	    },
	    ghostClass: 'sortable-ghost',
	    chosenClass: 'sortable-chosen',
	    dragClass: 'sortable-drag',
	    ignore: 'a, img',
	    filter: null,
	    preventOnFilter: true,
	    animation: 0,
	    easing: null,
	    setData: function setData(dataTransfer, dragEl) {
	      dataTransfer.setData('Text', dragEl.textContent);
	    },
	    dropBubble: false,
	    dragoverBubble: false,
	    dataIdAttr: 'data-id',
	    delay: 0,
	    delayOnTouchOnly: false,
	    touchStartThreshold: (Number.parseInt ? Number : window).parseInt(window.devicePixelRatio, 10) || 1,
	    forceFallback: false,
	    fallbackClass: 'sortable-fallback',
	    fallbackOnBody: false,
	    fallbackTolerance: 0,
	    fallbackOffset: {
	      x: 0,
	      y: 0
	    },
	    supportPointer: Sortable.supportPointer !== false && 'PointerEvent' in window,
	    emptyInsertThreshold: 5
	  };
	  PluginManager.initializePlugins(this, el, defaults); // Set default options

	  for (var name in defaults) {
	    !(name in options) && (options[name] = defaults[name]);
	  }

	  _prepareGroup(options); // Bind all private methods


	  for (var fn in this) {
	    if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
	      this[fn] = this[fn].bind(this);
	    }
	  } // Setup drag mode


	  this.nativeDraggable = options.forceFallback ? false : supportDraggable;

	  if (this.nativeDraggable) {
	    // Touch start threshold cannot be greater than the native dragstart threshold
	    this.options.touchStartThreshold = 1;
	  } // Bind events


	  if (options.supportPointer) {
	    on(el, 'pointerdown', this._onTapStart);
	  } else {
	    on(el, 'mousedown', this._onTapStart);
	    on(el, 'touchstart', this._onTapStart);
	  }

	  if (this.nativeDraggable) {
	    on(el, 'dragover', this);
	    on(el, 'dragenter', this);
	  }

	  sortables.push(this.el); // Restore sorting

	  options.store && options.store.get && this.sort(options.store.get(this) || []); // Add animation state manager

	  _extends(this, AnimationStateManager());
	}

	Sortable.prototype =
	/** @lends Sortable.prototype */
	{
	  constructor: Sortable,
	  _isOutsideThisEl: function _isOutsideThisEl(target) {
	    if (!this.el.contains(target) && target !== this.el) {
	      lastTarget = null;
	    }
	  },
	  _getDirection: function _getDirection(evt, target) {
	    return typeof this.options.direction === 'function' ? this.options.direction.call(this, evt, target, dragEl) : this.options.direction;
	  },
	  _onTapStart: function _onTapStart(
	  /** Event|TouchEvent */
	  evt) {
	    if (!evt.cancelable) return;

	    var _this = this,
	        el = this.el,
	        options = this.options,
	        preventOnFilter = options.preventOnFilter,
	        type = evt.type,
	        touch = evt.touches && evt.touches[0] || evt.pointerType && evt.pointerType === 'touch' && evt,
	        target = (touch || evt).target,
	        originalTarget = evt.target.shadowRoot && (evt.path && evt.path[0] || evt.composedPath && evt.composedPath()[0]) || target,
	        filter = options.filter;

	    _saveInputCheckedState(el); // Don't trigger start event when an element is been dragged, otherwise the evt.oldindex always wrong when set option.group.


	    if (dragEl) {
	      return;
	    }

	    if (/mousedown|pointerdown/.test(type) && evt.button !== 0 || options.disabled) {
	      return; // only left button and enabled
	    } // cancel dnd if original target is content editable


	    if (originalTarget.isContentEditable) {
	      return;
	    }

	    target = closest(target, options.draggable, el, false);

	    if (target && target.animated) {
	      return;
	    }

	    if (lastDownEl === target) {
	      // Ignoring duplicate `down`
	      return;
	    } // Get the index of the dragged element within its parent


	    oldIndex = index(target);
	    oldDraggableIndex = index(target, options.draggable); // Check filter

	    if (typeof filter === 'function') {
	      if (filter.call(this, evt, target, this)) {
	        _dispatchEvent({
	          sortable: _this,
	          rootEl: originalTarget,
	          name: 'filter',
	          targetEl: target,
	          toEl: el,
	          fromEl: el
	        });

	        pluginEvent('filter', _this, {
	          evt: evt
	        });
	        preventOnFilter && evt.cancelable && evt.preventDefault();
	        return; // cancel dnd
	      }
	    } else if (filter) {
	      filter = filter.split(',').some(function (criteria) {
	        criteria = closest(originalTarget, criteria.trim(), el, false);

	        if (criteria) {
	          _dispatchEvent({
	            sortable: _this,
	            rootEl: criteria,
	            name: 'filter',
	            targetEl: target,
	            fromEl: el,
	            toEl: el
	          });

	          pluginEvent('filter', _this, {
	            evt: evt
	          });
	          return true;
	        }
	      });

	      if (filter) {
	        preventOnFilter && evt.cancelable && evt.preventDefault();
	        return; // cancel dnd
	      }
	    }

	    if (options.handle && !closest(originalTarget, options.handle, el, false)) {
	      return;
	    } // Prepare `dragstart`


	    this._prepareDragStart(evt, touch, target);
	  },
	  _prepareDragStart: function _prepareDragStart(
	  /** Event */
	  evt,
	  /** Touch */
	  touch,
	  /** HTMLElement */
	  target) {
	    var _this = this,
	        el = _this.el,
	        options = _this.options,
	        ownerDocument = el.ownerDocument,
	        dragStartFn;

	    if (target && !dragEl && target.parentNode === el) {
	      var dragRect = getRect(target);
	      rootEl = el;
	      dragEl = target;
	      parentEl = dragEl.parentNode;
	      nextEl = dragEl.nextSibling;
	      lastDownEl = target;
	      activeGroup = options.group;
	      Sortable.dragged = dragEl;
	      tapEvt = {
	        target: dragEl,
	        clientX: (touch || evt).clientX,
	        clientY: (touch || evt).clientY
	      };
	      tapDistanceLeft = tapEvt.clientX - dragRect.left;
	      tapDistanceTop = tapEvt.clientY - dragRect.top;
	      this._lastX = (touch || evt).clientX;
	      this._lastY = (touch || evt).clientY;
	      dragEl.style['will-change'] = 'all';

	      dragStartFn = function dragStartFn() {
	        pluginEvent('delayEnded', _this, {
	          evt: evt
	        });

	        if (Sortable.eventCanceled) {
	          _this._onDrop();

	          return;
	        } // Delayed drag has been triggered
	        // we can re-enable the events: touchmove/mousemove


	        _this._disableDelayedDragEvents();

	        if (!FireFox && _this.nativeDraggable) {
	          dragEl.draggable = true;
	        } // Bind the events: dragstart/dragend


	        _this._triggerDragStart(evt, touch); // Drag start event


	        _dispatchEvent({
	          sortable: _this,
	          name: 'choose',
	          originalEvent: evt
	        }); // Chosen item


	        toggleClass(dragEl, options.chosenClass, true);
	      }; // Disable "draggable"


	      options.ignore.split(',').forEach(function (criteria) {
	        find(dragEl, criteria.trim(), _disableDraggable);
	      });
	      on(ownerDocument, 'dragover', nearestEmptyInsertDetectEvent);
	      on(ownerDocument, 'mousemove', nearestEmptyInsertDetectEvent);
	      on(ownerDocument, 'touchmove', nearestEmptyInsertDetectEvent);
	      on(ownerDocument, 'mouseup', _this._onDrop);
	      on(ownerDocument, 'touchend', _this._onDrop);
	      on(ownerDocument, 'touchcancel', _this._onDrop); // Make dragEl draggable (must be before delay for FireFox)

	      if (FireFox && this.nativeDraggable) {
	        this.options.touchStartThreshold = 4;
	        dragEl.draggable = true;
	      }

	      pluginEvent('delayStart', this, {
	        evt: evt
	      }); // Delay is impossible for native DnD in Edge or IE

	      if (options.delay && (!options.delayOnTouchOnly || touch) && (!this.nativeDraggable || !(Edge || IE11OrLess))) {
	        if (Sortable.eventCanceled) {
	          this._onDrop();

	          return;
	        } // If the user moves the pointer or let go the click or touch
	        // before the delay has been reached:
	        // disable the delayed drag


	        on(ownerDocument, 'mouseup', _this._disableDelayedDrag);
	        on(ownerDocument, 'touchend', _this._disableDelayedDrag);
	        on(ownerDocument, 'touchcancel', _this._disableDelayedDrag);
	        on(ownerDocument, 'mousemove', _this._delayedDragTouchMoveHandler);
	        on(ownerDocument, 'touchmove', _this._delayedDragTouchMoveHandler);
	        options.supportPointer && on(ownerDocument, 'pointermove', _this._delayedDragTouchMoveHandler);
	        _this._dragStartTimer = setTimeout(dragStartFn, options.delay);
	      } else {
	        dragStartFn();
	      }
	    }
	  },
	  _delayedDragTouchMoveHandler: function _delayedDragTouchMoveHandler(
	  /** TouchEvent|PointerEvent **/
	  e) {
	    var touch = e.touches ? e.touches[0] : e;

	    if (Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1))) {
	      this._disableDelayedDrag();
	    }
	  },
	  _disableDelayedDrag: function _disableDelayedDrag() {
	    dragEl && _disableDraggable(dragEl);
	    clearTimeout(this._dragStartTimer);

	    this._disableDelayedDragEvents();
	  },
	  _disableDelayedDragEvents: function _disableDelayedDragEvents() {
	    var ownerDocument = this.el.ownerDocument;
	    off(ownerDocument, 'mouseup', this._disableDelayedDrag);
	    off(ownerDocument, 'touchend', this._disableDelayedDrag);
	    off(ownerDocument, 'touchcancel', this._disableDelayedDrag);
	    off(ownerDocument, 'mousemove', this._delayedDragTouchMoveHandler);
	    off(ownerDocument, 'touchmove', this._delayedDragTouchMoveHandler);
	    off(ownerDocument, 'pointermove', this._delayedDragTouchMoveHandler);
	  },
	  _triggerDragStart: function _triggerDragStart(
	  /** Event */
	  evt,
	  /** Touch */
	  touch) {
	    touch = touch || evt.pointerType == 'touch' && evt;

	    if (!this.nativeDraggable || touch) {
	      if (this.options.supportPointer) {
	        on(document, 'pointermove', this._onTouchMove);
	      } else if (touch) {
	        on(document, 'touchmove', this._onTouchMove);
	      } else {
	        on(document, 'mousemove', this._onTouchMove);
	      }
	    } else {
	      on(dragEl, 'dragend', this);
	      on(rootEl, 'dragstart', this._onDragStart);
	    }

	    try {
	      if (document.selection) {
	        // Timeout neccessary for IE9
	        _nextTick(function () {
	          document.selection.empty();
	        });
	      } else {
	        window.getSelection().removeAllRanges();
	      }
	    } catch (err) {}
	  },
	  _dragStarted: function _dragStarted(fallback, evt) {

	    awaitingDragStarted = false;

	    if (rootEl && dragEl) {
	      pluginEvent('dragStarted', this, {
	        evt: evt
	      });

	      if (this.nativeDraggable) {
	        on(document, 'dragover', _checkOutsideTargetEl);
	      }

	      var options = this.options; // Apply effect

	      !fallback && toggleClass(dragEl, options.dragClass, false);
	      toggleClass(dragEl, options.ghostClass, true);
	      Sortable.active = this;
	      fallback && this._appendGhost(); // Drag start event

	      _dispatchEvent({
	        sortable: this,
	        name: 'start',
	        originalEvent: evt
	      });
	    } else {
	      this._nulling();
	    }
	  },
	  _emulateDragOver: function _emulateDragOver() {
	    if (touchEvt) {
	      this._lastX = touchEvt.clientX;
	      this._lastY = touchEvt.clientY;

	      _hideGhostForTarget();

	      var target = document.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
	      var parent = target;

	      while (target && target.shadowRoot) {
	        target = target.shadowRoot.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
	        if (target === parent) break;
	        parent = target;
	      }

	      dragEl.parentNode[expando]._isOutsideThisEl(target);

	      if (parent) {
	        do {
	          if (parent[expando]) {
	            var inserted = void 0;
	            inserted = parent[expando]._onDragOver({
	              clientX: touchEvt.clientX,
	              clientY: touchEvt.clientY,
	              target: target,
	              rootEl: parent
	            });

	            if (inserted && !this.options.dragoverBubble) {
	              break;
	            }
	          }

	          target = parent; // store last element
	        }
	        /* jshint boss:true */
	        while (parent = parent.parentNode);
	      }

	      _unhideGhostForTarget();
	    }
	  },
	  _onTouchMove: function _onTouchMove(
	  /**TouchEvent*/
	  evt) {
	    if (tapEvt) {
	      var options = this.options,
	          fallbackTolerance = options.fallbackTolerance,
	          fallbackOffset = options.fallbackOffset,
	          touch = evt.touches ? evt.touches[0] : evt,
	          ghostMatrix = ghostEl && matrix(ghostEl, true),
	          scaleX = ghostEl && ghostMatrix && ghostMatrix.a,
	          scaleY = ghostEl && ghostMatrix && ghostMatrix.d,
	          relativeScrollOffset = PositionGhostAbsolutely && ghostRelativeParent && getRelativeScrollOffset(ghostRelativeParent),
	          dx = (touch.clientX - tapEvt.clientX + fallbackOffset.x) / (scaleX || 1) + (relativeScrollOffset ? relativeScrollOffset[0] - ghostRelativeParentInitialScroll[0] : 0) / (scaleX || 1),
	          dy = (touch.clientY - tapEvt.clientY + fallbackOffset.y) / (scaleY || 1) + (relativeScrollOffset ? relativeScrollOffset[1] - ghostRelativeParentInitialScroll[1] : 0) / (scaleY || 1); // only set the status to dragging, when we are actually dragging

	      if (!Sortable.active && !awaitingDragStarted) {
	        if (fallbackTolerance && Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) < fallbackTolerance) {
	          return;
	        }

	        this._onDragStart(evt, true);
	      }

	      if (ghostEl) {
	        if (ghostMatrix) {
	          ghostMatrix.e += dx - (lastDx || 0);
	          ghostMatrix.f += dy - (lastDy || 0);
	        } else {
	          ghostMatrix = {
	            a: 1,
	            b: 0,
	            c: 0,
	            d: 1,
	            e: dx,
	            f: dy
	          };
	        }

	        var cssMatrix = "matrix(".concat(ghostMatrix.a, ",").concat(ghostMatrix.b, ",").concat(ghostMatrix.c, ",").concat(ghostMatrix.d, ",").concat(ghostMatrix.e, ",").concat(ghostMatrix.f, ")");
	        css(ghostEl, 'webkitTransform', cssMatrix);
	        css(ghostEl, 'mozTransform', cssMatrix);
	        css(ghostEl, 'msTransform', cssMatrix);
	        css(ghostEl, 'transform', cssMatrix);
	        lastDx = dx;
	        lastDy = dy;
	        touchEvt = touch;
	      }

	      evt.cancelable && evt.preventDefault();
	    }
	  },
	  _appendGhost: function _appendGhost() {
	    // Bug if using scale(): https://stackoverflow.com/questions/2637058
	    // Not being adjusted for
	    if (!ghostEl) {
	      var container = this.options.fallbackOnBody ? document.body : rootEl,
	          rect = getRect(dragEl, true, PositionGhostAbsolutely, true, container),
	          options = this.options; // Position absolutely

	      if (PositionGhostAbsolutely) {
	        // Get relatively positioned parent
	        ghostRelativeParent = container;

	        while (css(ghostRelativeParent, 'position') === 'static' && css(ghostRelativeParent, 'transform') === 'none' && ghostRelativeParent !== document) {
	          ghostRelativeParent = ghostRelativeParent.parentNode;
	        }

	        if (ghostRelativeParent !== document.body && ghostRelativeParent !== document.documentElement) {
	          if (ghostRelativeParent === document) ghostRelativeParent = getWindowScrollingElement();
	          rect.top += ghostRelativeParent.scrollTop;
	          rect.left += ghostRelativeParent.scrollLeft;
	        } else {
	          ghostRelativeParent = getWindowScrollingElement();
	        }

	        ghostRelativeParentInitialScroll = getRelativeScrollOffset(ghostRelativeParent);
	      }

	      ghostEl = dragEl.cloneNode(true);
	      toggleClass(ghostEl, options.ghostClass, false);
	      toggleClass(ghostEl, options.fallbackClass, true);
	      toggleClass(ghostEl, options.dragClass, true);
	      css(ghostEl, 'transition', '');
	      css(ghostEl, 'transform', '');
	      css(ghostEl, 'box-sizing', 'border-box');
	      css(ghostEl, 'margin', 0);
	      css(ghostEl, 'top', rect.top);
	      css(ghostEl, 'left', rect.left);
	      css(ghostEl, 'width', rect.width);
	      css(ghostEl, 'height', rect.height);
	      css(ghostEl, 'opacity', '0.8');
	      css(ghostEl, 'position', PositionGhostAbsolutely ? 'absolute' : 'fixed');
	      css(ghostEl, 'zIndex', '100000');
	      css(ghostEl, 'pointerEvents', 'none');
	      Sortable.ghost = ghostEl;
	      container.appendChild(ghostEl); // Set transform-origin

	      css(ghostEl, 'transform-origin', tapDistanceLeft / parseInt(ghostEl.style.width) * 100 + '% ' + tapDistanceTop / parseInt(ghostEl.style.height) * 100 + '%');
	    }
	  },
	  _onDragStart: function _onDragStart(
	  /**Event*/
	  evt,
	  /**boolean*/
	  fallback) {
	    var _this = this;

	    var dataTransfer = evt.dataTransfer;
	    var options = _this.options;
	    pluginEvent('dragStart', this, {
	      evt: evt
	    });

	    if (Sortable.eventCanceled) {
	      this._onDrop();

	      return;
	    }

	    pluginEvent('setupClone', this);

	    if (!Sortable.eventCanceled) {
	      cloneEl = clone(dragEl);
	      cloneEl.draggable = false;
	      cloneEl.style['will-change'] = '';

	      this._hideClone();

	      toggleClass(cloneEl, this.options.chosenClass, false);
	      Sortable.clone = cloneEl;
	    } // #1143: IFrame support workaround


	    _this.cloneId = _nextTick(function () {
	      pluginEvent('clone', _this);
	      if (Sortable.eventCanceled) return;

	      if (!_this.options.removeCloneOnHide) {
	        rootEl.insertBefore(cloneEl, dragEl);
	      }

	      _this._hideClone();

	      _dispatchEvent({
	        sortable: _this,
	        name: 'clone'
	      });
	    });
	    !fallback && toggleClass(dragEl, options.dragClass, true); // Set proper drop events

	    if (fallback) {
	      ignoreNextClick = true;
	      _this._loopId = setInterval(_this._emulateDragOver, 50);
	    } else {
	      // Undo what was set in _prepareDragStart before drag started
	      off(document, 'mouseup', _this._onDrop);
	      off(document, 'touchend', _this._onDrop);
	      off(document, 'touchcancel', _this._onDrop);

	      if (dataTransfer) {
	        dataTransfer.effectAllowed = 'move';
	        options.setData && options.setData.call(_this, dataTransfer, dragEl);
	      }

	      on(document, 'drop', _this); // #1276 fix:

	      css(dragEl, 'transform', 'translateZ(0)');
	    }

	    awaitingDragStarted = true;
	    _this._dragStartId = _nextTick(_this._dragStarted.bind(_this, fallback, evt));
	    on(document, 'selectstart', _this);
	    moved = true;

	    if (Safari) {
	      css(document.body, 'user-select', 'none');
	    }
	  },
	  // Returns true - if no further action is needed (either inserted or another condition)
	  _onDragOver: function _onDragOver(
	  /**Event*/
	  evt) {
	    var el = this.el,
	        target = evt.target,
	        dragRect,
	        targetRect,
	        revert,
	        options = this.options,
	        group = options.group,
	        activeSortable = Sortable.active,
	        isOwner = activeGroup === group,
	        canSort = options.sort,
	        fromSortable = putSortable || activeSortable,
	        vertical,
	        _this = this,
	        completedFired = false;

	    if (_silent) return;

	    function dragOverEvent(name, extra) {
	      pluginEvent(name, _this, _objectSpread({
	        evt: evt,
	        isOwner: isOwner,
	        axis: vertical ? 'vertical' : 'horizontal',
	        revert: revert,
	        dragRect: dragRect,
	        targetRect: targetRect,
	        canSort: canSort,
	        fromSortable: fromSortable,
	        target: target,
	        completed: completed,
	        onMove: function onMove(target, after) {
	          return _onMove(rootEl, el, dragEl, dragRect, target, getRect(target), evt, after);
	        },
	        changed: changed
	      }, extra));
	    } // Capture animation state


	    function capture() {
	      dragOverEvent('dragOverAnimationCapture');

	      _this.captureAnimationState();

	      if (_this !== fromSortable) {
	        fromSortable.captureAnimationState();
	      }
	    } // Return invocation when dragEl is inserted (or completed)


	    function completed(insertion) {
	      dragOverEvent('dragOverCompleted', {
	        insertion: insertion
	      });

	      if (insertion) {
	        // Clones must be hidden before folding animation to capture dragRectAbsolute properly
	        if (isOwner) {
	          activeSortable._hideClone();
	        } else {
	          activeSortable._showClone(_this);
	        }

	        if (_this !== fromSortable) {
	          // Set ghost class to new sortable's ghost class
	          toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : activeSortable.options.ghostClass, false);
	          toggleClass(dragEl, options.ghostClass, true);
	        }

	        if (putSortable !== _this && _this !== Sortable.active) {
	          putSortable = _this;
	        } else if (_this === Sortable.active && putSortable) {
	          putSortable = null;
	        } // Animation


	        if (fromSortable === _this) {
	          _this._ignoreWhileAnimating = target;
	        }

	        _this.animateAll(function () {
	          dragOverEvent('dragOverAnimationComplete');
	          _this._ignoreWhileAnimating = null;
	        });

	        if (_this !== fromSortable) {
	          fromSortable.animateAll();
	          fromSortable._ignoreWhileAnimating = null;
	        }
	      } // Null lastTarget if it is not inside a previously swapped element


	      if (target === dragEl && !dragEl.animated || target === el && !target.animated) {
	        lastTarget = null;
	      } // no bubbling and not fallback


	      if (!options.dragoverBubble && !evt.rootEl && target !== document) {
	        dragEl.parentNode[expando]._isOutsideThisEl(evt.target); // Do not detect for empty insert if already inserted


	        !insertion && nearestEmptyInsertDetectEvent(evt);
	      }

	      !options.dragoverBubble && evt.stopPropagation && evt.stopPropagation();
	      return completedFired = true;
	    } // Call when dragEl has been inserted


	    function changed() {
	      newIndex = index(dragEl);
	      newDraggableIndex = index(dragEl, options.draggable);

	      _dispatchEvent({
	        sortable: _this,
	        name: 'change',
	        toEl: el,
	        newIndex: newIndex,
	        newDraggableIndex: newDraggableIndex,
	        originalEvent: evt
	      });
	    }

	    if (evt.preventDefault !== void 0) {
	      evt.cancelable && evt.preventDefault();
	    }

	    target = closest(target, options.draggable, el, true);
	    dragOverEvent('dragOver');
	    if (Sortable.eventCanceled) return completedFired;

	    if (dragEl.contains(evt.target) || target.animated && target.animatingX && target.animatingY || _this._ignoreWhileAnimating === target) {
	      return completed(false);
	    }

	    ignoreNextClick = false;

	    if (activeSortable && !options.disabled && (isOwner ? canSort || (revert = !rootEl.contains(dragEl)) // Reverting item into the original list
	    : putSortable === this || (this.lastPutMode = activeGroup.checkPull(this, activeSortable, dragEl, evt)) && group.checkPut(this, activeSortable, dragEl, evt))) {
	      vertical = this._getDirection(evt, target) === 'vertical';
	      dragRect = getRect(dragEl);
	      dragOverEvent('dragOverValid');
	      if (Sortable.eventCanceled) return completedFired;

	      if (revert) {
	        parentEl = rootEl; // actualization

	        capture();

	        this._hideClone();

	        dragOverEvent('revert');

	        if (!Sortable.eventCanceled) {
	          if (nextEl) {
	            rootEl.insertBefore(dragEl, nextEl);
	          } else {
	            rootEl.appendChild(dragEl);
	          }
	        }

	        return completed(true);
	      }

	      var elLastChild = lastChild(el, options.draggable);

	      if (!elLastChild || _ghostIsLast(evt, vertical, this) && !elLastChild.animated) {
	        // If already at end of list: Do not insert
	        if (elLastChild === dragEl) {
	          return completed(false);
	        } // assign target only if condition is true


	        if (elLastChild && el === evt.target) {
	          target = elLastChild;
	        }

	        if (target) {
	          targetRect = getRect(target);
	        }

	        if (_onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, !!target) !== false) {
	          capture();
	          el.appendChild(dragEl);
	          parentEl = el; // actualization

	          changed();
	          return completed(true);
	        }
	      } else if (target.parentNode === el) {
	        targetRect = getRect(target);
	        var direction = 0,
	            targetBeforeFirstSwap,
	            differentLevel = dragEl.parentNode !== el,
	            differentRowCol = !_dragElInRowColumn(dragEl.animated && dragEl.toRect || dragRect, target.animated && target.toRect || targetRect, vertical),
	            side1 = vertical ? 'top' : 'left',
	            scrolledPastTop = isScrolledPast(target, 'top', 'top') || isScrolledPast(dragEl, 'top', 'top'),
	            scrollBefore = scrolledPastTop ? scrolledPastTop.scrollTop : void 0;

	        if (lastTarget !== target) {
	          targetBeforeFirstSwap = targetRect[side1];
	          pastFirstInvertThresh = false;
	          isCircumstantialInvert = !differentRowCol && options.invertSwap || differentLevel;
	        }

	        direction = _getSwapDirection(evt, target, targetRect, vertical, differentRowCol ? 1 : options.swapThreshold, options.invertedSwapThreshold == null ? options.swapThreshold : options.invertedSwapThreshold, isCircumstantialInvert, lastTarget === target);
	        var sibling;

	        if (direction !== 0) {
	          // Check if target is beside dragEl in respective direction (ignoring hidden elements)
	          var dragIndex = index(dragEl);

	          do {
	            dragIndex -= direction;
	            sibling = parentEl.children[dragIndex];
	          } while (sibling && (css(sibling, 'display') === 'none' || sibling === ghostEl));
	        } // If dragEl is already beside target: Do not insert


	        if (direction === 0 || sibling === target) {
	          return completed(false);
	        }

	        lastTarget = target;
	        lastDirection = direction;
	        var nextSibling = target.nextElementSibling,
	            after = false;
	        after = direction === 1;

	        var moveVector = _onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, after);

	        if (moveVector !== false) {
	          if (moveVector === 1 || moveVector === -1) {
	            after = moveVector === 1;
	          }

	          _silent = true;
	          setTimeout(_unsilent, 30);
	          capture();

	          if (after && !nextSibling) {
	            el.appendChild(dragEl);
	          } else {
	            target.parentNode.insertBefore(dragEl, after ? nextSibling : target);
	          } // Undo chrome's scroll adjustment (has no effect on other browsers)


	          if (scrolledPastTop) {
	            scrollBy(scrolledPastTop, 0, scrollBefore - scrolledPastTop.scrollTop);
	          }

	          parentEl = dragEl.parentNode; // actualization
	          // must be done before animation

	          if (targetBeforeFirstSwap !== undefined && !isCircumstantialInvert) {
	            targetMoveDistance = Math.abs(targetBeforeFirstSwap - getRect(target)[side1]);
	          }

	          changed();
	          return completed(true);
	        }
	      }

	      if (el.contains(dragEl)) {
	        return completed(false);
	      }
	    }

	    return false;
	  },
	  _ignoreWhileAnimating: null,
	  _offMoveEvents: function _offMoveEvents() {
	    off(document, 'mousemove', this._onTouchMove);
	    off(document, 'touchmove', this._onTouchMove);
	    off(document, 'pointermove', this._onTouchMove);
	    off(document, 'dragover', nearestEmptyInsertDetectEvent);
	    off(document, 'mousemove', nearestEmptyInsertDetectEvent);
	    off(document, 'touchmove', nearestEmptyInsertDetectEvent);
	  },
	  _offUpEvents: function _offUpEvents() {
	    var ownerDocument = this.el.ownerDocument;
	    off(ownerDocument, 'mouseup', this._onDrop);
	    off(ownerDocument, 'touchend', this._onDrop);
	    off(ownerDocument, 'pointerup', this._onDrop);
	    off(ownerDocument, 'touchcancel', this._onDrop);
	    off(document, 'selectstart', this);
	  },
	  _onDrop: function _onDrop(
	  /**Event*/
	  evt) {
	    var el = this.el,
	        options = this.options; // Get the index of the dragged element within its parent

	    newIndex = index(dragEl);
	    newDraggableIndex = index(dragEl, options.draggable);
	    pluginEvent('drop', this, {
	      evt: evt
	    });
	    parentEl = dragEl && dragEl.parentNode; // Get again after plugin event

	    newIndex = index(dragEl);
	    newDraggableIndex = index(dragEl, options.draggable);

	    if (Sortable.eventCanceled) {
	      this._nulling();

	      return;
	    }

	    awaitingDragStarted = false;
	    isCircumstantialInvert = false;
	    pastFirstInvertThresh = false;
	    clearInterval(this._loopId);
	    clearTimeout(this._dragStartTimer);

	    _cancelNextTick(this.cloneId);

	    _cancelNextTick(this._dragStartId); // Unbind events


	    if (this.nativeDraggable) {
	      off(document, 'drop', this);
	      off(el, 'dragstart', this._onDragStart);
	    }

	    this._offMoveEvents();

	    this._offUpEvents();

	    if (Safari) {
	      css(document.body, 'user-select', '');
	    }

	    css(dragEl, 'transform', '');

	    if (evt) {
	      if (moved) {
	        evt.cancelable && evt.preventDefault();
	        !options.dropBubble && evt.stopPropagation();
	      }

	      ghostEl && ghostEl.parentNode && ghostEl.parentNode.removeChild(ghostEl);

	      if (rootEl === parentEl || putSortable && putSortable.lastPutMode !== 'clone') {
	        // Remove clone(s)
	        cloneEl && cloneEl.parentNode && cloneEl.parentNode.removeChild(cloneEl);
	      }

	      if (dragEl) {
	        if (this.nativeDraggable) {
	          off(dragEl, 'dragend', this);
	        }

	        _disableDraggable(dragEl);

	        dragEl.style['will-change'] = ''; // Remove classes
	        // ghostClass is added in dragStarted

	        if (moved && !awaitingDragStarted) {
	          toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : this.options.ghostClass, false);
	        }

	        toggleClass(dragEl, this.options.chosenClass, false); // Drag stop event

	        _dispatchEvent({
	          sortable: this,
	          name: 'unchoose',
	          toEl: parentEl,
	          newIndex: null,
	          newDraggableIndex: null,
	          originalEvent: evt
	        });

	        if (rootEl !== parentEl) {
	          if (newIndex >= 0) {
	            // Add event
	            _dispatchEvent({
	              rootEl: parentEl,
	              name: 'add',
	              toEl: parentEl,
	              fromEl: rootEl,
	              originalEvent: evt
	            }); // Remove event


	            _dispatchEvent({
	              sortable: this,
	              name: 'remove',
	              toEl: parentEl,
	              originalEvent: evt
	            }); // drag from one list and drop into another


	            _dispatchEvent({
	              rootEl: parentEl,
	              name: 'sort',
	              toEl: parentEl,
	              fromEl: rootEl,
	              originalEvent: evt
	            });

	            _dispatchEvent({
	              sortable: this,
	              name: 'sort',
	              toEl: parentEl,
	              originalEvent: evt
	            });
	          }

	          putSortable && putSortable.save();
	        } else {
	          if (newIndex !== oldIndex) {
	            if (newIndex >= 0) {
	              // drag & drop within the same list
	              _dispatchEvent({
	                sortable: this,
	                name: 'update',
	                toEl: parentEl,
	                originalEvent: evt
	              });

	              _dispatchEvent({
	                sortable: this,
	                name: 'sort',
	                toEl: parentEl,
	                originalEvent: evt
	              });
	            }
	          }
	        }

	        if (Sortable.active) {
	          /* jshint eqnull:true */
	          if (newIndex == null || newIndex === -1) {
	            newIndex = oldIndex;
	            newDraggableIndex = oldDraggableIndex;
	          }

	          _dispatchEvent({
	            sortable: this,
	            name: 'end',
	            toEl: parentEl,
	            originalEvent: evt
	          }); // Save sorting


	          this.save();
	        }
	      }
	    }

	    this._nulling();
	  },
	  _nulling: function _nulling() {
	    pluginEvent('nulling', this);
	    rootEl = dragEl = parentEl = ghostEl = nextEl = cloneEl = lastDownEl = cloneHidden = tapEvt = touchEvt = moved = newIndex = newDraggableIndex = oldIndex = oldDraggableIndex = lastTarget = lastDirection = putSortable = activeGroup = Sortable.dragged = Sortable.ghost = Sortable.clone = Sortable.active = null;
	    savedInputChecked.forEach(function (el) {
	      el.checked = true;
	    });
	    savedInputChecked.length = lastDx = lastDy = 0;
	  },
	  handleEvent: function handleEvent(
	  /**Event*/
	  evt) {
	    switch (evt.type) {
	      case 'drop':
	      case 'dragend':
	        this._onDrop(evt);

	        break;

	      case 'dragenter':
	      case 'dragover':
	        if (dragEl) {
	          this._onDragOver(evt);

	          _globalDragOver(evt);
	        }

	        break;

	      case 'selectstart':
	        evt.preventDefault();
	        break;
	    }
	  },

	  /**
	   * Serializes the item into an array of string.
	   * @returns {String[]}
	   */
	  toArray: function toArray() {
	    var order = [],
	        el,
	        children = this.el.children,
	        i = 0,
	        n = children.length,
	        options = this.options;

	    for (; i < n; i++) {
	      el = children[i];

	      if (closest(el, options.draggable, this.el, false)) {
	        order.push(el.getAttribute(options.dataIdAttr) || _generateId(el));
	      }
	    }

	    return order;
	  },

	  /**
	   * Sorts the elements according to the array.
	   * @param  {String[]}  order  order of the items
	   */
	  sort: function sort(order) {
	    var items = {},
	        rootEl = this.el;
	    this.toArray().forEach(function (id, i) {
	      var el = rootEl.children[i];

	      if (closest(el, this.options.draggable, rootEl, false)) {
	        items[id] = el;
	      }
	    }, this);
	    order.forEach(function (id) {
	      if (items[id]) {
	        rootEl.removeChild(items[id]);
	        rootEl.appendChild(items[id]);
	      }
	    });
	  },

	  /**
	   * Save the current sorting
	   */
	  save: function save() {
	    var store = this.options.store;
	    store && store.set && store.set(this);
	  },

	  /**
	   * For each element in the set, get the first element that matches the selector by testing the element itself and traversing up through its ancestors in the DOM tree.
	   * @param   {HTMLElement}  el
	   * @param   {String}       [selector]  default: `options.draggable`
	   * @returns {HTMLElement|null}
	   */
	  closest: function closest$1(el, selector) {
	    return closest(el, selector || this.options.draggable, this.el, false);
	  },

	  /**
	   * Set/get option
	   * @param   {string} name
	   * @param   {*}      [value]
	   * @returns {*}
	   */
	  option: function option(name, value) {
	    var options = this.options;

	    if (value === void 0) {
	      return options[name];
	    } else {
	      var modifiedValue = PluginManager.modifyOption(this, name, value);

	      if (typeof modifiedValue !== 'undefined') {
	        options[name] = modifiedValue;
	      } else {
	        options[name] = value;
	      }

	      if (name === 'group') {
	        _prepareGroup(options);
	      }
	    }
	  },

	  /**
	   * Destroy
	   */
	  destroy: function destroy() {
	    pluginEvent('destroy', this);
	    var el = this.el;
	    el[expando] = null;
	    off(el, 'mousedown', this._onTapStart);
	    off(el, 'touchstart', this._onTapStart);
	    off(el, 'pointerdown', this._onTapStart);

	    if (this.nativeDraggable) {
	      off(el, 'dragover', this);
	      off(el, 'dragenter', this);
	    } // Remove draggable attributes


	    Array.prototype.forEach.call(el.querySelectorAll('[draggable]'), function (el) {
	      el.removeAttribute('draggable');
	    });

	    this._onDrop();

	    this._disableDelayedDragEvents();

	    sortables.splice(sortables.indexOf(this.el), 1);
	    this.el = el = null;
	  },
	  _hideClone: function _hideClone() {
	    if (!cloneHidden) {
	      pluginEvent('hideClone', this);
	      if (Sortable.eventCanceled) return;
	      css(cloneEl, 'display', 'none');

	      if (this.options.removeCloneOnHide && cloneEl.parentNode) {
	        cloneEl.parentNode.removeChild(cloneEl);
	      }

	      cloneHidden = true;
	    }
	  },
	  _showClone: function _showClone(putSortable) {
	    if (putSortable.lastPutMode !== 'clone') {
	      this._hideClone();

	      return;
	    }

	    if (cloneHidden) {
	      pluginEvent('showClone', this);
	      if (Sortable.eventCanceled) return; // show clone at dragEl or original position

	      if (rootEl.contains(dragEl) && !this.options.group.revertClone) {
	        rootEl.insertBefore(cloneEl, dragEl);
	      } else if (nextEl) {
	        rootEl.insertBefore(cloneEl, nextEl);
	      } else {
	        rootEl.appendChild(cloneEl);
	      }

	      if (this.options.group.revertClone) {
	        this.animate(dragEl, cloneEl);
	      }

	      css(cloneEl, 'display', '');
	      cloneHidden = false;
	    }
	  }
	};

	function _globalDragOver(
	/**Event*/
	evt) {
	  if (evt.dataTransfer) {
	    evt.dataTransfer.dropEffect = 'move';
	  }

	  evt.cancelable && evt.preventDefault();
	}

	function _onMove(fromEl, toEl, dragEl, dragRect, targetEl, targetRect, originalEvent, willInsertAfter) {
	  var evt,
	      sortable = fromEl[expando],
	      onMoveFn = sortable.options.onMove,
	      retVal; // Support for new CustomEvent feature

	  if (window.CustomEvent && !IE11OrLess && !Edge) {
	    evt = new CustomEvent('move', {
	      bubbles: true,
	      cancelable: true
	    });
	  } else {
	    evt = document.createEvent('Event');
	    evt.initEvent('move', true, true);
	  }

	  evt.to = toEl;
	  evt.from = fromEl;
	  evt.dragged = dragEl;
	  evt.draggedRect = dragRect;
	  evt.related = targetEl || toEl;
	  evt.relatedRect = targetRect || getRect(toEl);
	  evt.willInsertAfter = willInsertAfter;
	  evt.originalEvent = originalEvent;
	  fromEl.dispatchEvent(evt);

	  if (onMoveFn) {
	    retVal = onMoveFn.call(sortable, evt, originalEvent);
	  }

	  return retVal;
	}

	function _disableDraggable(el) {
	  el.draggable = false;
	}

	function _unsilent() {
	  _silent = false;
	}

	function _ghostIsLast(evt, vertical, sortable) {
	  var rect = getRect(lastChild(sortable.el, sortable.options.draggable));
	  var spacer = 10;
	  return vertical ? evt.clientX > rect.right + spacer || evt.clientX <= rect.right && evt.clientY > rect.bottom && evt.clientX >= rect.left : evt.clientX > rect.right && evt.clientY > rect.top || evt.clientX <= rect.right && evt.clientY > rect.bottom + spacer;
	}

	function _getSwapDirection(evt, target, targetRect, vertical, swapThreshold, invertedSwapThreshold, invertSwap, isLastTarget) {
	  var mouseOnAxis = vertical ? evt.clientY : evt.clientX,
	      targetLength = vertical ? targetRect.height : targetRect.width,
	      targetS1 = vertical ? targetRect.top : targetRect.left,
	      targetS2 = vertical ? targetRect.bottom : targetRect.right,
	      invert = false;

	  if (!invertSwap) {
	    // Never invert or create dragEl shadow when target movemenet causes mouse to move past the end of regular swapThreshold
	    if (isLastTarget && targetMoveDistance < targetLength * swapThreshold) {
	      // multiplied only by swapThreshold because mouse will already be inside target by (1 - threshold) * targetLength / 2
	      // check if past first invert threshold on side opposite of lastDirection
	      if (!pastFirstInvertThresh && (lastDirection === 1 ? mouseOnAxis > targetS1 + targetLength * invertedSwapThreshold / 2 : mouseOnAxis < targetS2 - targetLength * invertedSwapThreshold / 2)) {
	        // past first invert threshold, do not restrict inverted threshold to dragEl shadow
	        pastFirstInvertThresh = true;
	      }

	      if (!pastFirstInvertThresh) {
	        // dragEl shadow (target move distance shadow)
	        if (lastDirection === 1 ? mouseOnAxis < targetS1 + targetMoveDistance // over dragEl shadow
	        : mouseOnAxis > targetS2 - targetMoveDistance) {
	          return -lastDirection;
	        }
	      } else {
	        invert = true;
	      }
	    } else {
	      // Regular
	      if (mouseOnAxis > targetS1 + targetLength * (1 - swapThreshold) / 2 && mouseOnAxis < targetS2 - targetLength * (1 - swapThreshold) / 2) {
	        return _getInsertDirection(target);
	      }
	    }
	  }

	  invert = invert || invertSwap;

	  if (invert) {
	    // Invert of regular
	    if (mouseOnAxis < targetS1 + targetLength * invertedSwapThreshold / 2 || mouseOnAxis > targetS2 - targetLength * invertedSwapThreshold / 2) {
	      return mouseOnAxis > targetS1 + targetLength / 2 ? 1 : -1;
	    }
	  }

	  return 0;
	}
	/**
	 * Gets the direction dragEl must be swapped relative to target in order to make it
	 * seem that dragEl has been "inserted" into that element's position
	 * @param  {HTMLElement} target       The target whose position dragEl is being inserted at
	 * @return {Number}                   Direction dragEl must be swapped
	 */


	function _getInsertDirection(target) {
	  if (index(dragEl) < index(target)) {
	    return 1;
	  } else {
	    return -1;
	  }
	}
	/**
	 * Generate id
	 * @param   {HTMLElement} el
	 * @returns {String}
	 * @private
	 */


	function _generateId(el) {
	  var str = el.tagName + el.className + el.src + el.href + el.textContent,
	      i = str.length,
	      sum = 0;

	  while (i--) {
	    sum += str.charCodeAt(i);
	  }

	  return sum.toString(36);
	}

	function _saveInputCheckedState(root) {
	  savedInputChecked.length = 0;
	  var inputs = root.getElementsByTagName('input');
	  var idx = inputs.length;

	  while (idx--) {
	    var el = inputs[idx];
	    el.checked && savedInputChecked.push(el);
	  }
	}

	function _nextTick(fn) {
	  return setTimeout(fn, 0);
	}

	function _cancelNextTick(id) {
	  return clearTimeout(id);
	} // Fixed #973:


	if (documentExists) {
	  on(document, 'touchmove', function (evt) {
	    if ((Sortable.active || awaitingDragStarted) && evt.cancelable) {
	      evt.preventDefault();
	    }
	  });
	} // Export utils


	Sortable.utils = {
	  on: on,
	  off: off,
	  css: css,
	  find: find,
	  is: function is(el, selector) {
	    return !!closest(el, selector, el, false);
	  },
	  extend: extend,
	  throttle: throttle,
	  closest: closest,
	  toggleClass: toggleClass,
	  clone: clone,
	  index: index,
	  nextTick: _nextTick,
	  cancelNextTick: _cancelNextTick,
	  detectDirection: _detectDirection,
	  getChild: getChild
	};
	/**
	 * Get the Sortable instance of an element
	 * @param  {HTMLElement} element The element
	 * @return {Sortable|undefined}         The instance of Sortable
	 */

	Sortable.get = function (element) {
	  return element[expando];
	};
	/**
	 * Mount a plugin to Sortable
	 * @param  {...SortablePlugin|SortablePlugin[]} plugins       Plugins being mounted
	 */


	Sortable.mount = function () {
	  for (var _len = arguments.length, plugins = new Array(_len), _key = 0; _key < _len; _key++) {
	    plugins[_key] = arguments[_key];
	  }

	  if (plugins[0].constructor === Array) plugins = plugins[0];
	  plugins.forEach(function (plugin) {
	    if (!plugin.prototype || !plugin.prototype.constructor) {
	      throw "Sortable: Mounted plugin must be a constructor function, not ".concat({}.toString.call(plugin));
	    }

	    if (plugin.utils) Sortable.utils = _objectSpread({}, Sortable.utils, plugin.utils);
	    PluginManager.mount(plugin);
	  });
	};
	/**
	 * Create sortable instance
	 * @param {HTMLElement}  el
	 * @param {Object}      [options]
	 */


	Sortable.create = function (el, options) {
	  return new Sortable(el, options);
	}; // Export


	Sortable.version = version;

	var autoScrolls = [],
	    scrollEl,
	    scrollRootEl,
	    scrolling = false,
	    lastAutoScrollX,
	    lastAutoScrollY,
	    touchEvt$1,
	    pointerElemChangedInterval;

	function AutoScrollPlugin() {
	  function AutoScroll() {
	    this.defaults = {
	      scroll: true,
	      scrollSensitivity: 30,
	      scrollSpeed: 10,
	      bubbleScroll: true
	    }; // Bind all private methods

	    for (var fn in this) {
	      if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
	        this[fn] = this[fn].bind(this);
	      }
	    }
	  }

	  AutoScroll.prototype = {
	    dragStarted: function dragStarted(_ref) {
	      var originalEvent = _ref.originalEvent;

	      if (this.sortable.nativeDraggable) {
	        on(document, 'dragover', this._handleAutoScroll);
	      } else {
	        if (this.options.supportPointer) {
	          on(document, 'pointermove', this._handleFallbackAutoScroll);
	        } else if (originalEvent.touches) {
	          on(document, 'touchmove', this._handleFallbackAutoScroll);
	        } else {
	          on(document, 'mousemove', this._handleFallbackAutoScroll);
	        }
	      }
	    },
	    dragOverCompleted: function dragOverCompleted(_ref2) {
	      var originalEvent = _ref2.originalEvent;

	      // For when bubbling is canceled and using fallback (fallback 'touchmove' always reached)
	      if (!this.options.dragOverBubble && !originalEvent.rootEl) {
	        this._handleAutoScroll(originalEvent);
	      }
	    },
	    drop: function drop() {
	      if (this.sortable.nativeDraggable) {
	        off(document, 'dragover', this._handleAutoScroll);
	      } else {
	        off(document, 'pointermove', this._handleFallbackAutoScroll);
	        off(document, 'touchmove', this._handleFallbackAutoScroll);
	        off(document, 'mousemove', this._handleFallbackAutoScroll);
	      }

	      clearPointerElemChangedInterval();
	      clearAutoScrolls();
	      cancelThrottle();
	    },
	    nulling: function nulling() {
	      touchEvt$1 = scrollRootEl = scrollEl = scrolling = pointerElemChangedInterval = lastAutoScrollX = lastAutoScrollY = null;
	      autoScrolls.length = 0;
	    },
	    _handleFallbackAutoScroll: function _handleFallbackAutoScroll(evt) {
	      this._handleAutoScroll(evt, true);
	    },
	    _handleAutoScroll: function _handleAutoScroll(evt, fallback) {
	      var _this = this;

	      var x = (evt.touches ? evt.touches[0] : evt).clientX,
	          y = (evt.touches ? evt.touches[0] : evt).clientY,
	          elem = document.elementFromPoint(x, y);
	      touchEvt$1 = evt; // IE does not seem to have native autoscroll,
	      // Edge's autoscroll seems too conditional,
	      // MACOS Safari does not have autoscroll,
	      // Firefox and Chrome are good

	      if (fallback || Edge || IE11OrLess || Safari) {
	        autoScroll(evt, this.options, elem, fallback); // Listener for pointer element change

	        var ogElemScroller = getParentAutoScrollElement(elem, true);

	        if (scrolling && (!pointerElemChangedInterval || x !== lastAutoScrollX || y !== lastAutoScrollY)) {
	          pointerElemChangedInterval && clearPointerElemChangedInterval(); // Detect for pointer elem change, emulating native DnD behaviour

	          pointerElemChangedInterval = setInterval(function () {
	            var newElem = getParentAutoScrollElement(document.elementFromPoint(x, y), true);

	            if (newElem !== ogElemScroller) {
	              ogElemScroller = newElem;
	              clearAutoScrolls();
	            }

	            autoScroll(evt, _this.options, newElem, fallback);
	          }, 10);
	          lastAutoScrollX = x;
	          lastAutoScrollY = y;
	        }
	      } else {
	        // if DnD is enabled (and browser has good autoscrolling), first autoscroll will already scroll, so get parent autoscroll of first autoscroll
	        if (!this.options.bubbleScroll || getParentAutoScrollElement(elem, true) === getWindowScrollingElement()) {
	          clearAutoScrolls();
	          return;
	        }

	        autoScroll(evt, this.options, getParentAutoScrollElement(elem, false), false);
	      }
	    }
	  };
	  return _extends(AutoScroll, {
	    pluginName: 'scroll',
	    initializeByDefault: true
	  });
	}

	function clearAutoScrolls() {
	  autoScrolls.forEach(function (autoScroll) {
	    clearInterval(autoScroll.pid);
	  });
	  autoScrolls = [];
	}

	function clearPointerElemChangedInterval() {
	  clearInterval(pointerElemChangedInterval);
	}

	var autoScroll = throttle(function (evt, options, rootEl, isFallback) {
	  // Bug: https://bugzilla.mozilla.org/show_bug.cgi?id=505521
	  if (!options.scroll) return;
	  var x = (evt.touches ? evt.touches[0] : evt).clientX,
	      y = (evt.touches ? evt.touches[0] : evt).clientY,
	      sens = options.scrollSensitivity,
	      speed = options.scrollSpeed,
	      winScroller = getWindowScrollingElement();
	  var scrollThisInstance = false,
	      scrollCustomFn; // New scroll root, set scrollEl

	  if (scrollRootEl !== rootEl) {
	    scrollRootEl = rootEl;
	    clearAutoScrolls();
	    scrollEl = options.scroll;
	    scrollCustomFn = options.scrollFn;

	    if (scrollEl === true) {
	      scrollEl = getParentAutoScrollElement(rootEl, true);
	    }
	  }

	  var layersOut = 0;
	  var currentParent = scrollEl;

	  do {
	    var el = currentParent,
	        rect = getRect(el),
	        top = rect.top,
	        bottom = rect.bottom,
	        left = rect.left,
	        right = rect.right,
	        width = rect.width,
	        height = rect.height,
	        canScrollX = void 0,
	        canScrollY = void 0,
	        scrollWidth = el.scrollWidth,
	        scrollHeight = el.scrollHeight,
	        elCSS = css(el),
	        scrollPosX = el.scrollLeft,
	        scrollPosY = el.scrollTop;

	    if (el === winScroller) {
	      canScrollX = width < scrollWidth && (elCSS.overflowX === 'auto' || elCSS.overflowX === 'scroll' || elCSS.overflowX === 'visible');
	      canScrollY = height < scrollHeight && (elCSS.overflowY === 'auto' || elCSS.overflowY === 'scroll' || elCSS.overflowY === 'visible');
	    } else {
	      canScrollX = width < scrollWidth && (elCSS.overflowX === 'auto' || elCSS.overflowX === 'scroll');
	      canScrollY = height < scrollHeight && (elCSS.overflowY === 'auto' || elCSS.overflowY === 'scroll');
	    }

	    var vx = canScrollX && (Math.abs(right - x) <= sens && scrollPosX + width < scrollWidth) - (Math.abs(left - x) <= sens && !!scrollPosX);
	    var vy = canScrollY && (Math.abs(bottom - y) <= sens && scrollPosY + height < scrollHeight) - (Math.abs(top - y) <= sens && !!scrollPosY);

	    if (!autoScrolls[layersOut]) {
	      for (var i = 0; i <= layersOut; i++) {
	        if (!autoScrolls[i]) {
	          autoScrolls[i] = {};
	        }
	      }
	    }

	    if (autoScrolls[layersOut].vx != vx || autoScrolls[layersOut].vy != vy || autoScrolls[layersOut].el !== el) {
	      autoScrolls[layersOut].el = el;
	      autoScrolls[layersOut].vx = vx;
	      autoScrolls[layersOut].vy = vy;
	      clearInterval(autoScrolls[layersOut].pid);

	      if (vx != 0 || vy != 0) {
	        scrollThisInstance = true;
	        /* jshint loopfunc:true */

	        autoScrolls[layersOut].pid = setInterval(function () {
	          // emulate drag over during autoscroll (fallback), emulating native DnD behaviour
	          if (isFallback && this.layer === 0) {
	            Sortable.active._onTouchMove(touchEvt$1); // To move ghost if it is positioned absolutely

	          }

	          var scrollOffsetY = autoScrolls[this.layer].vy ? autoScrolls[this.layer].vy * speed : 0;
	          var scrollOffsetX = autoScrolls[this.layer].vx ? autoScrolls[this.layer].vx * speed : 0;

	          if (typeof scrollCustomFn === 'function') {
	            if (scrollCustomFn.call(Sortable.dragged.parentNode[expando], scrollOffsetX, scrollOffsetY, evt, touchEvt$1, autoScrolls[this.layer].el) !== 'continue') {
	              return;
	            }
	          }

	          scrollBy(autoScrolls[this.layer].el, scrollOffsetX, scrollOffsetY);
	        }.bind({
	          layer: layersOut
	        }), 24);
	      }
	    }

	    layersOut++;
	  } while (options.bubbleScroll && currentParent !== winScroller && (currentParent = getParentAutoScrollElement(currentParent, false)));

	  scrolling = scrollThisInstance; // in case another function catches scrolling as false in between when it is not
	}, 30);

	var drop = function drop(_ref) {
	  var originalEvent = _ref.originalEvent,
	      putSortable = _ref.putSortable,
	      dragEl = _ref.dragEl,
	      activeSortable = _ref.activeSortable,
	      dispatchSortableEvent = _ref.dispatchSortableEvent,
	      hideGhostForTarget = _ref.hideGhostForTarget,
	      unhideGhostForTarget = _ref.unhideGhostForTarget;
	  if (!originalEvent) return;
	  var toSortable = putSortable || activeSortable;
	  hideGhostForTarget();
	  var touch = originalEvent.changedTouches && originalEvent.changedTouches.length ? originalEvent.changedTouches[0] : originalEvent;
	  var target = document.elementFromPoint(touch.clientX, touch.clientY);
	  unhideGhostForTarget();

	  if (toSortable && !toSortable.el.contains(target)) {
	    dispatchSortableEvent('spill');
	    this.onSpill({
	      dragEl: dragEl,
	      putSortable: putSortable
	    });
	  }
	};

	function Revert() {}

	Revert.prototype = {
	  startIndex: null,
	  dragStart: function dragStart(_ref2) {
	    var oldDraggableIndex = _ref2.oldDraggableIndex;
	    this.startIndex = oldDraggableIndex;
	  },
	  onSpill: function onSpill(_ref3) {
	    var dragEl = _ref3.dragEl,
	        putSortable = _ref3.putSortable;
	    this.sortable.captureAnimationState();

	    if (putSortable) {
	      putSortable.captureAnimationState();
	    }

	    var nextSibling = getChild(this.sortable.el, this.startIndex, this.options);

	    if (nextSibling) {
	      this.sortable.el.insertBefore(dragEl, nextSibling);
	    } else {
	      this.sortable.el.appendChild(dragEl);
	    }

	    this.sortable.animateAll();

	    if (putSortable) {
	      putSortable.animateAll();
	    }
	  },
	  drop: drop
	};

	_extends(Revert, {
	  pluginName: 'revertOnSpill'
	});

	function Remove() {}

	Remove.prototype = {
	  onSpill: function onSpill(_ref4) {
	    var dragEl = _ref4.dragEl,
	        putSortable = _ref4.putSortable;
	    var parentSortable = putSortable || this.sortable;
	    parentSortable.captureAnimationState();
	    dragEl.parentNode && dragEl.parentNode.removeChild(dragEl);
	    parentSortable.animateAll();
	  },
	  drop: drop
	};

	_extends(Remove, {
	  pluginName: 'removeOnSpill'
	});

	Sortable.mount(new AutoScrollPlugin());
	Sortable.mount(Remove, Revert);

	function ArrayInput(input) {
	    var isAssociative = input.classList.contains('array-input-associative');
	    var inputName = input.getAttribute('data-name');

	    $$('.array-input-row', input).forEach(function (element) {
	        bindRowEvents(element);
	    });

	    Sortable.create(input, {
	        handle: '.sort-handle',
	        forceFallback: true
	    });

	    function addRow(row) {
	        var clone = row.cloneNode(true);
	        clearRow(clone);
	        bindRowEvents(clone);
	        if (row.nextSibling) {
	            row.parentNode.insertBefore(clone, row.nextSibling);
	        } else {
	            row.parentNode.appendChild(clone);
	        }
	    }

	    function removeRow(row) {
	        if ($$('.array-input-row', row.parentNode).length > 1) {
	            row.parentNode.removeChild(row);
	        } else {
	            clearRow(row);
	        }
	    }

	    function clearRow(row) {
	        var inputKey, inputValue;
	        if (isAssociative) {
	            inputKey = $('.array-input-key', row);
	            inputKey.value = '';
	            inputKey.removeAttribute('value');
	        }
	        inputValue = $('.array-input-value', row);
	        inputValue.value = '';
	        inputValue.removeAttribute('value');
	        inputValue.name = inputName + '[]';
	    }

	    function updateAssociativeRow(row) {
	        var inputKey = $('.array-input-key', row);
	        var inputValue = $('.array-input-value', row);
	        inputValue.name = inputName + '[' + inputKey.value.trim() + ']';
	    }

	    function bindRowEvents(row) {
	        var inputAdd = $('.array-input-add', row);
	        var inputRemove = $('.array-input-remove', row);
	        var inputKey, inputValue;

	        inputAdd.addEventListener('click', addRow.bind(inputAdd, row));
	        inputRemove.addEventListener('click', removeRow.bind(inputRemove, row));

	        if (isAssociative) {
	            inputKey = $('.array-input-key', row);
	            inputValue = $('.array-input-value', row);
	            inputKey.addEventListener('keyup', updateAssociativeRow.bind(inputKey, row));
	            inputValue.addEventListener('keyup',updateAssociativeRow.bind(inputValue, row));
	        }
	    }
	}

	function DatePicker(input, options) {
	    var defaults = {
	        dayLabels:  ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	        monthLabels: ['January', 'February', 'March', 'April', 'May', 'June', 'July' ,'August', 'September', 'October', 'November', 'December'],
	        weekStarts: 0,
	        todayLabel: 'Today',
	        format: 'YYYY-MM-DD'
	    };

	    var today = new Date();
	    var dateKeeper, dateHelpers, calendar;

	    options = Utils.extendObject({}, defaults, options);

	    dateKeeper = {
	        year: today.getFullYear(),
	        month: today.getMonth(),
	        day: today.getDate(),
	        setDate: function (date) {
	            this.year = date.getFullYear();
	            this.month = date.getMonth();
	            this.day = date.getDate();
	        },
	        lastDay: function () {
	            this.day = dateHelpers.daysInMonth(this.month, this.year);
	        },
	        prevYear: function () {
	            this.year--;
	        },
	        nextYear: function () {
	            this.year++;
	        },
	        prevMonth: function () {
	            this.month = dateHelpers.mod(this.month - 1, 12);
	            if (this.month === 11) {
	                this.prevYear();
	            }
	            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
	                this.lastDay();
	            }
	        },
	        nextMonth: function () {
	            this.month = dateHelpers.mod(this.month + 1, 12);
	            if (this.month === 0) {
	                this.nextYear();
	            }
	            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
	                this.lastDay();
	            }
	        },
	        prevWeek: function () {
	            this.day -= 7;
	            if (this.day < 1) {
	                this.prevMonth();
	                this.day += dateHelpers.daysInMonth(this.month, this.year);
	            }
	        },
	        nextWeek: function () {
	            this.day += 7;
	            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
	                this.day -= dateHelpers.daysInMonth(this.month, this.year);
	                this.nextMonth();
	            }
	        },
	        prevDay: function () {
	            this.day--;
	            if (this.day < 1) {
	                this.prevMonth();
	                this.lastDay();
	            }
	        },
	        nextDay: function () {
	            this.day++;
	            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
	                this.nextMonth();
	                this.day = 1;
	            }
	        }
	    };

	    dateHelpers = {
	        _daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
	        mod: function (n, m) {
	            return ((n % m) + m) % m;
	        },
	        pad: function (num) {
	            return num.toString().length === 1 ? '0' + num : num;
	        },
	        isValidDate: function (date) {
	            return date && !isNaN(Date.parse(date));
	        },
	        isLeapYear: function (year) {
	            return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
	        },
	        daysInMonth: function (month, year) {
	            return month === 1 && this.isLeapYear(year) ? 29 : this._daysInMonth[month];
	        },
	        formatDateTime: function (date) {
	            var format = options.format;
	            var year = date.getFullYear();
	            var month = date.getMonth() + 1;
	            var day = date.getDate();
	            var hours = date.getHours();
	            var minutes = date.getMinutes();
	            var seconds = date.getSeconds();
	            var am = hours < 12;
	            if (format.indexOf('a') > -1) {
	                hours = dateHelpers.mod(hours, 12) > 0 ? dateHelpers.mod(hours, 12) : 12;
	            }
	            return format.replace('YYYY', year)
	                .replace('YY', year.toString().substr(-2))
	                .replace('MM', dateHelpers.pad(month))
	                .replace('DD', dateHelpers.pad(day))
	                .replace('hh', dateHelpers.pad(hours))
	                .replace('mm', dateHelpers.pad(minutes))
	                .replace('ss', dateHelpers.pad(seconds))
	                .replace('a', am ? 'AM' : 'PM');
	        }
	    };

	    calendar = $('.calendar') ? $('.calendar') : generateCalendar();

	    initInput();

	    function initInput() {
	        var value = input.value;
	        input.readOnly = true;
	        input.size = options.format.length;
	        if (dateHelpers.isValidDate(value)) {
	            value = new Date(value);
	            input.setAttribute('data-date', value);
	            input.value = dateHelpers.formatDateTime(value);
	        }
	        input.addEventListener('change', function () {
	            if (this.value === '') {
	                this.setAttribute('data-date', '');
	            } else {
	                this.value = dateHelpers.formatDateTime(this.getAttribute('data-date'));
	            }
	        });
	        input.addEventListener('keydown', function (event) {
	            var date = this.getAttribute('data-date');
	            dateKeeper.setDate(dateHelpers.isValidDate(date) ? new Date(date) : new Date());
	            switch (event.which) {
	            case 13: // enter
	                $('.calendar-day.selected', calendar).click();
	                calendar.style.display = 'none';
	                break;
	            case 8: // backspace
	                this.value = '';
	                this.blur();
	                calendar.style.display = 'none';
	                break;
	            case 27: // escape
	                this.blur();
	                calendar.style.display = 'none';
	                break;
	            case 37: // left arrow
	                if (event.ctrlKey || event.metaKey) {
	                    if (event.shiftKey) {
	                        dateKeeper.prevYear();
	                    } else {
	                        dateKeeper.prevMonth();
	                    }
	                } else {
	                    dateKeeper.prevDay();
	                }
	                updateInput(this);
	                break;
	            case 38: // up arrow
	                dateKeeper.prevWeek();
	                updateInput(this);
	                break;
	            case 39: // right arrow
	                if (event.ctrlKey || event.metaKey) {
	                    if (event.shiftKey) {
	                        dateKeeper.nextYear();
	                    } else {
	                        dateKeeper.nextMonth();
	                    }
	                } else {
	                    dateKeeper.nextDay();
	                }
	                updateInput(this);
	                break;
	            case 40: // down arrow
	                dateKeeper.nextWeek();
	                updateInput(this);
	                break;
	            case 48: // 0
	                if (event.ctrlKey || event.metaKey) {
	                    dateKeeper.setDate(new Date());
	                }
	                updateInput(this);
	                break;
	            default:
	                return;
	            }
	            event.stopPropagation();
	            event.preventDefault();
	        });

	        input.addEventListener('focus', function () {
	            var date = dateHelpers.isValidDate(this.getAttribute('data-date')) ? new Date(this.getAttribute('data-date')) : new Date();
	            dateKeeper.setDate(date);
	            generateCalendarTable(dateKeeper.year, dateKeeper.month, dateKeeper.day);
	            calendar.style.display = 'block';
	            setCalendarPosition();
	        });

	        input.addEventListener('blur', function () {
	            calendar.style.display = 'none';
	        });
	    }

	    function updateInput(input) {
	        var date = new Date(dateKeeper.year, dateKeeper.month, dateKeeper.day);
	        generateCalendarTable(dateKeeper.year, dateKeeper.month, dateKeeper.day);
	        input.value = dateHelpers.formatDateTime(date);
	        input.setAttribute('data-date', date);
	    }

	    function getCurrentInput() {
	        return document.activeElement.classList.contains('date-input') ? document.activeElement : null;
	    }

	    function generateCalendar() {
	        calendar = document.createElement('div');
	        calendar.className = 'calendar';
	        calendar.innerHTML = '<div class="calendar-buttons"><button type="button" class="prevMonth"><i class="i-chevron-left"></i></button><button class="currentMonth">' + options.todayLabel + '</button><button type="button" class="nextMonth"><i class="i-chevron-right"></i></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>';
	        document.body.appendChild(calendar);

	        $('.currentMonth', calendar).addEventListener('mousedown', function (event) {
	            var input = getCurrentInput();
	            var today = new Date();
	            dateKeeper.setDate(today);
	            updateInput(input);
	            input.blur();
	            event.preventDefault();
	        });

	        Utils.longClick($('.prevMonth', calendar), function (event) {
	            dateKeeper.prevMonth();
	            generateCalendarTable(dateKeeper.year, dateKeeper.month);
	            event.preventDefault();
	        }, 750, 500);

	        Utils.longClick($('.nextMonth', calendar), function (event) {
	            dateKeeper.nextMonth();
	            generateCalendarTable(dateKeeper.year, dateKeeper.month);
	            event.preventDefault();
	        }, 750, 500);

	        window.addEventListener('mousedown', function (event) {
	            if (calendar.style.display !== 'none') {
	                if (event.target.closest('.calendar')) {
	                    event.preventDefault();
	                }
	            }
	        });

	        window.addEventListener('resize', Utils.throttle(setCalendarPosition, 100));

	        return calendar;
	    }

	    function generateCalendarTable(year, month, day) {
	        var i, j;
	        var num = 1;
	        var firstDay = new Date(year, month, 1).getDay();
	        var monthLength = dateHelpers.daysInMonth(month, year);
	        var monthName = options.monthLabels[month];
	        var start = dateHelpers.mod(firstDay - options.weekStarts, 7);
	        var html = '';
	        html += '<tr><th class="calendar-header" colspan="7">';
	        html += monthName + '&nbsp;' + year;
	        html += '</th></tr>';
	        html += '<tr>';
	        for (i = 0; i < 7; i++ ){
	            html += '<td class="calendar-header-day">';
	            html += options.dayLabels[dateHelpers.mod(i + options.weekStarts, 7)];
	            html += '</td>';
	        }
	        html += '</tr><tr>';
	        for (i = 0; i < 6; i++) {
	            for (j = 0; j < 7; j++) {
	                if (num <= monthLength && (i > 0 || j >= start)) {
	                    if (num === day) {
	                        html += '<td class="calendar-day selected">';
	                    } else {
	                        html += '<td class="calendar-day">';
	                    }
	                    html += num++;
	                } else if (num === 1) {
	                    html += '<td class="calendar-prev-month-day">';
	                    html += dateHelpers.daysInMonth(dateHelpers.mod(month - 1, 12), year) - start + j + 1;
	                } else {
	                    html += '<td class="calendar-next-month-day">';
	                    html += num++ - monthLength;
	                }
	                html += '</td>';
	            }
	            html += '</tr><tr>';
	        }
	        html += '</tr>';
	        $('.calendar-table', calendar).innerHTML = html;
	        $$('.calendar-day', calendar).forEach(function (element) {
	            element.addEventListener('mousedown', function (event) {
	                event.stopPropagation();
	                event.preventDefault();
	            });
	            element.addEventListener('click', function () {
	                var input = getCurrentInput();
	                var date = new Date(dateKeeper.year, dateKeeper.month, parseInt(this.textContent));
	                input.setAttribute('data-date', date);
	                input.value = dateHelpers.formatDateTime(date);
	                input.blur();
	            });
	        });
	    }

	    function setCalendarPosition() {
	        var inputRect, inputTop, inputLeft,
	            calendarRect, calendarTop, calendarLeft, calendarWidth, calendarHeight,
	            windowWidth, windowHeight;

	        input = getCurrentInput();

	        if (!input || calendar.style.display !== 'block') {
	            return;
	        }

	        inputRect = input.getBoundingClientRect();
	        inputTop = inputRect.top + window.pageYOffset;
	        inputLeft = inputRect.left + window.pageXOffset;
	        calendar.style.top = (inputTop + input.offsetHeight) + 'px';
	        calendar.style.left = (inputLeft + input.offsetLeft) + 'px';

	        calendarRect = calendar.getBoundingClientRect();
	        calendarTop = calendarRect.top + window.pageYOffset;
	        calendarLeft = calendarRect.left + window.pageXOffset;
	        calendarWidth = Utils.outerWidth(calendar);
	        calendarHeight = Utils.outerHeight(calendar);

	        windowWidth = document.documentElement.clientWidth;
	        windowHeight = document.documentElement.clientHeight;

	        if (calendarLeft + calendarWidth > windowWidth) {
	            calendar.style.left = (windowWidth - calendarWidth) + 'px';
	        }

	        if (calendarTop < window.pageYOffset || window.pageYOffset < calendarTop + calendarHeight - windowHeight) {
	            window.scrollTo(window.pageXOffset, calendarTop + calendarHeight - windowHeight);
	        }
	    }
	}

	var codemirror = createCommonjsModule(function (module, exports) {
	// CodeMirror, copyright (c) by Marijn Haverbeke and others
	// Distributed under an MIT license: https://codemirror.net/LICENSE

	// This is CodeMirror (https://codemirror.net), a code editor
	// implemented in JavaScript on top of the browser's DOM.
	//
	// You can find some technical background for some of the code below
	// at http://marijnhaverbeke.nl/blog/#cm-internals .

	(function (global, factory) {
	   module.exports = factory() ;
	}(commonjsGlobal, (function () {
	  // Kludges for bugs and behavior differences that can't be feature
	  // detected are enabled based on userAgent etc sniffing.
	  var userAgent = navigator.userAgent;
	  var platform = navigator.platform;

	  var gecko = /gecko\/\d/i.test(userAgent);
	  var ie_upto10 = /MSIE \d/.test(userAgent);
	  var ie_11up = /Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(userAgent);
	  var edge = /Edge\/(\d+)/.exec(userAgent);
	  var ie = ie_upto10 || ie_11up || edge;
	  var ie_version = ie && (ie_upto10 ? document.documentMode || 6 : +(edge || ie_11up)[1]);
	  var webkit = !edge && /WebKit\//.test(userAgent);
	  var qtwebkit = webkit && /Qt\/\d+\.\d+/.test(userAgent);
	  var chrome = !edge && /Chrome\//.test(userAgent);
	  var presto = /Opera\//.test(userAgent);
	  var safari = /Apple Computer/.test(navigator.vendor);
	  var mac_geMountainLion = /Mac OS X 1\d\D([8-9]|\d\d)\D/.test(userAgent);
	  var phantom = /PhantomJS/.test(userAgent);

	  var ios = !edge && /AppleWebKit/.test(userAgent) && /Mobile\/\w+/.test(userAgent);
	  var android = /Android/.test(userAgent);
	  // This is woefully incomplete. Suggestions for alternative methods welcome.
	  var mobile = ios || android || /webOS|BlackBerry|Opera Mini|Opera Mobi|IEMobile/i.test(userAgent);
	  var mac = ios || /Mac/.test(platform);
	  var chromeOS = /\bCrOS\b/.test(userAgent);
	  var windows = /win/i.test(platform);

	  var presto_version = presto && userAgent.match(/Version\/(\d*\.\d*)/);
	  if (presto_version) { presto_version = Number(presto_version[1]); }
	  if (presto_version && presto_version >= 15) { presto = false; webkit = true; }
	  // Some browsers use the wrong event properties to signal cmd/ctrl on OS X
	  var flipCtrlCmd = mac && (qtwebkit || presto && (presto_version == null || presto_version < 12.11));
	  var captureRightClick = gecko || (ie && ie_version >= 9);

	  function classTest(cls) { return new RegExp("(^|\\s)" + cls + "(?:$|\\s)\\s*") }

	  var rmClass = function(node, cls) {
	    var current = node.className;
	    var match = classTest(cls).exec(current);
	    if (match) {
	      var after = current.slice(match.index + match[0].length);
	      node.className = current.slice(0, match.index) + (after ? match[1] + after : "");
	    }
	  };

	  function removeChildren(e) {
	    for (var count = e.childNodes.length; count > 0; --count)
	      { e.removeChild(e.firstChild); }
	    return e
	  }

	  function removeChildrenAndAdd(parent, e) {
	    return removeChildren(parent).appendChild(e)
	  }

	  function elt(tag, content, className, style) {
	    var e = document.createElement(tag);
	    if (className) { e.className = className; }
	    if (style) { e.style.cssText = style; }
	    if (typeof content == "string") { e.appendChild(document.createTextNode(content)); }
	    else if (content) { for (var i = 0; i < content.length; ++i) { e.appendChild(content[i]); } }
	    return e
	  }
	  // wrapper for elt, which removes the elt from the accessibility tree
	  function eltP(tag, content, className, style) {
	    var e = elt(tag, content, className, style);
	    e.setAttribute("role", "presentation");
	    return e
	  }

	  var range;
	  if (document.createRange) { range = function(node, start, end, endNode) {
	    var r = document.createRange();
	    r.setEnd(endNode || node, end);
	    r.setStart(node, start);
	    return r
	  }; }
	  else { range = function(node, start, end) {
	    var r = document.body.createTextRange();
	    try { r.moveToElementText(node.parentNode); }
	    catch(e) { return r }
	    r.collapse(true);
	    r.moveEnd("character", end);
	    r.moveStart("character", start);
	    return r
	  }; }

	  function contains(parent, child) {
	    if (child.nodeType == 3) // Android browser always returns false when child is a textnode
	      { child = child.parentNode; }
	    if (parent.contains)
	      { return parent.contains(child) }
	    do {
	      if (child.nodeType == 11) { child = child.host; }
	      if (child == parent) { return true }
	    } while (child = child.parentNode)
	  }

	  function activeElt() {
	    // IE and Edge may throw an "Unspecified Error" when accessing document.activeElement.
	    // IE < 10 will throw when accessed while the page is loading or in an iframe.
	    // IE > 9 and Edge will throw when accessed in an iframe if document.body is unavailable.
	    var activeElement;
	    try {
	      activeElement = document.activeElement;
	    } catch(e) {
	      activeElement = document.body || null;
	    }
	    while (activeElement && activeElement.shadowRoot && activeElement.shadowRoot.activeElement)
	      { activeElement = activeElement.shadowRoot.activeElement; }
	    return activeElement
	  }

	  function addClass(node, cls) {
	    var current = node.className;
	    if (!classTest(cls).test(current)) { node.className += (current ? " " : "") + cls; }
	  }
	  function joinClasses(a, b) {
	    var as = a.split(" ");
	    for (var i = 0; i < as.length; i++)
	      { if (as[i] && !classTest(as[i]).test(b)) { b += " " + as[i]; } }
	    return b
	  }

	  var selectInput = function(node) { node.select(); };
	  if (ios) // Mobile Safari apparently has a bug where select() is broken.
	    { selectInput = function(node) { node.selectionStart = 0; node.selectionEnd = node.value.length; }; }
	  else if (ie) // Suppress mysterious IE10 errors
	    { selectInput = function(node) { try { node.select(); } catch(_e) {} }; }

	  function bind(f) {
	    var args = Array.prototype.slice.call(arguments, 1);
	    return function(){return f.apply(null, args)}
	  }

	  function copyObj(obj, target, overwrite) {
	    if (!target) { target = {}; }
	    for (var prop in obj)
	      { if (obj.hasOwnProperty(prop) && (overwrite !== false || !target.hasOwnProperty(prop)))
	        { target[prop] = obj[prop]; } }
	    return target
	  }

	  // Counts the column offset in a string, taking tabs into account.
	  // Used mostly to find indentation.
	  function countColumn(string, end, tabSize, startIndex, startValue) {
	    if (end == null) {
	      end = string.search(/[^\s\u00a0]/);
	      if (end == -1) { end = string.length; }
	    }
	    for (var i = startIndex || 0, n = startValue || 0;;) {
	      var nextTab = string.indexOf("\t", i);
	      if (nextTab < 0 || nextTab >= end)
	        { return n + (end - i) }
	      n += nextTab - i;
	      n += tabSize - (n % tabSize);
	      i = nextTab + 1;
	    }
	  }

	  var Delayed = function() {
	    this.id = null;
	    this.f = null;
	    this.time = 0;
	    this.handler = bind(this.onTimeout, this);
	  };
	  Delayed.prototype.onTimeout = function (self) {
	    self.id = 0;
	    if (self.time <= +new Date) {
	      self.f();
	    } else {
	      setTimeout(self.handler, self.time - +new Date);
	    }
	  };
	  Delayed.prototype.set = function (ms, f) {
	    this.f = f;
	    var time = +new Date + ms;
	    if (!this.id || time < this.time) {
	      clearTimeout(this.id);
	      this.id = setTimeout(this.handler, ms);
	      this.time = time;
	    }
	  };

	  function indexOf(array, elt) {
	    for (var i = 0; i < array.length; ++i)
	      { if (array[i] == elt) { return i } }
	    return -1
	  }

	  // Number of pixels added to scroller and sizer to hide scrollbar
	  var scrollerGap = 30;

	  // Returned or thrown by various protocols to signal 'I'm not
	  // handling this'.
	  var Pass = {toString: function(){return "CodeMirror.Pass"}};

	  // Reused option objects for setSelection & friends
	  var sel_dontScroll = {scroll: false}, sel_mouse = {origin: "*mouse"}, sel_move = {origin: "+move"};

	  // The inverse of countColumn -- find the offset that corresponds to
	  // a particular column.
	  function findColumn(string, goal, tabSize) {
	    for (var pos = 0, col = 0;;) {
	      var nextTab = string.indexOf("\t", pos);
	      if (nextTab == -1) { nextTab = string.length; }
	      var skipped = nextTab - pos;
	      if (nextTab == string.length || col + skipped >= goal)
	        { return pos + Math.min(skipped, goal - col) }
	      col += nextTab - pos;
	      col += tabSize - (col % tabSize);
	      pos = nextTab + 1;
	      if (col >= goal) { return pos }
	    }
	  }

	  var spaceStrs = [""];
	  function spaceStr(n) {
	    while (spaceStrs.length <= n)
	      { spaceStrs.push(lst(spaceStrs) + " "); }
	    return spaceStrs[n]
	  }

	  function lst(arr) { return arr[arr.length-1] }

	  function map(array, f) {
	    var out = [];
	    for (var i = 0; i < array.length; i++) { out[i] = f(array[i], i); }
	    return out
	  }

	  function insertSorted(array, value, score) {
	    var pos = 0, priority = score(value);
	    while (pos < array.length && score(array[pos]) <= priority) { pos++; }
	    array.splice(pos, 0, value);
	  }

	  function nothing() {}

	  function createObj(base, props) {
	    var inst;
	    if (Object.create) {
	      inst = Object.create(base);
	    } else {
	      nothing.prototype = base;
	      inst = new nothing();
	    }
	    if (props) { copyObj(props, inst); }
	    return inst
	  }

	  var nonASCIISingleCaseWordChar = /[\u00df\u0587\u0590-\u05f4\u0600-\u06ff\u3040-\u309f\u30a0-\u30ff\u3400-\u4db5\u4e00-\u9fcc\uac00-\ud7af]/;
	  function isWordCharBasic(ch) {
	    return /\w/.test(ch) || ch > "\x80" &&
	      (ch.toUpperCase() != ch.toLowerCase() || nonASCIISingleCaseWordChar.test(ch))
	  }
	  function isWordChar(ch, helper) {
	    if (!helper) { return isWordCharBasic(ch) }
	    if (helper.source.indexOf("\\w") > -1 && isWordCharBasic(ch)) { return true }
	    return helper.test(ch)
	  }

	  function isEmpty(obj) {
	    for (var n in obj) { if (obj.hasOwnProperty(n) && obj[n]) { return false } }
	    return true
	  }

	  // Extending unicode characters. A series of a non-extending char +
	  // any number of extending chars is treated as a single unit as far
	  // as editing and measuring is concerned. This is not fully correct,
	  // since some scripts/fonts/browsers also treat other configurations
	  // of code points as a group.
	  var extendingChars = /[\u0300-\u036f\u0483-\u0489\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7\u0610-\u061a\u064b-\u065e\u0670\u06d6-\u06dc\u06de-\u06e4\u06e7\u06e8\u06ea-\u06ed\u0711\u0730-\u074a\u07a6-\u07b0\u07eb-\u07f3\u0816-\u0819\u081b-\u0823\u0825-\u0827\u0829-\u082d\u0900-\u0902\u093c\u0941-\u0948\u094d\u0951-\u0955\u0962\u0963\u0981\u09bc\u09be\u09c1-\u09c4\u09cd\u09d7\u09e2\u09e3\u0a01\u0a02\u0a3c\u0a41\u0a42\u0a47\u0a48\u0a4b-\u0a4d\u0a51\u0a70\u0a71\u0a75\u0a81\u0a82\u0abc\u0ac1-\u0ac5\u0ac7\u0ac8\u0acd\u0ae2\u0ae3\u0b01\u0b3c\u0b3e\u0b3f\u0b41-\u0b44\u0b4d\u0b56\u0b57\u0b62\u0b63\u0b82\u0bbe\u0bc0\u0bcd\u0bd7\u0c3e-\u0c40\u0c46-\u0c48\u0c4a-\u0c4d\u0c55\u0c56\u0c62\u0c63\u0cbc\u0cbf\u0cc2\u0cc6\u0ccc\u0ccd\u0cd5\u0cd6\u0ce2\u0ce3\u0d3e\u0d41-\u0d44\u0d4d\u0d57\u0d62\u0d63\u0dca\u0dcf\u0dd2-\u0dd4\u0dd6\u0ddf\u0e31\u0e34-\u0e3a\u0e47-\u0e4e\u0eb1\u0eb4-\u0eb9\u0ebb\u0ebc\u0ec8-\u0ecd\u0f18\u0f19\u0f35\u0f37\u0f39\u0f71-\u0f7e\u0f80-\u0f84\u0f86\u0f87\u0f90-\u0f97\u0f99-\u0fbc\u0fc6\u102d-\u1030\u1032-\u1037\u1039\u103a\u103d\u103e\u1058\u1059\u105e-\u1060\u1071-\u1074\u1082\u1085\u1086\u108d\u109d\u135f\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17b7-\u17bd\u17c6\u17c9-\u17d3\u17dd\u180b-\u180d\u18a9\u1920-\u1922\u1927\u1928\u1932\u1939-\u193b\u1a17\u1a18\u1a56\u1a58-\u1a5e\u1a60\u1a62\u1a65-\u1a6c\u1a73-\u1a7c\u1a7f\u1b00-\u1b03\u1b34\u1b36-\u1b3a\u1b3c\u1b42\u1b6b-\u1b73\u1b80\u1b81\u1ba2-\u1ba5\u1ba8\u1ba9\u1c2c-\u1c33\u1c36\u1c37\u1cd0-\u1cd2\u1cd4-\u1ce0\u1ce2-\u1ce8\u1ced\u1dc0-\u1de6\u1dfd-\u1dff\u200c\u200d\u20d0-\u20f0\u2cef-\u2cf1\u2de0-\u2dff\u302a-\u302f\u3099\u309a\ua66f-\ua672\ua67c\ua67d\ua6f0\ua6f1\ua802\ua806\ua80b\ua825\ua826\ua8c4\ua8e0-\ua8f1\ua926-\ua92d\ua947-\ua951\ua980-\ua982\ua9b3\ua9b6-\ua9b9\ua9bc\uaa29-\uaa2e\uaa31\uaa32\uaa35\uaa36\uaa43\uaa4c\uaab0\uaab2-\uaab4\uaab7\uaab8\uaabe\uaabf\uaac1\uabe5\uabe8\uabed\udc00-\udfff\ufb1e\ufe00-\ufe0f\ufe20-\ufe26\uff9e\uff9f]/;
	  function isExtendingChar(ch) { return ch.charCodeAt(0) >= 768 && extendingChars.test(ch) }

	  // Returns a number from the range [`0`; `str.length`] unless `pos` is outside that range.
	  function skipExtendingChars(str, pos, dir) {
	    while ((dir < 0 ? pos > 0 : pos < str.length) && isExtendingChar(str.charAt(pos))) { pos += dir; }
	    return pos
	  }

	  // Returns the value from the range [`from`; `to`] that satisfies
	  // `pred` and is closest to `from`. Assumes that at least `to`
	  // satisfies `pred`. Supports `from` being greater than `to`.
	  function findFirst(pred, from, to) {
	    // At any point we are certain `to` satisfies `pred`, don't know
	    // whether `from` does.
	    var dir = from > to ? -1 : 1;
	    for (;;) {
	      if (from == to) { return from }
	      var midF = (from + to) / 2, mid = dir < 0 ? Math.ceil(midF) : Math.floor(midF);
	      if (mid == from) { return pred(mid) ? from : to }
	      if (pred(mid)) { to = mid; }
	      else { from = mid + dir; }
	    }
	  }

	  // BIDI HELPERS

	  function iterateBidiSections(order, from, to, f) {
	    if (!order) { return f(from, to, "ltr", 0) }
	    var found = false;
	    for (var i = 0; i < order.length; ++i) {
	      var part = order[i];
	      if (part.from < to && part.to > from || from == to && part.to == from) {
	        f(Math.max(part.from, from), Math.min(part.to, to), part.level == 1 ? "rtl" : "ltr", i);
	        found = true;
	      }
	    }
	    if (!found) { f(from, to, "ltr"); }
	  }

	  var bidiOther = null;
	  function getBidiPartAt(order, ch, sticky) {
	    var found;
	    bidiOther = null;
	    for (var i = 0; i < order.length; ++i) {
	      var cur = order[i];
	      if (cur.from < ch && cur.to > ch) { return i }
	      if (cur.to == ch) {
	        if (cur.from != cur.to && sticky == "before") { found = i; }
	        else { bidiOther = i; }
	      }
	      if (cur.from == ch) {
	        if (cur.from != cur.to && sticky != "before") { found = i; }
	        else { bidiOther = i; }
	      }
	    }
	    return found != null ? found : bidiOther
	  }

	  // Bidirectional ordering algorithm
	  // See http://unicode.org/reports/tr9/tr9-13.html for the algorithm
	  // that this (partially) implements.

	  // One-char codes used for character types:
	  // L (L):   Left-to-Right
	  // R (R):   Right-to-Left
	  // r (AL):  Right-to-Left Arabic
	  // 1 (EN):  European Number
	  // + (ES):  European Number Separator
	  // % (ET):  European Number Terminator
	  // n (AN):  Arabic Number
	  // , (CS):  Common Number Separator
	  // m (NSM): Non-Spacing Mark
	  // b (BN):  Boundary Neutral
	  // s (B):   Paragraph Separator
	  // t (S):   Segment Separator
	  // w (WS):  Whitespace
	  // N (ON):  Other Neutrals

	  // Returns null if characters are ordered as they appear
	  // (left-to-right), or an array of sections ({from, to, level}
	  // objects) in the order in which they occur visually.
	  var bidiOrdering = (function() {
	    // Character types for codepoints 0 to 0xff
	    var lowTypes = "bbbbbbbbbtstwsbbbbbbbbbbbbbbssstwNN%%%NNNNNN,N,N1111111111NNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNbbbbbbsbbbbbbbbbbbbbbbbbbbbbbbbbb,N%%%%NNNNLNNNNN%%11NLNNN1LNNNNNLLLLLLLLLLLLLLLLLLLLLLLNLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLN";
	    // Character types for codepoints 0x600 to 0x6f9
	    var arabicTypes = "nnnnnnNNr%%r,rNNmmmmmmmmmmmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmmmmmmmmnnnnnnnnnn%nnrrrmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmnNmmmmmmrrmmNmmmmrr1111111111";
	    function charType(code) {
	      if (code <= 0xf7) { return lowTypes.charAt(code) }
	      else if (0x590 <= code && code <= 0x5f4) { return "R" }
	      else if (0x600 <= code && code <= 0x6f9) { return arabicTypes.charAt(code - 0x600) }
	      else if (0x6ee <= code && code <= 0x8ac) { return "r" }
	      else if (0x2000 <= code && code <= 0x200b) { return "w" }
	      else if (code == 0x200c) { return "b" }
	      else { return "L" }
	    }

	    var bidiRE = /[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/;
	    var isNeutral = /[stwN]/, isStrong = /[LRr]/, countsAsLeft = /[Lb1n]/, countsAsNum = /[1n]/;

	    function BidiSpan(level, from, to) {
	      this.level = level;
	      this.from = from; this.to = to;
	    }

	    return function(str, direction) {
	      var outerType = direction == "ltr" ? "L" : "R";

	      if (str.length == 0 || direction == "ltr" && !bidiRE.test(str)) { return false }
	      var len = str.length, types = [];
	      for (var i = 0; i < len; ++i)
	        { types.push(charType(str.charCodeAt(i))); }

	      // W1. Examine each non-spacing mark (NSM) in the level run, and
	      // change the type of the NSM to the type of the previous
	      // character. If the NSM is at the start of the level run, it will
	      // get the type of sor.
	      for (var i$1 = 0, prev = outerType; i$1 < len; ++i$1) {
	        var type = types[i$1];
	        if (type == "m") { types[i$1] = prev; }
	        else { prev = type; }
	      }

	      // W2. Search backwards from each instance of a European number
	      // until the first strong type (R, L, AL, or sor) is found. If an
	      // AL is found, change the type of the European number to Arabic
	      // number.
	      // W3. Change all ALs to R.
	      for (var i$2 = 0, cur = outerType; i$2 < len; ++i$2) {
	        var type$1 = types[i$2];
	        if (type$1 == "1" && cur == "r") { types[i$2] = "n"; }
	        else if (isStrong.test(type$1)) { cur = type$1; if (type$1 == "r") { types[i$2] = "R"; } }
	      }

	      // W4. A single European separator between two European numbers
	      // changes to a European number. A single common separator between
	      // two numbers of the same type changes to that type.
	      for (var i$3 = 1, prev$1 = types[0]; i$3 < len - 1; ++i$3) {
	        var type$2 = types[i$3];
	        if (type$2 == "+" && prev$1 == "1" && types[i$3+1] == "1") { types[i$3] = "1"; }
	        else if (type$2 == "," && prev$1 == types[i$3+1] &&
	                 (prev$1 == "1" || prev$1 == "n")) { types[i$3] = prev$1; }
	        prev$1 = type$2;
	      }

	      // W5. A sequence of European terminators adjacent to European
	      // numbers changes to all European numbers.
	      // W6. Otherwise, separators and terminators change to Other
	      // Neutral.
	      for (var i$4 = 0; i$4 < len; ++i$4) {
	        var type$3 = types[i$4];
	        if (type$3 == ",") { types[i$4] = "N"; }
	        else if (type$3 == "%") {
	          var end = (void 0);
	          for (end = i$4 + 1; end < len && types[end] == "%"; ++end) {}
	          var replace = (i$4 && types[i$4-1] == "!") || (end < len && types[end] == "1") ? "1" : "N";
	          for (var j = i$4; j < end; ++j) { types[j] = replace; }
	          i$4 = end - 1;
	        }
	      }

	      // W7. Search backwards from each instance of a European number
	      // until the first strong type (R, L, or sor) is found. If an L is
	      // found, then change the type of the European number to L.
	      for (var i$5 = 0, cur$1 = outerType; i$5 < len; ++i$5) {
	        var type$4 = types[i$5];
	        if (cur$1 == "L" && type$4 == "1") { types[i$5] = "L"; }
	        else if (isStrong.test(type$4)) { cur$1 = type$4; }
	      }

	      // N1. A sequence of neutrals takes the direction of the
	      // surrounding strong text if the text on both sides has the same
	      // direction. European and Arabic numbers act as if they were R in
	      // terms of their influence on neutrals. Start-of-level-run (sor)
	      // and end-of-level-run (eor) are used at level run boundaries.
	      // N2. Any remaining neutrals take the embedding direction.
	      for (var i$6 = 0; i$6 < len; ++i$6) {
	        if (isNeutral.test(types[i$6])) {
	          var end$1 = (void 0);
	          for (end$1 = i$6 + 1; end$1 < len && isNeutral.test(types[end$1]); ++end$1) {}
	          var before = (i$6 ? types[i$6-1] : outerType) == "L";
	          var after = (end$1 < len ? types[end$1] : outerType) == "L";
	          var replace$1 = before == after ? (before ? "L" : "R") : outerType;
	          for (var j$1 = i$6; j$1 < end$1; ++j$1) { types[j$1] = replace$1; }
	          i$6 = end$1 - 1;
	        }
	      }

	      // Here we depart from the documented algorithm, in order to avoid
	      // building up an actual levels array. Since there are only three
	      // levels (0, 1, 2) in an implementation that doesn't take
	      // explicit embedding into account, we can build up the order on
	      // the fly, without following the level-based algorithm.
	      var order = [], m;
	      for (var i$7 = 0; i$7 < len;) {
	        if (countsAsLeft.test(types[i$7])) {
	          var start = i$7;
	          for (++i$7; i$7 < len && countsAsLeft.test(types[i$7]); ++i$7) {}
	          order.push(new BidiSpan(0, start, i$7));
	        } else {
	          var pos = i$7, at = order.length, isRTL = direction == "rtl" ? 1 : 0;
	          for (++i$7; i$7 < len && types[i$7] != "L"; ++i$7) {}
	          for (var j$2 = pos; j$2 < i$7;) {
	            if (countsAsNum.test(types[j$2])) {
	              if (pos < j$2) { order.splice(at, 0, new BidiSpan(1, pos, j$2)); at += isRTL; }
	              var nstart = j$2;
	              for (++j$2; j$2 < i$7 && countsAsNum.test(types[j$2]); ++j$2) {}
	              order.splice(at, 0, new BidiSpan(2, nstart, j$2));
	              at += isRTL;
	              pos = j$2;
	            } else { ++j$2; }
	          }
	          if (pos < i$7) { order.splice(at, 0, new BidiSpan(1, pos, i$7)); }
	        }
	      }
	      if (direction == "ltr") {
	        if (order[0].level == 1 && (m = str.match(/^\s+/))) {
	          order[0].from = m[0].length;
	          order.unshift(new BidiSpan(0, 0, m[0].length));
	        }
	        if (lst(order).level == 1 && (m = str.match(/\s+$/))) {
	          lst(order).to -= m[0].length;
	          order.push(new BidiSpan(0, len - m[0].length, len));
	        }
	      }

	      return direction == "rtl" ? order.reverse() : order
	    }
	  })();

	  // Get the bidi ordering for the given line (and cache it). Returns
	  // false for lines that are fully left-to-right, and an array of
	  // BidiSpan objects otherwise.
	  function getOrder(line, direction) {
	    var order = line.order;
	    if (order == null) { order = line.order = bidiOrdering(line.text, direction); }
	    return order
	  }

	  // EVENT HANDLING

	  // Lightweight event framework. on/off also work on DOM nodes,
	  // registering native DOM handlers.

	  var noHandlers = [];

	  var on = function(emitter, type, f) {
	    if (emitter.addEventListener) {
	      emitter.addEventListener(type, f, false);
	    } else if (emitter.attachEvent) {
	      emitter.attachEvent("on" + type, f);
	    } else {
	      var map = emitter._handlers || (emitter._handlers = {});
	      map[type] = (map[type] || noHandlers).concat(f);
	    }
	  };

	  function getHandlers(emitter, type) {
	    return emitter._handlers && emitter._handlers[type] || noHandlers
	  }

	  function off(emitter, type, f) {
	    if (emitter.removeEventListener) {
	      emitter.removeEventListener(type, f, false);
	    } else if (emitter.detachEvent) {
	      emitter.detachEvent("on" + type, f);
	    } else {
	      var map = emitter._handlers, arr = map && map[type];
	      if (arr) {
	        var index = indexOf(arr, f);
	        if (index > -1)
	          { map[type] = arr.slice(0, index).concat(arr.slice(index + 1)); }
	      }
	    }
	  }

	  function signal(emitter, type /*, values...*/) {
	    var handlers = getHandlers(emitter, type);
	    if (!handlers.length) { return }
	    var args = Array.prototype.slice.call(arguments, 2);
	    for (var i = 0; i < handlers.length; ++i) { handlers[i].apply(null, args); }
	  }

	  // The DOM events that CodeMirror handles can be overridden by
	  // registering a (non-DOM) handler on the editor for the event name,
	  // and preventDefault-ing the event in that handler.
	  function signalDOMEvent(cm, e, override) {
	    if (typeof e == "string")
	      { e = {type: e, preventDefault: function() { this.defaultPrevented = true; }}; }
	    signal(cm, override || e.type, cm, e);
	    return e_defaultPrevented(e) || e.codemirrorIgnore
	  }

	  function signalCursorActivity(cm) {
	    var arr = cm._handlers && cm._handlers.cursorActivity;
	    if (!arr) { return }
	    var set = cm.curOp.cursorActivityHandlers || (cm.curOp.cursorActivityHandlers = []);
	    for (var i = 0; i < arr.length; ++i) { if (indexOf(set, arr[i]) == -1)
	      { set.push(arr[i]); } }
	  }

	  function hasHandler(emitter, type) {
	    return getHandlers(emitter, type).length > 0
	  }

	  // Add on and off methods to a constructor's prototype, to make
	  // registering events on such objects more convenient.
	  function eventMixin(ctor) {
	    ctor.prototype.on = function(type, f) {on(this, type, f);};
	    ctor.prototype.off = function(type, f) {off(this, type, f);};
	  }

	  // Due to the fact that we still support jurassic IE versions, some
	  // compatibility wrappers are needed.

	  function e_preventDefault(e) {
	    if (e.preventDefault) { e.preventDefault(); }
	    else { e.returnValue = false; }
	  }
	  function e_stopPropagation(e) {
	    if (e.stopPropagation) { e.stopPropagation(); }
	    else { e.cancelBubble = true; }
	  }
	  function e_defaultPrevented(e) {
	    return e.defaultPrevented != null ? e.defaultPrevented : e.returnValue == false
	  }
	  function e_stop(e) {e_preventDefault(e); e_stopPropagation(e);}

	  function e_target(e) {return e.target || e.srcElement}
	  function e_button(e) {
	    var b = e.which;
	    if (b == null) {
	      if (e.button & 1) { b = 1; }
	      else if (e.button & 2) { b = 3; }
	      else if (e.button & 4) { b = 2; }
	    }
	    if (mac && e.ctrlKey && b == 1) { b = 3; }
	    return b
	  }

	  // Detect drag-and-drop
	  var dragAndDrop = function() {
	    // There is *some* kind of drag-and-drop support in IE6-8, but I
	    // couldn't get it to work yet.
	    if (ie && ie_version < 9) { return false }
	    var div = elt('div');
	    return "draggable" in div || "dragDrop" in div
	  }();

	  var zwspSupported;
	  function zeroWidthElement(measure) {
	    if (zwspSupported == null) {
	      var test = elt("span", "\u200b");
	      removeChildrenAndAdd(measure, elt("span", [test, document.createTextNode("x")]));
	      if (measure.firstChild.offsetHeight != 0)
	        { zwspSupported = test.offsetWidth <= 1 && test.offsetHeight > 2 && !(ie && ie_version < 8); }
	    }
	    var node = zwspSupported ? elt("span", "\u200b") :
	      elt("span", "\u00a0", null, "display: inline-block; width: 1px; margin-right: -1px");
	    node.setAttribute("cm-text", "");
	    return node
	  }

	  // Feature-detect IE's crummy client rect reporting for bidi text
	  var badBidiRects;
	  function hasBadBidiRects(measure) {
	    if (badBidiRects != null) { return badBidiRects }
	    var txt = removeChildrenAndAdd(measure, document.createTextNode("A\u062eA"));
	    var r0 = range(txt, 0, 1).getBoundingClientRect();
	    var r1 = range(txt, 1, 2).getBoundingClientRect();
	    removeChildren(measure);
	    if (!r0 || r0.left == r0.right) { return false } // Safari returns null in some cases (#2780)
	    return badBidiRects = (r1.right - r0.right < 3)
	  }

	  // See if "".split is the broken IE version, if so, provide an
	  // alternative way to split lines.
	  var splitLinesAuto = "\n\nb".split(/\n/).length != 3 ? function (string) {
	    var pos = 0, result = [], l = string.length;
	    while (pos <= l) {
	      var nl = string.indexOf("\n", pos);
	      if (nl == -1) { nl = string.length; }
	      var line = string.slice(pos, string.charAt(nl - 1) == "\r" ? nl - 1 : nl);
	      var rt = line.indexOf("\r");
	      if (rt != -1) {
	        result.push(line.slice(0, rt));
	        pos += rt + 1;
	      } else {
	        result.push(line);
	        pos = nl + 1;
	      }
	    }
	    return result
	  } : function (string) { return string.split(/\r\n?|\n/); };

	  var hasSelection = window.getSelection ? function (te) {
	    try { return te.selectionStart != te.selectionEnd }
	    catch(e) { return false }
	  } : function (te) {
	    var range;
	    try {range = te.ownerDocument.selection.createRange();}
	    catch(e) {}
	    if (!range || range.parentElement() != te) { return false }
	    return range.compareEndPoints("StartToEnd", range) != 0
	  };

	  var hasCopyEvent = (function () {
	    var e = elt("div");
	    if ("oncopy" in e) { return true }
	    e.setAttribute("oncopy", "return;");
	    return typeof e.oncopy == "function"
	  })();

	  var badZoomedRects = null;
	  function hasBadZoomedRects(measure) {
	    if (badZoomedRects != null) { return badZoomedRects }
	    var node = removeChildrenAndAdd(measure, elt("span", "x"));
	    var normal = node.getBoundingClientRect();
	    var fromRange = range(node, 0, 1).getBoundingClientRect();
	    return badZoomedRects = Math.abs(normal.left - fromRange.left) > 1
	  }

	  // Known modes, by name and by MIME
	  var modes = {}, mimeModes = {};

	  // Extra arguments are stored as the mode's dependencies, which is
	  // used by (legacy) mechanisms like loadmode.js to automatically
	  // load a mode. (Preferred mechanism is the require/define calls.)
	  function defineMode(name, mode) {
	    if (arguments.length > 2)
	      { mode.dependencies = Array.prototype.slice.call(arguments, 2); }
	    modes[name] = mode;
	  }

	  function defineMIME(mime, spec) {
	    mimeModes[mime] = spec;
	  }

	  // Given a MIME type, a {name, ...options} config object, or a name
	  // string, return a mode config object.
	  function resolveMode(spec) {
	    if (typeof spec == "string" && mimeModes.hasOwnProperty(spec)) {
	      spec = mimeModes[spec];
	    } else if (spec && typeof spec.name == "string" && mimeModes.hasOwnProperty(spec.name)) {
	      var found = mimeModes[spec.name];
	      if (typeof found == "string") { found = {name: found}; }
	      spec = createObj(found, spec);
	      spec.name = found.name;
	    } else if (typeof spec == "string" && /^[\w\-]+\/[\w\-]+\+xml$/.test(spec)) {
	      return resolveMode("application/xml")
	    } else if (typeof spec == "string" && /^[\w\-]+\/[\w\-]+\+json$/.test(spec)) {
	      return resolveMode("application/json")
	    }
	    if (typeof spec == "string") { return {name: spec} }
	    else { return spec || {name: "null"} }
	  }

	  // Given a mode spec (anything that resolveMode accepts), find and
	  // initialize an actual mode object.
	  function getMode(options, spec) {
	    spec = resolveMode(spec);
	    var mfactory = modes[spec.name];
	    if (!mfactory) { return getMode(options, "text/plain") }
	    var modeObj = mfactory(options, spec);
	    if (modeExtensions.hasOwnProperty(spec.name)) {
	      var exts = modeExtensions[spec.name];
	      for (var prop in exts) {
	        if (!exts.hasOwnProperty(prop)) { continue }
	        if (modeObj.hasOwnProperty(prop)) { modeObj["_" + prop] = modeObj[prop]; }
	        modeObj[prop] = exts[prop];
	      }
	    }
	    modeObj.name = spec.name;
	    if (spec.helperType) { modeObj.helperType = spec.helperType; }
	    if (spec.modeProps) { for (var prop$1 in spec.modeProps)
	      { modeObj[prop$1] = spec.modeProps[prop$1]; } }

	    return modeObj
	  }

	  // This can be used to attach properties to mode objects from
	  // outside the actual mode definition.
	  var modeExtensions = {};
	  function extendMode(mode, properties) {
	    var exts = modeExtensions.hasOwnProperty(mode) ? modeExtensions[mode] : (modeExtensions[mode] = {});
	    copyObj(properties, exts);
	  }

	  function copyState(mode, state) {
	    if (state === true) { return state }
	    if (mode.copyState) { return mode.copyState(state) }
	    var nstate = {};
	    for (var n in state) {
	      var val = state[n];
	      if (val instanceof Array) { val = val.concat([]); }
	      nstate[n] = val;
	    }
	    return nstate
	  }

	  // Given a mode and a state (for that mode), find the inner mode and
	  // state at the position that the state refers to.
	  function innerMode(mode, state) {
	    var info;
	    while (mode.innerMode) {
	      info = mode.innerMode(state);
	      if (!info || info.mode == mode) { break }
	      state = info.state;
	      mode = info.mode;
	    }
	    return info || {mode: mode, state: state}
	  }

	  function startState(mode, a1, a2) {
	    return mode.startState ? mode.startState(a1, a2) : true
	  }

	  // STRING STREAM

	  // Fed to the mode parsers, provides helper functions to make
	  // parsers more succinct.

	  var StringStream = function(string, tabSize, lineOracle) {
	    this.pos = this.start = 0;
	    this.string = string;
	    this.tabSize = tabSize || 8;
	    this.lastColumnPos = this.lastColumnValue = 0;
	    this.lineStart = 0;
	    this.lineOracle = lineOracle;
	  };

	  StringStream.prototype.eol = function () {return this.pos >= this.string.length};
	  StringStream.prototype.sol = function () {return this.pos == this.lineStart};
	  StringStream.prototype.peek = function () {return this.string.charAt(this.pos) || undefined};
	  StringStream.prototype.next = function () {
	    if (this.pos < this.string.length)
	      { return this.string.charAt(this.pos++) }
	  };
	  StringStream.prototype.eat = function (match) {
	    var ch = this.string.charAt(this.pos);
	    var ok;
	    if (typeof match == "string") { ok = ch == match; }
	    else { ok = ch && (match.test ? match.test(ch) : match(ch)); }
	    if (ok) {++this.pos; return ch}
	  };
	  StringStream.prototype.eatWhile = function (match) {
	    var start = this.pos;
	    while (this.eat(match)){}
	    return this.pos > start
	  };
	  StringStream.prototype.eatSpace = function () {
	    var start = this.pos;
	    while (/[\s\u00a0]/.test(this.string.charAt(this.pos))) { ++this.pos; }
	    return this.pos > start
	  };
	  StringStream.prototype.skipToEnd = function () {this.pos = this.string.length;};
	  StringStream.prototype.skipTo = function (ch) {
	    var found = this.string.indexOf(ch, this.pos);
	    if (found > -1) {this.pos = found; return true}
	  };
	  StringStream.prototype.backUp = function (n) {this.pos -= n;};
	  StringStream.prototype.column = function () {
	    if (this.lastColumnPos < this.start) {
	      this.lastColumnValue = countColumn(this.string, this.start, this.tabSize, this.lastColumnPos, this.lastColumnValue);
	      this.lastColumnPos = this.start;
	    }
	    return this.lastColumnValue - (this.lineStart ? countColumn(this.string, this.lineStart, this.tabSize) : 0)
	  };
	  StringStream.prototype.indentation = function () {
	    return countColumn(this.string, null, this.tabSize) -
	      (this.lineStart ? countColumn(this.string, this.lineStart, this.tabSize) : 0)
	  };
	  StringStream.prototype.match = function (pattern, consume, caseInsensitive) {
	    if (typeof pattern == "string") {
	      var cased = function (str) { return caseInsensitive ? str.toLowerCase() : str; };
	      var substr = this.string.substr(this.pos, pattern.length);
	      if (cased(substr) == cased(pattern)) {
	        if (consume !== false) { this.pos += pattern.length; }
	        return true
	      }
	    } else {
	      var match = this.string.slice(this.pos).match(pattern);
	      if (match && match.index > 0) { return null }
	      if (match && consume !== false) { this.pos += match[0].length; }
	      return match
	    }
	  };
	  StringStream.prototype.current = function (){return this.string.slice(this.start, this.pos)};
	  StringStream.prototype.hideFirstChars = function (n, inner) {
	    this.lineStart += n;
	    try { return inner() }
	    finally { this.lineStart -= n; }
	  };
	  StringStream.prototype.lookAhead = function (n) {
	    var oracle = this.lineOracle;
	    return oracle && oracle.lookAhead(n)
	  };
	  StringStream.prototype.baseToken = function () {
	    var oracle = this.lineOracle;
	    return oracle && oracle.baseToken(this.pos)
	  };

	  // Find the line object corresponding to the given line number.
	  function getLine(doc, n) {
	    n -= doc.first;
	    if (n < 0 || n >= doc.size) { throw new Error("There is no line " + (n + doc.first) + " in the document.") }
	    var chunk = doc;
	    while (!chunk.lines) {
	      for (var i = 0;; ++i) {
	        var child = chunk.children[i], sz = child.chunkSize();
	        if (n < sz) { chunk = child; break }
	        n -= sz;
	      }
	    }
	    return chunk.lines[n]
	  }

	  // Get the part of a document between two positions, as an array of
	  // strings.
	  function getBetween(doc, start, end) {
	    var out = [], n = start.line;
	    doc.iter(start.line, end.line + 1, function (line) {
	      var text = line.text;
	      if (n == end.line) { text = text.slice(0, end.ch); }
	      if (n == start.line) { text = text.slice(start.ch); }
	      out.push(text);
	      ++n;
	    });
	    return out
	  }
	  // Get the lines between from and to, as array of strings.
	  function getLines(doc, from, to) {
	    var out = [];
	    doc.iter(from, to, function (line) { out.push(line.text); }); // iter aborts when callback returns truthy value
	    return out
	  }

	  // Update the height of a line, propagating the height change
	  // upwards to parent nodes.
	  function updateLineHeight(line, height) {
	    var diff = height - line.height;
	    if (diff) { for (var n = line; n; n = n.parent) { n.height += diff; } }
	  }

	  // Given a line object, find its line number by walking up through
	  // its parent links.
	  function lineNo(line) {
	    if (line.parent == null) { return null }
	    var cur = line.parent, no = indexOf(cur.lines, line);
	    for (var chunk = cur.parent; chunk; cur = chunk, chunk = chunk.parent) {
	      for (var i = 0;; ++i) {
	        if (chunk.children[i] == cur) { break }
	        no += chunk.children[i].chunkSize();
	      }
	    }
	    return no + cur.first
	  }

	  // Find the line at the given vertical position, using the height
	  // information in the document tree.
	  function lineAtHeight(chunk, h) {
	    var n = chunk.first;
	    outer: do {
	      for (var i$1 = 0; i$1 < chunk.children.length; ++i$1) {
	        var child = chunk.children[i$1], ch = child.height;
	        if (h < ch) { chunk = child; continue outer }
	        h -= ch;
	        n += child.chunkSize();
	      }
	      return n
	    } while (!chunk.lines)
	    var i = 0;
	    for (; i < chunk.lines.length; ++i) {
	      var line = chunk.lines[i], lh = line.height;
	      if (h < lh) { break }
	      h -= lh;
	    }
	    return n + i
	  }

	  function isLine(doc, l) {return l >= doc.first && l < doc.first + doc.size}

	  function lineNumberFor(options, i) {
	    return String(options.lineNumberFormatter(i + options.firstLineNumber))
	  }

	  // A Pos instance represents a position within the text.
	  function Pos(line, ch, sticky) {
	    if ( sticky === void 0 ) sticky = null;

	    if (!(this instanceof Pos)) { return new Pos(line, ch, sticky) }
	    this.line = line;
	    this.ch = ch;
	    this.sticky = sticky;
	  }

	  // Compare two positions, return 0 if they are the same, a negative
	  // number when a is less, and a positive number otherwise.
	  function cmp(a, b) { return a.line - b.line || a.ch - b.ch }

	  function equalCursorPos(a, b) { return a.sticky == b.sticky && cmp(a, b) == 0 }

	  function copyPos(x) {return Pos(x.line, x.ch)}
	  function maxPos(a, b) { return cmp(a, b) < 0 ? b : a }
	  function minPos(a, b) { return cmp(a, b) < 0 ? a : b }

	  // Most of the external API clips given positions to make sure they
	  // actually exist within the document.
	  function clipLine(doc, n) {return Math.max(doc.first, Math.min(n, doc.first + doc.size - 1))}
	  function clipPos(doc, pos) {
	    if (pos.line < doc.first) { return Pos(doc.first, 0) }
	    var last = doc.first + doc.size - 1;
	    if (pos.line > last) { return Pos(last, getLine(doc, last).text.length) }
	    return clipToLen(pos, getLine(doc, pos.line).text.length)
	  }
	  function clipToLen(pos, linelen) {
	    var ch = pos.ch;
	    if (ch == null || ch > linelen) { return Pos(pos.line, linelen) }
	    else if (ch < 0) { return Pos(pos.line, 0) }
	    else { return pos }
	  }
	  function clipPosArray(doc, array) {
	    var out = [];
	    for (var i = 0; i < array.length; i++) { out[i] = clipPos(doc, array[i]); }
	    return out
	  }

	  var SavedContext = function(state, lookAhead) {
	    this.state = state;
	    this.lookAhead = lookAhead;
	  };

	  var Context = function(doc, state, line, lookAhead) {
	    this.state = state;
	    this.doc = doc;
	    this.line = line;
	    this.maxLookAhead = lookAhead || 0;
	    this.baseTokens = null;
	    this.baseTokenPos = 1;
	  };

	  Context.prototype.lookAhead = function (n) {
	    var line = this.doc.getLine(this.line + n);
	    if (line != null && n > this.maxLookAhead) { this.maxLookAhead = n; }
	    return line
	  };

	  Context.prototype.baseToken = function (n) {
	    if (!this.baseTokens) { return null }
	    while (this.baseTokens[this.baseTokenPos] <= n)
	      { this.baseTokenPos += 2; }
	    var type = this.baseTokens[this.baseTokenPos + 1];
	    return {type: type && type.replace(/( |^)overlay .*/, ""),
	            size: this.baseTokens[this.baseTokenPos] - n}
	  };

	  Context.prototype.nextLine = function () {
	    this.line++;
	    if (this.maxLookAhead > 0) { this.maxLookAhead--; }
	  };

	  Context.fromSaved = function (doc, saved, line) {
	    if (saved instanceof SavedContext)
	      { return new Context(doc, copyState(doc.mode, saved.state), line, saved.lookAhead) }
	    else
	      { return new Context(doc, copyState(doc.mode, saved), line) }
	  };

	  Context.prototype.save = function (copy) {
	    var state = copy !== false ? copyState(this.doc.mode, this.state) : this.state;
	    return this.maxLookAhead > 0 ? new SavedContext(state, this.maxLookAhead) : state
	  };


	  // Compute a style array (an array starting with a mode generation
	  // -- for invalidation -- followed by pairs of end positions and
	  // style strings), which is used to highlight the tokens on the
	  // line.
	  function highlightLine(cm, line, context, forceToEnd) {
	    // A styles array always starts with a number identifying the
	    // mode/overlays that it is based on (for easy invalidation).
	    var st = [cm.state.modeGen], lineClasses = {};
	    // Compute the base array of styles
	    runMode(cm, line.text, cm.doc.mode, context, function (end, style) { return st.push(end, style); },
	            lineClasses, forceToEnd);
	    var state = context.state;

	    // Run overlays, adjust style array.
	    var loop = function ( o ) {
	      context.baseTokens = st;
	      var overlay = cm.state.overlays[o], i = 1, at = 0;
	      context.state = true;
	      runMode(cm, line.text, overlay.mode, context, function (end, style) {
	        var start = i;
	        // Ensure there's a token end at the current position, and that i points at it
	        while (at < end) {
	          var i_end = st[i];
	          if (i_end > end)
	            { st.splice(i, 1, end, st[i+1], i_end); }
	          i += 2;
	          at = Math.min(end, i_end);
	        }
	        if (!style) { return }
	        if (overlay.opaque) {
	          st.splice(start, i - start, end, "overlay " + style);
	          i = start + 2;
	        } else {
	          for (; start < i; start += 2) {
	            var cur = st[start+1];
	            st[start+1] = (cur ? cur + " " : "") + "overlay " + style;
	          }
	        }
	      }, lineClasses);
	      context.state = state;
	      context.baseTokens = null;
	      context.baseTokenPos = 1;
	    };

	    for (var o = 0; o < cm.state.overlays.length; ++o) loop( o );

	    return {styles: st, classes: lineClasses.bgClass || lineClasses.textClass ? lineClasses : null}
	  }

	  function getLineStyles(cm, line, updateFrontier) {
	    if (!line.styles || line.styles[0] != cm.state.modeGen) {
	      var context = getContextBefore(cm, lineNo(line));
	      var resetState = line.text.length > cm.options.maxHighlightLength && copyState(cm.doc.mode, context.state);
	      var result = highlightLine(cm, line, context);
	      if (resetState) { context.state = resetState; }
	      line.stateAfter = context.save(!resetState);
	      line.styles = result.styles;
	      if (result.classes) { line.styleClasses = result.classes; }
	      else if (line.styleClasses) { line.styleClasses = null; }
	      if (updateFrontier === cm.doc.highlightFrontier)
	        { cm.doc.modeFrontier = Math.max(cm.doc.modeFrontier, ++cm.doc.highlightFrontier); }
	    }
	    return line.styles
	  }

	  function getContextBefore(cm, n, precise) {
	    var doc = cm.doc, display = cm.display;
	    if (!doc.mode.startState) { return new Context(doc, true, n) }
	    var start = findStartLine(cm, n, precise);
	    var saved = start > doc.first && getLine(doc, start - 1).stateAfter;
	    var context = saved ? Context.fromSaved(doc, saved, start) : new Context(doc, startState(doc.mode), start);

	    doc.iter(start, n, function (line) {
	      processLine(cm, line.text, context);
	      var pos = context.line;
	      line.stateAfter = pos == n - 1 || pos % 5 == 0 || pos >= display.viewFrom && pos < display.viewTo ? context.save() : null;
	      context.nextLine();
	    });
	    if (precise) { doc.modeFrontier = context.line; }
	    return context
	  }

	  // Lightweight form of highlight -- proceed over this line and
	  // update state, but don't save a style array. Used for lines that
	  // aren't currently visible.
	  function processLine(cm, text, context, startAt) {
	    var mode = cm.doc.mode;
	    var stream = new StringStream(text, cm.options.tabSize, context);
	    stream.start = stream.pos = startAt || 0;
	    if (text == "") { callBlankLine(mode, context.state); }
	    while (!stream.eol()) {
	      readToken(mode, stream, context.state);
	      stream.start = stream.pos;
	    }
	  }

	  function callBlankLine(mode, state) {
	    if (mode.blankLine) { return mode.blankLine(state) }
	    if (!mode.innerMode) { return }
	    var inner = innerMode(mode, state);
	    if (inner.mode.blankLine) { return inner.mode.blankLine(inner.state) }
	  }

	  function readToken(mode, stream, state, inner) {
	    for (var i = 0; i < 10; i++) {
	      if (inner) { inner[0] = innerMode(mode, state).mode; }
	      var style = mode.token(stream, state);
	      if (stream.pos > stream.start) { return style }
	    }
	    throw new Error("Mode " + mode.name + " failed to advance stream.")
	  }

	  var Token = function(stream, type, state) {
	    this.start = stream.start; this.end = stream.pos;
	    this.string = stream.current();
	    this.type = type || null;
	    this.state = state;
	  };

	  // Utility for getTokenAt and getLineTokens
	  function takeToken(cm, pos, precise, asArray) {
	    var doc = cm.doc, mode = doc.mode, style;
	    pos = clipPos(doc, pos);
	    var line = getLine(doc, pos.line), context = getContextBefore(cm, pos.line, precise);
	    var stream = new StringStream(line.text, cm.options.tabSize, context), tokens;
	    if (asArray) { tokens = []; }
	    while ((asArray || stream.pos < pos.ch) && !stream.eol()) {
	      stream.start = stream.pos;
	      style = readToken(mode, stream, context.state);
	      if (asArray) { tokens.push(new Token(stream, style, copyState(doc.mode, context.state))); }
	    }
	    return asArray ? tokens : new Token(stream, style, context.state)
	  }

	  function extractLineClasses(type, output) {
	    if (type) { for (;;) {
	      var lineClass = type.match(/(?:^|\s+)line-(background-)?(\S+)/);
	      if (!lineClass) { break }
	      type = type.slice(0, lineClass.index) + type.slice(lineClass.index + lineClass[0].length);
	      var prop = lineClass[1] ? "bgClass" : "textClass";
	      if (output[prop] == null)
	        { output[prop] = lineClass[2]; }
	      else if (!(new RegExp("(?:^|\s)" + lineClass[2] + "(?:$|\s)")).test(output[prop]))
	        { output[prop] += " " + lineClass[2]; }
	    } }
	    return type
	  }

	  // Run the given mode's parser over a line, calling f for each token.
	  function runMode(cm, text, mode, context, f, lineClasses, forceToEnd) {
	    var flattenSpans = mode.flattenSpans;
	    if (flattenSpans == null) { flattenSpans = cm.options.flattenSpans; }
	    var curStart = 0, curStyle = null;
	    var stream = new StringStream(text, cm.options.tabSize, context), style;
	    var inner = cm.options.addModeClass && [null];
	    if (text == "") { extractLineClasses(callBlankLine(mode, context.state), lineClasses); }
	    while (!stream.eol()) {
	      if (stream.pos > cm.options.maxHighlightLength) {
	        flattenSpans = false;
	        if (forceToEnd) { processLine(cm, text, context, stream.pos); }
	        stream.pos = text.length;
	        style = null;
	      } else {
	        style = extractLineClasses(readToken(mode, stream, context.state, inner), lineClasses);
	      }
	      if (inner) {
	        var mName = inner[0].name;
	        if (mName) { style = "m-" + (style ? mName + " " + style : mName); }
	      }
	      if (!flattenSpans || curStyle != style) {
	        while (curStart < stream.start) {
	          curStart = Math.min(stream.start, curStart + 5000);
	          f(curStart, curStyle);
	        }
	        curStyle = style;
	      }
	      stream.start = stream.pos;
	    }
	    while (curStart < stream.pos) {
	      // Webkit seems to refuse to render text nodes longer than 57444
	      // characters, and returns inaccurate measurements in nodes
	      // starting around 5000 chars.
	      var pos = Math.min(stream.pos, curStart + 5000);
	      f(pos, curStyle);
	      curStart = pos;
	    }
	  }

	  // Finds the line to start with when starting a parse. Tries to
	  // find a line with a stateAfter, so that it can start with a
	  // valid state. If that fails, it returns the line with the
	  // smallest indentation, which tends to need the least context to
	  // parse correctly.
	  function findStartLine(cm, n, precise) {
	    var minindent, minline, doc = cm.doc;
	    var lim = precise ? -1 : n - (cm.doc.mode.innerMode ? 1000 : 100);
	    for (var search = n; search > lim; --search) {
	      if (search <= doc.first) { return doc.first }
	      var line = getLine(doc, search - 1), after = line.stateAfter;
	      if (after && (!precise || search + (after instanceof SavedContext ? after.lookAhead : 0) <= doc.modeFrontier))
	        { return search }
	      var indented = countColumn(line.text, null, cm.options.tabSize);
	      if (minline == null || minindent > indented) {
	        minline = search - 1;
	        minindent = indented;
	      }
	    }
	    return minline
	  }

	  function retreatFrontier(doc, n) {
	    doc.modeFrontier = Math.min(doc.modeFrontier, n);
	    if (doc.highlightFrontier < n - 10) { return }
	    var start = doc.first;
	    for (var line = n - 1; line > start; line--) {
	      var saved = getLine(doc, line).stateAfter;
	      // change is on 3
	      // state on line 1 looked ahead 2 -- so saw 3
	      // test 1 + 2 < 3 should cover this
	      if (saved && (!(saved instanceof SavedContext) || line + saved.lookAhead < n)) {
	        start = line + 1;
	        break
	      }
	    }
	    doc.highlightFrontier = Math.min(doc.highlightFrontier, start);
	  }

	  // Optimize some code when these features are not used.
	  var sawReadOnlySpans = false, sawCollapsedSpans = false;

	  function seeReadOnlySpans() {
	    sawReadOnlySpans = true;
	  }

	  function seeCollapsedSpans() {
	    sawCollapsedSpans = true;
	  }

	  // TEXTMARKER SPANS

	  function MarkedSpan(marker, from, to) {
	    this.marker = marker;
	    this.from = from; this.to = to;
	  }

	  // Search an array of spans for a span matching the given marker.
	  function getMarkedSpanFor(spans, marker) {
	    if (spans) { for (var i = 0; i < spans.length; ++i) {
	      var span = spans[i];
	      if (span.marker == marker) { return span }
	    } }
	  }
	  // Remove a span from an array, returning undefined if no spans are
	  // left (we don't store arrays for lines without spans).
	  function removeMarkedSpan(spans, span) {
	    var r;
	    for (var i = 0; i < spans.length; ++i)
	      { if (spans[i] != span) { (r || (r = [])).push(spans[i]); } }
	    return r
	  }
	  // Add a span to a line.
	  function addMarkedSpan(line, span) {
	    line.markedSpans = line.markedSpans ? line.markedSpans.concat([span]) : [span];
	    span.marker.attachLine(line);
	  }

	  // Used for the algorithm that adjusts markers for a change in the
	  // document. These functions cut an array of spans at a given
	  // character position, returning an array of remaining chunks (or
	  // undefined if nothing remains).
	  function markedSpansBefore(old, startCh, isInsert) {
	    var nw;
	    if (old) { for (var i = 0; i < old.length; ++i) {
	      var span = old[i], marker = span.marker;
	      var startsBefore = span.from == null || (marker.inclusiveLeft ? span.from <= startCh : span.from < startCh);
	      if (startsBefore || span.from == startCh && marker.type == "bookmark" && (!isInsert || !span.marker.insertLeft)) {
	        var endsAfter = span.to == null || (marker.inclusiveRight ? span.to >= startCh : span.to > startCh)
	        ;(nw || (nw = [])).push(new MarkedSpan(marker, span.from, endsAfter ? null : span.to));
	      }
	    } }
	    return nw
	  }
	  function markedSpansAfter(old, endCh, isInsert) {
	    var nw;
	    if (old) { for (var i = 0; i < old.length; ++i) {
	      var span = old[i], marker = span.marker;
	      var endsAfter = span.to == null || (marker.inclusiveRight ? span.to >= endCh : span.to > endCh);
	      if (endsAfter || span.from == endCh && marker.type == "bookmark" && (!isInsert || span.marker.insertLeft)) {
	        var startsBefore = span.from == null || (marker.inclusiveLeft ? span.from <= endCh : span.from < endCh)
	        ;(nw || (nw = [])).push(new MarkedSpan(marker, startsBefore ? null : span.from - endCh,
	                                              span.to == null ? null : span.to - endCh));
	      }
	    } }
	    return nw
	  }

	  // Given a change object, compute the new set of marker spans that
	  // cover the line in which the change took place. Removes spans
	  // entirely within the change, reconnects spans belonging to the
	  // same marker that appear on both sides of the change, and cuts off
	  // spans partially within the change. Returns an array of span
	  // arrays with one element for each line in (after) the change.
	  function stretchSpansOverChange(doc, change) {
	    if (change.full) { return null }
	    var oldFirst = isLine(doc, change.from.line) && getLine(doc, change.from.line).markedSpans;
	    var oldLast = isLine(doc, change.to.line) && getLine(doc, change.to.line).markedSpans;
	    if (!oldFirst && !oldLast) { return null }

	    var startCh = change.from.ch, endCh = change.to.ch, isInsert = cmp(change.from, change.to) == 0;
	    // Get the spans that 'stick out' on both sides
	    var first = markedSpansBefore(oldFirst, startCh, isInsert);
	    var last = markedSpansAfter(oldLast, endCh, isInsert);

	    // Next, merge those two ends
	    var sameLine = change.text.length == 1, offset = lst(change.text).length + (sameLine ? startCh : 0);
	    if (first) {
	      // Fix up .to properties of first
	      for (var i = 0; i < first.length; ++i) {
	        var span = first[i];
	        if (span.to == null) {
	          var found = getMarkedSpanFor(last, span.marker);
	          if (!found) { span.to = startCh; }
	          else if (sameLine) { span.to = found.to == null ? null : found.to + offset; }
	        }
	      }
	    }
	    if (last) {
	      // Fix up .from in last (or move them into first in case of sameLine)
	      for (var i$1 = 0; i$1 < last.length; ++i$1) {
	        var span$1 = last[i$1];
	        if (span$1.to != null) { span$1.to += offset; }
	        if (span$1.from == null) {
	          var found$1 = getMarkedSpanFor(first, span$1.marker);
	          if (!found$1) {
	            span$1.from = offset;
	            if (sameLine) { (first || (first = [])).push(span$1); }
	          }
	        } else {
	          span$1.from += offset;
	          if (sameLine) { (first || (first = [])).push(span$1); }
	        }
	      }
	    }
	    // Make sure we didn't create any zero-length spans
	    if (first) { first = clearEmptySpans(first); }
	    if (last && last != first) { last = clearEmptySpans(last); }

	    var newMarkers = [first];
	    if (!sameLine) {
	      // Fill gap with whole-line-spans
	      var gap = change.text.length - 2, gapMarkers;
	      if (gap > 0 && first)
	        { for (var i$2 = 0; i$2 < first.length; ++i$2)
	          { if (first[i$2].to == null)
	            { (gapMarkers || (gapMarkers = [])).push(new MarkedSpan(first[i$2].marker, null, null)); } } }
	      for (var i$3 = 0; i$3 < gap; ++i$3)
	        { newMarkers.push(gapMarkers); }
	      newMarkers.push(last);
	    }
	    return newMarkers
	  }

	  // Remove spans that are empty and don't have a clearWhenEmpty
	  // option of false.
	  function clearEmptySpans(spans) {
	    for (var i = 0; i < spans.length; ++i) {
	      var span = spans[i];
	      if (span.from != null && span.from == span.to && span.marker.clearWhenEmpty !== false)
	        { spans.splice(i--, 1); }
	    }
	    if (!spans.length) { return null }
	    return spans
	  }

	  // Used to 'clip' out readOnly ranges when making a change.
	  function removeReadOnlyRanges(doc, from, to) {
	    var markers = null;
	    doc.iter(from.line, to.line + 1, function (line) {
	      if (line.markedSpans) { for (var i = 0; i < line.markedSpans.length; ++i) {
	        var mark = line.markedSpans[i].marker;
	        if (mark.readOnly && (!markers || indexOf(markers, mark) == -1))
	          { (markers || (markers = [])).push(mark); }
	      } }
	    });
	    if (!markers) { return null }
	    var parts = [{from: from, to: to}];
	    for (var i = 0; i < markers.length; ++i) {
	      var mk = markers[i], m = mk.find(0);
	      for (var j = 0; j < parts.length; ++j) {
	        var p = parts[j];
	        if (cmp(p.to, m.from) < 0 || cmp(p.from, m.to) > 0) { continue }
	        var newParts = [j, 1], dfrom = cmp(p.from, m.from), dto = cmp(p.to, m.to);
	        if (dfrom < 0 || !mk.inclusiveLeft && !dfrom)
	          { newParts.push({from: p.from, to: m.from}); }
	        if (dto > 0 || !mk.inclusiveRight && !dto)
	          { newParts.push({from: m.to, to: p.to}); }
	        parts.splice.apply(parts, newParts);
	        j += newParts.length - 3;
	      }
	    }
	    return parts
	  }

	  // Connect or disconnect spans from a line.
	  function detachMarkedSpans(line) {
	    var spans = line.markedSpans;
	    if (!spans) { return }
	    for (var i = 0; i < spans.length; ++i)
	      { spans[i].marker.detachLine(line); }
	    line.markedSpans = null;
	  }
	  function attachMarkedSpans(line, spans) {
	    if (!spans) { return }
	    for (var i = 0; i < spans.length; ++i)
	      { spans[i].marker.attachLine(line); }
	    line.markedSpans = spans;
	  }

	  // Helpers used when computing which overlapping collapsed span
	  // counts as the larger one.
	  function extraLeft(marker) { return marker.inclusiveLeft ? -1 : 0 }
	  function extraRight(marker) { return marker.inclusiveRight ? 1 : 0 }

	  // Returns a number indicating which of two overlapping collapsed
	  // spans is larger (and thus includes the other). Falls back to
	  // comparing ids when the spans cover exactly the same range.
	  function compareCollapsedMarkers(a, b) {
	    var lenDiff = a.lines.length - b.lines.length;
	    if (lenDiff != 0) { return lenDiff }
	    var aPos = a.find(), bPos = b.find();
	    var fromCmp = cmp(aPos.from, bPos.from) || extraLeft(a) - extraLeft(b);
	    if (fromCmp) { return -fromCmp }
	    var toCmp = cmp(aPos.to, bPos.to) || extraRight(a) - extraRight(b);
	    if (toCmp) { return toCmp }
	    return b.id - a.id
	  }

	  // Find out whether a line ends or starts in a collapsed span. If
	  // so, return the marker for that span.
	  function collapsedSpanAtSide(line, start) {
	    var sps = sawCollapsedSpans && line.markedSpans, found;
	    if (sps) { for (var sp = (void 0), i = 0; i < sps.length; ++i) {
	      sp = sps[i];
	      if (sp.marker.collapsed && (start ? sp.from : sp.to) == null &&
	          (!found || compareCollapsedMarkers(found, sp.marker) < 0))
	        { found = sp.marker; }
	    } }
	    return found
	  }
	  function collapsedSpanAtStart(line) { return collapsedSpanAtSide(line, true) }
	  function collapsedSpanAtEnd(line) { return collapsedSpanAtSide(line, false) }

	  function collapsedSpanAround(line, ch) {
	    var sps = sawCollapsedSpans && line.markedSpans, found;
	    if (sps) { for (var i = 0; i < sps.length; ++i) {
	      var sp = sps[i];
	      if (sp.marker.collapsed && (sp.from == null || sp.from < ch) && (sp.to == null || sp.to > ch) &&
	          (!found || compareCollapsedMarkers(found, sp.marker) < 0)) { found = sp.marker; }
	    } }
	    return found
	  }

	  // Test whether there exists a collapsed span that partially
	  // overlaps (covers the start or end, but not both) of a new span.
	  // Such overlap is not allowed.
	  function conflictingCollapsedRange(doc, lineNo, from, to, marker) {
	    var line = getLine(doc, lineNo);
	    var sps = sawCollapsedSpans && line.markedSpans;
	    if (sps) { for (var i = 0; i < sps.length; ++i) {
	      var sp = sps[i];
	      if (!sp.marker.collapsed) { continue }
	      var found = sp.marker.find(0);
	      var fromCmp = cmp(found.from, from) || extraLeft(sp.marker) - extraLeft(marker);
	      var toCmp = cmp(found.to, to) || extraRight(sp.marker) - extraRight(marker);
	      if (fromCmp >= 0 && toCmp <= 0 || fromCmp <= 0 && toCmp >= 0) { continue }
	      if (fromCmp <= 0 && (sp.marker.inclusiveRight && marker.inclusiveLeft ? cmp(found.to, from) >= 0 : cmp(found.to, from) > 0) ||
	          fromCmp >= 0 && (sp.marker.inclusiveRight && marker.inclusiveLeft ? cmp(found.from, to) <= 0 : cmp(found.from, to) < 0))
	        { return true }
	    } }
	  }

	  // A visual line is a line as drawn on the screen. Folding, for
	  // example, can cause multiple logical lines to appear on the same
	  // visual line. This finds the start of the visual line that the
	  // given line is part of (usually that is the line itself).
	  function visualLine(line) {
	    var merged;
	    while (merged = collapsedSpanAtStart(line))
	      { line = merged.find(-1, true).line; }
	    return line
	  }

	  function visualLineEnd(line) {
	    var merged;
	    while (merged = collapsedSpanAtEnd(line))
	      { line = merged.find(1, true).line; }
	    return line
	  }

	  // Returns an array of logical lines that continue the visual line
	  // started by the argument, or undefined if there are no such lines.
	  function visualLineContinued(line) {
	    var merged, lines;
	    while (merged = collapsedSpanAtEnd(line)) {
	      line = merged.find(1, true).line
	      ;(lines || (lines = [])).push(line);
	    }
	    return lines
	  }

	  // Get the line number of the start of the visual line that the
	  // given line number is part of.
	  function visualLineNo(doc, lineN) {
	    var line = getLine(doc, lineN), vis = visualLine(line);
	    if (line == vis) { return lineN }
	    return lineNo(vis)
	  }

	  // Get the line number of the start of the next visual line after
	  // the given line.
	  function visualLineEndNo(doc, lineN) {
	    if (lineN > doc.lastLine()) { return lineN }
	    var line = getLine(doc, lineN), merged;
	    if (!lineIsHidden(doc, line)) { return lineN }
	    while (merged = collapsedSpanAtEnd(line))
	      { line = merged.find(1, true).line; }
	    return lineNo(line) + 1
	  }

	  // Compute whether a line is hidden. Lines count as hidden when they
	  // are part of a visual line that starts with another line, or when
	  // they are entirely covered by collapsed, non-widget span.
	  function lineIsHidden(doc, line) {
	    var sps = sawCollapsedSpans && line.markedSpans;
	    if (sps) { for (var sp = (void 0), i = 0; i < sps.length; ++i) {
	      sp = sps[i];
	      if (!sp.marker.collapsed) { continue }
	      if (sp.from == null) { return true }
	      if (sp.marker.widgetNode) { continue }
	      if (sp.from == 0 && sp.marker.inclusiveLeft && lineIsHiddenInner(doc, line, sp))
	        { return true }
	    } }
	  }
	  function lineIsHiddenInner(doc, line, span) {
	    if (span.to == null) {
	      var end = span.marker.find(1, true);
	      return lineIsHiddenInner(doc, end.line, getMarkedSpanFor(end.line.markedSpans, span.marker))
	    }
	    if (span.marker.inclusiveRight && span.to == line.text.length)
	      { return true }
	    for (var sp = (void 0), i = 0; i < line.markedSpans.length; ++i) {
	      sp = line.markedSpans[i];
	      if (sp.marker.collapsed && !sp.marker.widgetNode && sp.from == span.to &&
	          (sp.to == null || sp.to != span.from) &&
	          (sp.marker.inclusiveLeft || span.marker.inclusiveRight) &&
	          lineIsHiddenInner(doc, line, sp)) { return true }
	    }
	  }

	  // Find the height above the given line.
	  function heightAtLine(lineObj) {
	    lineObj = visualLine(lineObj);

	    var h = 0, chunk = lineObj.parent;
	    for (var i = 0; i < chunk.lines.length; ++i) {
	      var line = chunk.lines[i];
	      if (line == lineObj) { break }
	      else { h += line.height; }
	    }
	    for (var p = chunk.parent; p; chunk = p, p = chunk.parent) {
	      for (var i$1 = 0; i$1 < p.children.length; ++i$1) {
	        var cur = p.children[i$1];
	        if (cur == chunk) { break }
	        else { h += cur.height; }
	      }
	    }
	    return h
	  }

	  // Compute the character length of a line, taking into account
	  // collapsed ranges (see markText) that might hide parts, and join
	  // other lines onto it.
	  function lineLength(line) {
	    if (line.height == 0) { return 0 }
	    var len = line.text.length, merged, cur = line;
	    while (merged = collapsedSpanAtStart(cur)) {
	      var found = merged.find(0, true);
	      cur = found.from.line;
	      len += found.from.ch - found.to.ch;
	    }
	    cur = line;
	    while (merged = collapsedSpanAtEnd(cur)) {
	      var found$1 = merged.find(0, true);
	      len -= cur.text.length - found$1.from.ch;
	      cur = found$1.to.line;
	      len += cur.text.length - found$1.to.ch;
	    }
	    return len
	  }

	  // Find the longest line in the document.
	  function findMaxLine(cm) {
	    var d = cm.display, doc = cm.doc;
	    d.maxLine = getLine(doc, doc.first);
	    d.maxLineLength = lineLength(d.maxLine);
	    d.maxLineChanged = true;
	    doc.iter(function (line) {
	      var len = lineLength(line);
	      if (len > d.maxLineLength) {
	        d.maxLineLength = len;
	        d.maxLine = line;
	      }
	    });
	  }

	  // LINE DATA STRUCTURE

	  // Line objects. These hold state related to a line, including
	  // highlighting info (the styles array).
	  var Line = function(text, markedSpans, estimateHeight) {
	    this.text = text;
	    attachMarkedSpans(this, markedSpans);
	    this.height = estimateHeight ? estimateHeight(this) : 1;
	  };

	  Line.prototype.lineNo = function () { return lineNo(this) };
	  eventMixin(Line);

	  // Change the content (text, markers) of a line. Automatically
	  // invalidates cached information and tries to re-estimate the
	  // line's height.
	  function updateLine(line, text, markedSpans, estimateHeight) {
	    line.text = text;
	    if (line.stateAfter) { line.stateAfter = null; }
	    if (line.styles) { line.styles = null; }
	    if (line.order != null) { line.order = null; }
	    detachMarkedSpans(line);
	    attachMarkedSpans(line, markedSpans);
	    var estHeight = estimateHeight ? estimateHeight(line) : 1;
	    if (estHeight != line.height) { updateLineHeight(line, estHeight); }
	  }

	  // Detach a line from the document tree and its markers.
	  function cleanUpLine(line) {
	    line.parent = null;
	    detachMarkedSpans(line);
	  }

	  // Convert a style as returned by a mode (either null, or a string
	  // containing one or more styles) to a CSS style. This is cached,
	  // and also looks for line-wide styles.
	  var styleToClassCache = {}, styleToClassCacheWithMode = {};
	  function interpretTokenStyle(style, options) {
	    if (!style || /^\s*$/.test(style)) { return null }
	    var cache = options.addModeClass ? styleToClassCacheWithMode : styleToClassCache;
	    return cache[style] ||
	      (cache[style] = style.replace(/\S+/g, "cm-$&"))
	  }

	  // Render the DOM representation of the text of a line. Also builds
	  // up a 'line map', which points at the DOM nodes that represent
	  // specific stretches of text, and is used by the measuring code.
	  // The returned object contains the DOM node, this map, and
	  // information about line-wide styles that were set by the mode.
	  function buildLineContent(cm, lineView) {
	    // The padding-right forces the element to have a 'border', which
	    // is needed on Webkit to be able to get line-level bounding
	    // rectangles for it (in measureChar).
	    var content = eltP("span", null, null, webkit ? "padding-right: .1px" : null);
	    var builder = {pre: eltP("pre", [content], "CodeMirror-line"), content: content,
	                   col: 0, pos: 0, cm: cm,
	                   trailingSpace: false,
	                   splitSpaces: cm.getOption("lineWrapping")};
	    lineView.measure = {};

	    // Iterate over the logical lines that make up this visual line.
	    for (var i = 0; i <= (lineView.rest ? lineView.rest.length : 0); i++) {
	      var line = i ? lineView.rest[i - 1] : lineView.line, order = (void 0);
	      builder.pos = 0;
	      builder.addToken = buildToken;
	      // Optionally wire in some hacks into the token-rendering
	      // algorithm, to deal with browser quirks.
	      if (hasBadBidiRects(cm.display.measure) && (order = getOrder(line, cm.doc.direction)))
	        { builder.addToken = buildTokenBadBidi(builder.addToken, order); }
	      builder.map = [];
	      var allowFrontierUpdate = lineView != cm.display.externalMeasured && lineNo(line);
	      insertLineContent(line, builder, getLineStyles(cm, line, allowFrontierUpdate));
	      if (line.styleClasses) {
	        if (line.styleClasses.bgClass)
	          { builder.bgClass = joinClasses(line.styleClasses.bgClass, builder.bgClass || ""); }
	        if (line.styleClasses.textClass)
	          { builder.textClass = joinClasses(line.styleClasses.textClass, builder.textClass || ""); }
	      }

	      // Ensure at least a single node is present, for measuring.
	      if (builder.map.length == 0)
	        { builder.map.push(0, 0, builder.content.appendChild(zeroWidthElement(cm.display.measure))); }

	      // Store the map and a cache object for the current logical line
	      if (i == 0) {
	        lineView.measure.map = builder.map;
	        lineView.measure.cache = {};
	      } else {
	  (lineView.measure.maps || (lineView.measure.maps = [])).push(builder.map)
	        ;(lineView.measure.caches || (lineView.measure.caches = [])).push({});
	      }
	    }

	    // See issue #2901
	    if (webkit) {
	      var last = builder.content.lastChild;
	      if (/\bcm-tab\b/.test(last.className) || (last.querySelector && last.querySelector(".cm-tab")))
	        { builder.content.className = "cm-tab-wrap-hack"; }
	    }

	    signal(cm, "renderLine", cm, lineView.line, builder.pre);
	    if (builder.pre.className)
	      { builder.textClass = joinClasses(builder.pre.className, builder.textClass || ""); }

	    return builder
	  }

	  function defaultSpecialCharPlaceholder(ch) {
	    var token = elt("span", "\u2022", "cm-invalidchar");
	    token.title = "\\u" + ch.charCodeAt(0).toString(16);
	    token.setAttribute("aria-label", token.title);
	    return token
	  }

	  // Build up the DOM representation for a single token, and add it to
	  // the line map. Takes care to render special characters separately.
	  function buildToken(builder, text, style, startStyle, endStyle, css, attributes) {
	    if (!text) { return }
	    var displayText = builder.splitSpaces ? splitSpaces(text, builder.trailingSpace) : text;
	    var special = builder.cm.state.specialChars, mustWrap = false;
	    var content;
	    if (!special.test(text)) {
	      builder.col += text.length;
	      content = document.createTextNode(displayText);
	      builder.map.push(builder.pos, builder.pos + text.length, content);
	      if (ie && ie_version < 9) { mustWrap = true; }
	      builder.pos += text.length;
	    } else {
	      content = document.createDocumentFragment();
	      var pos = 0;
	      while (true) {
	        special.lastIndex = pos;
	        var m = special.exec(text);
	        var skipped = m ? m.index - pos : text.length - pos;
	        if (skipped) {
	          var txt = document.createTextNode(displayText.slice(pos, pos + skipped));
	          if (ie && ie_version < 9) { content.appendChild(elt("span", [txt])); }
	          else { content.appendChild(txt); }
	          builder.map.push(builder.pos, builder.pos + skipped, txt);
	          builder.col += skipped;
	          builder.pos += skipped;
	        }
	        if (!m) { break }
	        pos += skipped + 1;
	        var txt$1 = (void 0);
	        if (m[0] == "\t") {
	          var tabSize = builder.cm.options.tabSize, tabWidth = tabSize - builder.col % tabSize;
	          txt$1 = content.appendChild(elt("span", spaceStr(tabWidth), "cm-tab"));
	          txt$1.setAttribute("role", "presentation");
	          txt$1.setAttribute("cm-text", "\t");
	          builder.col += tabWidth;
	        } else if (m[0] == "\r" || m[0] == "\n") {
	          txt$1 = content.appendChild(elt("span", m[0] == "\r" ? "\u240d" : "\u2424", "cm-invalidchar"));
	          txt$1.setAttribute("cm-text", m[0]);
	          builder.col += 1;
	        } else {
	          txt$1 = builder.cm.options.specialCharPlaceholder(m[0]);
	          txt$1.setAttribute("cm-text", m[0]);
	          if (ie && ie_version < 9) { content.appendChild(elt("span", [txt$1])); }
	          else { content.appendChild(txt$1); }
	          builder.col += 1;
	        }
	        builder.map.push(builder.pos, builder.pos + 1, txt$1);
	        builder.pos++;
	      }
	    }
	    builder.trailingSpace = displayText.charCodeAt(text.length - 1) == 32;
	    if (style || startStyle || endStyle || mustWrap || css) {
	      var fullStyle = style || "";
	      if (startStyle) { fullStyle += startStyle; }
	      if (endStyle) { fullStyle += endStyle; }
	      var token = elt("span", [content], fullStyle, css);
	      if (attributes) {
	        for (var attr in attributes) { if (attributes.hasOwnProperty(attr) && attr != "style" && attr != "class")
	          { token.setAttribute(attr, attributes[attr]); } }
	      }
	      return builder.content.appendChild(token)
	    }
	    builder.content.appendChild(content);
	  }

	  // Change some spaces to NBSP to prevent the browser from collapsing
	  // trailing spaces at the end of a line when rendering text (issue #1362).
	  function splitSpaces(text, trailingBefore) {
	    if (text.length > 1 && !/  /.test(text)) { return text }
	    var spaceBefore = trailingBefore, result = "";
	    for (var i = 0; i < text.length; i++) {
	      var ch = text.charAt(i);
	      if (ch == " " && spaceBefore && (i == text.length - 1 || text.charCodeAt(i + 1) == 32))
	        { ch = "\u00a0"; }
	      result += ch;
	      spaceBefore = ch == " ";
	    }
	    return result
	  }

	  // Work around nonsense dimensions being reported for stretches of
	  // right-to-left text.
	  function buildTokenBadBidi(inner, order) {
	    return function (builder, text, style, startStyle, endStyle, css, attributes) {
	      style = style ? style + " cm-force-border" : "cm-force-border";
	      var start = builder.pos, end = start + text.length;
	      for (;;) {
	        // Find the part that overlaps with the start of this text
	        var part = (void 0);
	        for (var i = 0; i < order.length; i++) {
	          part = order[i];
	          if (part.to > start && part.from <= start) { break }
	        }
	        if (part.to >= end) { return inner(builder, text, style, startStyle, endStyle, css, attributes) }
	        inner(builder, text.slice(0, part.to - start), style, startStyle, null, css, attributes);
	        startStyle = null;
	        text = text.slice(part.to - start);
	        start = part.to;
	      }
	    }
	  }

	  function buildCollapsedSpan(builder, size, marker, ignoreWidget) {
	    var widget = !ignoreWidget && marker.widgetNode;
	    if (widget) { builder.map.push(builder.pos, builder.pos + size, widget); }
	    if (!ignoreWidget && builder.cm.display.input.needsContentAttribute) {
	      if (!widget)
	        { widget = builder.content.appendChild(document.createElement("span")); }
	      widget.setAttribute("cm-marker", marker.id);
	    }
	    if (widget) {
	      builder.cm.display.input.setUneditable(widget);
	      builder.content.appendChild(widget);
	    }
	    builder.pos += size;
	    builder.trailingSpace = false;
	  }

	  // Outputs a number of spans to make up a line, taking highlighting
	  // and marked text into account.
	  function insertLineContent(line, builder, styles) {
	    var spans = line.markedSpans, allText = line.text, at = 0;
	    if (!spans) {
	      for (var i$1 = 1; i$1 < styles.length; i$1+=2)
	        { builder.addToken(builder, allText.slice(at, at = styles[i$1]), interpretTokenStyle(styles[i$1+1], builder.cm.options)); }
	      return
	    }

	    var len = allText.length, pos = 0, i = 1, text = "", style, css;
	    var nextChange = 0, spanStyle, spanEndStyle, spanStartStyle, collapsed, attributes;
	    for (;;) {
	      if (nextChange == pos) { // Update current marker set
	        spanStyle = spanEndStyle = spanStartStyle = css = "";
	        attributes = null;
	        collapsed = null; nextChange = Infinity;
	        var foundBookmarks = [], endStyles = (void 0);
	        for (var j = 0; j < spans.length; ++j) {
	          var sp = spans[j], m = sp.marker;
	          if (m.type == "bookmark" && sp.from == pos && m.widgetNode) {
	            foundBookmarks.push(m);
	          } else if (sp.from <= pos && (sp.to == null || sp.to > pos || m.collapsed && sp.to == pos && sp.from == pos)) {
	            if (sp.to != null && sp.to != pos && nextChange > sp.to) {
	              nextChange = sp.to;
	              spanEndStyle = "";
	            }
	            if (m.className) { spanStyle += " " + m.className; }
	            if (m.css) { css = (css ? css + ";" : "") + m.css; }
	            if (m.startStyle && sp.from == pos) { spanStartStyle += " " + m.startStyle; }
	            if (m.endStyle && sp.to == nextChange) { (endStyles || (endStyles = [])).push(m.endStyle, sp.to); }
	            // support for the old title property
	            // https://github.com/codemirror/CodeMirror/pull/5673
	            if (m.title) { (attributes || (attributes = {})).title = m.title; }
	            if (m.attributes) {
	              for (var attr in m.attributes)
	                { (attributes || (attributes = {}))[attr] = m.attributes[attr]; }
	            }
	            if (m.collapsed && (!collapsed || compareCollapsedMarkers(collapsed.marker, m) < 0))
	              { collapsed = sp; }
	          } else if (sp.from > pos && nextChange > sp.from) {
	            nextChange = sp.from;
	          }
	        }
	        if (endStyles) { for (var j$1 = 0; j$1 < endStyles.length; j$1 += 2)
	          { if (endStyles[j$1 + 1] == nextChange) { spanEndStyle += " " + endStyles[j$1]; } } }

	        if (!collapsed || collapsed.from == pos) { for (var j$2 = 0; j$2 < foundBookmarks.length; ++j$2)
	          { buildCollapsedSpan(builder, 0, foundBookmarks[j$2]); } }
	        if (collapsed && (collapsed.from || 0) == pos) {
	          buildCollapsedSpan(builder, (collapsed.to == null ? len + 1 : collapsed.to) - pos,
	                             collapsed.marker, collapsed.from == null);
	          if (collapsed.to == null) { return }
	          if (collapsed.to == pos) { collapsed = false; }
	        }
	      }
	      if (pos >= len) { break }

	      var upto = Math.min(len, nextChange);
	      while (true) {
	        if (text) {
	          var end = pos + text.length;
	          if (!collapsed) {
	            var tokenText = end > upto ? text.slice(0, upto - pos) : text;
	            builder.addToken(builder, tokenText, style ? style + spanStyle : spanStyle,
	                             spanStartStyle, pos + tokenText.length == nextChange ? spanEndStyle : "", css, attributes);
	          }
	          if (end >= upto) {text = text.slice(upto - pos); pos = upto; break}
	          pos = end;
	          spanStartStyle = "";
	        }
	        text = allText.slice(at, at = styles[i++]);
	        style = interpretTokenStyle(styles[i++], builder.cm.options);
	      }
	    }
	  }


	  // These objects are used to represent the visible (currently drawn)
	  // part of the document. A LineView may correspond to multiple
	  // logical lines, if those are connected by collapsed ranges.
	  function LineView(doc, line, lineN) {
	    // The starting line
	    this.line = line;
	    // Continuing lines, if any
	    this.rest = visualLineContinued(line);
	    // Number of logical lines in this visual line
	    this.size = this.rest ? lineNo(lst(this.rest)) - lineN + 1 : 1;
	    this.node = this.text = null;
	    this.hidden = lineIsHidden(doc, line);
	  }

	  // Create a range of LineView objects for the given lines.
	  function buildViewArray(cm, from, to) {
	    var array = [], nextPos;
	    for (var pos = from; pos < to; pos = nextPos) {
	      var view = new LineView(cm.doc, getLine(cm.doc, pos), pos);
	      nextPos = pos + view.size;
	      array.push(view);
	    }
	    return array
	  }

	  var operationGroup = null;

	  function pushOperation(op) {
	    if (operationGroup) {
	      operationGroup.ops.push(op);
	    } else {
	      op.ownsGroup = operationGroup = {
	        ops: [op],
	        delayedCallbacks: []
	      };
	    }
	  }

	  function fireCallbacksForOps(group) {
	    // Calls delayed callbacks and cursorActivity handlers until no
	    // new ones appear
	    var callbacks = group.delayedCallbacks, i = 0;
	    do {
	      for (; i < callbacks.length; i++)
	        { callbacks[i].call(null); }
	      for (var j = 0; j < group.ops.length; j++) {
	        var op = group.ops[j];
	        if (op.cursorActivityHandlers)
	          { while (op.cursorActivityCalled < op.cursorActivityHandlers.length)
	            { op.cursorActivityHandlers[op.cursorActivityCalled++].call(null, op.cm); } }
	      }
	    } while (i < callbacks.length)
	  }

	  function finishOperation(op, endCb) {
	    var group = op.ownsGroup;
	    if (!group) { return }

	    try { fireCallbacksForOps(group); }
	    finally {
	      operationGroup = null;
	      endCb(group);
	    }
	  }

	  var orphanDelayedCallbacks = null;

	  // Often, we want to signal events at a point where we are in the
	  // middle of some work, but don't want the handler to start calling
	  // other methods on the editor, which might be in an inconsistent
	  // state or simply not expect any other events to happen.
	  // signalLater looks whether there are any handlers, and schedules
	  // them to be executed when the last operation ends, or, if no
	  // operation is active, when a timeout fires.
	  function signalLater(emitter, type /*, values...*/) {
	    var arr = getHandlers(emitter, type);
	    if (!arr.length) { return }
	    var args = Array.prototype.slice.call(arguments, 2), list;
	    if (operationGroup) {
	      list = operationGroup.delayedCallbacks;
	    } else if (orphanDelayedCallbacks) {
	      list = orphanDelayedCallbacks;
	    } else {
	      list = orphanDelayedCallbacks = [];
	      setTimeout(fireOrphanDelayed, 0);
	    }
	    var loop = function ( i ) {
	      list.push(function () { return arr[i].apply(null, args); });
	    };

	    for (var i = 0; i < arr.length; ++i)
	      loop( i );
	  }

	  function fireOrphanDelayed() {
	    var delayed = orphanDelayedCallbacks;
	    orphanDelayedCallbacks = null;
	    for (var i = 0; i < delayed.length; ++i) { delayed[i](); }
	  }

	  // When an aspect of a line changes, a string is added to
	  // lineView.changes. This updates the relevant part of the line's
	  // DOM structure.
	  function updateLineForChanges(cm, lineView, lineN, dims) {
	    for (var j = 0; j < lineView.changes.length; j++) {
	      var type = lineView.changes[j];
	      if (type == "text") { updateLineText(cm, lineView); }
	      else if (type == "gutter") { updateLineGutter(cm, lineView, lineN, dims); }
	      else if (type == "class") { updateLineClasses(cm, lineView); }
	      else if (type == "widget") { updateLineWidgets(cm, lineView, dims); }
	    }
	    lineView.changes = null;
	  }

	  // Lines with gutter elements, widgets or a background class need to
	  // be wrapped, and have the extra elements added to the wrapper div
	  function ensureLineWrapped(lineView) {
	    if (lineView.node == lineView.text) {
	      lineView.node = elt("div", null, null, "position: relative");
	      if (lineView.text.parentNode)
	        { lineView.text.parentNode.replaceChild(lineView.node, lineView.text); }
	      lineView.node.appendChild(lineView.text);
	      if (ie && ie_version < 8) { lineView.node.style.zIndex = 2; }
	    }
	    return lineView.node
	  }

	  function updateLineBackground(cm, lineView) {
	    var cls = lineView.bgClass ? lineView.bgClass + " " + (lineView.line.bgClass || "") : lineView.line.bgClass;
	    if (cls) { cls += " CodeMirror-linebackground"; }
	    if (lineView.background) {
	      if (cls) { lineView.background.className = cls; }
	      else { lineView.background.parentNode.removeChild(lineView.background); lineView.background = null; }
	    } else if (cls) {
	      var wrap = ensureLineWrapped(lineView);
	      lineView.background = wrap.insertBefore(elt("div", null, cls), wrap.firstChild);
	      cm.display.input.setUneditable(lineView.background);
	    }
	  }

	  // Wrapper around buildLineContent which will reuse the structure
	  // in display.externalMeasured when possible.
	  function getLineContent(cm, lineView) {
	    var ext = cm.display.externalMeasured;
	    if (ext && ext.line == lineView.line) {
	      cm.display.externalMeasured = null;
	      lineView.measure = ext.measure;
	      return ext.built
	    }
	    return buildLineContent(cm, lineView)
	  }

	  // Redraw the line's text. Interacts with the background and text
	  // classes because the mode may output tokens that influence these
	  // classes.
	  function updateLineText(cm, lineView) {
	    var cls = lineView.text.className;
	    var built = getLineContent(cm, lineView);
	    if (lineView.text == lineView.node) { lineView.node = built.pre; }
	    lineView.text.parentNode.replaceChild(built.pre, lineView.text);
	    lineView.text = built.pre;
	    if (built.bgClass != lineView.bgClass || built.textClass != lineView.textClass) {
	      lineView.bgClass = built.bgClass;
	      lineView.textClass = built.textClass;
	      updateLineClasses(cm, lineView);
	    } else if (cls) {
	      lineView.text.className = cls;
	    }
	  }

	  function updateLineClasses(cm, lineView) {
	    updateLineBackground(cm, lineView);
	    if (lineView.line.wrapClass)
	      { ensureLineWrapped(lineView).className = lineView.line.wrapClass; }
	    else if (lineView.node != lineView.text)
	      { lineView.node.className = ""; }
	    var textClass = lineView.textClass ? lineView.textClass + " " + (lineView.line.textClass || "") : lineView.line.textClass;
	    lineView.text.className = textClass || "";
	  }

	  function updateLineGutter(cm, lineView, lineN, dims) {
	    if (lineView.gutter) {
	      lineView.node.removeChild(lineView.gutter);
	      lineView.gutter = null;
	    }
	    if (lineView.gutterBackground) {
	      lineView.node.removeChild(lineView.gutterBackground);
	      lineView.gutterBackground = null;
	    }
	    if (lineView.line.gutterClass) {
	      var wrap = ensureLineWrapped(lineView);
	      lineView.gutterBackground = elt("div", null, "CodeMirror-gutter-background " + lineView.line.gutterClass,
	                                      ("left: " + (cm.options.fixedGutter ? dims.fixedPos : -dims.gutterTotalWidth) + "px; width: " + (dims.gutterTotalWidth) + "px"));
	      cm.display.input.setUneditable(lineView.gutterBackground);
	      wrap.insertBefore(lineView.gutterBackground, lineView.text);
	    }
	    var markers = lineView.line.gutterMarkers;
	    if (cm.options.lineNumbers || markers) {
	      var wrap$1 = ensureLineWrapped(lineView);
	      var gutterWrap = lineView.gutter = elt("div", null, "CodeMirror-gutter-wrapper", ("left: " + (cm.options.fixedGutter ? dims.fixedPos : -dims.gutterTotalWidth) + "px"));
	      cm.display.input.setUneditable(gutterWrap);
	      wrap$1.insertBefore(gutterWrap, lineView.text);
	      if (lineView.line.gutterClass)
	        { gutterWrap.className += " " + lineView.line.gutterClass; }
	      if (cm.options.lineNumbers && (!markers || !markers["CodeMirror-linenumbers"]))
	        { lineView.lineNumber = gutterWrap.appendChild(
	          elt("div", lineNumberFor(cm.options, lineN),
	              "CodeMirror-linenumber CodeMirror-gutter-elt",
	              ("left: " + (dims.gutterLeft["CodeMirror-linenumbers"]) + "px; width: " + (cm.display.lineNumInnerWidth) + "px"))); }
	      if (markers) { for (var k = 0; k < cm.display.gutterSpecs.length; ++k) {
	        var id = cm.display.gutterSpecs[k].className, found = markers.hasOwnProperty(id) && markers[id];
	        if (found)
	          { gutterWrap.appendChild(elt("div", [found], "CodeMirror-gutter-elt",
	                                     ("left: " + (dims.gutterLeft[id]) + "px; width: " + (dims.gutterWidth[id]) + "px"))); }
	      } }
	    }
	  }

	  function updateLineWidgets(cm, lineView, dims) {
	    if (lineView.alignable) { lineView.alignable = null; }
	    var isWidget = classTest("CodeMirror-linewidget");
	    for (var node = lineView.node.firstChild, next = (void 0); node; node = next) {
	      next = node.nextSibling;
	      if (isWidget.test(node.className)) { lineView.node.removeChild(node); }
	    }
	    insertLineWidgets(cm, lineView, dims);
	  }

	  // Build a line's DOM representation from scratch
	  function buildLineElement(cm, lineView, lineN, dims) {
	    var built = getLineContent(cm, lineView);
	    lineView.text = lineView.node = built.pre;
	    if (built.bgClass) { lineView.bgClass = built.bgClass; }
	    if (built.textClass) { lineView.textClass = built.textClass; }

	    updateLineClasses(cm, lineView);
	    updateLineGutter(cm, lineView, lineN, dims);
	    insertLineWidgets(cm, lineView, dims);
	    return lineView.node
	  }

	  // A lineView may contain multiple logical lines (when merged by
	  // collapsed spans). The widgets for all of them need to be drawn.
	  function insertLineWidgets(cm, lineView, dims) {
	    insertLineWidgetsFor(cm, lineView.line, lineView, dims, true);
	    if (lineView.rest) { for (var i = 0; i < lineView.rest.length; i++)
	      { insertLineWidgetsFor(cm, lineView.rest[i], lineView, dims, false); } }
	  }

	  function insertLineWidgetsFor(cm, line, lineView, dims, allowAbove) {
	    if (!line.widgets) { return }
	    var wrap = ensureLineWrapped(lineView);
	    for (var i = 0, ws = line.widgets; i < ws.length; ++i) {
	      var widget = ws[i], node = elt("div", [widget.node], "CodeMirror-linewidget" + (widget.className ? " " + widget.className : ""));
	      if (!widget.handleMouseEvents) { node.setAttribute("cm-ignore-events", "true"); }
	      positionLineWidget(widget, node, lineView, dims);
	      cm.display.input.setUneditable(node);
	      if (allowAbove && widget.above)
	        { wrap.insertBefore(node, lineView.gutter || lineView.text); }
	      else
	        { wrap.appendChild(node); }
	      signalLater(widget, "redraw");
	    }
	  }

	  function positionLineWidget(widget, node, lineView, dims) {
	    if (widget.noHScroll) {
	  (lineView.alignable || (lineView.alignable = [])).push(node);
	      var width = dims.wrapperWidth;
	      node.style.left = dims.fixedPos + "px";
	      if (!widget.coverGutter) {
	        width -= dims.gutterTotalWidth;
	        node.style.paddingLeft = dims.gutterTotalWidth + "px";
	      }
	      node.style.width = width + "px";
	    }
	    if (widget.coverGutter) {
	      node.style.zIndex = 5;
	      node.style.position = "relative";
	      if (!widget.noHScroll) { node.style.marginLeft = -dims.gutterTotalWidth + "px"; }
	    }
	  }

	  function widgetHeight(widget) {
	    if (widget.height != null) { return widget.height }
	    var cm = widget.doc.cm;
	    if (!cm) { return 0 }
	    if (!contains(document.body, widget.node)) {
	      var parentStyle = "position: relative;";
	      if (widget.coverGutter)
	        { parentStyle += "margin-left: -" + cm.display.gutters.offsetWidth + "px;"; }
	      if (widget.noHScroll)
	        { parentStyle += "width: " + cm.display.wrapper.clientWidth + "px;"; }
	      removeChildrenAndAdd(cm.display.measure, elt("div", [widget.node], null, parentStyle));
	    }
	    return widget.height = widget.node.parentNode.offsetHeight
	  }

	  // Return true when the given mouse event happened in a widget
	  function eventInWidget(display, e) {
	    for (var n = e_target(e); n != display.wrapper; n = n.parentNode) {
	      if (!n || (n.nodeType == 1 && n.getAttribute("cm-ignore-events") == "true") ||
	          (n.parentNode == display.sizer && n != display.mover))
	        { return true }
	    }
	  }

	  // POSITION MEASUREMENT

	  function paddingTop(display) {return display.lineSpace.offsetTop}
	  function paddingVert(display) {return display.mover.offsetHeight - display.lineSpace.offsetHeight}
	  function paddingH(display) {
	    if (display.cachedPaddingH) { return display.cachedPaddingH }
	    var e = removeChildrenAndAdd(display.measure, elt("pre", "x", "CodeMirror-line-like"));
	    var style = window.getComputedStyle ? window.getComputedStyle(e) : e.currentStyle;
	    var data = {left: parseInt(style.paddingLeft), right: parseInt(style.paddingRight)};
	    if (!isNaN(data.left) && !isNaN(data.right)) { display.cachedPaddingH = data; }
	    return data
	  }

	  function scrollGap(cm) { return scrollerGap - cm.display.nativeBarWidth }
	  function displayWidth(cm) {
	    return cm.display.scroller.clientWidth - scrollGap(cm) - cm.display.barWidth
	  }
	  function displayHeight(cm) {
	    return cm.display.scroller.clientHeight - scrollGap(cm) - cm.display.barHeight
	  }

	  // Ensure the lineView.wrapping.heights array is populated. This is
	  // an array of bottom offsets for the lines that make up a drawn
	  // line. When lineWrapping is on, there might be more than one
	  // height.
	  function ensureLineHeights(cm, lineView, rect) {
	    var wrapping = cm.options.lineWrapping;
	    var curWidth = wrapping && displayWidth(cm);
	    if (!lineView.measure.heights || wrapping && lineView.measure.width != curWidth) {
	      var heights = lineView.measure.heights = [];
	      if (wrapping) {
	        lineView.measure.width = curWidth;
	        var rects = lineView.text.firstChild.getClientRects();
	        for (var i = 0; i < rects.length - 1; i++) {
	          var cur = rects[i], next = rects[i + 1];
	          if (Math.abs(cur.bottom - next.bottom) > 2)
	            { heights.push((cur.bottom + next.top) / 2 - rect.top); }
	        }
	      }
	      heights.push(rect.bottom - rect.top);
	    }
	  }

	  // Find a line map (mapping character offsets to text nodes) and a
	  // measurement cache for the given line number. (A line view might
	  // contain multiple lines when collapsed ranges are present.)
	  function mapFromLineView(lineView, line, lineN) {
	    if (lineView.line == line)
	      { return {map: lineView.measure.map, cache: lineView.measure.cache} }
	    for (var i = 0; i < lineView.rest.length; i++)
	      { if (lineView.rest[i] == line)
	        { return {map: lineView.measure.maps[i], cache: lineView.measure.caches[i]} } }
	    for (var i$1 = 0; i$1 < lineView.rest.length; i$1++)
	      { if (lineNo(lineView.rest[i$1]) > lineN)
	        { return {map: lineView.measure.maps[i$1], cache: lineView.measure.caches[i$1], before: true} } }
	  }

	  // Render a line into the hidden node display.externalMeasured. Used
	  // when measurement is needed for a line that's not in the viewport.
	  function updateExternalMeasurement(cm, line) {
	    line = visualLine(line);
	    var lineN = lineNo(line);
	    var view = cm.display.externalMeasured = new LineView(cm.doc, line, lineN);
	    view.lineN = lineN;
	    var built = view.built = buildLineContent(cm, view);
	    view.text = built.pre;
	    removeChildrenAndAdd(cm.display.lineMeasure, built.pre);
	    return view
	  }

	  // Get a {top, bottom, left, right} box (in line-local coordinates)
	  // for a given character.
	  function measureChar(cm, line, ch, bias) {
	    return measureCharPrepared(cm, prepareMeasureForLine(cm, line), ch, bias)
	  }

	  // Find a line view that corresponds to the given line number.
	  function findViewForLine(cm, lineN) {
	    if (lineN >= cm.display.viewFrom && lineN < cm.display.viewTo)
	      { return cm.display.view[findViewIndex(cm, lineN)] }
	    var ext = cm.display.externalMeasured;
	    if (ext && lineN >= ext.lineN && lineN < ext.lineN + ext.size)
	      { return ext }
	  }

	  // Measurement can be split in two steps, the set-up work that
	  // applies to the whole line, and the measurement of the actual
	  // character. Functions like coordsChar, that need to do a lot of
	  // measurements in a row, can thus ensure that the set-up work is
	  // only done once.
	  function prepareMeasureForLine(cm, line) {
	    var lineN = lineNo(line);
	    var view = findViewForLine(cm, lineN);
	    if (view && !view.text) {
	      view = null;
	    } else if (view && view.changes) {
	      updateLineForChanges(cm, view, lineN, getDimensions(cm));
	      cm.curOp.forceUpdate = true;
	    }
	    if (!view)
	      { view = updateExternalMeasurement(cm, line); }

	    var info = mapFromLineView(view, line, lineN);
	    return {
	      line: line, view: view, rect: null,
	      map: info.map, cache: info.cache, before: info.before,
	      hasHeights: false
	    }
	  }

	  // Given a prepared measurement object, measures the position of an
	  // actual character (or fetches it from the cache).
	  function measureCharPrepared(cm, prepared, ch, bias, varHeight) {
	    if (prepared.before) { ch = -1; }
	    var key = ch + (bias || ""), found;
	    if (prepared.cache.hasOwnProperty(key)) {
	      found = prepared.cache[key];
	    } else {
	      if (!prepared.rect)
	        { prepared.rect = prepared.view.text.getBoundingClientRect(); }
	      if (!prepared.hasHeights) {
	        ensureLineHeights(cm, prepared.view, prepared.rect);
	        prepared.hasHeights = true;
	      }
	      found = measureCharInner(cm, prepared, ch, bias);
	      if (!found.bogus) { prepared.cache[key] = found; }
	    }
	    return {left: found.left, right: found.right,
	            top: varHeight ? found.rtop : found.top,
	            bottom: varHeight ? found.rbottom : found.bottom}
	  }

	  var nullRect = {left: 0, right: 0, top: 0, bottom: 0};

	  function nodeAndOffsetInLineMap(map, ch, bias) {
	    var node, start, end, collapse, mStart, mEnd;
	    // First, search the line map for the text node corresponding to,
	    // or closest to, the target character.
	    for (var i = 0; i < map.length; i += 3) {
	      mStart = map[i];
	      mEnd = map[i + 1];
	      if (ch < mStart) {
	        start = 0; end = 1;
	        collapse = "left";
	      } else if (ch < mEnd) {
	        start = ch - mStart;
	        end = start + 1;
	      } else if (i == map.length - 3 || ch == mEnd && map[i + 3] > ch) {
	        end = mEnd - mStart;
	        start = end - 1;
	        if (ch >= mEnd) { collapse = "right"; }
	      }
	      if (start != null) {
	        node = map[i + 2];
	        if (mStart == mEnd && bias == (node.insertLeft ? "left" : "right"))
	          { collapse = bias; }
	        if (bias == "left" && start == 0)
	          { while (i && map[i - 2] == map[i - 3] && map[i - 1].insertLeft) {
	            node = map[(i -= 3) + 2];
	            collapse = "left";
	          } }
	        if (bias == "right" && start == mEnd - mStart)
	          { while (i < map.length - 3 && map[i + 3] == map[i + 4] && !map[i + 5].insertLeft) {
	            node = map[(i += 3) + 2];
	            collapse = "right";
	          } }
	        break
	      }
	    }
	    return {node: node, start: start, end: end, collapse: collapse, coverStart: mStart, coverEnd: mEnd}
	  }

	  function getUsefulRect(rects, bias) {
	    var rect = nullRect;
	    if (bias == "left") { for (var i = 0; i < rects.length; i++) {
	      if ((rect = rects[i]).left != rect.right) { break }
	    } } else { for (var i$1 = rects.length - 1; i$1 >= 0; i$1--) {
	      if ((rect = rects[i$1]).left != rect.right) { break }
	    } }
	    return rect
	  }

	  function measureCharInner(cm, prepared, ch, bias) {
	    var place = nodeAndOffsetInLineMap(prepared.map, ch, bias);
	    var node = place.node, start = place.start, end = place.end, collapse = place.collapse;

	    var rect;
	    if (node.nodeType == 3) { // If it is a text node, use a range to retrieve the coordinates.
	      for (var i$1 = 0; i$1 < 4; i$1++) { // Retry a maximum of 4 times when nonsense rectangles are returned
	        while (start && isExtendingChar(prepared.line.text.charAt(place.coverStart + start))) { --start; }
	        while (place.coverStart + end < place.coverEnd && isExtendingChar(prepared.line.text.charAt(place.coverStart + end))) { ++end; }
	        if (ie && ie_version < 9 && start == 0 && end == place.coverEnd - place.coverStart)
	          { rect = node.parentNode.getBoundingClientRect(); }
	        else
	          { rect = getUsefulRect(range(node, start, end).getClientRects(), bias); }
	        if (rect.left || rect.right || start == 0) { break }
	        end = start;
	        start = start - 1;
	        collapse = "right";
	      }
	      if (ie && ie_version < 11) { rect = maybeUpdateRectForZooming(cm.display.measure, rect); }
	    } else { // If it is a widget, simply get the box for the whole widget.
	      if (start > 0) { collapse = bias = "right"; }
	      var rects;
	      if (cm.options.lineWrapping && (rects = node.getClientRects()).length > 1)
	        { rect = rects[bias == "right" ? rects.length - 1 : 0]; }
	      else
	        { rect = node.getBoundingClientRect(); }
	    }
	    if (ie && ie_version < 9 && !start && (!rect || !rect.left && !rect.right)) {
	      var rSpan = node.parentNode.getClientRects()[0];
	      if (rSpan)
	        { rect = {left: rSpan.left, right: rSpan.left + charWidth(cm.display), top: rSpan.top, bottom: rSpan.bottom}; }
	      else
	        { rect = nullRect; }
	    }

	    var rtop = rect.top - prepared.rect.top, rbot = rect.bottom - prepared.rect.top;
	    var mid = (rtop + rbot) / 2;
	    var heights = prepared.view.measure.heights;
	    var i = 0;
	    for (; i < heights.length - 1; i++)
	      { if (mid < heights[i]) { break } }
	    var top = i ? heights[i - 1] : 0, bot = heights[i];
	    var result = {left: (collapse == "right" ? rect.right : rect.left) - prepared.rect.left,
	                  right: (collapse == "left" ? rect.left : rect.right) - prepared.rect.left,
	                  top: top, bottom: bot};
	    if (!rect.left && !rect.right) { result.bogus = true; }
	    if (!cm.options.singleCursorHeightPerLine) { result.rtop = rtop; result.rbottom = rbot; }

	    return result
	  }

	  // Work around problem with bounding client rects on ranges being
	  // returned incorrectly when zoomed on IE10 and below.
	  function maybeUpdateRectForZooming(measure, rect) {
	    if (!window.screen || screen.logicalXDPI == null ||
	        screen.logicalXDPI == screen.deviceXDPI || !hasBadZoomedRects(measure))
	      { return rect }
	    var scaleX = screen.logicalXDPI / screen.deviceXDPI;
	    var scaleY = screen.logicalYDPI / screen.deviceYDPI;
	    return {left: rect.left * scaleX, right: rect.right * scaleX,
	            top: rect.top * scaleY, bottom: rect.bottom * scaleY}
	  }

	  function clearLineMeasurementCacheFor(lineView) {
	    if (lineView.measure) {
	      lineView.measure.cache = {};
	      lineView.measure.heights = null;
	      if (lineView.rest) { for (var i = 0; i < lineView.rest.length; i++)
	        { lineView.measure.caches[i] = {}; } }
	    }
	  }

	  function clearLineMeasurementCache(cm) {
	    cm.display.externalMeasure = null;
	    removeChildren(cm.display.lineMeasure);
	    for (var i = 0; i < cm.display.view.length; i++)
	      { clearLineMeasurementCacheFor(cm.display.view[i]); }
	  }

	  function clearCaches(cm) {
	    clearLineMeasurementCache(cm);
	    cm.display.cachedCharWidth = cm.display.cachedTextHeight = cm.display.cachedPaddingH = null;
	    if (!cm.options.lineWrapping) { cm.display.maxLineChanged = true; }
	    cm.display.lineNumChars = null;
	  }

	  function pageScrollX() {
	    // Work around https://bugs.chromium.org/p/chromium/issues/detail?id=489206
	    // which causes page_Offset and bounding client rects to use
	    // different reference viewports and invalidate our calculations.
	    if (chrome && android) { return -(document.body.getBoundingClientRect().left - parseInt(getComputedStyle(document.body).marginLeft)) }
	    return window.pageXOffset || (document.documentElement || document.body).scrollLeft
	  }
	  function pageScrollY() {
	    if (chrome && android) { return -(document.body.getBoundingClientRect().top - parseInt(getComputedStyle(document.body).marginTop)) }
	    return window.pageYOffset || (document.documentElement || document.body).scrollTop
	  }

	  function widgetTopHeight(lineObj) {
	    var height = 0;
	    if (lineObj.widgets) { for (var i = 0; i < lineObj.widgets.length; ++i) { if (lineObj.widgets[i].above)
	      { height += widgetHeight(lineObj.widgets[i]); } } }
	    return height
	  }

	  // Converts a {top, bottom, left, right} box from line-local
	  // coordinates into another coordinate system. Context may be one of
	  // "line", "div" (display.lineDiv), "local"./null (editor), "window",
	  // or "page".
	  function intoCoordSystem(cm, lineObj, rect, context, includeWidgets) {
	    if (!includeWidgets) {
	      var height = widgetTopHeight(lineObj);
	      rect.top += height; rect.bottom += height;
	    }
	    if (context == "line") { return rect }
	    if (!context) { context = "local"; }
	    var yOff = heightAtLine(lineObj);
	    if (context == "local") { yOff += paddingTop(cm.display); }
	    else { yOff -= cm.display.viewOffset; }
	    if (context == "page" || context == "window") {
	      var lOff = cm.display.lineSpace.getBoundingClientRect();
	      yOff += lOff.top + (context == "window" ? 0 : pageScrollY());
	      var xOff = lOff.left + (context == "window" ? 0 : pageScrollX());
	      rect.left += xOff; rect.right += xOff;
	    }
	    rect.top += yOff; rect.bottom += yOff;
	    return rect
	  }

	  // Coverts a box from "div" coords to another coordinate system.
	  // Context may be "window", "page", "div", or "local"./null.
	  function fromCoordSystem(cm, coords, context) {
	    if (context == "div") { return coords }
	    var left = coords.left, top = coords.top;
	    // First move into "page" coordinate system
	    if (context == "page") {
	      left -= pageScrollX();
	      top -= pageScrollY();
	    } else if (context == "local" || !context) {
	      var localBox = cm.display.sizer.getBoundingClientRect();
	      left += localBox.left;
	      top += localBox.top;
	    }

	    var lineSpaceBox = cm.display.lineSpace.getBoundingClientRect();
	    return {left: left - lineSpaceBox.left, top: top - lineSpaceBox.top}
	  }

	  function charCoords(cm, pos, context, lineObj, bias) {
	    if (!lineObj) { lineObj = getLine(cm.doc, pos.line); }
	    return intoCoordSystem(cm, lineObj, measureChar(cm, lineObj, pos.ch, bias), context)
	  }

	  // Returns a box for a given cursor position, which may have an
	  // 'other' property containing the position of the secondary cursor
	  // on a bidi boundary.
	  // A cursor Pos(line, char, "before") is on the same visual line as `char - 1`
	  // and after `char - 1` in writing order of `char - 1`
	  // A cursor Pos(line, char, "after") is on the same visual line as `char`
	  // and before `char` in writing order of `char`
	  // Examples (upper-case letters are RTL, lower-case are LTR):
	  //     Pos(0, 1, ...)
	  //     before   after
	  // ab     a|b     a|b
	  // aB     a|B     aB|
	  // Ab     |Ab     A|b
	  // AB     B|A     B|A
	  // Every position after the last character on a line is considered to stick
	  // to the last character on the line.
	  function cursorCoords(cm, pos, context, lineObj, preparedMeasure, varHeight) {
	    lineObj = lineObj || getLine(cm.doc, pos.line);
	    if (!preparedMeasure) { preparedMeasure = prepareMeasureForLine(cm, lineObj); }
	    function get(ch, right) {
	      var m = measureCharPrepared(cm, preparedMeasure, ch, right ? "right" : "left", varHeight);
	      if (right) { m.left = m.right; } else { m.right = m.left; }
	      return intoCoordSystem(cm, lineObj, m, context)
	    }
	    var order = getOrder(lineObj, cm.doc.direction), ch = pos.ch, sticky = pos.sticky;
	    if (ch >= lineObj.text.length) {
	      ch = lineObj.text.length;
	      sticky = "before";
	    } else if (ch <= 0) {
	      ch = 0;
	      sticky = "after";
	    }
	    if (!order) { return get(sticky == "before" ? ch - 1 : ch, sticky == "before") }

	    function getBidi(ch, partPos, invert) {
	      var part = order[partPos], right = part.level == 1;
	      return get(invert ? ch - 1 : ch, right != invert)
	    }
	    var partPos = getBidiPartAt(order, ch, sticky);
	    var other = bidiOther;
	    var val = getBidi(ch, partPos, sticky == "before");
	    if (other != null) { val.other = getBidi(ch, other, sticky != "before"); }
	    return val
	  }

	  // Used to cheaply estimate the coordinates for a position. Used for
	  // intermediate scroll updates.
	  function estimateCoords(cm, pos) {
	    var left = 0;
	    pos = clipPos(cm.doc, pos);
	    if (!cm.options.lineWrapping) { left = charWidth(cm.display) * pos.ch; }
	    var lineObj = getLine(cm.doc, pos.line);
	    var top = heightAtLine(lineObj) + paddingTop(cm.display);
	    return {left: left, right: left, top: top, bottom: top + lineObj.height}
	  }

	  // Positions returned by coordsChar contain some extra information.
	  // xRel is the relative x position of the input coordinates compared
	  // to the found position (so xRel > 0 means the coordinates are to
	  // the right of the character position, for example). When outside
	  // is true, that means the coordinates lie outside the line's
	  // vertical range.
	  function PosWithInfo(line, ch, sticky, outside, xRel) {
	    var pos = Pos(line, ch, sticky);
	    pos.xRel = xRel;
	    if (outside) { pos.outside = outside; }
	    return pos
	  }

	  // Compute the character position closest to the given coordinates.
	  // Input must be lineSpace-local ("div" coordinate system).
	  function coordsChar(cm, x, y) {
	    var doc = cm.doc;
	    y += cm.display.viewOffset;
	    if (y < 0) { return PosWithInfo(doc.first, 0, null, -1, -1) }
	    var lineN = lineAtHeight(doc, y), last = doc.first + doc.size - 1;
	    if (lineN > last)
	      { return PosWithInfo(doc.first + doc.size - 1, getLine(doc, last).text.length, null, 1, 1) }
	    if (x < 0) { x = 0; }

	    var lineObj = getLine(doc, lineN);
	    for (;;) {
	      var found = coordsCharInner(cm, lineObj, lineN, x, y);
	      var collapsed = collapsedSpanAround(lineObj, found.ch + (found.xRel > 0 || found.outside > 0 ? 1 : 0));
	      if (!collapsed) { return found }
	      var rangeEnd = collapsed.find(1);
	      if (rangeEnd.line == lineN) { return rangeEnd }
	      lineObj = getLine(doc, lineN = rangeEnd.line);
	    }
	  }

	  function wrappedLineExtent(cm, lineObj, preparedMeasure, y) {
	    y -= widgetTopHeight(lineObj);
	    var end = lineObj.text.length;
	    var begin = findFirst(function (ch) { return measureCharPrepared(cm, preparedMeasure, ch - 1).bottom <= y; }, end, 0);
	    end = findFirst(function (ch) { return measureCharPrepared(cm, preparedMeasure, ch).top > y; }, begin, end);
	    return {begin: begin, end: end}
	  }

	  function wrappedLineExtentChar(cm, lineObj, preparedMeasure, target) {
	    if (!preparedMeasure) { preparedMeasure = prepareMeasureForLine(cm, lineObj); }
	    var targetTop = intoCoordSystem(cm, lineObj, measureCharPrepared(cm, preparedMeasure, target), "line").top;
	    return wrappedLineExtent(cm, lineObj, preparedMeasure, targetTop)
	  }

	  // Returns true if the given side of a box is after the given
	  // coordinates, in top-to-bottom, left-to-right order.
	  function boxIsAfter(box, x, y, left) {
	    return box.bottom <= y ? false : box.top > y ? true : (left ? box.left : box.right) > x
	  }

	  function coordsCharInner(cm, lineObj, lineNo, x, y) {
	    // Move y into line-local coordinate space
	    y -= heightAtLine(lineObj);
	    var preparedMeasure = prepareMeasureForLine(cm, lineObj);
	    // When directly calling `measureCharPrepared`, we have to adjust
	    // for the widgets at this line.
	    var widgetHeight = widgetTopHeight(lineObj);
	    var begin = 0, end = lineObj.text.length, ltr = true;

	    var order = getOrder(lineObj, cm.doc.direction);
	    // If the line isn't plain left-to-right text, first figure out
	    // which bidi section the coordinates fall into.
	    if (order) {
	      var part = (cm.options.lineWrapping ? coordsBidiPartWrapped : coordsBidiPart)
	                   (cm, lineObj, lineNo, preparedMeasure, order, x, y);
	      ltr = part.level != 1;
	      // The awkward -1 offsets are needed because findFirst (called
	      // on these below) will treat its first bound as inclusive,
	      // second as exclusive, but we want to actually address the
	      // characters in the part's range
	      begin = ltr ? part.from : part.to - 1;
	      end = ltr ? part.to : part.from - 1;
	    }

	    // A binary search to find the first character whose bounding box
	    // starts after the coordinates. If we run across any whose box wrap
	    // the coordinates, store that.
	    var chAround = null, boxAround = null;
	    var ch = findFirst(function (ch) {
	      var box = measureCharPrepared(cm, preparedMeasure, ch);
	      box.top += widgetHeight; box.bottom += widgetHeight;
	      if (!boxIsAfter(box, x, y, false)) { return false }
	      if (box.top <= y && box.left <= x) {
	        chAround = ch;
	        boxAround = box;
	      }
	      return true
	    }, begin, end);

	    var baseX, sticky, outside = false;
	    // If a box around the coordinates was found, use that
	    if (boxAround) {
	      // Distinguish coordinates nearer to the left or right side of the box
	      var atLeft = x - boxAround.left < boxAround.right - x, atStart = atLeft == ltr;
	      ch = chAround + (atStart ? 0 : 1);
	      sticky = atStart ? "after" : "before";
	      baseX = atLeft ? boxAround.left : boxAround.right;
	    } else {
	      // (Adjust for extended bound, if necessary.)
	      if (!ltr && (ch == end || ch == begin)) { ch++; }
	      // To determine which side to associate with, get the box to the
	      // left of the character and compare it's vertical position to the
	      // coordinates
	      sticky = ch == 0 ? "after" : ch == lineObj.text.length ? "before" :
	        (measureCharPrepared(cm, preparedMeasure, ch - (ltr ? 1 : 0)).bottom + widgetHeight <= y) == ltr ?
	        "after" : "before";
	      // Now get accurate coordinates for this place, in order to get a
	      // base X position
	      var coords = cursorCoords(cm, Pos(lineNo, ch, sticky), "line", lineObj, preparedMeasure);
	      baseX = coords.left;
	      outside = y < coords.top ? -1 : y >= coords.bottom ? 1 : 0;
	    }

	    ch = skipExtendingChars(lineObj.text, ch, 1);
	    return PosWithInfo(lineNo, ch, sticky, outside, x - baseX)
	  }

	  function coordsBidiPart(cm, lineObj, lineNo, preparedMeasure, order, x, y) {
	    // Bidi parts are sorted left-to-right, and in a non-line-wrapping
	    // situation, we can take this ordering to correspond to the visual
	    // ordering. This finds the first part whose end is after the given
	    // coordinates.
	    var index = findFirst(function (i) {
	      var part = order[i], ltr = part.level != 1;
	      return boxIsAfter(cursorCoords(cm, Pos(lineNo, ltr ? part.to : part.from, ltr ? "before" : "after"),
	                                     "line", lineObj, preparedMeasure), x, y, true)
	    }, 0, order.length - 1);
	    var part = order[index];
	    // If this isn't the first part, the part's start is also after
	    // the coordinates, and the coordinates aren't on the same line as
	    // that start, move one part back.
	    if (index > 0) {
	      var ltr = part.level != 1;
	      var start = cursorCoords(cm, Pos(lineNo, ltr ? part.from : part.to, ltr ? "after" : "before"),
	                               "line", lineObj, preparedMeasure);
	      if (boxIsAfter(start, x, y, true) && start.top > y)
	        { part = order[index - 1]; }
	    }
	    return part
	  }

	  function coordsBidiPartWrapped(cm, lineObj, _lineNo, preparedMeasure, order, x, y) {
	    // In a wrapped line, rtl text on wrapping boundaries can do things
	    // that don't correspond to the ordering in our `order` array at
	    // all, so a binary search doesn't work, and we want to return a
	    // part that only spans one line so that the binary search in
	    // coordsCharInner is safe. As such, we first find the extent of the
	    // wrapped line, and then do a flat search in which we discard any
	    // spans that aren't on the line.
	    var ref = wrappedLineExtent(cm, lineObj, preparedMeasure, y);
	    var begin = ref.begin;
	    var end = ref.end;
	    if (/\s/.test(lineObj.text.charAt(end - 1))) { end--; }
	    var part = null, closestDist = null;
	    for (var i = 0; i < order.length; i++) {
	      var p = order[i];
	      if (p.from >= end || p.to <= begin) { continue }
	      var ltr = p.level != 1;
	      var endX = measureCharPrepared(cm, preparedMeasure, ltr ? Math.min(end, p.to) - 1 : Math.max(begin, p.from)).right;
	      // Weigh against spans ending before this, so that they are only
	      // picked if nothing ends after
	      var dist = endX < x ? x - endX + 1e9 : endX - x;
	      if (!part || closestDist > dist) {
	        part = p;
	        closestDist = dist;
	      }
	    }
	    if (!part) { part = order[order.length - 1]; }
	    // Clip the part to the wrapped line.
	    if (part.from < begin) { part = {from: begin, to: part.to, level: part.level}; }
	    if (part.to > end) { part = {from: part.from, to: end, level: part.level}; }
	    return part
	  }

	  var measureText;
	  // Compute the default text height.
	  function textHeight(display) {
	    if (display.cachedTextHeight != null) { return display.cachedTextHeight }
	    if (measureText == null) {
	      measureText = elt("pre", null, "CodeMirror-line-like");
	      // Measure a bunch of lines, for browsers that compute
	      // fractional heights.
	      for (var i = 0; i < 49; ++i) {
	        measureText.appendChild(document.createTextNode("x"));
	        measureText.appendChild(elt("br"));
	      }
	      measureText.appendChild(document.createTextNode("x"));
	    }
	    removeChildrenAndAdd(display.measure, measureText);
	    var height = measureText.offsetHeight / 50;
	    if (height > 3) { display.cachedTextHeight = height; }
	    removeChildren(display.measure);
	    return height || 1
	  }

	  // Compute the default character width.
	  function charWidth(display) {
	    if (display.cachedCharWidth != null) { return display.cachedCharWidth }
	    var anchor = elt("span", "xxxxxxxxxx");
	    var pre = elt("pre", [anchor], "CodeMirror-line-like");
	    removeChildrenAndAdd(display.measure, pre);
	    var rect = anchor.getBoundingClientRect(), width = (rect.right - rect.left) / 10;
	    if (width > 2) { display.cachedCharWidth = width; }
	    return width || 10
	  }

	  // Do a bulk-read of the DOM positions and sizes needed to draw the
	  // view, so that we don't interleave reading and writing to the DOM.
	  function getDimensions(cm) {
	    var d = cm.display, left = {}, width = {};
	    var gutterLeft = d.gutters.clientLeft;
	    for (var n = d.gutters.firstChild, i = 0; n; n = n.nextSibling, ++i) {
	      var id = cm.display.gutterSpecs[i].className;
	      left[id] = n.offsetLeft + n.clientLeft + gutterLeft;
	      width[id] = n.clientWidth;
	    }
	    return {fixedPos: compensateForHScroll(d),
	            gutterTotalWidth: d.gutters.offsetWidth,
	            gutterLeft: left,
	            gutterWidth: width,
	            wrapperWidth: d.wrapper.clientWidth}
	  }

	  // Computes display.scroller.scrollLeft + display.gutters.offsetWidth,
	  // but using getBoundingClientRect to get a sub-pixel-accurate
	  // result.
	  function compensateForHScroll(display) {
	    return display.scroller.getBoundingClientRect().left - display.sizer.getBoundingClientRect().left
	  }

	  // Returns a function that estimates the height of a line, to use as
	  // first approximation until the line becomes visible (and is thus
	  // properly measurable).
	  function estimateHeight(cm) {
	    var th = textHeight(cm.display), wrapping = cm.options.lineWrapping;
	    var perLine = wrapping && Math.max(5, cm.display.scroller.clientWidth / charWidth(cm.display) - 3);
	    return function (line) {
	      if (lineIsHidden(cm.doc, line)) { return 0 }

	      var widgetsHeight = 0;
	      if (line.widgets) { for (var i = 0; i < line.widgets.length; i++) {
	        if (line.widgets[i].height) { widgetsHeight += line.widgets[i].height; }
	      } }

	      if (wrapping)
	        { return widgetsHeight + (Math.ceil(line.text.length / perLine) || 1) * th }
	      else
	        { return widgetsHeight + th }
	    }
	  }

	  function estimateLineHeights(cm) {
	    var doc = cm.doc, est = estimateHeight(cm);
	    doc.iter(function (line) {
	      var estHeight = est(line);
	      if (estHeight != line.height) { updateLineHeight(line, estHeight); }
	    });
	  }

	  // Given a mouse event, find the corresponding position. If liberal
	  // is false, it checks whether a gutter or scrollbar was clicked,
	  // and returns null if it was. forRect is used by rectangular
	  // selections, and tries to estimate a character position even for
	  // coordinates beyond the right of the text.
	  function posFromMouse(cm, e, liberal, forRect) {
	    var display = cm.display;
	    if (!liberal && e_target(e).getAttribute("cm-not-content") == "true") { return null }

	    var x, y, space = display.lineSpace.getBoundingClientRect();
	    // Fails unpredictably on IE[67] when mouse is dragged around quickly.
	    try { x = e.clientX - space.left; y = e.clientY - space.top; }
	    catch (e) { return null }
	    var coords = coordsChar(cm, x, y), line;
	    if (forRect && coords.xRel > 0 && (line = getLine(cm.doc, coords.line).text).length == coords.ch) {
	      var colDiff = countColumn(line, line.length, cm.options.tabSize) - line.length;
	      coords = Pos(coords.line, Math.max(0, Math.round((x - paddingH(cm.display).left) / charWidth(cm.display)) - colDiff));
	    }
	    return coords
	  }

	  // Find the view element corresponding to a given line. Return null
	  // when the line isn't visible.
	  function findViewIndex(cm, n) {
	    if (n >= cm.display.viewTo) { return null }
	    n -= cm.display.viewFrom;
	    if (n < 0) { return null }
	    var view = cm.display.view;
	    for (var i = 0; i < view.length; i++) {
	      n -= view[i].size;
	      if (n < 0) { return i }
	    }
	  }

	  // Updates the display.view data structure for a given change to the
	  // document. From and to are in pre-change coordinates. Lendiff is
	  // the amount of lines added or subtracted by the change. This is
	  // used for changes that span multiple lines, or change the way
	  // lines are divided into visual lines. regLineChange (below)
	  // registers single-line changes.
	  function regChange(cm, from, to, lendiff) {
	    if (from == null) { from = cm.doc.first; }
	    if (to == null) { to = cm.doc.first + cm.doc.size; }
	    if (!lendiff) { lendiff = 0; }

	    var display = cm.display;
	    if (lendiff && to < display.viewTo &&
	        (display.updateLineNumbers == null || display.updateLineNumbers > from))
	      { display.updateLineNumbers = from; }

	    cm.curOp.viewChanged = true;

	    if (from >= display.viewTo) { // Change after
	      if (sawCollapsedSpans && visualLineNo(cm.doc, from) < display.viewTo)
	        { resetView(cm); }
	    } else if (to <= display.viewFrom) { // Change before
	      if (sawCollapsedSpans && visualLineEndNo(cm.doc, to + lendiff) > display.viewFrom) {
	        resetView(cm);
	      } else {
	        display.viewFrom += lendiff;
	        display.viewTo += lendiff;
	      }
	    } else if (from <= display.viewFrom && to >= display.viewTo) { // Full overlap
	      resetView(cm);
	    } else if (from <= display.viewFrom) { // Top overlap
	      var cut = viewCuttingPoint(cm, to, to + lendiff, 1);
	      if (cut) {
	        display.view = display.view.slice(cut.index);
	        display.viewFrom = cut.lineN;
	        display.viewTo += lendiff;
	      } else {
	        resetView(cm);
	      }
	    } else if (to >= display.viewTo) { // Bottom overlap
	      var cut$1 = viewCuttingPoint(cm, from, from, -1);
	      if (cut$1) {
	        display.view = display.view.slice(0, cut$1.index);
	        display.viewTo = cut$1.lineN;
	      } else {
	        resetView(cm);
	      }
	    } else { // Gap in the middle
	      var cutTop = viewCuttingPoint(cm, from, from, -1);
	      var cutBot = viewCuttingPoint(cm, to, to + lendiff, 1);
	      if (cutTop && cutBot) {
	        display.view = display.view.slice(0, cutTop.index)
	          .concat(buildViewArray(cm, cutTop.lineN, cutBot.lineN))
	          .concat(display.view.slice(cutBot.index));
	        display.viewTo += lendiff;
	      } else {
	        resetView(cm);
	      }
	    }

	    var ext = display.externalMeasured;
	    if (ext) {
	      if (to < ext.lineN)
	        { ext.lineN += lendiff; }
	      else if (from < ext.lineN + ext.size)
	        { display.externalMeasured = null; }
	    }
	  }

	  // Register a change to a single line. Type must be one of "text",
	  // "gutter", "class", "widget"
	  function regLineChange(cm, line, type) {
	    cm.curOp.viewChanged = true;
	    var display = cm.display, ext = cm.display.externalMeasured;
	    if (ext && line >= ext.lineN && line < ext.lineN + ext.size)
	      { display.externalMeasured = null; }

	    if (line < display.viewFrom || line >= display.viewTo) { return }
	    var lineView = display.view[findViewIndex(cm, line)];
	    if (lineView.node == null) { return }
	    var arr = lineView.changes || (lineView.changes = []);
	    if (indexOf(arr, type) == -1) { arr.push(type); }
	  }

	  // Clear the view.
	  function resetView(cm) {
	    cm.display.viewFrom = cm.display.viewTo = cm.doc.first;
	    cm.display.view = [];
	    cm.display.viewOffset = 0;
	  }

	  function viewCuttingPoint(cm, oldN, newN, dir) {
	    var index = findViewIndex(cm, oldN), diff, view = cm.display.view;
	    if (!sawCollapsedSpans || newN == cm.doc.first + cm.doc.size)
	      { return {index: index, lineN: newN} }
	    var n = cm.display.viewFrom;
	    for (var i = 0; i < index; i++)
	      { n += view[i].size; }
	    if (n != oldN) {
	      if (dir > 0) {
	        if (index == view.length - 1) { return null }
	        diff = (n + view[index].size) - oldN;
	        index++;
	      } else {
	        diff = n - oldN;
	      }
	      oldN += diff; newN += diff;
	    }
	    while (visualLineNo(cm.doc, newN) != newN) {
	      if (index == (dir < 0 ? 0 : view.length - 1)) { return null }
	      newN += dir * view[index - (dir < 0 ? 1 : 0)].size;
	      index += dir;
	    }
	    return {index: index, lineN: newN}
	  }

	  // Force the view to cover a given range, adding empty view element
	  // or clipping off existing ones as needed.
	  function adjustView(cm, from, to) {
	    var display = cm.display, view = display.view;
	    if (view.length == 0 || from >= display.viewTo || to <= display.viewFrom) {
	      display.view = buildViewArray(cm, from, to);
	      display.viewFrom = from;
	    } else {
	      if (display.viewFrom > from)
	        { display.view = buildViewArray(cm, from, display.viewFrom).concat(display.view); }
	      else if (display.viewFrom < from)
	        { display.view = display.view.slice(findViewIndex(cm, from)); }
	      display.viewFrom = from;
	      if (display.viewTo < to)
	        { display.view = display.view.concat(buildViewArray(cm, display.viewTo, to)); }
	      else if (display.viewTo > to)
	        { display.view = display.view.slice(0, findViewIndex(cm, to)); }
	    }
	    display.viewTo = to;
	  }

	  // Count the number of lines in the view whose DOM representation is
	  // out of date (or nonexistent).
	  function countDirtyView(cm) {
	    var view = cm.display.view, dirty = 0;
	    for (var i = 0; i < view.length; i++) {
	      var lineView = view[i];
	      if (!lineView.hidden && (!lineView.node || lineView.changes)) { ++dirty; }
	    }
	    return dirty
	  }

	  function updateSelection(cm) {
	    cm.display.input.showSelection(cm.display.input.prepareSelection());
	  }

	  function prepareSelection(cm, primary) {
	    if ( primary === void 0 ) primary = true;

	    var doc = cm.doc, result = {};
	    var curFragment = result.cursors = document.createDocumentFragment();
	    var selFragment = result.selection = document.createDocumentFragment();

	    for (var i = 0; i < doc.sel.ranges.length; i++) {
	      if (!primary && i == doc.sel.primIndex) { continue }
	      var range = doc.sel.ranges[i];
	      if (range.from().line >= cm.display.viewTo || range.to().line < cm.display.viewFrom) { continue }
	      var collapsed = range.empty();
	      if (collapsed || cm.options.showCursorWhenSelecting)
	        { drawSelectionCursor(cm, range.head, curFragment); }
	      if (!collapsed)
	        { drawSelectionRange(cm, range, selFragment); }
	    }
	    return result
	  }

	  // Draws a cursor for the given range
	  function drawSelectionCursor(cm, head, output) {
	    var pos = cursorCoords(cm, head, "div", null, null, !cm.options.singleCursorHeightPerLine);

	    var cursor = output.appendChild(elt("div", "\u00a0", "CodeMirror-cursor"));
	    cursor.style.left = pos.left + "px";
	    cursor.style.top = pos.top + "px";
	    cursor.style.height = Math.max(0, pos.bottom - pos.top) * cm.options.cursorHeight + "px";

	    if (pos.other) {
	      // Secondary cursor, shown when on a 'jump' in bi-directional text
	      var otherCursor = output.appendChild(elt("div", "\u00a0", "CodeMirror-cursor CodeMirror-secondarycursor"));
	      otherCursor.style.display = "";
	      otherCursor.style.left = pos.other.left + "px";
	      otherCursor.style.top = pos.other.top + "px";
	      otherCursor.style.height = (pos.other.bottom - pos.other.top) * .85 + "px";
	    }
	  }

	  function cmpCoords(a, b) { return a.top - b.top || a.left - b.left }

	  // Draws the given range as a highlighted selection
	  function drawSelectionRange(cm, range, output) {
	    var display = cm.display, doc = cm.doc;
	    var fragment = document.createDocumentFragment();
	    var padding = paddingH(cm.display), leftSide = padding.left;
	    var rightSide = Math.max(display.sizerWidth, displayWidth(cm) - display.sizer.offsetLeft) - padding.right;
	    var docLTR = doc.direction == "ltr";

	    function add(left, top, width, bottom) {
	      if (top < 0) { top = 0; }
	      top = Math.round(top);
	      bottom = Math.round(bottom);
	      fragment.appendChild(elt("div", null, "CodeMirror-selected", ("position: absolute; left: " + left + "px;\n                             top: " + top + "px; width: " + (width == null ? rightSide - left : width) + "px;\n                             height: " + (bottom - top) + "px")));
	    }

	    function drawForLine(line, fromArg, toArg) {
	      var lineObj = getLine(doc, line);
	      var lineLen = lineObj.text.length;
	      var start, end;
	      function coords(ch, bias) {
	        return charCoords(cm, Pos(line, ch), "div", lineObj, bias)
	      }

	      function wrapX(pos, dir, side) {
	        var extent = wrappedLineExtentChar(cm, lineObj, null, pos);
	        var prop = (dir == "ltr") == (side == "after") ? "left" : "right";
	        var ch = side == "after" ? extent.begin : extent.end - (/\s/.test(lineObj.text.charAt(extent.end - 1)) ? 2 : 1);
	        return coords(ch, prop)[prop]
	      }

	      var order = getOrder(lineObj, doc.direction);
	      iterateBidiSections(order, fromArg || 0, toArg == null ? lineLen : toArg, function (from, to, dir, i) {
	        var ltr = dir == "ltr";
	        var fromPos = coords(from, ltr ? "left" : "right");
	        var toPos = coords(to - 1, ltr ? "right" : "left");

	        var openStart = fromArg == null && from == 0, openEnd = toArg == null && to == lineLen;
	        var first = i == 0, last = !order || i == order.length - 1;
	        if (toPos.top - fromPos.top <= 3) { // Single line
	          var openLeft = (docLTR ? openStart : openEnd) && first;
	          var openRight = (docLTR ? openEnd : openStart) && last;
	          var left = openLeft ? leftSide : (ltr ? fromPos : toPos).left;
	          var right = openRight ? rightSide : (ltr ? toPos : fromPos).right;
	          add(left, fromPos.top, right - left, fromPos.bottom);
	        } else { // Multiple lines
	          var topLeft, topRight, botLeft, botRight;
	          if (ltr) {
	            topLeft = docLTR && openStart && first ? leftSide : fromPos.left;
	            topRight = docLTR ? rightSide : wrapX(from, dir, "before");
	            botLeft = docLTR ? leftSide : wrapX(to, dir, "after");
	            botRight = docLTR && openEnd && last ? rightSide : toPos.right;
	          } else {
	            topLeft = !docLTR ? leftSide : wrapX(from, dir, "before");
	            topRight = !docLTR && openStart && first ? rightSide : fromPos.right;
	            botLeft = !docLTR && openEnd && last ? leftSide : toPos.left;
	            botRight = !docLTR ? rightSide : wrapX(to, dir, "after");
	          }
	          add(topLeft, fromPos.top, topRight - topLeft, fromPos.bottom);
	          if (fromPos.bottom < toPos.top) { add(leftSide, fromPos.bottom, null, toPos.top); }
	          add(botLeft, toPos.top, botRight - botLeft, toPos.bottom);
	        }

	        if (!start || cmpCoords(fromPos, start) < 0) { start = fromPos; }
	        if (cmpCoords(toPos, start) < 0) { start = toPos; }
	        if (!end || cmpCoords(fromPos, end) < 0) { end = fromPos; }
	        if (cmpCoords(toPos, end) < 0) { end = toPos; }
	      });
	      return {start: start, end: end}
	    }

	    var sFrom = range.from(), sTo = range.to();
	    if (sFrom.line == sTo.line) {
	      drawForLine(sFrom.line, sFrom.ch, sTo.ch);
	    } else {
	      var fromLine = getLine(doc, sFrom.line), toLine = getLine(doc, sTo.line);
	      var singleVLine = visualLine(fromLine) == visualLine(toLine);
	      var leftEnd = drawForLine(sFrom.line, sFrom.ch, singleVLine ? fromLine.text.length + 1 : null).end;
	      var rightStart = drawForLine(sTo.line, singleVLine ? 0 : null, sTo.ch).start;
	      if (singleVLine) {
	        if (leftEnd.top < rightStart.top - 2) {
	          add(leftEnd.right, leftEnd.top, null, leftEnd.bottom);
	          add(leftSide, rightStart.top, rightStart.left, rightStart.bottom);
	        } else {
	          add(leftEnd.right, leftEnd.top, rightStart.left - leftEnd.right, leftEnd.bottom);
	        }
	      }
	      if (leftEnd.bottom < rightStart.top)
	        { add(leftSide, leftEnd.bottom, null, rightStart.top); }
	    }

	    output.appendChild(fragment);
	  }

	  // Cursor-blinking
	  function restartBlink(cm) {
	    if (!cm.state.focused) { return }
	    var display = cm.display;
	    clearInterval(display.blinker);
	    var on = true;
	    display.cursorDiv.style.visibility = "";
	    if (cm.options.cursorBlinkRate > 0)
	      { display.blinker = setInterval(function () { return display.cursorDiv.style.visibility = (on = !on) ? "" : "hidden"; },
	        cm.options.cursorBlinkRate); }
	    else if (cm.options.cursorBlinkRate < 0)
	      { display.cursorDiv.style.visibility = "hidden"; }
	  }

	  function ensureFocus(cm) {
	    if (!cm.state.focused) { cm.display.input.focus(); onFocus(cm); }
	  }

	  function delayBlurEvent(cm) {
	    cm.state.delayingBlurEvent = true;
	    setTimeout(function () { if (cm.state.delayingBlurEvent) {
	      cm.state.delayingBlurEvent = false;
	      onBlur(cm);
	    } }, 100);
	  }

	  function onFocus(cm, e) {
	    if (cm.state.delayingBlurEvent) { cm.state.delayingBlurEvent = false; }

	    if (cm.options.readOnly == "nocursor") { return }
	    if (!cm.state.focused) {
	      signal(cm, "focus", cm, e);
	      cm.state.focused = true;
	      addClass(cm.display.wrapper, "CodeMirror-focused");
	      // This test prevents this from firing when a context
	      // menu is closed (since the input reset would kill the
	      // select-all detection hack)
	      if (!cm.curOp && cm.display.selForContextMenu != cm.doc.sel) {
	        cm.display.input.reset();
	        if (webkit) { setTimeout(function () { return cm.display.input.reset(true); }, 20); } // Issue #1730
	      }
	      cm.display.input.receivedFocus();
	    }
	    restartBlink(cm);
	  }
	  function onBlur(cm, e) {
	    if (cm.state.delayingBlurEvent) { return }

	    if (cm.state.focused) {
	      signal(cm, "blur", cm, e);
	      cm.state.focused = false;
	      rmClass(cm.display.wrapper, "CodeMirror-focused");
	    }
	    clearInterval(cm.display.blinker);
	    setTimeout(function () { if (!cm.state.focused) { cm.display.shift = false; } }, 150);
	  }

	  // Read the actual heights of the rendered lines, and update their
	  // stored heights to match.
	  function updateHeightsInViewport(cm) {
	    var display = cm.display;
	    var prevBottom = display.lineDiv.offsetTop;
	    for (var i = 0; i < display.view.length; i++) {
	      var cur = display.view[i], wrapping = cm.options.lineWrapping;
	      var height = (void 0), width = 0;
	      if (cur.hidden) { continue }
	      if (ie && ie_version < 8) {
	        var bot = cur.node.offsetTop + cur.node.offsetHeight;
	        height = bot - prevBottom;
	        prevBottom = bot;
	      } else {
	        var box = cur.node.getBoundingClientRect();
	        height = box.bottom - box.top;
	        // Check that lines don't extend past the right of the current
	        // editor width
	        if (!wrapping && cur.text.firstChild)
	          { width = cur.text.firstChild.getBoundingClientRect().right - box.left - 1; }
	      }
	      var diff = cur.line.height - height;
	      if (diff > .005 || diff < -.005) {
	        updateLineHeight(cur.line, height);
	        updateWidgetHeight(cur.line);
	        if (cur.rest) { for (var j = 0; j < cur.rest.length; j++)
	          { updateWidgetHeight(cur.rest[j]); } }
	      }
	      if (width > cm.display.sizerWidth) {
	        var chWidth = Math.ceil(width / charWidth(cm.display));
	        if (chWidth > cm.display.maxLineLength) {
	          cm.display.maxLineLength = chWidth;
	          cm.display.maxLine = cur.line;
	          cm.display.maxLineChanged = true;
	        }
	      }
	    }
	  }

	  // Read and store the height of line widgets associated with the
	  // given line.
	  function updateWidgetHeight(line) {
	    if (line.widgets) { for (var i = 0; i < line.widgets.length; ++i) {
	      var w = line.widgets[i], parent = w.node.parentNode;
	      if (parent) { w.height = parent.offsetHeight; }
	    } }
	  }

	  // Compute the lines that are visible in a given viewport (defaults
	  // the the current scroll position). viewport may contain top,
	  // height, and ensure (see op.scrollToPos) properties.
	  function visibleLines(display, doc, viewport) {
	    var top = viewport && viewport.top != null ? Math.max(0, viewport.top) : display.scroller.scrollTop;
	    top = Math.floor(top - paddingTop(display));
	    var bottom = viewport && viewport.bottom != null ? viewport.bottom : top + display.wrapper.clientHeight;

	    var from = lineAtHeight(doc, top), to = lineAtHeight(doc, bottom);
	    // Ensure is a {from: {line, ch}, to: {line, ch}} object, and
	    // forces those lines into the viewport (if possible).
	    if (viewport && viewport.ensure) {
	      var ensureFrom = viewport.ensure.from.line, ensureTo = viewport.ensure.to.line;
	      if (ensureFrom < from) {
	        from = ensureFrom;
	        to = lineAtHeight(doc, heightAtLine(getLine(doc, ensureFrom)) + display.wrapper.clientHeight);
	      } else if (Math.min(ensureTo, doc.lastLine()) >= to) {
	        from = lineAtHeight(doc, heightAtLine(getLine(doc, ensureTo)) - display.wrapper.clientHeight);
	        to = ensureTo;
	      }
	    }
	    return {from: from, to: Math.max(to, from + 1)}
	  }

	  // SCROLLING THINGS INTO VIEW

	  // If an editor sits on the top or bottom of the window, partially
	  // scrolled out of view, this ensures that the cursor is visible.
	  function maybeScrollWindow(cm, rect) {
	    if (signalDOMEvent(cm, "scrollCursorIntoView")) { return }

	    var display = cm.display, box = display.sizer.getBoundingClientRect(), doScroll = null;
	    if (rect.top + box.top < 0) { doScroll = true; }
	    else if (rect.bottom + box.top > (window.innerHeight || document.documentElement.clientHeight)) { doScroll = false; }
	    if (doScroll != null && !phantom) {
	      var scrollNode = elt("div", "\u200b", null, ("position: absolute;\n                         top: " + (rect.top - display.viewOffset - paddingTop(cm.display)) + "px;\n                         height: " + (rect.bottom - rect.top + scrollGap(cm) + display.barHeight) + "px;\n                         left: " + (rect.left) + "px; width: " + (Math.max(2, rect.right - rect.left)) + "px;"));
	      cm.display.lineSpace.appendChild(scrollNode);
	      scrollNode.scrollIntoView(doScroll);
	      cm.display.lineSpace.removeChild(scrollNode);
	    }
	  }

	  // Scroll a given position into view (immediately), verifying that
	  // it actually became visible (as line heights are accurately
	  // measured, the position of something may 'drift' during drawing).
	  function scrollPosIntoView(cm, pos, end, margin) {
	    if (margin == null) { margin = 0; }
	    var rect;
	    if (!cm.options.lineWrapping && pos == end) {
	      // Set pos and end to the cursor positions around the character pos sticks to
	      // If pos.sticky == "before", that is around pos.ch - 1, otherwise around pos.ch
	      // If pos == Pos(_, 0, "before"), pos and end are unchanged
	      pos = pos.ch ? Pos(pos.line, pos.sticky == "before" ? pos.ch - 1 : pos.ch, "after") : pos;
	      end = pos.sticky == "before" ? Pos(pos.line, pos.ch + 1, "before") : pos;
	    }
	    for (var limit = 0; limit < 5; limit++) {
	      var changed = false;
	      var coords = cursorCoords(cm, pos);
	      var endCoords = !end || end == pos ? coords : cursorCoords(cm, end);
	      rect = {left: Math.min(coords.left, endCoords.left),
	              top: Math.min(coords.top, endCoords.top) - margin,
	              right: Math.max(coords.left, endCoords.left),
	              bottom: Math.max(coords.bottom, endCoords.bottom) + margin};
	      var scrollPos = calculateScrollPos(cm, rect);
	      var startTop = cm.doc.scrollTop, startLeft = cm.doc.scrollLeft;
	      if (scrollPos.scrollTop != null) {
	        updateScrollTop(cm, scrollPos.scrollTop);
	        if (Math.abs(cm.doc.scrollTop - startTop) > 1) { changed = true; }
	      }
	      if (scrollPos.scrollLeft != null) {
	        setScrollLeft(cm, scrollPos.scrollLeft);
	        if (Math.abs(cm.doc.scrollLeft - startLeft) > 1) { changed = true; }
	      }
	      if (!changed) { break }
	    }
	    return rect
	  }

	  // Scroll a given set of coordinates into view (immediately).
	  function scrollIntoView(cm, rect) {
	    var scrollPos = calculateScrollPos(cm, rect);
	    if (scrollPos.scrollTop != null) { updateScrollTop(cm, scrollPos.scrollTop); }
	    if (scrollPos.scrollLeft != null) { setScrollLeft(cm, scrollPos.scrollLeft); }
	  }

	  // Calculate a new scroll position needed to scroll the given
	  // rectangle into view. Returns an object with scrollTop and
	  // scrollLeft properties. When these are undefined, the
	  // vertical/horizontal position does not need to be adjusted.
	  function calculateScrollPos(cm, rect) {
	    var display = cm.display, snapMargin = textHeight(cm.display);
	    if (rect.top < 0) { rect.top = 0; }
	    var screentop = cm.curOp && cm.curOp.scrollTop != null ? cm.curOp.scrollTop : display.scroller.scrollTop;
	    var screen = displayHeight(cm), result = {};
	    if (rect.bottom - rect.top > screen) { rect.bottom = rect.top + screen; }
	    var docBottom = cm.doc.height + paddingVert(display);
	    var atTop = rect.top < snapMargin, atBottom = rect.bottom > docBottom - snapMargin;
	    if (rect.top < screentop) {
	      result.scrollTop = atTop ? 0 : rect.top;
	    } else if (rect.bottom > screentop + screen) {
	      var newTop = Math.min(rect.top, (atBottom ? docBottom : rect.bottom) - screen);
	      if (newTop != screentop) { result.scrollTop = newTop; }
	    }

	    var screenleft = cm.curOp && cm.curOp.scrollLeft != null ? cm.curOp.scrollLeft : display.scroller.scrollLeft;
	    var screenw = displayWidth(cm) - (cm.options.fixedGutter ? display.gutters.offsetWidth : 0);
	    var tooWide = rect.right - rect.left > screenw;
	    if (tooWide) { rect.right = rect.left + screenw; }
	    if (rect.left < 10)
	      { result.scrollLeft = 0; }
	    else if (rect.left < screenleft)
	      { result.scrollLeft = Math.max(0, rect.left - (tooWide ? 0 : 10)); }
	    else if (rect.right > screenw + screenleft - 3)
	      { result.scrollLeft = rect.right + (tooWide ? 0 : 10) - screenw; }
	    return result
	  }

	  // Store a relative adjustment to the scroll position in the current
	  // operation (to be applied when the operation finishes).
	  function addToScrollTop(cm, top) {
	    if (top == null) { return }
	    resolveScrollToPos(cm);
	    cm.curOp.scrollTop = (cm.curOp.scrollTop == null ? cm.doc.scrollTop : cm.curOp.scrollTop) + top;
	  }

	  // Make sure that at the end of the operation the current cursor is
	  // shown.
	  function ensureCursorVisible(cm) {
	    resolveScrollToPos(cm);
	    var cur = cm.getCursor();
	    cm.curOp.scrollToPos = {from: cur, to: cur, margin: cm.options.cursorScrollMargin};
	  }

	  function scrollToCoords(cm, x, y) {
	    if (x != null || y != null) { resolveScrollToPos(cm); }
	    if (x != null) { cm.curOp.scrollLeft = x; }
	    if (y != null) { cm.curOp.scrollTop = y; }
	  }

	  function scrollToRange(cm, range) {
	    resolveScrollToPos(cm);
	    cm.curOp.scrollToPos = range;
	  }

	  // When an operation has its scrollToPos property set, and another
	  // scroll action is applied before the end of the operation, this
	  // 'simulates' scrolling that position into view in a cheap way, so
	  // that the effect of intermediate scroll commands is not ignored.
	  function resolveScrollToPos(cm) {
	    var range = cm.curOp.scrollToPos;
	    if (range) {
	      cm.curOp.scrollToPos = null;
	      var from = estimateCoords(cm, range.from), to = estimateCoords(cm, range.to);
	      scrollToCoordsRange(cm, from, to, range.margin);
	    }
	  }

	  function scrollToCoordsRange(cm, from, to, margin) {
	    var sPos = calculateScrollPos(cm, {
	      left: Math.min(from.left, to.left),
	      top: Math.min(from.top, to.top) - margin,
	      right: Math.max(from.right, to.right),
	      bottom: Math.max(from.bottom, to.bottom) + margin
	    });
	    scrollToCoords(cm, sPos.scrollLeft, sPos.scrollTop);
	  }

	  // Sync the scrollable area and scrollbars, ensure the viewport
	  // covers the visible area.
	  function updateScrollTop(cm, val) {
	    if (Math.abs(cm.doc.scrollTop - val) < 2) { return }
	    if (!gecko) { updateDisplaySimple(cm, {top: val}); }
	    setScrollTop(cm, val, true);
	    if (gecko) { updateDisplaySimple(cm); }
	    startWorker(cm, 100);
	  }

	  function setScrollTop(cm, val, forceScroll) {
	    val = Math.max(0, Math.min(cm.display.scroller.scrollHeight - cm.display.scroller.clientHeight, val));
	    if (cm.display.scroller.scrollTop == val && !forceScroll) { return }
	    cm.doc.scrollTop = val;
	    cm.display.scrollbars.setScrollTop(val);
	    if (cm.display.scroller.scrollTop != val) { cm.display.scroller.scrollTop = val; }
	  }

	  // Sync scroller and scrollbar, ensure the gutter elements are
	  // aligned.
	  function setScrollLeft(cm, val, isScroller, forceScroll) {
	    val = Math.max(0, Math.min(val, cm.display.scroller.scrollWidth - cm.display.scroller.clientWidth));
	    if ((isScroller ? val == cm.doc.scrollLeft : Math.abs(cm.doc.scrollLeft - val) < 2) && !forceScroll) { return }
	    cm.doc.scrollLeft = val;
	    alignHorizontally(cm);
	    if (cm.display.scroller.scrollLeft != val) { cm.display.scroller.scrollLeft = val; }
	    cm.display.scrollbars.setScrollLeft(val);
	  }

	  // SCROLLBARS

	  // Prepare DOM reads needed to update the scrollbars. Done in one
	  // shot to minimize update/measure roundtrips.
	  function measureForScrollbars(cm) {
	    var d = cm.display, gutterW = d.gutters.offsetWidth;
	    var docH = Math.round(cm.doc.height + paddingVert(cm.display));
	    return {
	      clientHeight: d.scroller.clientHeight,
	      viewHeight: d.wrapper.clientHeight,
	      scrollWidth: d.scroller.scrollWidth, clientWidth: d.scroller.clientWidth,
	      viewWidth: d.wrapper.clientWidth,
	      barLeft: cm.options.fixedGutter ? gutterW : 0,
	      docHeight: docH,
	      scrollHeight: docH + scrollGap(cm) + d.barHeight,
	      nativeBarWidth: d.nativeBarWidth,
	      gutterWidth: gutterW
	    }
	  }

	  var NativeScrollbars = function(place, scroll, cm) {
	    this.cm = cm;
	    var vert = this.vert = elt("div", [elt("div", null, null, "min-width: 1px")], "CodeMirror-vscrollbar");
	    var horiz = this.horiz = elt("div", [elt("div", null, null, "height: 100%; min-height: 1px")], "CodeMirror-hscrollbar");
	    vert.tabIndex = horiz.tabIndex = -1;
	    place(vert); place(horiz);

	    on(vert, "scroll", function () {
	      if (vert.clientHeight) { scroll(vert.scrollTop, "vertical"); }
	    });
	    on(horiz, "scroll", function () {
	      if (horiz.clientWidth) { scroll(horiz.scrollLeft, "horizontal"); }
	    });

	    this.checkedZeroWidth = false;
	    // Need to set a minimum width to see the scrollbar on IE7 (but must not set it on IE8).
	    if (ie && ie_version < 8) { this.horiz.style.minHeight = this.vert.style.minWidth = "18px"; }
	  };

	  NativeScrollbars.prototype.update = function (measure) {
	    var needsH = measure.scrollWidth > measure.clientWidth + 1;
	    var needsV = measure.scrollHeight > measure.clientHeight + 1;
	    var sWidth = measure.nativeBarWidth;

	    if (needsV) {
	      this.vert.style.display = "block";
	      this.vert.style.bottom = needsH ? sWidth + "px" : "0";
	      var totalHeight = measure.viewHeight - (needsH ? sWidth : 0);
	      // A bug in IE8 can cause this value to be negative, so guard it.
	      this.vert.firstChild.style.height =
	        Math.max(0, measure.scrollHeight - measure.clientHeight + totalHeight) + "px";
	    } else {
	      this.vert.style.display = "";
	      this.vert.firstChild.style.height = "0";
	    }

	    if (needsH) {
	      this.horiz.style.display = "block";
	      this.horiz.style.right = needsV ? sWidth + "px" : "0";
	      this.horiz.style.left = measure.barLeft + "px";
	      var totalWidth = measure.viewWidth - measure.barLeft - (needsV ? sWidth : 0);
	      this.horiz.firstChild.style.width =
	        Math.max(0, measure.scrollWidth - measure.clientWidth + totalWidth) + "px";
	    } else {
	      this.horiz.style.display = "";
	      this.horiz.firstChild.style.width = "0";
	    }

	    if (!this.checkedZeroWidth && measure.clientHeight > 0) {
	      if (sWidth == 0) { this.zeroWidthHack(); }
	      this.checkedZeroWidth = true;
	    }

	    return {right: needsV ? sWidth : 0, bottom: needsH ? sWidth : 0}
	  };

	  NativeScrollbars.prototype.setScrollLeft = function (pos) {
	    if (this.horiz.scrollLeft != pos) { this.horiz.scrollLeft = pos; }
	    if (this.disableHoriz) { this.enableZeroWidthBar(this.horiz, this.disableHoriz, "horiz"); }
	  };

	  NativeScrollbars.prototype.setScrollTop = function (pos) {
	    if (this.vert.scrollTop != pos) { this.vert.scrollTop = pos; }
	    if (this.disableVert) { this.enableZeroWidthBar(this.vert, this.disableVert, "vert"); }
	  };

	  NativeScrollbars.prototype.zeroWidthHack = function () {
	    var w = mac && !mac_geMountainLion ? "12px" : "18px";
	    this.horiz.style.height = this.vert.style.width = w;
	    this.horiz.style.pointerEvents = this.vert.style.pointerEvents = "none";
	    this.disableHoriz = new Delayed;
	    this.disableVert = new Delayed;
	  };

	  NativeScrollbars.prototype.enableZeroWidthBar = function (bar, delay, type) {
	    bar.style.pointerEvents = "auto";
	    function maybeDisable() {
	      // To find out whether the scrollbar is still visible, we
	      // check whether the element under the pixel in the bottom
	      // right corner of the scrollbar box is the scrollbar box
	      // itself (when the bar is still visible) or its filler child
	      // (when the bar is hidden). If it is still visible, we keep
	      // it enabled, if it's hidden, we disable pointer events.
	      var box = bar.getBoundingClientRect();
	      var elt = type == "vert" ? document.elementFromPoint(box.right - 1, (box.top + box.bottom) / 2)
	          : document.elementFromPoint((box.right + box.left) / 2, box.bottom - 1);
	      if (elt != bar) { bar.style.pointerEvents = "none"; }
	      else { delay.set(1000, maybeDisable); }
	    }
	    delay.set(1000, maybeDisable);
	  };

	  NativeScrollbars.prototype.clear = function () {
	    var parent = this.horiz.parentNode;
	    parent.removeChild(this.horiz);
	    parent.removeChild(this.vert);
	  };

	  var NullScrollbars = function () {};

	  NullScrollbars.prototype.update = function () { return {bottom: 0, right: 0} };
	  NullScrollbars.prototype.setScrollLeft = function () {};
	  NullScrollbars.prototype.setScrollTop = function () {};
	  NullScrollbars.prototype.clear = function () {};

	  function updateScrollbars(cm, measure) {
	    if (!measure) { measure = measureForScrollbars(cm); }
	    var startWidth = cm.display.barWidth, startHeight = cm.display.barHeight;
	    updateScrollbarsInner(cm, measure);
	    for (var i = 0; i < 4 && startWidth != cm.display.barWidth || startHeight != cm.display.barHeight; i++) {
	      if (startWidth != cm.display.barWidth && cm.options.lineWrapping)
	        { updateHeightsInViewport(cm); }
	      updateScrollbarsInner(cm, measureForScrollbars(cm));
	      startWidth = cm.display.barWidth; startHeight = cm.display.barHeight;
	    }
	  }

	  // Re-synchronize the fake scrollbars with the actual size of the
	  // content.
	  function updateScrollbarsInner(cm, measure) {
	    var d = cm.display;
	    var sizes = d.scrollbars.update(measure);

	    d.sizer.style.paddingRight = (d.barWidth = sizes.right) + "px";
	    d.sizer.style.paddingBottom = (d.barHeight = sizes.bottom) + "px";
	    d.heightForcer.style.borderBottom = sizes.bottom + "px solid transparent";

	    if (sizes.right && sizes.bottom) {
	      d.scrollbarFiller.style.display = "block";
	      d.scrollbarFiller.style.height = sizes.bottom + "px";
	      d.scrollbarFiller.style.width = sizes.right + "px";
	    } else { d.scrollbarFiller.style.display = ""; }
	    if (sizes.bottom && cm.options.coverGutterNextToScrollbar && cm.options.fixedGutter) {
	      d.gutterFiller.style.display = "block";
	      d.gutterFiller.style.height = sizes.bottom + "px";
	      d.gutterFiller.style.width = measure.gutterWidth + "px";
	    } else { d.gutterFiller.style.display = ""; }
	  }

	  var scrollbarModel = {"native": NativeScrollbars, "null": NullScrollbars};

	  function initScrollbars(cm) {
	    if (cm.display.scrollbars) {
	      cm.display.scrollbars.clear();
	      if (cm.display.scrollbars.addClass)
	        { rmClass(cm.display.wrapper, cm.display.scrollbars.addClass); }
	    }

	    cm.display.scrollbars = new scrollbarModel[cm.options.scrollbarStyle](function (node) {
	      cm.display.wrapper.insertBefore(node, cm.display.scrollbarFiller);
	      // Prevent clicks in the scrollbars from killing focus
	      on(node, "mousedown", function () {
	        if (cm.state.focused) { setTimeout(function () { return cm.display.input.focus(); }, 0); }
	      });
	      node.setAttribute("cm-not-content", "true");
	    }, function (pos, axis) {
	      if (axis == "horizontal") { setScrollLeft(cm, pos); }
	      else { updateScrollTop(cm, pos); }
	    }, cm);
	    if (cm.display.scrollbars.addClass)
	      { addClass(cm.display.wrapper, cm.display.scrollbars.addClass); }
	  }

	  // Operations are used to wrap a series of changes to the editor
	  // state in such a way that each change won't have to update the
	  // cursor and display (which would be awkward, slow, and
	  // error-prone). Instead, display updates are batched and then all
	  // combined and executed at once.

	  var nextOpId = 0;
	  // Start a new operation.
	  function startOperation(cm) {
	    cm.curOp = {
	      cm: cm,
	      viewChanged: false,      // Flag that indicates that lines might need to be redrawn
	      startHeight: cm.doc.height, // Used to detect need to update scrollbar
	      forceUpdate: false,      // Used to force a redraw
	      updateInput: 0,       // Whether to reset the input textarea
	      typing: false,           // Whether this reset should be careful to leave existing text (for compositing)
	      changeObjs: null,        // Accumulated changes, for firing change events
	      cursorActivityHandlers: null, // Set of handlers to fire cursorActivity on
	      cursorActivityCalled: 0, // Tracks which cursorActivity handlers have been called already
	      selectionChanged: false, // Whether the selection needs to be redrawn
	      updateMaxLine: false,    // Set when the widest line needs to be determined anew
	      scrollLeft: null, scrollTop: null, // Intermediate scroll position, not pushed to DOM yet
	      scrollToPos: null,       // Used to scroll to a specific position
	      focus: false,
	      id: ++nextOpId           // Unique ID
	    };
	    pushOperation(cm.curOp);
	  }

	  // Finish an operation, updating the display and signalling delayed events
	  function endOperation(cm) {
	    var op = cm.curOp;
	    if (op) { finishOperation(op, function (group) {
	      for (var i = 0; i < group.ops.length; i++)
	        { group.ops[i].cm.curOp = null; }
	      endOperations(group);
	    }); }
	  }

	  // The DOM updates done when an operation finishes are batched so
	  // that the minimum number of relayouts are required.
	  function endOperations(group) {
	    var ops = group.ops;
	    for (var i = 0; i < ops.length; i++) // Read DOM
	      { endOperation_R1(ops[i]); }
	    for (var i$1 = 0; i$1 < ops.length; i$1++) // Write DOM (maybe)
	      { endOperation_W1(ops[i$1]); }
	    for (var i$2 = 0; i$2 < ops.length; i$2++) // Read DOM
	      { endOperation_R2(ops[i$2]); }
	    for (var i$3 = 0; i$3 < ops.length; i$3++) // Write DOM (maybe)
	      { endOperation_W2(ops[i$3]); }
	    for (var i$4 = 0; i$4 < ops.length; i$4++) // Read DOM
	      { endOperation_finish(ops[i$4]); }
	  }

	  function endOperation_R1(op) {
	    var cm = op.cm, display = cm.display;
	    maybeClipScrollbars(cm);
	    if (op.updateMaxLine) { findMaxLine(cm); }

	    op.mustUpdate = op.viewChanged || op.forceUpdate || op.scrollTop != null ||
	      op.scrollToPos && (op.scrollToPos.from.line < display.viewFrom ||
	                         op.scrollToPos.to.line >= display.viewTo) ||
	      display.maxLineChanged && cm.options.lineWrapping;
	    op.update = op.mustUpdate &&
	      new DisplayUpdate(cm, op.mustUpdate && {top: op.scrollTop, ensure: op.scrollToPos}, op.forceUpdate);
	  }

	  function endOperation_W1(op) {
	    op.updatedDisplay = op.mustUpdate && updateDisplayIfNeeded(op.cm, op.update);
	  }

	  function endOperation_R2(op) {
	    var cm = op.cm, display = cm.display;
	    if (op.updatedDisplay) { updateHeightsInViewport(cm); }

	    op.barMeasure = measureForScrollbars(cm);

	    // If the max line changed since it was last measured, measure it,
	    // and ensure the document's width matches it.
	    // updateDisplay_W2 will use these properties to do the actual resizing
	    if (display.maxLineChanged && !cm.options.lineWrapping) {
	      op.adjustWidthTo = measureChar(cm, display.maxLine, display.maxLine.text.length).left + 3;
	      cm.display.sizerWidth = op.adjustWidthTo;
	      op.barMeasure.scrollWidth =
	        Math.max(display.scroller.clientWidth, display.sizer.offsetLeft + op.adjustWidthTo + scrollGap(cm) + cm.display.barWidth);
	      op.maxScrollLeft = Math.max(0, display.sizer.offsetLeft + op.adjustWidthTo - displayWidth(cm));
	    }

	    if (op.updatedDisplay || op.selectionChanged)
	      { op.preparedSelection = display.input.prepareSelection(); }
	  }

	  function endOperation_W2(op) {
	    var cm = op.cm;

	    if (op.adjustWidthTo != null) {
	      cm.display.sizer.style.minWidth = op.adjustWidthTo + "px";
	      if (op.maxScrollLeft < cm.doc.scrollLeft)
	        { setScrollLeft(cm, Math.min(cm.display.scroller.scrollLeft, op.maxScrollLeft), true); }
	      cm.display.maxLineChanged = false;
	    }

	    var takeFocus = op.focus && op.focus == activeElt();
	    if (op.preparedSelection)
	      { cm.display.input.showSelection(op.preparedSelection, takeFocus); }
	    if (op.updatedDisplay || op.startHeight != cm.doc.height)
	      { updateScrollbars(cm, op.barMeasure); }
	    if (op.updatedDisplay)
	      { setDocumentHeight(cm, op.barMeasure); }

	    if (op.selectionChanged) { restartBlink(cm); }

	    if (cm.state.focused && op.updateInput)
	      { cm.display.input.reset(op.typing); }
	    if (takeFocus) { ensureFocus(op.cm); }
	  }

	  function endOperation_finish(op) {
	    var cm = op.cm, display = cm.display, doc = cm.doc;

	    if (op.updatedDisplay) { postUpdateDisplay(cm, op.update); }

	    // Abort mouse wheel delta measurement, when scrolling explicitly
	    if (display.wheelStartX != null && (op.scrollTop != null || op.scrollLeft != null || op.scrollToPos))
	      { display.wheelStartX = display.wheelStartY = null; }

	    // Propagate the scroll position to the actual DOM scroller
	    if (op.scrollTop != null) { setScrollTop(cm, op.scrollTop, op.forceScroll); }

	    if (op.scrollLeft != null) { setScrollLeft(cm, op.scrollLeft, true, true); }
	    // If we need to scroll a specific position into view, do so.
	    if (op.scrollToPos) {
	      var rect = scrollPosIntoView(cm, clipPos(doc, op.scrollToPos.from),
	                                   clipPos(doc, op.scrollToPos.to), op.scrollToPos.margin);
	      maybeScrollWindow(cm, rect);
	    }

	    // Fire events for markers that are hidden/unidden by editing or
	    // undoing
	    var hidden = op.maybeHiddenMarkers, unhidden = op.maybeUnhiddenMarkers;
	    if (hidden) { for (var i = 0; i < hidden.length; ++i)
	      { if (!hidden[i].lines.length) { signal(hidden[i], "hide"); } } }
	    if (unhidden) { for (var i$1 = 0; i$1 < unhidden.length; ++i$1)
	      { if (unhidden[i$1].lines.length) { signal(unhidden[i$1], "unhide"); } } }

	    if (display.wrapper.offsetHeight)
	      { doc.scrollTop = cm.display.scroller.scrollTop; }

	    // Fire change events, and delayed event handlers
	    if (op.changeObjs)
	      { signal(cm, "changes", cm, op.changeObjs); }
	    if (op.update)
	      { op.update.finish(); }
	  }

	  // Run the given function in an operation
	  function runInOp(cm, f) {
	    if (cm.curOp) { return f() }
	    startOperation(cm);
	    try { return f() }
	    finally { endOperation(cm); }
	  }
	  // Wraps a function in an operation. Returns the wrapped function.
	  function operation(cm, f) {
	    return function() {
	      if (cm.curOp) { return f.apply(cm, arguments) }
	      startOperation(cm);
	      try { return f.apply(cm, arguments) }
	      finally { endOperation(cm); }
	    }
	  }
	  // Used to add methods to editor and doc instances, wrapping them in
	  // operations.
	  function methodOp(f) {
	    return function() {
	      if (this.curOp) { return f.apply(this, arguments) }
	      startOperation(this);
	      try { return f.apply(this, arguments) }
	      finally { endOperation(this); }
	    }
	  }
	  function docMethodOp(f) {
	    return function() {
	      var cm = this.cm;
	      if (!cm || cm.curOp) { return f.apply(this, arguments) }
	      startOperation(cm);
	      try { return f.apply(this, arguments) }
	      finally { endOperation(cm); }
	    }
	  }

	  // HIGHLIGHT WORKER

	  function startWorker(cm, time) {
	    if (cm.doc.highlightFrontier < cm.display.viewTo)
	      { cm.state.highlight.set(time, bind(highlightWorker, cm)); }
	  }

	  function highlightWorker(cm) {
	    var doc = cm.doc;
	    if (doc.highlightFrontier >= cm.display.viewTo) { return }
	    var end = +new Date + cm.options.workTime;
	    var context = getContextBefore(cm, doc.highlightFrontier);
	    var changedLines = [];

	    doc.iter(context.line, Math.min(doc.first + doc.size, cm.display.viewTo + 500), function (line) {
	      if (context.line >= cm.display.viewFrom) { // Visible
	        var oldStyles = line.styles;
	        var resetState = line.text.length > cm.options.maxHighlightLength ? copyState(doc.mode, context.state) : null;
	        var highlighted = highlightLine(cm, line, context, true);
	        if (resetState) { context.state = resetState; }
	        line.styles = highlighted.styles;
	        var oldCls = line.styleClasses, newCls = highlighted.classes;
	        if (newCls) { line.styleClasses = newCls; }
	        else if (oldCls) { line.styleClasses = null; }
	        var ischange = !oldStyles || oldStyles.length != line.styles.length ||
	          oldCls != newCls && (!oldCls || !newCls || oldCls.bgClass != newCls.bgClass || oldCls.textClass != newCls.textClass);
	        for (var i = 0; !ischange && i < oldStyles.length; ++i) { ischange = oldStyles[i] != line.styles[i]; }
	        if (ischange) { changedLines.push(context.line); }
	        line.stateAfter = context.save();
	        context.nextLine();
	      } else {
	        if (line.text.length <= cm.options.maxHighlightLength)
	          { processLine(cm, line.text, context); }
	        line.stateAfter = context.line % 5 == 0 ? context.save() : null;
	        context.nextLine();
	      }
	      if (+new Date > end) {
	        startWorker(cm, cm.options.workDelay);
	        return true
	      }
	    });
	    doc.highlightFrontier = context.line;
	    doc.modeFrontier = Math.max(doc.modeFrontier, context.line);
	    if (changedLines.length) { runInOp(cm, function () {
	      for (var i = 0; i < changedLines.length; i++)
	        { regLineChange(cm, changedLines[i], "text"); }
	    }); }
	  }

	  // DISPLAY DRAWING

	  var DisplayUpdate = function(cm, viewport, force) {
	    var display = cm.display;

	    this.viewport = viewport;
	    // Store some values that we'll need later (but don't want to force a relayout for)
	    this.visible = visibleLines(display, cm.doc, viewport);
	    this.editorIsHidden = !display.wrapper.offsetWidth;
	    this.wrapperHeight = display.wrapper.clientHeight;
	    this.wrapperWidth = display.wrapper.clientWidth;
	    this.oldDisplayWidth = displayWidth(cm);
	    this.force = force;
	    this.dims = getDimensions(cm);
	    this.events = [];
	  };

	  DisplayUpdate.prototype.signal = function (emitter, type) {
	    if (hasHandler(emitter, type))
	      { this.events.push(arguments); }
	  };
	  DisplayUpdate.prototype.finish = function () {
	    for (var i = 0; i < this.events.length; i++)
	      { signal.apply(null, this.events[i]); }
	  };

	  function maybeClipScrollbars(cm) {
	    var display = cm.display;
	    if (!display.scrollbarsClipped && display.scroller.offsetWidth) {
	      display.nativeBarWidth = display.scroller.offsetWidth - display.scroller.clientWidth;
	      display.heightForcer.style.height = scrollGap(cm) + "px";
	      display.sizer.style.marginBottom = -display.nativeBarWidth + "px";
	      display.sizer.style.borderRightWidth = scrollGap(cm) + "px";
	      display.scrollbarsClipped = true;
	    }
	  }

	  function selectionSnapshot(cm) {
	    if (cm.hasFocus()) { return null }
	    var active = activeElt();
	    if (!active || !contains(cm.display.lineDiv, active)) { return null }
	    var result = {activeElt: active};
	    if (window.getSelection) {
	      var sel = window.getSelection();
	      if (sel.anchorNode && sel.extend && contains(cm.display.lineDiv, sel.anchorNode)) {
	        result.anchorNode = sel.anchorNode;
	        result.anchorOffset = sel.anchorOffset;
	        result.focusNode = sel.focusNode;
	        result.focusOffset = sel.focusOffset;
	      }
	    }
	    return result
	  }

	  function restoreSelection(snapshot) {
	    if (!snapshot || !snapshot.activeElt || snapshot.activeElt == activeElt()) { return }
	    snapshot.activeElt.focus();
	    if (snapshot.anchorNode && contains(document.body, snapshot.anchorNode) && contains(document.body, snapshot.focusNode)) {
	      var sel = window.getSelection(), range = document.createRange();
	      range.setEnd(snapshot.anchorNode, snapshot.anchorOffset);
	      range.collapse(false);
	      sel.removeAllRanges();
	      sel.addRange(range);
	      sel.extend(snapshot.focusNode, snapshot.focusOffset);
	    }
	  }

	  // Does the actual updating of the line display. Bails out
	  // (returning false) when there is nothing to be done and forced is
	  // false.
	  function updateDisplayIfNeeded(cm, update) {
	    var display = cm.display, doc = cm.doc;

	    if (update.editorIsHidden) {
	      resetView(cm);
	      return false
	    }

	    // Bail out if the visible area is already rendered and nothing changed.
	    if (!update.force &&
	        update.visible.from >= display.viewFrom && update.visible.to <= display.viewTo &&
	        (display.updateLineNumbers == null || display.updateLineNumbers >= display.viewTo) &&
	        display.renderedView == display.view && countDirtyView(cm) == 0)
	      { return false }

	    if (maybeUpdateLineNumberWidth(cm)) {
	      resetView(cm);
	      update.dims = getDimensions(cm);
	    }

	    // Compute a suitable new viewport (from & to)
	    var end = doc.first + doc.size;
	    var from = Math.max(update.visible.from - cm.options.viewportMargin, doc.first);
	    var to = Math.min(end, update.visible.to + cm.options.viewportMargin);
	    if (display.viewFrom < from && from - display.viewFrom < 20) { from = Math.max(doc.first, display.viewFrom); }
	    if (display.viewTo > to && display.viewTo - to < 20) { to = Math.min(end, display.viewTo); }
	    if (sawCollapsedSpans) {
	      from = visualLineNo(cm.doc, from);
	      to = visualLineEndNo(cm.doc, to);
	    }

	    var different = from != display.viewFrom || to != display.viewTo ||
	      display.lastWrapHeight != update.wrapperHeight || display.lastWrapWidth != update.wrapperWidth;
	    adjustView(cm, from, to);

	    display.viewOffset = heightAtLine(getLine(cm.doc, display.viewFrom));
	    // Position the mover div to align with the current scroll position
	    cm.display.mover.style.top = display.viewOffset + "px";

	    var toUpdate = countDirtyView(cm);
	    if (!different && toUpdate == 0 && !update.force && display.renderedView == display.view &&
	        (display.updateLineNumbers == null || display.updateLineNumbers >= display.viewTo))
	      { return false }

	    // For big changes, we hide the enclosing element during the
	    // update, since that speeds up the operations on most browsers.
	    var selSnapshot = selectionSnapshot(cm);
	    if (toUpdate > 4) { display.lineDiv.style.display = "none"; }
	    patchDisplay(cm, display.updateLineNumbers, update.dims);
	    if (toUpdate > 4) { display.lineDiv.style.display = ""; }
	    display.renderedView = display.view;
	    // There might have been a widget with a focused element that got
	    // hidden or updated, if so re-focus it.
	    restoreSelection(selSnapshot);

	    // Prevent selection and cursors from interfering with the scroll
	    // width and height.
	    removeChildren(display.cursorDiv);
	    removeChildren(display.selectionDiv);
	    display.gutters.style.height = display.sizer.style.minHeight = 0;

	    if (different) {
	      display.lastWrapHeight = update.wrapperHeight;
	      display.lastWrapWidth = update.wrapperWidth;
	      startWorker(cm, 400);
	    }

	    display.updateLineNumbers = null;

	    return true
	  }

	  function postUpdateDisplay(cm, update) {
	    var viewport = update.viewport;

	    for (var first = true;; first = false) {
	      if (!first || !cm.options.lineWrapping || update.oldDisplayWidth == displayWidth(cm)) {
	        // Clip forced viewport to actual scrollable area.
	        if (viewport && viewport.top != null)
	          { viewport = {top: Math.min(cm.doc.height + paddingVert(cm.display) - displayHeight(cm), viewport.top)}; }
	        // Updated line heights might result in the drawn area not
	        // actually covering the viewport. Keep looping until it does.
	        update.visible = visibleLines(cm.display, cm.doc, viewport);
	        if (update.visible.from >= cm.display.viewFrom && update.visible.to <= cm.display.viewTo)
	          { break }
	      }
	      if (!updateDisplayIfNeeded(cm, update)) { break }
	      updateHeightsInViewport(cm);
	      var barMeasure = measureForScrollbars(cm);
	      updateSelection(cm);
	      updateScrollbars(cm, barMeasure);
	      setDocumentHeight(cm, barMeasure);
	      update.force = false;
	    }

	    update.signal(cm, "update", cm);
	    if (cm.display.viewFrom != cm.display.reportedViewFrom || cm.display.viewTo != cm.display.reportedViewTo) {
	      update.signal(cm, "viewportChange", cm, cm.display.viewFrom, cm.display.viewTo);
	      cm.display.reportedViewFrom = cm.display.viewFrom; cm.display.reportedViewTo = cm.display.viewTo;
	    }
	  }

	  function updateDisplaySimple(cm, viewport) {
	    var update = new DisplayUpdate(cm, viewport);
	    if (updateDisplayIfNeeded(cm, update)) {
	      updateHeightsInViewport(cm);
	      postUpdateDisplay(cm, update);
	      var barMeasure = measureForScrollbars(cm);
	      updateSelection(cm);
	      updateScrollbars(cm, barMeasure);
	      setDocumentHeight(cm, barMeasure);
	      update.finish();
	    }
	  }

	  // Sync the actual display DOM structure with display.view, removing
	  // nodes for lines that are no longer in view, and creating the ones
	  // that are not there yet, and updating the ones that are out of
	  // date.
	  function patchDisplay(cm, updateNumbersFrom, dims) {
	    var display = cm.display, lineNumbers = cm.options.lineNumbers;
	    var container = display.lineDiv, cur = container.firstChild;

	    function rm(node) {
	      var next = node.nextSibling;
	      // Works around a throw-scroll bug in OS X Webkit
	      if (webkit && mac && cm.display.currentWheelTarget == node)
	        { node.style.display = "none"; }
	      else
	        { node.parentNode.removeChild(node); }
	      return next
	    }

	    var view = display.view, lineN = display.viewFrom;
	    // Loop over the elements in the view, syncing cur (the DOM nodes
	    // in display.lineDiv) with the view as we go.
	    for (var i = 0; i < view.length; i++) {
	      var lineView = view[i];
	      if (lineView.hidden) ; else if (!lineView.node || lineView.node.parentNode != container) { // Not drawn yet
	        var node = buildLineElement(cm, lineView, lineN, dims);
	        container.insertBefore(node, cur);
	      } else { // Already drawn
	        while (cur != lineView.node) { cur = rm(cur); }
	        var updateNumber = lineNumbers && updateNumbersFrom != null &&
	          updateNumbersFrom <= lineN && lineView.lineNumber;
	        if (lineView.changes) {
	          if (indexOf(lineView.changes, "gutter") > -1) { updateNumber = false; }
	          updateLineForChanges(cm, lineView, lineN, dims);
	        }
	        if (updateNumber) {
	          removeChildren(lineView.lineNumber);
	          lineView.lineNumber.appendChild(document.createTextNode(lineNumberFor(cm.options, lineN)));
	        }
	        cur = lineView.node.nextSibling;
	      }
	      lineN += lineView.size;
	    }
	    while (cur) { cur = rm(cur); }
	  }

	  function updateGutterSpace(display) {
	    var width = display.gutters.offsetWidth;
	    display.sizer.style.marginLeft = width + "px";
	  }

	  function setDocumentHeight(cm, measure) {
	    cm.display.sizer.style.minHeight = measure.docHeight + "px";
	    cm.display.heightForcer.style.top = measure.docHeight + "px";
	    cm.display.gutters.style.height = (measure.docHeight + cm.display.barHeight + scrollGap(cm)) + "px";
	  }

	  // Re-align line numbers and gutter marks to compensate for
	  // horizontal scrolling.
	  function alignHorizontally(cm) {
	    var display = cm.display, view = display.view;
	    if (!display.alignWidgets && (!display.gutters.firstChild || !cm.options.fixedGutter)) { return }
	    var comp = compensateForHScroll(display) - display.scroller.scrollLeft + cm.doc.scrollLeft;
	    var gutterW = display.gutters.offsetWidth, left = comp + "px";
	    for (var i = 0; i < view.length; i++) { if (!view[i].hidden) {
	      if (cm.options.fixedGutter) {
	        if (view[i].gutter)
	          { view[i].gutter.style.left = left; }
	        if (view[i].gutterBackground)
	          { view[i].gutterBackground.style.left = left; }
	      }
	      var align = view[i].alignable;
	      if (align) { for (var j = 0; j < align.length; j++)
	        { align[j].style.left = left; } }
	    } }
	    if (cm.options.fixedGutter)
	      { display.gutters.style.left = (comp + gutterW) + "px"; }
	  }

	  // Used to ensure that the line number gutter is still the right
	  // size for the current document size. Returns true when an update
	  // is needed.
	  function maybeUpdateLineNumberWidth(cm) {
	    if (!cm.options.lineNumbers) { return false }
	    var doc = cm.doc, last = lineNumberFor(cm.options, doc.first + doc.size - 1), display = cm.display;
	    if (last.length != display.lineNumChars) {
	      var test = display.measure.appendChild(elt("div", [elt("div", last)],
	                                                 "CodeMirror-linenumber CodeMirror-gutter-elt"));
	      var innerW = test.firstChild.offsetWidth, padding = test.offsetWidth - innerW;
	      display.lineGutter.style.width = "";
	      display.lineNumInnerWidth = Math.max(innerW, display.lineGutter.offsetWidth - padding) + 1;
	      display.lineNumWidth = display.lineNumInnerWidth + padding;
	      display.lineNumChars = display.lineNumInnerWidth ? last.length : -1;
	      display.lineGutter.style.width = display.lineNumWidth + "px";
	      updateGutterSpace(cm.display);
	      return true
	    }
	    return false
	  }

	  function getGutters(gutters, lineNumbers) {
	    var result = [], sawLineNumbers = false;
	    for (var i = 0; i < gutters.length; i++) {
	      var name = gutters[i], style = null;
	      if (typeof name != "string") { style = name.style; name = name.className; }
	      if (name == "CodeMirror-linenumbers") {
	        if (!lineNumbers) { continue }
	        else { sawLineNumbers = true; }
	      }
	      result.push({className: name, style: style});
	    }
	    if (lineNumbers && !sawLineNumbers) { result.push({className: "CodeMirror-linenumbers", style: null}); }
	    return result
	  }

	  // Rebuild the gutter elements, ensure the margin to the left of the
	  // code matches their width.
	  function renderGutters(display) {
	    var gutters = display.gutters, specs = display.gutterSpecs;
	    removeChildren(gutters);
	    display.lineGutter = null;
	    for (var i = 0; i < specs.length; ++i) {
	      var ref = specs[i];
	      var className = ref.className;
	      var style = ref.style;
	      var gElt = gutters.appendChild(elt("div", null, "CodeMirror-gutter " + className));
	      if (style) { gElt.style.cssText = style; }
	      if (className == "CodeMirror-linenumbers") {
	        display.lineGutter = gElt;
	        gElt.style.width = (display.lineNumWidth || 1) + "px";
	      }
	    }
	    gutters.style.display = specs.length ? "" : "none";
	    updateGutterSpace(display);
	  }

	  function updateGutters(cm) {
	    renderGutters(cm.display);
	    regChange(cm);
	    alignHorizontally(cm);
	  }

	  // The display handles the DOM integration, both for input reading
	  // and content drawing. It holds references to DOM nodes and
	  // display-related state.

	  function Display(place, doc, input, options) {
	    var d = this;
	    this.input = input;

	    // Covers bottom-right square when both scrollbars are present.
	    d.scrollbarFiller = elt("div", null, "CodeMirror-scrollbar-filler");
	    d.scrollbarFiller.setAttribute("cm-not-content", "true");
	    // Covers bottom of gutter when coverGutterNextToScrollbar is on
	    // and h scrollbar is present.
	    d.gutterFiller = elt("div", null, "CodeMirror-gutter-filler");
	    d.gutterFiller.setAttribute("cm-not-content", "true");
	    // Will contain the actual code, positioned to cover the viewport.
	    d.lineDiv = eltP("div", null, "CodeMirror-code");
	    // Elements are added to these to represent selection and cursors.
	    d.selectionDiv = elt("div", null, null, "position: relative; z-index: 1");
	    d.cursorDiv = elt("div", null, "CodeMirror-cursors");
	    // A visibility: hidden element used to find the size of things.
	    d.measure = elt("div", null, "CodeMirror-measure");
	    // When lines outside of the viewport are measured, they are drawn in this.
	    d.lineMeasure = elt("div", null, "CodeMirror-measure");
	    // Wraps everything that needs to exist inside the vertically-padded coordinate system
	    d.lineSpace = eltP("div", [d.measure, d.lineMeasure, d.selectionDiv, d.cursorDiv, d.lineDiv],
	                      null, "position: relative; outline: none");
	    var lines = eltP("div", [d.lineSpace], "CodeMirror-lines");
	    // Moved around its parent to cover visible view.
	    d.mover = elt("div", [lines], null, "position: relative");
	    // Set to the height of the document, allowing scrolling.
	    d.sizer = elt("div", [d.mover], "CodeMirror-sizer");
	    d.sizerWidth = null;
	    // Behavior of elts with overflow: auto and padding is
	    // inconsistent across browsers. This is used to ensure the
	    // scrollable area is big enough.
	    d.heightForcer = elt("div", null, null, "position: absolute; height: " + scrollerGap + "px; width: 1px;");
	    // Will contain the gutters, if any.
	    d.gutters = elt("div", null, "CodeMirror-gutters");
	    d.lineGutter = null;
	    // Actual scrollable element.
	    d.scroller = elt("div", [d.sizer, d.heightForcer, d.gutters], "CodeMirror-scroll");
	    d.scroller.setAttribute("tabIndex", "-1");
	    // The element in which the editor lives.
	    d.wrapper = elt("div", [d.scrollbarFiller, d.gutterFiller, d.scroller], "CodeMirror");

	    // Work around IE7 z-index bug (not perfect, hence IE7 not really being supported)
	    if (ie && ie_version < 8) { d.gutters.style.zIndex = -1; d.scroller.style.paddingRight = 0; }
	    if (!webkit && !(gecko && mobile)) { d.scroller.draggable = true; }

	    if (place) {
	      if (place.appendChild) { place.appendChild(d.wrapper); }
	      else { place(d.wrapper); }
	    }

	    // Current rendered range (may be bigger than the view window).
	    d.viewFrom = d.viewTo = doc.first;
	    d.reportedViewFrom = d.reportedViewTo = doc.first;
	    // Information about the rendered lines.
	    d.view = [];
	    d.renderedView = null;
	    // Holds info about a single rendered line when it was rendered
	    // for measurement, while not in view.
	    d.externalMeasured = null;
	    // Empty space (in pixels) above the view
	    d.viewOffset = 0;
	    d.lastWrapHeight = d.lastWrapWidth = 0;
	    d.updateLineNumbers = null;

	    d.nativeBarWidth = d.barHeight = d.barWidth = 0;
	    d.scrollbarsClipped = false;

	    // Used to only resize the line number gutter when necessary (when
	    // the amount of lines crosses a boundary that makes its width change)
	    d.lineNumWidth = d.lineNumInnerWidth = d.lineNumChars = null;
	    // Set to true when a non-horizontal-scrolling line widget is
	    // added. As an optimization, line widget aligning is skipped when
	    // this is false.
	    d.alignWidgets = false;

	    d.cachedCharWidth = d.cachedTextHeight = d.cachedPaddingH = null;

	    // Tracks the maximum line length so that the horizontal scrollbar
	    // can be kept static when scrolling.
	    d.maxLine = null;
	    d.maxLineLength = 0;
	    d.maxLineChanged = false;

	    // Used for measuring wheel scrolling granularity
	    d.wheelDX = d.wheelDY = d.wheelStartX = d.wheelStartY = null;

	    // True when shift is held down.
	    d.shift = false;

	    // Used to track whether anything happened since the context menu
	    // was opened.
	    d.selForContextMenu = null;

	    d.activeTouch = null;

	    d.gutterSpecs = getGutters(options.gutters, options.lineNumbers);
	    renderGutters(d);

	    input.init(d);
	  }

	  // Since the delta values reported on mouse wheel events are
	  // unstandardized between browsers and even browser versions, and
	  // generally horribly unpredictable, this code starts by measuring
	  // the scroll effect that the first few mouse wheel events have,
	  // and, from that, detects the way it can convert deltas to pixel
	  // offsets afterwards.
	  //
	  // The reason we want to know the amount a wheel event will scroll
	  // is that it gives us a chance to update the display before the
	  // actual scrolling happens, reducing flickering.

	  var wheelSamples = 0, wheelPixelsPerUnit = null;
	  // Fill in a browser-detected starting value on browsers where we
	  // know one. These don't have to be accurate -- the result of them
	  // being wrong would just be a slight flicker on the first wheel
	  // scroll (if it is large enough).
	  if (ie) { wheelPixelsPerUnit = -.53; }
	  else if (gecko) { wheelPixelsPerUnit = 15; }
	  else if (chrome) { wheelPixelsPerUnit = -.7; }
	  else if (safari) { wheelPixelsPerUnit = -1/3; }

	  function wheelEventDelta(e) {
	    var dx = e.wheelDeltaX, dy = e.wheelDeltaY;
	    if (dx == null && e.detail && e.axis == e.HORIZONTAL_AXIS) { dx = e.detail; }
	    if (dy == null && e.detail && e.axis == e.VERTICAL_AXIS) { dy = e.detail; }
	    else if (dy == null) { dy = e.wheelDelta; }
	    return {x: dx, y: dy}
	  }
	  function wheelEventPixels(e) {
	    var delta = wheelEventDelta(e);
	    delta.x *= wheelPixelsPerUnit;
	    delta.y *= wheelPixelsPerUnit;
	    return delta
	  }

	  function onScrollWheel(cm, e) {
	    var delta = wheelEventDelta(e), dx = delta.x, dy = delta.y;

	    var display = cm.display, scroll = display.scroller;
	    // Quit if there's nothing to scroll here
	    var canScrollX = scroll.scrollWidth > scroll.clientWidth;
	    var canScrollY = scroll.scrollHeight > scroll.clientHeight;
	    if (!(dx && canScrollX || dy && canScrollY)) { return }

	    // Webkit browsers on OS X abort momentum scrolls when the target
	    // of the scroll event is removed from the scrollable element.
	    // This hack (see related code in patchDisplay) makes sure the
	    // element is kept around.
	    if (dy && mac && webkit) {
	      outer: for (var cur = e.target, view = display.view; cur != scroll; cur = cur.parentNode) {
	        for (var i = 0; i < view.length; i++) {
	          if (view[i].node == cur) {
	            cm.display.currentWheelTarget = cur;
	            break outer
	          }
	        }
	      }
	    }

	    // On some browsers, horizontal scrolling will cause redraws to
	    // happen before the gutter has been realigned, causing it to
	    // wriggle around in a most unseemly way. When we have an
	    // estimated pixels/delta value, we just handle horizontal
	    // scrolling entirely here. It'll be slightly off from native, but
	    // better than glitching out.
	    if (dx && !gecko && !presto && wheelPixelsPerUnit != null) {
	      if (dy && canScrollY)
	        { updateScrollTop(cm, Math.max(0, scroll.scrollTop + dy * wheelPixelsPerUnit)); }
	      setScrollLeft(cm, Math.max(0, scroll.scrollLeft + dx * wheelPixelsPerUnit));
	      // Only prevent default scrolling if vertical scrolling is
	      // actually possible. Otherwise, it causes vertical scroll
	      // jitter on OSX trackpads when deltaX is small and deltaY
	      // is large (issue #3579)
	      if (!dy || (dy && canScrollY))
	        { e_preventDefault(e); }
	      display.wheelStartX = null; // Abort measurement, if in progress
	      return
	    }

	    // 'Project' the visible viewport to cover the area that is being
	    // scrolled into view (if we know enough to estimate it).
	    if (dy && wheelPixelsPerUnit != null) {
	      var pixels = dy * wheelPixelsPerUnit;
	      var top = cm.doc.scrollTop, bot = top + display.wrapper.clientHeight;
	      if (pixels < 0) { top = Math.max(0, top + pixels - 50); }
	      else { bot = Math.min(cm.doc.height, bot + pixels + 50); }
	      updateDisplaySimple(cm, {top: top, bottom: bot});
	    }

	    if (wheelSamples < 20) {
	      if (display.wheelStartX == null) {
	        display.wheelStartX = scroll.scrollLeft; display.wheelStartY = scroll.scrollTop;
	        display.wheelDX = dx; display.wheelDY = dy;
	        setTimeout(function () {
	          if (display.wheelStartX == null) { return }
	          var movedX = scroll.scrollLeft - display.wheelStartX;
	          var movedY = scroll.scrollTop - display.wheelStartY;
	          var sample = (movedY && display.wheelDY && movedY / display.wheelDY) ||
	            (movedX && display.wheelDX && movedX / display.wheelDX);
	          display.wheelStartX = display.wheelStartY = null;
	          if (!sample) { return }
	          wheelPixelsPerUnit = (wheelPixelsPerUnit * wheelSamples + sample) / (wheelSamples + 1);
	          ++wheelSamples;
	        }, 200);
	      } else {
	        display.wheelDX += dx; display.wheelDY += dy;
	      }
	    }
	  }

	  // Selection objects are immutable. A new one is created every time
	  // the selection changes. A selection is one or more non-overlapping
	  // (and non-touching) ranges, sorted, and an integer that indicates
	  // which one is the primary selection (the one that's scrolled into
	  // view, that getCursor returns, etc).
	  var Selection = function(ranges, primIndex) {
	    this.ranges = ranges;
	    this.primIndex = primIndex;
	  };

	  Selection.prototype.primary = function () { return this.ranges[this.primIndex] };

	  Selection.prototype.equals = function (other) {
	    if (other == this) { return true }
	    if (other.primIndex != this.primIndex || other.ranges.length != this.ranges.length) { return false }
	    for (var i = 0; i < this.ranges.length; i++) {
	      var here = this.ranges[i], there = other.ranges[i];
	      if (!equalCursorPos(here.anchor, there.anchor) || !equalCursorPos(here.head, there.head)) { return false }
	    }
	    return true
	  };

	  Selection.prototype.deepCopy = function () {
	    var out = [];
	    for (var i = 0; i < this.ranges.length; i++)
	      { out[i] = new Range(copyPos(this.ranges[i].anchor), copyPos(this.ranges[i].head)); }
	    return new Selection(out, this.primIndex)
	  };

	  Selection.prototype.somethingSelected = function () {
	    for (var i = 0; i < this.ranges.length; i++)
	      { if (!this.ranges[i].empty()) { return true } }
	    return false
	  };

	  Selection.prototype.contains = function (pos, end) {
	    if (!end) { end = pos; }
	    for (var i = 0; i < this.ranges.length; i++) {
	      var range = this.ranges[i];
	      if (cmp(end, range.from()) >= 0 && cmp(pos, range.to()) <= 0)
	        { return i }
	    }
	    return -1
	  };

	  var Range = function(anchor, head) {
	    this.anchor = anchor; this.head = head;
	  };

	  Range.prototype.from = function () { return minPos(this.anchor, this.head) };
	  Range.prototype.to = function () { return maxPos(this.anchor, this.head) };
	  Range.prototype.empty = function () { return this.head.line == this.anchor.line && this.head.ch == this.anchor.ch };

	  // Take an unsorted, potentially overlapping set of ranges, and
	  // build a selection out of it. 'Consumes' ranges array (modifying
	  // it).
	  function normalizeSelection(cm, ranges, primIndex) {
	    var mayTouch = cm && cm.options.selectionsMayTouch;
	    var prim = ranges[primIndex];
	    ranges.sort(function (a, b) { return cmp(a.from(), b.from()); });
	    primIndex = indexOf(ranges, prim);
	    for (var i = 1; i < ranges.length; i++) {
	      var cur = ranges[i], prev = ranges[i - 1];
	      var diff = cmp(prev.to(), cur.from());
	      if (mayTouch && !cur.empty() ? diff > 0 : diff >= 0) {
	        var from = minPos(prev.from(), cur.from()), to = maxPos(prev.to(), cur.to());
	        var inv = prev.empty() ? cur.from() == cur.head : prev.from() == prev.head;
	        if (i <= primIndex) { --primIndex; }
	        ranges.splice(--i, 2, new Range(inv ? to : from, inv ? from : to));
	      }
	    }
	    return new Selection(ranges, primIndex)
	  }

	  function simpleSelection(anchor, head) {
	    return new Selection([new Range(anchor, head || anchor)], 0)
	  }

	  // Compute the position of the end of a change (its 'to' property
	  // refers to the pre-change end).
	  function changeEnd(change) {
	    if (!change.text) { return change.to }
	    return Pos(change.from.line + change.text.length - 1,
	               lst(change.text).length + (change.text.length == 1 ? change.from.ch : 0))
	  }

	  // Adjust a position to refer to the post-change position of the
	  // same text, or the end of the change if the change covers it.
	  function adjustForChange(pos, change) {
	    if (cmp(pos, change.from) < 0) { return pos }
	    if (cmp(pos, change.to) <= 0) { return changeEnd(change) }

	    var line = pos.line + change.text.length - (change.to.line - change.from.line) - 1, ch = pos.ch;
	    if (pos.line == change.to.line) { ch += changeEnd(change).ch - change.to.ch; }
	    return Pos(line, ch)
	  }

	  function computeSelAfterChange(doc, change) {
	    var out = [];
	    for (var i = 0; i < doc.sel.ranges.length; i++) {
	      var range = doc.sel.ranges[i];
	      out.push(new Range(adjustForChange(range.anchor, change),
	                         adjustForChange(range.head, change)));
	    }
	    return normalizeSelection(doc.cm, out, doc.sel.primIndex)
	  }

	  function offsetPos(pos, old, nw) {
	    if (pos.line == old.line)
	      { return Pos(nw.line, pos.ch - old.ch + nw.ch) }
	    else
	      { return Pos(nw.line + (pos.line - old.line), pos.ch) }
	  }

	  // Used by replaceSelections to allow moving the selection to the
	  // start or around the replaced test. Hint may be "start" or "around".
	  function computeReplacedSel(doc, changes, hint) {
	    var out = [];
	    var oldPrev = Pos(doc.first, 0), newPrev = oldPrev;
	    for (var i = 0; i < changes.length; i++) {
	      var change = changes[i];
	      var from = offsetPos(change.from, oldPrev, newPrev);
	      var to = offsetPos(changeEnd(change), oldPrev, newPrev);
	      oldPrev = change.to;
	      newPrev = to;
	      if (hint == "around") {
	        var range = doc.sel.ranges[i], inv = cmp(range.head, range.anchor) < 0;
	        out[i] = new Range(inv ? to : from, inv ? from : to);
	      } else {
	        out[i] = new Range(from, from);
	      }
	    }
	    return new Selection(out, doc.sel.primIndex)
	  }

	  // Used to get the editor into a consistent state again when options change.

	  function loadMode(cm) {
	    cm.doc.mode = getMode(cm.options, cm.doc.modeOption);
	    resetModeState(cm);
	  }

	  function resetModeState(cm) {
	    cm.doc.iter(function (line) {
	      if (line.stateAfter) { line.stateAfter = null; }
	      if (line.styles) { line.styles = null; }
	    });
	    cm.doc.modeFrontier = cm.doc.highlightFrontier = cm.doc.first;
	    startWorker(cm, 100);
	    cm.state.modeGen++;
	    if (cm.curOp) { regChange(cm); }
	  }

	  // DOCUMENT DATA STRUCTURE

	  // By default, updates that start and end at the beginning of a line
	  // are treated specially, in order to make the association of line
	  // widgets and marker elements with the text behave more intuitive.
	  function isWholeLineUpdate(doc, change) {
	    return change.from.ch == 0 && change.to.ch == 0 && lst(change.text) == "" &&
	      (!doc.cm || doc.cm.options.wholeLineUpdateBefore)
	  }

	  // Perform a change on the document data structure.
	  function updateDoc(doc, change, markedSpans, estimateHeight) {
	    function spansFor(n) {return markedSpans ? markedSpans[n] : null}
	    function update(line, text, spans) {
	      updateLine(line, text, spans, estimateHeight);
	      signalLater(line, "change", line, change);
	    }
	    function linesFor(start, end) {
	      var result = [];
	      for (var i = start; i < end; ++i)
	        { result.push(new Line(text[i], spansFor(i), estimateHeight)); }
	      return result
	    }

	    var from = change.from, to = change.to, text = change.text;
	    var firstLine = getLine(doc, from.line), lastLine = getLine(doc, to.line);
	    var lastText = lst(text), lastSpans = spansFor(text.length - 1), nlines = to.line - from.line;

	    // Adjust the line structure
	    if (change.full) {
	      doc.insert(0, linesFor(0, text.length));
	      doc.remove(text.length, doc.size - text.length);
	    } else if (isWholeLineUpdate(doc, change)) {
	      // This is a whole-line replace. Treated specially to make
	      // sure line objects move the way they are supposed to.
	      var added = linesFor(0, text.length - 1);
	      update(lastLine, lastLine.text, lastSpans);
	      if (nlines) { doc.remove(from.line, nlines); }
	      if (added.length) { doc.insert(from.line, added); }
	    } else if (firstLine == lastLine) {
	      if (text.length == 1) {
	        update(firstLine, firstLine.text.slice(0, from.ch) + lastText + firstLine.text.slice(to.ch), lastSpans);
	      } else {
	        var added$1 = linesFor(1, text.length - 1);
	        added$1.push(new Line(lastText + firstLine.text.slice(to.ch), lastSpans, estimateHeight));
	        update(firstLine, firstLine.text.slice(0, from.ch) + text[0], spansFor(0));
	        doc.insert(from.line + 1, added$1);
	      }
	    } else if (text.length == 1) {
	      update(firstLine, firstLine.text.slice(0, from.ch) + text[0] + lastLine.text.slice(to.ch), spansFor(0));
	      doc.remove(from.line + 1, nlines);
	    } else {
	      update(firstLine, firstLine.text.slice(0, from.ch) + text[0], spansFor(0));
	      update(lastLine, lastText + lastLine.text.slice(to.ch), lastSpans);
	      var added$2 = linesFor(1, text.length - 1);
	      if (nlines > 1) { doc.remove(from.line + 1, nlines - 1); }
	      doc.insert(from.line + 1, added$2);
	    }

	    signalLater(doc, "change", doc, change);
	  }

	  // Call f for all linked documents.
	  function linkedDocs(doc, f, sharedHistOnly) {
	    function propagate(doc, skip, sharedHist) {
	      if (doc.linked) { for (var i = 0; i < doc.linked.length; ++i) {
	        var rel = doc.linked[i];
	        if (rel.doc == skip) { continue }
	        var shared = sharedHist && rel.sharedHist;
	        if (sharedHistOnly && !shared) { continue }
	        f(rel.doc, shared);
	        propagate(rel.doc, doc, shared);
	      } }
	    }
	    propagate(doc, null, true);
	  }

	  // Attach a document to an editor.
	  function attachDoc(cm, doc) {
	    if (doc.cm) { throw new Error("This document is already in use.") }
	    cm.doc = doc;
	    doc.cm = cm;
	    estimateLineHeights(cm);
	    loadMode(cm);
	    setDirectionClass(cm);
	    if (!cm.options.lineWrapping) { findMaxLine(cm); }
	    cm.options.mode = doc.modeOption;
	    regChange(cm);
	  }

	  function setDirectionClass(cm) {
	  (cm.doc.direction == "rtl" ? addClass : rmClass)(cm.display.lineDiv, "CodeMirror-rtl");
	  }

	  function directionChanged(cm) {
	    runInOp(cm, function () {
	      setDirectionClass(cm);
	      regChange(cm);
	    });
	  }

	  function History(startGen) {
	    // Arrays of change events and selections. Doing something adds an
	    // event to done and clears undo. Undoing moves events from done
	    // to undone, redoing moves them in the other direction.
	    this.done = []; this.undone = [];
	    this.undoDepth = Infinity;
	    // Used to track when changes can be merged into a single undo
	    // event
	    this.lastModTime = this.lastSelTime = 0;
	    this.lastOp = this.lastSelOp = null;
	    this.lastOrigin = this.lastSelOrigin = null;
	    // Used by the isClean() method
	    this.generation = this.maxGeneration = startGen || 1;
	  }

	  // Create a history change event from an updateDoc-style change
	  // object.
	  function historyChangeFromChange(doc, change) {
	    var histChange = {from: copyPos(change.from), to: changeEnd(change), text: getBetween(doc, change.from, change.to)};
	    attachLocalSpans(doc, histChange, change.from.line, change.to.line + 1);
	    linkedDocs(doc, function (doc) { return attachLocalSpans(doc, histChange, change.from.line, change.to.line + 1); }, true);
	    return histChange
	  }

	  // Pop all selection events off the end of a history array. Stop at
	  // a change event.
	  function clearSelectionEvents(array) {
	    while (array.length) {
	      var last = lst(array);
	      if (last.ranges) { array.pop(); }
	      else { break }
	    }
	  }

	  // Find the top change event in the history. Pop off selection
	  // events that are in the way.
	  function lastChangeEvent(hist, force) {
	    if (force) {
	      clearSelectionEvents(hist.done);
	      return lst(hist.done)
	    } else if (hist.done.length && !lst(hist.done).ranges) {
	      return lst(hist.done)
	    } else if (hist.done.length > 1 && !hist.done[hist.done.length - 2].ranges) {
	      hist.done.pop();
	      return lst(hist.done)
	    }
	  }

	  // Register a change in the history. Merges changes that are within
	  // a single operation, or are close together with an origin that
	  // allows merging (starting with "+") into a single event.
	  function addChangeToHistory(doc, change, selAfter, opId) {
	    var hist = doc.history;
	    hist.undone.length = 0;
	    var time = +new Date, cur;
	    var last;

	    if ((hist.lastOp == opId ||
	         hist.lastOrigin == change.origin && change.origin &&
	         ((change.origin.charAt(0) == "+" && hist.lastModTime > time - (doc.cm ? doc.cm.options.historyEventDelay : 500)) ||
	          change.origin.charAt(0) == "*")) &&
	        (cur = lastChangeEvent(hist, hist.lastOp == opId))) {
	      // Merge this change into the last event
	      last = lst(cur.changes);
	      if (cmp(change.from, change.to) == 0 && cmp(change.from, last.to) == 0) {
	        // Optimized case for simple insertion -- don't want to add
	        // new changesets for every character typed
	        last.to = changeEnd(change);
	      } else {
	        // Add new sub-event
	        cur.changes.push(historyChangeFromChange(doc, change));
	      }
	    } else {
	      // Can not be merged, start a new event.
	      var before = lst(hist.done);
	      if (!before || !before.ranges)
	        { pushSelectionToHistory(doc.sel, hist.done); }
	      cur = {changes: [historyChangeFromChange(doc, change)],
	             generation: hist.generation};
	      hist.done.push(cur);
	      while (hist.done.length > hist.undoDepth) {
	        hist.done.shift();
	        if (!hist.done[0].ranges) { hist.done.shift(); }
	      }
	    }
	    hist.done.push(selAfter);
	    hist.generation = ++hist.maxGeneration;
	    hist.lastModTime = hist.lastSelTime = time;
	    hist.lastOp = hist.lastSelOp = opId;
	    hist.lastOrigin = hist.lastSelOrigin = change.origin;

	    if (!last) { signal(doc, "historyAdded"); }
	  }

	  function selectionEventCanBeMerged(doc, origin, prev, sel) {
	    var ch = origin.charAt(0);
	    return ch == "*" ||
	      ch == "+" &&
	      prev.ranges.length == sel.ranges.length &&
	      prev.somethingSelected() == sel.somethingSelected() &&
	      new Date - doc.history.lastSelTime <= (doc.cm ? doc.cm.options.historyEventDelay : 500)
	  }

	  // Called whenever the selection changes, sets the new selection as
	  // the pending selection in the history, and pushes the old pending
	  // selection into the 'done' array when it was significantly
	  // different (in number of selected ranges, emptiness, or time).
	  function addSelectionToHistory(doc, sel, opId, options) {
	    var hist = doc.history, origin = options && options.origin;

	    // A new event is started when the previous origin does not match
	    // the current, or the origins don't allow matching. Origins
	    // starting with * are always merged, those starting with + are
	    // merged when similar and close together in time.
	    if (opId == hist.lastSelOp ||
	        (origin && hist.lastSelOrigin == origin &&
	         (hist.lastModTime == hist.lastSelTime && hist.lastOrigin == origin ||
	          selectionEventCanBeMerged(doc, origin, lst(hist.done), sel))))
	      { hist.done[hist.done.length - 1] = sel; }
	    else
	      { pushSelectionToHistory(sel, hist.done); }

	    hist.lastSelTime = +new Date;
	    hist.lastSelOrigin = origin;
	    hist.lastSelOp = opId;
	    if (options && options.clearRedo !== false)
	      { clearSelectionEvents(hist.undone); }
	  }

	  function pushSelectionToHistory(sel, dest) {
	    var top = lst(dest);
	    if (!(top && top.ranges && top.equals(sel)))
	      { dest.push(sel); }
	  }

	  // Used to store marked span information in the history.
	  function attachLocalSpans(doc, change, from, to) {
	    var existing = change["spans_" + doc.id], n = 0;
	    doc.iter(Math.max(doc.first, from), Math.min(doc.first + doc.size, to), function (line) {
	      if (line.markedSpans)
	        { (existing || (existing = change["spans_" + doc.id] = {}))[n] = line.markedSpans; }
	      ++n;
	    });
	  }

	  // When un/re-doing restores text containing marked spans, those
	  // that have been explicitly cleared should not be restored.
	  function removeClearedSpans(spans) {
	    if (!spans) { return null }
	    var out;
	    for (var i = 0; i < spans.length; ++i) {
	      if (spans[i].marker.explicitlyCleared) { if (!out) { out = spans.slice(0, i); } }
	      else if (out) { out.push(spans[i]); }
	    }
	    return !out ? spans : out.length ? out : null
	  }

	  // Retrieve and filter the old marked spans stored in a change event.
	  function getOldSpans(doc, change) {
	    var found = change["spans_" + doc.id];
	    if (!found) { return null }
	    var nw = [];
	    for (var i = 0; i < change.text.length; ++i)
	      { nw.push(removeClearedSpans(found[i])); }
	    return nw
	  }

	  // Used for un/re-doing changes from the history. Combines the
	  // result of computing the existing spans with the set of spans that
	  // existed in the history (so that deleting around a span and then
	  // undoing brings back the span).
	  function mergeOldSpans(doc, change) {
	    var old = getOldSpans(doc, change);
	    var stretched = stretchSpansOverChange(doc, change);
	    if (!old) { return stretched }
	    if (!stretched) { return old }

	    for (var i = 0; i < old.length; ++i) {
	      var oldCur = old[i], stretchCur = stretched[i];
	      if (oldCur && stretchCur) {
	        spans: for (var j = 0; j < stretchCur.length; ++j) {
	          var span = stretchCur[j];
	          for (var k = 0; k < oldCur.length; ++k)
	            { if (oldCur[k].marker == span.marker) { continue spans } }
	          oldCur.push(span);
	        }
	      } else if (stretchCur) {
	        old[i] = stretchCur;
	      }
	    }
	    return old
	  }

	  // Used both to provide a JSON-safe object in .getHistory, and, when
	  // detaching a document, to split the history in two
	  function copyHistoryArray(events, newGroup, instantiateSel) {
	    var copy = [];
	    for (var i = 0; i < events.length; ++i) {
	      var event = events[i];
	      if (event.ranges) {
	        copy.push(instantiateSel ? Selection.prototype.deepCopy.call(event) : event);
	        continue
	      }
	      var changes = event.changes, newChanges = [];
	      copy.push({changes: newChanges});
	      for (var j = 0; j < changes.length; ++j) {
	        var change = changes[j], m = (void 0);
	        newChanges.push({from: change.from, to: change.to, text: change.text});
	        if (newGroup) { for (var prop in change) { if (m = prop.match(/^spans_(\d+)$/)) {
	          if (indexOf(newGroup, Number(m[1])) > -1) {
	            lst(newChanges)[prop] = change[prop];
	            delete change[prop];
	          }
	        } } }
	      }
	    }
	    return copy
	  }

	  // The 'scroll' parameter given to many of these indicated whether
	  // the new cursor position should be scrolled into view after
	  // modifying the selection.

	  // If shift is held or the extend flag is set, extends a range to
	  // include a given position (and optionally a second position).
	  // Otherwise, simply returns the range between the given positions.
	  // Used for cursor motion and such.
	  function extendRange(range, head, other, extend) {
	    if (extend) {
	      var anchor = range.anchor;
	      if (other) {
	        var posBefore = cmp(head, anchor) < 0;
	        if (posBefore != (cmp(other, anchor) < 0)) {
	          anchor = head;
	          head = other;
	        } else if (posBefore != (cmp(head, other) < 0)) {
	          head = other;
	        }
	      }
	      return new Range(anchor, head)
	    } else {
	      return new Range(other || head, head)
	    }
	  }

	  // Extend the primary selection range, discard the rest.
	  function extendSelection(doc, head, other, options, extend) {
	    if (extend == null) { extend = doc.cm && (doc.cm.display.shift || doc.extend); }
	    setSelection(doc, new Selection([extendRange(doc.sel.primary(), head, other, extend)], 0), options);
	  }

	  // Extend all selections (pos is an array of selections with length
	  // equal the number of selections)
	  function extendSelections(doc, heads, options) {
	    var out = [];
	    var extend = doc.cm && (doc.cm.display.shift || doc.extend);
	    for (var i = 0; i < doc.sel.ranges.length; i++)
	      { out[i] = extendRange(doc.sel.ranges[i], heads[i], null, extend); }
	    var newSel = normalizeSelection(doc.cm, out, doc.sel.primIndex);
	    setSelection(doc, newSel, options);
	  }

	  // Updates a single range in the selection.
	  function replaceOneSelection(doc, i, range, options) {
	    var ranges = doc.sel.ranges.slice(0);
	    ranges[i] = range;
	    setSelection(doc, normalizeSelection(doc.cm, ranges, doc.sel.primIndex), options);
	  }

	  // Reset the selection to a single range.
	  function setSimpleSelection(doc, anchor, head, options) {
	    setSelection(doc, simpleSelection(anchor, head), options);
	  }

	  // Give beforeSelectionChange handlers a change to influence a
	  // selection update.
	  function filterSelectionChange(doc, sel, options) {
	    var obj = {
	      ranges: sel.ranges,
	      update: function(ranges) {
	        this.ranges = [];
	        for (var i = 0; i < ranges.length; i++)
	          { this.ranges[i] = new Range(clipPos(doc, ranges[i].anchor),
	                                     clipPos(doc, ranges[i].head)); }
	      },
	      origin: options && options.origin
	    };
	    signal(doc, "beforeSelectionChange", doc, obj);
	    if (doc.cm) { signal(doc.cm, "beforeSelectionChange", doc.cm, obj); }
	    if (obj.ranges != sel.ranges) { return normalizeSelection(doc.cm, obj.ranges, obj.ranges.length - 1) }
	    else { return sel }
	  }

	  function setSelectionReplaceHistory(doc, sel, options) {
	    var done = doc.history.done, last = lst(done);
	    if (last && last.ranges) {
	      done[done.length - 1] = sel;
	      setSelectionNoUndo(doc, sel, options);
	    } else {
	      setSelection(doc, sel, options);
	    }
	  }

	  // Set a new selection.
	  function setSelection(doc, sel, options) {
	    setSelectionNoUndo(doc, sel, options);
	    addSelectionToHistory(doc, doc.sel, doc.cm ? doc.cm.curOp.id : NaN, options);
	  }

	  function setSelectionNoUndo(doc, sel, options) {
	    if (hasHandler(doc, "beforeSelectionChange") || doc.cm && hasHandler(doc.cm, "beforeSelectionChange"))
	      { sel = filterSelectionChange(doc, sel, options); }

	    var bias = options && options.bias ||
	      (cmp(sel.primary().head, doc.sel.primary().head) < 0 ? -1 : 1);
	    setSelectionInner(doc, skipAtomicInSelection(doc, sel, bias, true));

	    if (!(options && options.scroll === false) && doc.cm)
	      { ensureCursorVisible(doc.cm); }
	  }

	  function setSelectionInner(doc, sel) {
	    if (sel.equals(doc.sel)) { return }

	    doc.sel = sel;

	    if (doc.cm) {
	      doc.cm.curOp.updateInput = 1;
	      doc.cm.curOp.selectionChanged = true;
	      signalCursorActivity(doc.cm);
	    }
	    signalLater(doc, "cursorActivity", doc);
	  }

	  // Verify that the selection does not partially select any atomic
	  // marked ranges.
	  function reCheckSelection(doc) {
	    setSelectionInner(doc, skipAtomicInSelection(doc, doc.sel, null, false));
	  }

	  // Return a selection that does not partially select any atomic
	  // ranges.
	  function skipAtomicInSelection(doc, sel, bias, mayClear) {
	    var out;
	    for (var i = 0; i < sel.ranges.length; i++) {
	      var range = sel.ranges[i];
	      var old = sel.ranges.length == doc.sel.ranges.length && doc.sel.ranges[i];
	      var newAnchor = skipAtomic(doc, range.anchor, old && old.anchor, bias, mayClear);
	      var newHead = skipAtomic(doc, range.head, old && old.head, bias, mayClear);
	      if (out || newAnchor != range.anchor || newHead != range.head) {
	        if (!out) { out = sel.ranges.slice(0, i); }
	        out[i] = new Range(newAnchor, newHead);
	      }
	    }
	    return out ? normalizeSelection(doc.cm, out, sel.primIndex) : sel
	  }

	  function skipAtomicInner(doc, pos, oldPos, dir, mayClear) {
	    var line = getLine(doc, pos.line);
	    if (line.markedSpans) { for (var i = 0; i < line.markedSpans.length; ++i) {
	      var sp = line.markedSpans[i], m = sp.marker;

	      // Determine if we should prevent the cursor being placed to the left/right of an atomic marker
	      // Historically this was determined using the inclusiveLeft/Right option, but the new way to control it
	      // is with selectLeft/Right
	      var preventCursorLeft = ("selectLeft" in m) ? !m.selectLeft : m.inclusiveLeft;
	      var preventCursorRight = ("selectRight" in m) ? !m.selectRight : m.inclusiveRight;

	      if ((sp.from == null || (preventCursorLeft ? sp.from <= pos.ch : sp.from < pos.ch)) &&
	          (sp.to == null || (preventCursorRight ? sp.to >= pos.ch : sp.to > pos.ch))) {
	        if (mayClear) {
	          signal(m, "beforeCursorEnter");
	          if (m.explicitlyCleared) {
	            if (!line.markedSpans) { break }
	            else {--i; continue}
	          }
	        }
	        if (!m.atomic) { continue }

	        if (oldPos) {
	          var near = m.find(dir < 0 ? 1 : -1), diff = (void 0);
	          if (dir < 0 ? preventCursorRight : preventCursorLeft)
	            { near = movePos(doc, near, -dir, near && near.line == pos.line ? line : null); }
	          if (near && near.line == pos.line && (diff = cmp(near, oldPos)) && (dir < 0 ? diff < 0 : diff > 0))
	            { return skipAtomicInner(doc, near, pos, dir, mayClear) }
	        }

	        var far = m.find(dir < 0 ? -1 : 1);
	        if (dir < 0 ? preventCursorLeft : preventCursorRight)
	          { far = movePos(doc, far, dir, far.line == pos.line ? line : null); }
	        return far ? skipAtomicInner(doc, far, pos, dir, mayClear) : null
	      }
	    } }
	    return pos
	  }

	  // Ensure a given position is not inside an atomic range.
	  function skipAtomic(doc, pos, oldPos, bias, mayClear) {
	    var dir = bias || 1;
	    var found = skipAtomicInner(doc, pos, oldPos, dir, mayClear) ||
	        (!mayClear && skipAtomicInner(doc, pos, oldPos, dir, true)) ||
	        skipAtomicInner(doc, pos, oldPos, -dir, mayClear) ||
	        (!mayClear && skipAtomicInner(doc, pos, oldPos, -dir, true));
	    if (!found) {
	      doc.cantEdit = true;
	      return Pos(doc.first, 0)
	    }
	    return found
	  }

	  function movePos(doc, pos, dir, line) {
	    if (dir < 0 && pos.ch == 0) {
	      if (pos.line > doc.first) { return clipPos(doc, Pos(pos.line - 1)) }
	      else { return null }
	    } else if (dir > 0 && pos.ch == (line || getLine(doc, pos.line)).text.length) {
	      if (pos.line < doc.first + doc.size - 1) { return Pos(pos.line + 1, 0) }
	      else { return null }
	    } else {
	      return new Pos(pos.line, pos.ch + dir)
	    }
	  }

	  function selectAll(cm) {
	    cm.setSelection(Pos(cm.firstLine(), 0), Pos(cm.lastLine()), sel_dontScroll);
	  }

	  // UPDATING

	  // Allow "beforeChange" event handlers to influence a change
	  function filterChange(doc, change, update) {
	    var obj = {
	      canceled: false,
	      from: change.from,
	      to: change.to,
	      text: change.text,
	      origin: change.origin,
	      cancel: function () { return obj.canceled = true; }
	    };
	    if (update) { obj.update = function (from, to, text, origin) {
	      if (from) { obj.from = clipPos(doc, from); }
	      if (to) { obj.to = clipPos(doc, to); }
	      if (text) { obj.text = text; }
	      if (origin !== undefined) { obj.origin = origin; }
	    }; }
	    signal(doc, "beforeChange", doc, obj);
	    if (doc.cm) { signal(doc.cm, "beforeChange", doc.cm, obj); }

	    if (obj.canceled) {
	      if (doc.cm) { doc.cm.curOp.updateInput = 2; }
	      return null
	    }
	    return {from: obj.from, to: obj.to, text: obj.text, origin: obj.origin}
	  }

	  // Apply a change to a document, and add it to the document's
	  // history, and propagating it to all linked documents.
	  function makeChange(doc, change, ignoreReadOnly) {
	    if (doc.cm) {
	      if (!doc.cm.curOp) { return operation(doc.cm, makeChange)(doc, change, ignoreReadOnly) }
	      if (doc.cm.state.suppressEdits) { return }
	    }

	    if (hasHandler(doc, "beforeChange") || doc.cm && hasHandler(doc.cm, "beforeChange")) {
	      change = filterChange(doc, change, true);
	      if (!change) { return }
	    }

	    // Possibly split or suppress the update based on the presence
	    // of read-only spans in its range.
	    var split = sawReadOnlySpans && !ignoreReadOnly && removeReadOnlyRanges(doc, change.from, change.to);
	    if (split) {
	      for (var i = split.length - 1; i >= 0; --i)
	        { makeChangeInner(doc, {from: split[i].from, to: split[i].to, text: i ? [""] : change.text, origin: change.origin}); }
	    } else {
	      makeChangeInner(doc, change);
	    }
	  }

	  function makeChangeInner(doc, change) {
	    if (change.text.length == 1 && change.text[0] == "" && cmp(change.from, change.to) == 0) { return }
	    var selAfter = computeSelAfterChange(doc, change);
	    addChangeToHistory(doc, change, selAfter, doc.cm ? doc.cm.curOp.id : NaN);

	    makeChangeSingleDoc(doc, change, selAfter, stretchSpansOverChange(doc, change));
	    var rebased = [];

	    linkedDocs(doc, function (doc, sharedHist) {
	      if (!sharedHist && indexOf(rebased, doc.history) == -1) {
	        rebaseHist(doc.history, change);
	        rebased.push(doc.history);
	      }
	      makeChangeSingleDoc(doc, change, null, stretchSpansOverChange(doc, change));
	    });
	  }

	  // Revert a change stored in a document's history.
	  function makeChangeFromHistory(doc, type, allowSelectionOnly) {
	    var suppress = doc.cm && doc.cm.state.suppressEdits;
	    if (suppress && !allowSelectionOnly) { return }

	    var hist = doc.history, event, selAfter = doc.sel;
	    var source = type == "undo" ? hist.done : hist.undone, dest = type == "undo" ? hist.undone : hist.done;

	    // Verify that there is a useable event (so that ctrl-z won't
	    // needlessly clear selection events)
	    var i = 0;
	    for (; i < source.length; i++) {
	      event = source[i];
	      if (allowSelectionOnly ? event.ranges && !event.equals(doc.sel) : !event.ranges)
	        { break }
	    }
	    if (i == source.length) { return }
	    hist.lastOrigin = hist.lastSelOrigin = null;

	    for (;;) {
	      event = source.pop();
	      if (event.ranges) {
	        pushSelectionToHistory(event, dest);
	        if (allowSelectionOnly && !event.equals(doc.sel)) {
	          setSelection(doc, event, {clearRedo: false});
	          return
	        }
	        selAfter = event;
	      } else if (suppress) {
	        source.push(event);
	        return
	      } else { break }
	    }

	    // Build up a reverse change object to add to the opposite history
	    // stack (redo when undoing, and vice versa).
	    var antiChanges = [];
	    pushSelectionToHistory(selAfter, dest);
	    dest.push({changes: antiChanges, generation: hist.generation});
	    hist.generation = event.generation || ++hist.maxGeneration;

	    var filter = hasHandler(doc, "beforeChange") || doc.cm && hasHandler(doc.cm, "beforeChange");

	    var loop = function ( i ) {
	      var change = event.changes[i];
	      change.origin = type;
	      if (filter && !filterChange(doc, change, false)) {
	        source.length = 0;
	        return {}
	      }

	      antiChanges.push(historyChangeFromChange(doc, change));

	      var after = i ? computeSelAfterChange(doc, change) : lst(source);
	      makeChangeSingleDoc(doc, change, after, mergeOldSpans(doc, change));
	      if (!i && doc.cm) { doc.cm.scrollIntoView({from: change.from, to: changeEnd(change)}); }
	      var rebased = [];

	      // Propagate to the linked documents
	      linkedDocs(doc, function (doc, sharedHist) {
	        if (!sharedHist && indexOf(rebased, doc.history) == -1) {
	          rebaseHist(doc.history, change);
	          rebased.push(doc.history);
	        }
	        makeChangeSingleDoc(doc, change, null, mergeOldSpans(doc, change));
	      });
	    };

	    for (var i$1 = event.changes.length - 1; i$1 >= 0; --i$1) {
	      var returned = loop( i$1 );

	      if ( returned ) return returned.v;
	    }
	  }

	  // Sub-views need their line numbers shifted when text is added
	  // above or below them in the parent document.
	  function shiftDoc(doc, distance) {
	    if (distance == 0) { return }
	    doc.first += distance;
	    doc.sel = new Selection(map(doc.sel.ranges, function (range) { return new Range(
	      Pos(range.anchor.line + distance, range.anchor.ch),
	      Pos(range.head.line + distance, range.head.ch)
	    ); }), doc.sel.primIndex);
	    if (doc.cm) {
	      regChange(doc.cm, doc.first, doc.first - distance, distance);
	      for (var d = doc.cm.display, l = d.viewFrom; l < d.viewTo; l++)
	        { regLineChange(doc.cm, l, "gutter"); }
	    }
	  }

	  // More lower-level change function, handling only a single document
	  // (not linked ones).
	  function makeChangeSingleDoc(doc, change, selAfter, spans) {
	    if (doc.cm && !doc.cm.curOp)
	      { return operation(doc.cm, makeChangeSingleDoc)(doc, change, selAfter, spans) }

	    if (change.to.line < doc.first) {
	      shiftDoc(doc, change.text.length - 1 - (change.to.line - change.from.line));
	      return
	    }
	    if (change.from.line > doc.lastLine()) { return }

	    // Clip the change to the size of this doc
	    if (change.from.line < doc.first) {
	      var shift = change.text.length - 1 - (doc.first - change.from.line);
	      shiftDoc(doc, shift);
	      change = {from: Pos(doc.first, 0), to: Pos(change.to.line + shift, change.to.ch),
	                text: [lst(change.text)], origin: change.origin};
	    }
	    var last = doc.lastLine();
	    if (change.to.line > last) {
	      change = {from: change.from, to: Pos(last, getLine(doc, last).text.length),
	                text: [change.text[0]], origin: change.origin};
	    }

	    change.removed = getBetween(doc, change.from, change.to);

	    if (!selAfter) { selAfter = computeSelAfterChange(doc, change); }
	    if (doc.cm) { makeChangeSingleDocInEditor(doc.cm, change, spans); }
	    else { updateDoc(doc, change, spans); }
	    setSelectionNoUndo(doc, selAfter, sel_dontScroll);

	    if (doc.cantEdit && skipAtomic(doc, Pos(doc.firstLine(), 0)))
	      { doc.cantEdit = false; }
	  }

	  // Handle the interaction of a change to a document with the editor
	  // that this document is part of.
	  function makeChangeSingleDocInEditor(cm, change, spans) {
	    var doc = cm.doc, display = cm.display, from = change.from, to = change.to;

	    var recomputeMaxLength = false, checkWidthStart = from.line;
	    if (!cm.options.lineWrapping) {
	      checkWidthStart = lineNo(visualLine(getLine(doc, from.line)));
	      doc.iter(checkWidthStart, to.line + 1, function (line) {
	        if (line == display.maxLine) {
	          recomputeMaxLength = true;
	          return true
	        }
	      });
	    }

	    if (doc.sel.contains(change.from, change.to) > -1)
	      { signalCursorActivity(cm); }

	    updateDoc(doc, change, spans, estimateHeight(cm));

	    if (!cm.options.lineWrapping) {
	      doc.iter(checkWidthStart, from.line + change.text.length, function (line) {
	        var len = lineLength(line);
	        if (len > display.maxLineLength) {
	          display.maxLine = line;
	          display.maxLineLength = len;
	          display.maxLineChanged = true;
	          recomputeMaxLength = false;
	        }
	      });
	      if (recomputeMaxLength) { cm.curOp.updateMaxLine = true; }
	    }

	    retreatFrontier(doc, from.line);
	    startWorker(cm, 400);

	    var lendiff = change.text.length - (to.line - from.line) - 1;
	    // Remember that these lines changed, for updating the display
	    if (change.full)
	      { regChange(cm); }
	    else if (from.line == to.line && change.text.length == 1 && !isWholeLineUpdate(cm.doc, change))
	      { regLineChange(cm, from.line, "text"); }
	    else
	      { regChange(cm, from.line, to.line + 1, lendiff); }

	    var changesHandler = hasHandler(cm, "changes"), changeHandler = hasHandler(cm, "change");
	    if (changeHandler || changesHandler) {
	      var obj = {
	        from: from, to: to,
	        text: change.text,
	        removed: change.removed,
	        origin: change.origin
	      };
	      if (changeHandler) { signalLater(cm, "change", cm, obj); }
	      if (changesHandler) { (cm.curOp.changeObjs || (cm.curOp.changeObjs = [])).push(obj); }
	    }
	    cm.display.selForContextMenu = null;
	  }

	  function replaceRange(doc, code, from, to, origin) {
	    var assign;

	    if (!to) { to = from; }
	    if (cmp(to, from) < 0) { (assign = [to, from], from = assign[0], to = assign[1]); }
	    if (typeof code == "string") { code = doc.splitLines(code); }
	    makeChange(doc, {from: from, to: to, text: code, origin: origin});
	  }

	  // Rebasing/resetting history to deal with externally-sourced changes

	  function rebaseHistSelSingle(pos, from, to, diff) {
	    if (to < pos.line) {
	      pos.line += diff;
	    } else if (from < pos.line) {
	      pos.line = from;
	      pos.ch = 0;
	    }
	  }

	  // Tries to rebase an array of history events given a change in the
	  // document. If the change touches the same lines as the event, the
	  // event, and everything 'behind' it, is discarded. If the change is
	  // before the event, the event's positions are updated. Uses a
	  // copy-on-write scheme for the positions, to avoid having to
	  // reallocate them all on every rebase, but also avoid problems with
	  // shared position objects being unsafely updated.
	  function rebaseHistArray(array, from, to, diff) {
	    for (var i = 0; i < array.length; ++i) {
	      var sub = array[i], ok = true;
	      if (sub.ranges) {
	        if (!sub.copied) { sub = array[i] = sub.deepCopy(); sub.copied = true; }
	        for (var j = 0; j < sub.ranges.length; j++) {
	          rebaseHistSelSingle(sub.ranges[j].anchor, from, to, diff);
	          rebaseHistSelSingle(sub.ranges[j].head, from, to, diff);
	        }
	        continue
	      }
	      for (var j$1 = 0; j$1 < sub.changes.length; ++j$1) {
	        var cur = sub.changes[j$1];
	        if (to < cur.from.line) {
	          cur.from = Pos(cur.from.line + diff, cur.from.ch);
	          cur.to = Pos(cur.to.line + diff, cur.to.ch);
	        } else if (from <= cur.to.line) {
	          ok = false;
	          break
	        }
	      }
	      if (!ok) {
	        array.splice(0, i + 1);
	        i = 0;
	      }
	    }
	  }

	  function rebaseHist(hist, change) {
	    var from = change.from.line, to = change.to.line, diff = change.text.length - (to - from) - 1;
	    rebaseHistArray(hist.done, from, to, diff);
	    rebaseHistArray(hist.undone, from, to, diff);
	  }

	  // Utility for applying a change to a line by handle or number,
	  // returning the number and optionally registering the line as
	  // changed.
	  function changeLine(doc, handle, changeType, op) {
	    var no = handle, line = handle;
	    if (typeof handle == "number") { line = getLine(doc, clipLine(doc, handle)); }
	    else { no = lineNo(handle); }
	    if (no == null) { return null }
	    if (op(line, no) && doc.cm) { regLineChange(doc.cm, no, changeType); }
	    return line
	  }

	  // The document is represented as a BTree consisting of leaves, with
	  // chunk of lines in them, and branches, with up to ten leaves or
	  // other branch nodes below them. The top node is always a branch
	  // node, and is the document object itself (meaning it has
	  // additional methods and properties).
	  //
	  // All nodes have parent links. The tree is used both to go from
	  // line numbers to line objects, and to go from objects to numbers.
	  // It also indexes by height, and is used to convert between height
	  // and line object, and to find the total height of the document.
	  //
	  // See also http://marijnhaverbeke.nl/blog/codemirror-line-tree.html

	  function LeafChunk(lines) {
	    this.lines = lines;
	    this.parent = null;
	    var height = 0;
	    for (var i = 0; i < lines.length; ++i) {
	      lines[i].parent = this;
	      height += lines[i].height;
	    }
	    this.height = height;
	  }

	  LeafChunk.prototype = {
	    chunkSize: function() { return this.lines.length },

	    // Remove the n lines at offset 'at'.
	    removeInner: function(at, n) {
	      for (var i = at, e = at + n; i < e; ++i) {
	        var line = this.lines[i];
	        this.height -= line.height;
	        cleanUpLine(line);
	        signalLater(line, "delete");
	      }
	      this.lines.splice(at, n);
	    },

	    // Helper used to collapse a small branch into a single leaf.
	    collapse: function(lines) {
	      lines.push.apply(lines, this.lines);
	    },

	    // Insert the given array of lines at offset 'at', count them as
	    // having the given height.
	    insertInner: function(at, lines, height) {
	      this.height += height;
	      this.lines = this.lines.slice(0, at).concat(lines).concat(this.lines.slice(at));
	      for (var i = 0; i < lines.length; ++i) { lines[i].parent = this; }
	    },

	    // Used to iterate over a part of the tree.
	    iterN: function(at, n, op) {
	      for (var e = at + n; at < e; ++at)
	        { if (op(this.lines[at])) { return true } }
	    }
	  };

	  function BranchChunk(children) {
	    this.children = children;
	    var size = 0, height = 0;
	    for (var i = 0; i < children.length; ++i) {
	      var ch = children[i];
	      size += ch.chunkSize(); height += ch.height;
	      ch.parent = this;
	    }
	    this.size = size;
	    this.height = height;
	    this.parent = null;
	  }

	  BranchChunk.prototype = {
	    chunkSize: function() { return this.size },

	    removeInner: function(at, n) {
	      this.size -= n;
	      for (var i = 0; i < this.children.length; ++i) {
	        var child = this.children[i], sz = child.chunkSize();
	        if (at < sz) {
	          var rm = Math.min(n, sz - at), oldHeight = child.height;
	          child.removeInner(at, rm);
	          this.height -= oldHeight - child.height;
	          if (sz == rm) { this.children.splice(i--, 1); child.parent = null; }
	          if ((n -= rm) == 0) { break }
	          at = 0;
	        } else { at -= sz; }
	      }
	      // If the result is smaller than 25 lines, ensure that it is a
	      // single leaf node.
	      if (this.size - n < 25 &&
	          (this.children.length > 1 || !(this.children[0] instanceof LeafChunk))) {
	        var lines = [];
	        this.collapse(lines);
	        this.children = [new LeafChunk(lines)];
	        this.children[0].parent = this;
	      }
	    },

	    collapse: function(lines) {
	      for (var i = 0; i < this.children.length; ++i) { this.children[i].collapse(lines); }
	    },

	    insertInner: function(at, lines, height) {
	      this.size += lines.length;
	      this.height += height;
	      for (var i = 0; i < this.children.length; ++i) {
	        var child = this.children[i], sz = child.chunkSize();
	        if (at <= sz) {
	          child.insertInner(at, lines, height);
	          if (child.lines && child.lines.length > 50) {
	            // To avoid memory thrashing when child.lines is huge (e.g. first view of a large file), it's never spliced.
	            // Instead, small slices are taken. They're taken in order because sequential memory accesses are fastest.
	            var remaining = child.lines.length % 25 + 25;
	            for (var pos = remaining; pos < child.lines.length;) {
	              var leaf = new LeafChunk(child.lines.slice(pos, pos += 25));
	              child.height -= leaf.height;
	              this.children.splice(++i, 0, leaf);
	              leaf.parent = this;
	            }
	            child.lines = child.lines.slice(0, remaining);
	            this.maybeSpill();
	          }
	          break
	        }
	        at -= sz;
	      }
	    },

	    // When a node has grown, check whether it should be split.
	    maybeSpill: function() {
	      if (this.children.length <= 10) { return }
	      var me = this;
	      do {
	        var spilled = me.children.splice(me.children.length - 5, 5);
	        var sibling = new BranchChunk(spilled);
	        if (!me.parent) { // Become the parent node
	          var copy = new BranchChunk(me.children);
	          copy.parent = me;
	          me.children = [copy, sibling];
	          me = copy;
	       } else {
	          me.size -= sibling.size;
	          me.height -= sibling.height;
	          var myIndex = indexOf(me.parent.children, me);
	          me.parent.children.splice(myIndex + 1, 0, sibling);
	        }
	        sibling.parent = me.parent;
	      } while (me.children.length > 10)
	      me.parent.maybeSpill();
	    },

	    iterN: function(at, n, op) {
	      for (var i = 0; i < this.children.length; ++i) {
	        var child = this.children[i], sz = child.chunkSize();
	        if (at < sz) {
	          var used = Math.min(n, sz - at);
	          if (child.iterN(at, used, op)) { return true }
	          if ((n -= used) == 0) { break }
	          at = 0;
	        } else { at -= sz; }
	      }
	    }
	  };

	  // Line widgets are block elements displayed above or below a line.

	  var LineWidget = function(doc, node, options) {
	    if (options) { for (var opt in options) { if (options.hasOwnProperty(opt))
	      { this[opt] = options[opt]; } } }
	    this.doc = doc;
	    this.node = node;
	  };

	  LineWidget.prototype.clear = function () {
	    var cm = this.doc.cm, ws = this.line.widgets, line = this.line, no = lineNo(line);
	    if (no == null || !ws) { return }
	    for (var i = 0; i < ws.length; ++i) { if (ws[i] == this) { ws.splice(i--, 1); } }
	    if (!ws.length) { line.widgets = null; }
	    var height = widgetHeight(this);
	    updateLineHeight(line, Math.max(0, line.height - height));
	    if (cm) {
	      runInOp(cm, function () {
	        adjustScrollWhenAboveVisible(cm, line, -height);
	        regLineChange(cm, no, "widget");
	      });
	      signalLater(cm, "lineWidgetCleared", cm, this, no);
	    }
	  };

	  LineWidget.prototype.changed = function () {
	      var this$1 = this;

	    var oldH = this.height, cm = this.doc.cm, line = this.line;
	    this.height = null;
	    var diff = widgetHeight(this) - oldH;
	    if (!diff) { return }
	    if (!lineIsHidden(this.doc, line)) { updateLineHeight(line, line.height + diff); }
	    if (cm) {
	      runInOp(cm, function () {
	        cm.curOp.forceUpdate = true;
	        adjustScrollWhenAboveVisible(cm, line, diff);
	        signalLater(cm, "lineWidgetChanged", cm, this$1, lineNo(line));
	      });
	    }
	  };
	  eventMixin(LineWidget);

	  function adjustScrollWhenAboveVisible(cm, line, diff) {
	    if (heightAtLine(line) < ((cm.curOp && cm.curOp.scrollTop) || cm.doc.scrollTop))
	      { addToScrollTop(cm, diff); }
	  }

	  function addLineWidget(doc, handle, node, options) {
	    var widget = new LineWidget(doc, node, options);
	    var cm = doc.cm;
	    if (cm && widget.noHScroll) { cm.display.alignWidgets = true; }
	    changeLine(doc, handle, "widget", function (line) {
	      var widgets = line.widgets || (line.widgets = []);
	      if (widget.insertAt == null) { widgets.push(widget); }
	      else { widgets.splice(Math.min(widgets.length - 1, Math.max(0, widget.insertAt)), 0, widget); }
	      widget.line = line;
	      if (cm && !lineIsHidden(doc, line)) {
	        var aboveVisible = heightAtLine(line) < doc.scrollTop;
	        updateLineHeight(line, line.height + widgetHeight(widget));
	        if (aboveVisible) { addToScrollTop(cm, widget.height); }
	        cm.curOp.forceUpdate = true;
	      }
	      return true
	    });
	    if (cm) { signalLater(cm, "lineWidgetAdded", cm, widget, typeof handle == "number" ? handle : lineNo(handle)); }
	    return widget
	  }

	  // TEXTMARKERS

	  // Created with markText and setBookmark methods. A TextMarker is a
	  // handle that can be used to clear or find a marked position in the
	  // document. Line objects hold arrays (markedSpans) containing
	  // {from, to, marker} object pointing to such marker objects, and
	  // indicating that such a marker is present on that line. Multiple
	  // lines may point to the same marker when it spans across lines.
	  // The spans will have null for their from/to properties when the
	  // marker continues beyond the start/end of the line. Markers have
	  // links back to the lines they currently touch.

	  // Collapsed markers have unique ids, in order to be able to order
	  // them, which is needed for uniquely determining an outer marker
	  // when they overlap (they may nest, but not partially overlap).
	  var nextMarkerId = 0;

	  var TextMarker = function(doc, type) {
	    this.lines = [];
	    this.type = type;
	    this.doc = doc;
	    this.id = ++nextMarkerId;
	  };

	  // Clear the marker.
	  TextMarker.prototype.clear = function () {
	    if (this.explicitlyCleared) { return }
	    var cm = this.doc.cm, withOp = cm && !cm.curOp;
	    if (withOp) { startOperation(cm); }
	    if (hasHandler(this, "clear")) {
	      var found = this.find();
	      if (found) { signalLater(this, "clear", found.from, found.to); }
	    }
	    var min = null, max = null;
	    for (var i = 0; i < this.lines.length; ++i) {
	      var line = this.lines[i];
	      var span = getMarkedSpanFor(line.markedSpans, this);
	      if (cm && !this.collapsed) { regLineChange(cm, lineNo(line), "text"); }
	      else if (cm) {
	        if (span.to != null) { max = lineNo(line); }
	        if (span.from != null) { min = lineNo(line); }
	      }
	      line.markedSpans = removeMarkedSpan(line.markedSpans, span);
	      if (span.from == null && this.collapsed && !lineIsHidden(this.doc, line) && cm)
	        { updateLineHeight(line, textHeight(cm.display)); }
	    }
	    if (cm && this.collapsed && !cm.options.lineWrapping) { for (var i$1 = 0; i$1 < this.lines.length; ++i$1) {
	      var visual = visualLine(this.lines[i$1]), len = lineLength(visual);
	      if (len > cm.display.maxLineLength) {
	        cm.display.maxLine = visual;
	        cm.display.maxLineLength = len;
	        cm.display.maxLineChanged = true;
	      }
	    } }

	    if (min != null && cm && this.collapsed) { regChange(cm, min, max + 1); }
	    this.lines.length = 0;
	    this.explicitlyCleared = true;
	    if (this.atomic && this.doc.cantEdit) {
	      this.doc.cantEdit = false;
	      if (cm) { reCheckSelection(cm.doc); }
	    }
	    if (cm) { signalLater(cm, "markerCleared", cm, this, min, max); }
	    if (withOp) { endOperation(cm); }
	    if (this.parent) { this.parent.clear(); }
	  };

	  // Find the position of the marker in the document. Returns a {from,
	  // to} object by default. Side can be passed to get a specific side
	  // -- 0 (both), -1 (left), or 1 (right). When lineObj is true, the
	  // Pos objects returned contain a line object, rather than a line
	  // number (used to prevent looking up the same line twice).
	  TextMarker.prototype.find = function (side, lineObj) {
	    if (side == null && this.type == "bookmark") { side = 1; }
	    var from, to;
	    for (var i = 0; i < this.lines.length; ++i) {
	      var line = this.lines[i];
	      var span = getMarkedSpanFor(line.markedSpans, this);
	      if (span.from != null) {
	        from = Pos(lineObj ? line : lineNo(line), span.from);
	        if (side == -1) { return from }
	      }
	      if (span.to != null) {
	        to = Pos(lineObj ? line : lineNo(line), span.to);
	        if (side == 1) { return to }
	      }
	    }
	    return from && {from: from, to: to}
	  };

	  // Signals that the marker's widget changed, and surrounding layout
	  // should be recomputed.
	  TextMarker.prototype.changed = function () {
	      var this$1 = this;

	    var pos = this.find(-1, true), widget = this, cm = this.doc.cm;
	    if (!pos || !cm) { return }
	    runInOp(cm, function () {
	      var line = pos.line, lineN = lineNo(pos.line);
	      var view = findViewForLine(cm, lineN);
	      if (view) {
	        clearLineMeasurementCacheFor(view);
	        cm.curOp.selectionChanged = cm.curOp.forceUpdate = true;
	      }
	      cm.curOp.updateMaxLine = true;
	      if (!lineIsHidden(widget.doc, line) && widget.height != null) {
	        var oldHeight = widget.height;
	        widget.height = null;
	        var dHeight = widgetHeight(widget) - oldHeight;
	        if (dHeight)
	          { updateLineHeight(line, line.height + dHeight); }
	      }
	      signalLater(cm, "markerChanged", cm, this$1);
	    });
	  };

	  TextMarker.prototype.attachLine = function (line) {
	    if (!this.lines.length && this.doc.cm) {
	      var op = this.doc.cm.curOp;
	      if (!op.maybeHiddenMarkers || indexOf(op.maybeHiddenMarkers, this) == -1)
	        { (op.maybeUnhiddenMarkers || (op.maybeUnhiddenMarkers = [])).push(this); }
	    }
	    this.lines.push(line);
	  };

	  TextMarker.prototype.detachLine = function (line) {
	    this.lines.splice(indexOf(this.lines, line), 1);
	    if (!this.lines.length && this.doc.cm) {
	      var op = this.doc.cm.curOp
	      ;(op.maybeHiddenMarkers || (op.maybeHiddenMarkers = [])).push(this);
	    }
	  };
	  eventMixin(TextMarker);

	  // Create a marker, wire it up to the right lines, and
	  function markText(doc, from, to, options, type) {
	    // Shared markers (across linked documents) are handled separately
	    // (markTextShared will call out to this again, once per
	    // document).
	    if (options && options.shared) { return markTextShared(doc, from, to, options, type) }
	    // Ensure we are in an operation.
	    if (doc.cm && !doc.cm.curOp) { return operation(doc.cm, markText)(doc, from, to, options, type) }

	    var marker = new TextMarker(doc, type), diff = cmp(from, to);
	    if (options) { copyObj(options, marker, false); }
	    // Don't connect empty markers unless clearWhenEmpty is false
	    if (diff > 0 || diff == 0 && marker.clearWhenEmpty !== false)
	      { return marker }
	    if (marker.replacedWith) {
	      // Showing up as a widget implies collapsed (widget replaces text)
	      marker.collapsed = true;
	      marker.widgetNode = eltP("span", [marker.replacedWith], "CodeMirror-widget");
	      if (!options.handleMouseEvents) { marker.widgetNode.setAttribute("cm-ignore-events", "true"); }
	      if (options.insertLeft) { marker.widgetNode.insertLeft = true; }
	    }
	    if (marker.collapsed) {
	      if (conflictingCollapsedRange(doc, from.line, from, to, marker) ||
	          from.line != to.line && conflictingCollapsedRange(doc, to.line, from, to, marker))
	        { throw new Error("Inserting collapsed marker partially overlapping an existing one") }
	      seeCollapsedSpans();
	    }

	    if (marker.addToHistory)
	      { addChangeToHistory(doc, {from: from, to: to, origin: "markText"}, doc.sel, NaN); }

	    var curLine = from.line, cm = doc.cm, updateMaxLine;
	    doc.iter(curLine, to.line + 1, function (line) {
	      if (cm && marker.collapsed && !cm.options.lineWrapping && visualLine(line) == cm.display.maxLine)
	        { updateMaxLine = true; }
	      if (marker.collapsed && curLine != from.line) { updateLineHeight(line, 0); }
	      addMarkedSpan(line, new MarkedSpan(marker,
	                                         curLine == from.line ? from.ch : null,
	                                         curLine == to.line ? to.ch : null));
	      ++curLine;
	    });
	    // lineIsHidden depends on the presence of the spans, so needs a second pass
	    if (marker.collapsed) { doc.iter(from.line, to.line + 1, function (line) {
	      if (lineIsHidden(doc, line)) { updateLineHeight(line, 0); }
	    }); }

	    if (marker.clearOnEnter) { on(marker, "beforeCursorEnter", function () { return marker.clear(); }); }

	    if (marker.readOnly) {
	      seeReadOnlySpans();
	      if (doc.history.done.length || doc.history.undone.length)
	        { doc.clearHistory(); }
	    }
	    if (marker.collapsed) {
	      marker.id = ++nextMarkerId;
	      marker.atomic = true;
	    }
	    if (cm) {
	      // Sync editor state
	      if (updateMaxLine) { cm.curOp.updateMaxLine = true; }
	      if (marker.collapsed)
	        { regChange(cm, from.line, to.line + 1); }
	      else if (marker.className || marker.startStyle || marker.endStyle || marker.css ||
	               marker.attributes || marker.title)
	        { for (var i = from.line; i <= to.line; i++) { regLineChange(cm, i, "text"); } }
	      if (marker.atomic) { reCheckSelection(cm.doc); }
	      signalLater(cm, "markerAdded", cm, marker);
	    }
	    return marker
	  }

	  // SHARED TEXTMARKERS

	  // A shared marker spans multiple linked documents. It is
	  // implemented as a meta-marker-object controlling multiple normal
	  // markers.
	  var SharedTextMarker = function(markers, primary) {
	    this.markers = markers;
	    this.primary = primary;
	    for (var i = 0; i < markers.length; ++i)
	      { markers[i].parent = this; }
	  };

	  SharedTextMarker.prototype.clear = function () {
	    if (this.explicitlyCleared) { return }
	    this.explicitlyCleared = true;
	    for (var i = 0; i < this.markers.length; ++i)
	      { this.markers[i].clear(); }
	    signalLater(this, "clear");
	  };

	  SharedTextMarker.prototype.find = function (side, lineObj) {
	    return this.primary.find(side, lineObj)
	  };
	  eventMixin(SharedTextMarker);

	  function markTextShared(doc, from, to, options, type) {
	    options = copyObj(options);
	    options.shared = false;
	    var markers = [markText(doc, from, to, options, type)], primary = markers[0];
	    var widget = options.widgetNode;
	    linkedDocs(doc, function (doc) {
	      if (widget) { options.widgetNode = widget.cloneNode(true); }
	      markers.push(markText(doc, clipPos(doc, from), clipPos(doc, to), options, type));
	      for (var i = 0; i < doc.linked.length; ++i)
	        { if (doc.linked[i].isParent) { return } }
	      primary = lst(markers);
	    });
	    return new SharedTextMarker(markers, primary)
	  }

	  function findSharedMarkers(doc) {
	    return doc.findMarks(Pos(doc.first, 0), doc.clipPos(Pos(doc.lastLine())), function (m) { return m.parent; })
	  }

	  function copySharedMarkers(doc, markers) {
	    for (var i = 0; i < markers.length; i++) {
	      var marker = markers[i], pos = marker.find();
	      var mFrom = doc.clipPos(pos.from), mTo = doc.clipPos(pos.to);
	      if (cmp(mFrom, mTo)) {
	        var subMark = markText(doc, mFrom, mTo, marker.primary, marker.primary.type);
	        marker.markers.push(subMark);
	        subMark.parent = marker;
	      }
	    }
	  }

	  function detachSharedMarkers(markers) {
	    var loop = function ( i ) {
	      var marker = markers[i], linked = [marker.primary.doc];
	      linkedDocs(marker.primary.doc, function (d) { return linked.push(d); });
	      for (var j = 0; j < marker.markers.length; j++) {
	        var subMarker = marker.markers[j];
	        if (indexOf(linked, subMarker.doc) == -1) {
	          subMarker.parent = null;
	          marker.markers.splice(j--, 1);
	        }
	      }
	    };

	    for (var i = 0; i < markers.length; i++) loop( i );
	  }

	  var nextDocId = 0;
	  var Doc = function(text, mode, firstLine, lineSep, direction) {
	    if (!(this instanceof Doc)) { return new Doc(text, mode, firstLine, lineSep, direction) }
	    if (firstLine == null) { firstLine = 0; }

	    BranchChunk.call(this, [new LeafChunk([new Line("", null)])]);
	    this.first = firstLine;
	    this.scrollTop = this.scrollLeft = 0;
	    this.cantEdit = false;
	    this.cleanGeneration = 1;
	    this.modeFrontier = this.highlightFrontier = firstLine;
	    var start = Pos(firstLine, 0);
	    this.sel = simpleSelection(start);
	    this.history = new History(null);
	    this.id = ++nextDocId;
	    this.modeOption = mode;
	    this.lineSep = lineSep;
	    this.direction = (direction == "rtl") ? "rtl" : "ltr";
	    this.extend = false;

	    if (typeof text == "string") { text = this.splitLines(text); }
	    updateDoc(this, {from: start, to: start, text: text});
	    setSelection(this, simpleSelection(start), sel_dontScroll);
	  };

	  Doc.prototype = createObj(BranchChunk.prototype, {
	    constructor: Doc,
	    // Iterate over the document. Supports two forms -- with only one
	    // argument, it calls that for each line in the document. With
	    // three, it iterates over the range given by the first two (with
	    // the second being non-inclusive).
	    iter: function(from, to, op) {
	      if (op) { this.iterN(from - this.first, to - from, op); }
	      else { this.iterN(this.first, this.first + this.size, from); }
	    },

	    // Non-public interface for adding and removing lines.
	    insert: function(at, lines) {
	      var height = 0;
	      for (var i = 0; i < lines.length; ++i) { height += lines[i].height; }
	      this.insertInner(at - this.first, lines, height);
	    },
	    remove: function(at, n) { this.removeInner(at - this.first, n); },

	    // From here, the methods are part of the public interface. Most
	    // are also available from CodeMirror (editor) instances.

	    getValue: function(lineSep) {
	      var lines = getLines(this, this.first, this.first + this.size);
	      if (lineSep === false) { return lines }
	      return lines.join(lineSep || this.lineSeparator())
	    },
	    setValue: docMethodOp(function(code) {
	      var top = Pos(this.first, 0), last = this.first + this.size - 1;
	      makeChange(this, {from: top, to: Pos(last, getLine(this, last).text.length),
	                        text: this.splitLines(code), origin: "setValue", full: true}, true);
	      if (this.cm) { scrollToCoords(this.cm, 0, 0); }
	      setSelection(this, simpleSelection(top), sel_dontScroll);
	    }),
	    replaceRange: function(code, from, to, origin) {
	      from = clipPos(this, from);
	      to = to ? clipPos(this, to) : from;
	      replaceRange(this, code, from, to, origin);
	    },
	    getRange: function(from, to, lineSep) {
	      var lines = getBetween(this, clipPos(this, from), clipPos(this, to));
	      if (lineSep === false) { return lines }
	      return lines.join(lineSep || this.lineSeparator())
	    },

	    getLine: function(line) {var l = this.getLineHandle(line); return l && l.text},

	    getLineHandle: function(line) {if (isLine(this, line)) { return getLine(this, line) }},
	    getLineNumber: function(line) {return lineNo(line)},

	    getLineHandleVisualStart: function(line) {
	      if (typeof line == "number") { line = getLine(this, line); }
	      return visualLine(line)
	    },

	    lineCount: function() {return this.size},
	    firstLine: function() {return this.first},
	    lastLine: function() {return this.first + this.size - 1},

	    clipPos: function(pos) {return clipPos(this, pos)},

	    getCursor: function(start) {
	      var range = this.sel.primary(), pos;
	      if (start == null || start == "head") { pos = range.head; }
	      else if (start == "anchor") { pos = range.anchor; }
	      else if (start == "end" || start == "to" || start === false) { pos = range.to(); }
	      else { pos = range.from(); }
	      return pos
	    },
	    listSelections: function() { return this.sel.ranges },
	    somethingSelected: function() {return this.sel.somethingSelected()},

	    setCursor: docMethodOp(function(line, ch, options) {
	      setSimpleSelection(this, clipPos(this, typeof line == "number" ? Pos(line, ch || 0) : line), null, options);
	    }),
	    setSelection: docMethodOp(function(anchor, head, options) {
	      setSimpleSelection(this, clipPos(this, anchor), clipPos(this, head || anchor), options);
	    }),
	    extendSelection: docMethodOp(function(head, other, options) {
	      extendSelection(this, clipPos(this, head), other && clipPos(this, other), options);
	    }),
	    extendSelections: docMethodOp(function(heads, options) {
	      extendSelections(this, clipPosArray(this, heads), options);
	    }),
	    extendSelectionsBy: docMethodOp(function(f, options) {
	      var heads = map(this.sel.ranges, f);
	      extendSelections(this, clipPosArray(this, heads), options);
	    }),
	    setSelections: docMethodOp(function(ranges, primary, options) {
	      if (!ranges.length) { return }
	      var out = [];
	      for (var i = 0; i < ranges.length; i++)
	        { out[i] = new Range(clipPos(this, ranges[i].anchor),
	                           clipPos(this, ranges[i].head)); }
	      if (primary == null) { primary = Math.min(ranges.length - 1, this.sel.primIndex); }
	      setSelection(this, normalizeSelection(this.cm, out, primary), options);
	    }),
	    addSelection: docMethodOp(function(anchor, head, options) {
	      var ranges = this.sel.ranges.slice(0);
	      ranges.push(new Range(clipPos(this, anchor), clipPos(this, head || anchor)));
	      setSelection(this, normalizeSelection(this.cm, ranges, ranges.length - 1), options);
	    }),

	    getSelection: function(lineSep) {
	      var ranges = this.sel.ranges, lines;
	      for (var i = 0; i < ranges.length; i++) {
	        var sel = getBetween(this, ranges[i].from(), ranges[i].to());
	        lines = lines ? lines.concat(sel) : sel;
	      }
	      if (lineSep === false) { return lines }
	      else { return lines.join(lineSep || this.lineSeparator()) }
	    },
	    getSelections: function(lineSep) {
	      var parts = [], ranges = this.sel.ranges;
	      for (var i = 0; i < ranges.length; i++) {
	        var sel = getBetween(this, ranges[i].from(), ranges[i].to());
	        if (lineSep !== false) { sel = sel.join(lineSep || this.lineSeparator()); }
	        parts[i] = sel;
	      }
	      return parts
	    },
	    replaceSelection: function(code, collapse, origin) {
	      var dup = [];
	      for (var i = 0; i < this.sel.ranges.length; i++)
	        { dup[i] = code; }
	      this.replaceSelections(dup, collapse, origin || "+input");
	    },
	    replaceSelections: docMethodOp(function(code, collapse, origin) {
	      var changes = [], sel = this.sel;
	      for (var i = 0; i < sel.ranges.length; i++) {
	        var range = sel.ranges[i];
	        changes[i] = {from: range.from(), to: range.to(), text: this.splitLines(code[i]), origin: origin};
	      }
	      var newSel = collapse && collapse != "end" && computeReplacedSel(this, changes, collapse);
	      for (var i$1 = changes.length - 1; i$1 >= 0; i$1--)
	        { makeChange(this, changes[i$1]); }
	      if (newSel) { setSelectionReplaceHistory(this, newSel); }
	      else if (this.cm) { ensureCursorVisible(this.cm); }
	    }),
	    undo: docMethodOp(function() {makeChangeFromHistory(this, "undo");}),
	    redo: docMethodOp(function() {makeChangeFromHistory(this, "redo");}),
	    undoSelection: docMethodOp(function() {makeChangeFromHistory(this, "undo", true);}),
	    redoSelection: docMethodOp(function() {makeChangeFromHistory(this, "redo", true);}),

	    setExtending: function(val) {this.extend = val;},
	    getExtending: function() {return this.extend},

	    historySize: function() {
	      var hist = this.history, done = 0, undone = 0;
	      for (var i = 0; i < hist.done.length; i++) { if (!hist.done[i].ranges) { ++done; } }
	      for (var i$1 = 0; i$1 < hist.undone.length; i$1++) { if (!hist.undone[i$1].ranges) { ++undone; } }
	      return {undo: done, redo: undone}
	    },
	    clearHistory: function() {
	      var this$1 = this;

	      this.history = new History(this.history.maxGeneration);
	      linkedDocs(this, function (doc) { return doc.history = this$1.history; }, true);
	    },

	    markClean: function() {
	      this.cleanGeneration = this.changeGeneration(true);
	    },
	    changeGeneration: function(forceSplit) {
	      if (forceSplit)
	        { this.history.lastOp = this.history.lastSelOp = this.history.lastOrigin = null; }
	      return this.history.generation
	    },
	    isClean: function (gen) {
	      return this.history.generation == (gen || this.cleanGeneration)
	    },

	    getHistory: function() {
	      return {done: copyHistoryArray(this.history.done),
	              undone: copyHistoryArray(this.history.undone)}
	    },
	    setHistory: function(histData) {
	      var hist = this.history = new History(this.history.maxGeneration);
	      hist.done = copyHistoryArray(histData.done.slice(0), null, true);
	      hist.undone = copyHistoryArray(histData.undone.slice(0), null, true);
	    },

	    setGutterMarker: docMethodOp(function(line, gutterID, value) {
	      return changeLine(this, line, "gutter", function (line) {
	        var markers = line.gutterMarkers || (line.gutterMarkers = {});
	        markers[gutterID] = value;
	        if (!value && isEmpty(markers)) { line.gutterMarkers = null; }
	        return true
	      })
	    }),

	    clearGutter: docMethodOp(function(gutterID) {
	      var this$1 = this;

	      this.iter(function (line) {
	        if (line.gutterMarkers && line.gutterMarkers[gutterID]) {
	          changeLine(this$1, line, "gutter", function () {
	            line.gutterMarkers[gutterID] = null;
	            if (isEmpty(line.gutterMarkers)) { line.gutterMarkers = null; }
	            return true
	          });
	        }
	      });
	    }),

	    lineInfo: function(line) {
	      var n;
	      if (typeof line == "number") {
	        if (!isLine(this, line)) { return null }
	        n = line;
	        line = getLine(this, line);
	        if (!line) { return null }
	      } else {
	        n = lineNo(line);
	        if (n == null) { return null }
	      }
	      return {line: n, handle: line, text: line.text, gutterMarkers: line.gutterMarkers,
	              textClass: line.textClass, bgClass: line.bgClass, wrapClass: line.wrapClass,
	              widgets: line.widgets}
	    },

	    addLineClass: docMethodOp(function(handle, where, cls) {
	      return changeLine(this, handle, where == "gutter" ? "gutter" : "class", function (line) {
	        var prop = where == "text" ? "textClass"
	                 : where == "background" ? "bgClass"
	                 : where == "gutter" ? "gutterClass" : "wrapClass";
	        if (!line[prop]) { line[prop] = cls; }
	        else if (classTest(cls).test(line[prop])) { return false }
	        else { line[prop] += " " + cls; }
	        return true
	      })
	    }),
	    removeLineClass: docMethodOp(function(handle, where, cls) {
	      return changeLine(this, handle, where == "gutter" ? "gutter" : "class", function (line) {
	        var prop = where == "text" ? "textClass"
	                 : where == "background" ? "bgClass"
	                 : where == "gutter" ? "gutterClass" : "wrapClass";
	        var cur = line[prop];
	        if (!cur) { return false }
	        else if (cls == null) { line[prop] = null; }
	        else {
	          var found = cur.match(classTest(cls));
	          if (!found) { return false }
	          var end = found.index + found[0].length;
	          line[prop] = cur.slice(0, found.index) + (!found.index || end == cur.length ? "" : " ") + cur.slice(end) || null;
	        }
	        return true
	      })
	    }),

	    addLineWidget: docMethodOp(function(handle, node, options) {
	      return addLineWidget(this, handle, node, options)
	    }),
	    removeLineWidget: function(widget) { widget.clear(); },

	    markText: function(from, to, options) {
	      return markText(this, clipPos(this, from), clipPos(this, to), options, options && options.type || "range")
	    },
	    setBookmark: function(pos, options) {
	      var realOpts = {replacedWith: options && (options.nodeType == null ? options.widget : options),
	                      insertLeft: options && options.insertLeft,
	                      clearWhenEmpty: false, shared: options && options.shared,
	                      handleMouseEvents: options && options.handleMouseEvents};
	      pos = clipPos(this, pos);
	      return markText(this, pos, pos, realOpts, "bookmark")
	    },
	    findMarksAt: function(pos) {
	      pos = clipPos(this, pos);
	      var markers = [], spans = getLine(this, pos.line).markedSpans;
	      if (spans) { for (var i = 0; i < spans.length; ++i) {
	        var span = spans[i];
	        if ((span.from == null || span.from <= pos.ch) &&
	            (span.to == null || span.to >= pos.ch))
	          { markers.push(span.marker.parent || span.marker); }
	      } }
	      return markers
	    },
	    findMarks: function(from, to, filter) {
	      from = clipPos(this, from); to = clipPos(this, to);
	      var found = [], lineNo = from.line;
	      this.iter(from.line, to.line + 1, function (line) {
	        var spans = line.markedSpans;
	        if (spans) { for (var i = 0; i < spans.length; i++) {
	          var span = spans[i];
	          if (!(span.to != null && lineNo == from.line && from.ch >= span.to ||
	                span.from == null && lineNo != from.line ||
	                span.from != null && lineNo == to.line && span.from >= to.ch) &&
	              (!filter || filter(span.marker)))
	            { found.push(span.marker.parent || span.marker); }
	        } }
	        ++lineNo;
	      });
	      return found
	    },
	    getAllMarks: function() {
	      var markers = [];
	      this.iter(function (line) {
	        var sps = line.markedSpans;
	        if (sps) { for (var i = 0; i < sps.length; ++i)
	          { if (sps[i].from != null) { markers.push(sps[i].marker); } } }
	      });
	      return markers
	    },

	    posFromIndex: function(off) {
	      var ch, lineNo = this.first, sepSize = this.lineSeparator().length;
	      this.iter(function (line) {
	        var sz = line.text.length + sepSize;
	        if (sz > off) { ch = off; return true }
	        off -= sz;
	        ++lineNo;
	      });
	      return clipPos(this, Pos(lineNo, ch))
	    },
	    indexFromPos: function (coords) {
	      coords = clipPos(this, coords);
	      var index = coords.ch;
	      if (coords.line < this.first || coords.ch < 0) { return 0 }
	      var sepSize = this.lineSeparator().length;
	      this.iter(this.first, coords.line, function (line) { // iter aborts when callback returns a truthy value
	        index += line.text.length + sepSize;
	      });
	      return index
	    },

	    copy: function(copyHistory) {
	      var doc = new Doc(getLines(this, this.first, this.first + this.size),
	                        this.modeOption, this.first, this.lineSep, this.direction);
	      doc.scrollTop = this.scrollTop; doc.scrollLeft = this.scrollLeft;
	      doc.sel = this.sel;
	      doc.extend = false;
	      if (copyHistory) {
	        doc.history.undoDepth = this.history.undoDepth;
	        doc.setHistory(this.getHistory());
	      }
	      return doc
	    },

	    linkedDoc: function(options) {
	      if (!options) { options = {}; }
	      var from = this.first, to = this.first + this.size;
	      if (options.from != null && options.from > from) { from = options.from; }
	      if (options.to != null && options.to < to) { to = options.to; }
	      var copy = new Doc(getLines(this, from, to), options.mode || this.modeOption, from, this.lineSep, this.direction);
	      if (options.sharedHist) { copy.history = this.history
	      ; }(this.linked || (this.linked = [])).push({doc: copy, sharedHist: options.sharedHist});
	      copy.linked = [{doc: this, isParent: true, sharedHist: options.sharedHist}];
	      copySharedMarkers(copy, findSharedMarkers(this));
	      return copy
	    },
	    unlinkDoc: function(other) {
	      if (other instanceof CodeMirror) { other = other.doc; }
	      if (this.linked) { for (var i = 0; i < this.linked.length; ++i) {
	        var link = this.linked[i];
	        if (link.doc != other) { continue }
	        this.linked.splice(i, 1);
	        other.unlinkDoc(this);
	        detachSharedMarkers(findSharedMarkers(this));
	        break
	      } }
	      // If the histories were shared, split them again
	      if (other.history == this.history) {
	        var splitIds = [other.id];
	        linkedDocs(other, function (doc) { return splitIds.push(doc.id); }, true);
	        other.history = new History(null);
	        other.history.done = copyHistoryArray(this.history.done, splitIds);
	        other.history.undone = copyHistoryArray(this.history.undone, splitIds);
	      }
	    },
	    iterLinkedDocs: function(f) {linkedDocs(this, f);},

	    getMode: function() {return this.mode},
	    getEditor: function() {return this.cm},

	    splitLines: function(str) {
	      if (this.lineSep) { return str.split(this.lineSep) }
	      return splitLinesAuto(str)
	    },
	    lineSeparator: function() { return this.lineSep || "\n" },

	    setDirection: docMethodOp(function (dir) {
	      if (dir != "rtl") { dir = "ltr"; }
	      if (dir == this.direction) { return }
	      this.direction = dir;
	      this.iter(function (line) { return line.order = null; });
	      if (this.cm) { directionChanged(this.cm); }
	    })
	  });

	  // Public alias.
	  Doc.prototype.eachLine = Doc.prototype.iter;

	  // Kludge to work around strange IE behavior where it'll sometimes
	  // re-fire a series of drag-related events right after the drop (#1551)
	  var lastDrop = 0;

	  function onDrop(e) {
	    var cm = this;
	    clearDragCursor(cm);
	    if (signalDOMEvent(cm, e) || eventInWidget(cm.display, e))
	      { return }
	    e_preventDefault(e);
	    if (ie) { lastDrop = +new Date; }
	    var pos = posFromMouse(cm, e, true), files = e.dataTransfer.files;
	    if (!pos || cm.isReadOnly()) { return }
	    // Might be a file drop, in which case we simply extract the text
	    // and insert it.
	    if (files && files.length && window.FileReader && window.File) {
	      var n = files.length, text = Array(n), read = 0;
	      var markAsReadAndPasteIfAllFilesAreRead = function () {
	        if (++read == n) {
	          operation(cm, function () {
	            pos = clipPos(cm.doc, pos);
	            var change = {from: pos, to: pos,
	                          text: cm.doc.splitLines(
	                              text.filter(function (t) { return t != null; }).join(cm.doc.lineSeparator())),
	                          origin: "paste"};
	            makeChange(cm.doc, change);
	            setSelectionReplaceHistory(cm.doc, simpleSelection(clipPos(cm.doc, pos), clipPos(cm.doc, changeEnd(change))));
	          })();
	        }
	      };
	      var readTextFromFile = function (file, i) {
	        if (cm.options.allowDropFileTypes &&
	            indexOf(cm.options.allowDropFileTypes, file.type) == -1) {
	          markAsReadAndPasteIfAllFilesAreRead();
	          return
	        }
	        var reader = new FileReader;
	        reader.onerror = function () { return markAsReadAndPasteIfAllFilesAreRead(); };
	        reader.onload = function () {
	          var content = reader.result;
	          if (/[\x00-\x08\x0e-\x1f]{2}/.test(content)) {
	            markAsReadAndPasteIfAllFilesAreRead();
	            return
	          }
	          text[i] = content;
	          markAsReadAndPasteIfAllFilesAreRead();
	        };
	        reader.readAsText(file);
	      };
	      for (var i = 0; i < files.length; i++) { readTextFromFile(files[i], i); }
	    } else { // Normal drop
	      // Don't do a replace if the drop happened inside of the selected text.
	      if (cm.state.draggingText && cm.doc.sel.contains(pos) > -1) {
	        cm.state.draggingText(e);
	        // Ensure the editor is re-focused
	        setTimeout(function () { return cm.display.input.focus(); }, 20);
	        return
	      }
	      try {
	        var text$1 = e.dataTransfer.getData("Text");
	        if (text$1) {
	          var selected;
	          if (cm.state.draggingText && !cm.state.draggingText.copy)
	            { selected = cm.listSelections(); }
	          setSelectionNoUndo(cm.doc, simpleSelection(pos, pos));
	          if (selected) { for (var i$1 = 0; i$1 < selected.length; ++i$1)
	            { replaceRange(cm.doc, "", selected[i$1].anchor, selected[i$1].head, "drag"); } }
	          cm.replaceSelection(text$1, "around", "paste");
	          cm.display.input.focus();
	        }
	      }
	      catch(e){}
	    }
	  }

	  function onDragStart(cm, e) {
	    if (ie && (!cm.state.draggingText || +new Date - lastDrop < 100)) { e_stop(e); return }
	    if (signalDOMEvent(cm, e) || eventInWidget(cm.display, e)) { return }

	    e.dataTransfer.setData("Text", cm.getSelection());
	    e.dataTransfer.effectAllowed = "copyMove";

	    // Use dummy image instead of default browsers image.
	    // Recent Safari (~6.0.2) have a tendency to segfault when this happens, so we don't do it there.
	    if (e.dataTransfer.setDragImage && !safari) {
	      var img = elt("img", null, null, "position: fixed; left: 0; top: 0;");
	      img.src = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
	      if (presto) {
	        img.width = img.height = 1;
	        cm.display.wrapper.appendChild(img);
	        // Force a relayout, or Opera won't use our image for some obscure reason
	        img._top = img.offsetTop;
	      }
	      e.dataTransfer.setDragImage(img, 0, 0);
	      if (presto) { img.parentNode.removeChild(img); }
	    }
	  }

	  function onDragOver(cm, e) {
	    var pos = posFromMouse(cm, e);
	    if (!pos) { return }
	    var frag = document.createDocumentFragment();
	    drawSelectionCursor(cm, pos, frag);
	    if (!cm.display.dragCursor) {
	      cm.display.dragCursor = elt("div", null, "CodeMirror-cursors CodeMirror-dragcursors");
	      cm.display.lineSpace.insertBefore(cm.display.dragCursor, cm.display.cursorDiv);
	    }
	    removeChildrenAndAdd(cm.display.dragCursor, frag);
	  }

	  function clearDragCursor(cm) {
	    if (cm.display.dragCursor) {
	      cm.display.lineSpace.removeChild(cm.display.dragCursor);
	      cm.display.dragCursor = null;
	    }
	  }

	  // These must be handled carefully, because naively registering a
	  // handler for each editor will cause the editors to never be
	  // garbage collected.

	  function forEachCodeMirror(f) {
	    if (!document.getElementsByClassName) { return }
	    var byClass = document.getElementsByClassName("CodeMirror"), editors = [];
	    for (var i = 0; i < byClass.length; i++) {
	      var cm = byClass[i].CodeMirror;
	      if (cm) { editors.push(cm); }
	    }
	    if (editors.length) { editors[0].operation(function () {
	      for (var i = 0; i < editors.length; i++) { f(editors[i]); }
	    }); }
	  }

	  var globalsRegistered = false;
	  function ensureGlobalHandlers() {
	    if (globalsRegistered) { return }
	    registerGlobalHandlers();
	    globalsRegistered = true;
	  }
	  function registerGlobalHandlers() {
	    // When the window resizes, we need to refresh active editors.
	    var resizeTimer;
	    on(window, "resize", function () {
	      if (resizeTimer == null) { resizeTimer = setTimeout(function () {
	        resizeTimer = null;
	        forEachCodeMirror(onResize);
	      }, 100); }
	    });
	    // When the window loses focus, we want to show the editor as blurred
	    on(window, "blur", function () { return forEachCodeMirror(onBlur); });
	  }
	  // Called when the window resizes
	  function onResize(cm) {
	    var d = cm.display;
	    // Might be a text scaling operation, clear size caches.
	    d.cachedCharWidth = d.cachedTextHeight = d.cachedPaddingH = null;
	    d.scrollbarsClipped = false;
	    cm.setSize();
	  }

	  var keyNames = {
	    3: "Pause", 8: "Backspace", 9: "Tab", 13: "Enter", 16: "Shift", 17: "Ctrl", 18: "Alt",
	    19: "Pause", 20: "CapsLock", 27: "Esc", 32: "Space", 33: "PageUp", 34: "PageDown", 35: "End",
	    36: "Home", 37: "Left", 38: "Up", 39: "Right", 40: "Down", 44: "PrintScrn", 45: "Insert",
	    46: "Delete", 59: ";", 61: "=", 91: "Mod", 92: "Mod", 93: "Mod",
	    106: "*", 107: "=", 109: "-", 110: ".", 111: "/", 145: "ScrollLock",
	    173: "-", 186: ";", 187: "=", 188: ",", 189: "-", 190: ".", 191: "/", 192: "`", 219: "[", 220: "\\",
	    221: "]", 222: "'", 63232: "Up", 63233: "Down", 63234: "Left", 63235: "Right", 63272: "Delete",
	    63273: "Home", 63275: "End", 63276: "PageUp", 63277: "PageDown", 63302: "Insert"
	  };

	  // Number keys
	  for (var i = 0; i < 10; i++) { keyNames[i + 48] = keyNames[i + 96] = String(i); }
	  // Alphabetic keys
	  for (var i$1 = 65; i$1 <= 90; i$1++) { keyNames[i$1] = String.fromCharCode(i$1); }
	  // Function keys
	  for (var i$2 = 1; i$2 <= 12; i$2++) { keyNames[i$2 + 111] = keyNames[i$2 + 63235] = "F" + i$2; }

	  var keyMap = {};

	  keyMap.basic = {
	    "Left": "goCharLeft", "Right": "goCharRight", "Up": "goLineUp", "Down": "goLineDown",
	    "End": "goLineEnd", "Home": "goLineStartSmart", "PageUp": "goPageUp", "PageDown": "goPageDown",
	    "Delete": "delCharAfter", "Backspace": "delCharBefore", "Shift-Backspace": "delCharBefore",
	    "Tab": "defaultTab", "Shift-Tab": "indentAuto",
	    "Enter": "newlineAndIndent", "Insert": "toggleOverwrite",
	    "Esc": "singleSelection"
	  };
	  // Note that the save and find-related commands aren't defined by
	  // default. User code or addons can define them. Unknown commands
	  // are simply ignored.
	  keyMap.pcDefault = {
	    "Ctrl-A": "selectAll", "Ctrl-D": "deleteLine", "Ctrl-Z": "undo", "Shift-Ctrl-Z": "redo", "Ctrl-Y": "redo",
	    "Ctrl-Home": "goDocStart", "Ctrl-End": "goDocEnd", "Ctrl-Up": "goLineUp", "Ctrl-Down": "goLineDown",
	    "Ctrl-Left": "goGroupLeft", "Ctrl-Right": "goGroupRight", "Alt-Left": "goLineStart", "Alt-Right": "goLineEnd",
	    "Ctrl-Backspace": "delGroupBefore", "Ctrl-Delete": "delGroupAfter", "Ctrl-S": "save", "Ctrl-F": "find",
	    "Ctrl-G": "findNext", "Shift-Ctrl-G": "findPrev", "Shift-Ctrl-F": "replace", "Shift-Ctrl-R": "replaceAll",
	    "Ctrl-[": "indentLess", "Ctrl-]": "indentMore",
	    "Ctrl-U": "undoSelection", "Shift-Ctrl-U": "redoSelection", "Alt-U": "redoSelection",
	    "fallthrough": "basic"
	  };
	  // Very basic readline/emacs-style bindings, which are standard on Mac.
	  keyMap.emacsy = {
	    "Ctrl-F": "goCharRight", "Ctrl-B": "goCharLeft", "Ctrl-P": "goLineUp", "Ctrl-N": "goLineDown",
	    "Alt-F": "goWordRight", "Alt-B": "goWordLeft", "Ctrl-A": "goLineStart", "Ctrl-E": "goLineEnd",
	    "Ctrl-V": "goPageDown", "Shift-Ctrl-V": "goPageUp", "Ctrl-D": "delCharAfter", "Ctrl-H": "delCharBefore",
	    "Alt-D": "delWordAfter", "Alt-Backspace": "delWordBefore", "Ctrl-K": "killLine", "Ctrl-T": "transposeChars",
	    "Ctrl-O": "openLine"
	  };
	  keyMap.macDefault = {
	    "Cmd-A": "selectAll", "Cmd-D": "deleteLine", "Cmd-Z": "undo", "Shift-Cmd-Z": "redo", "Cmd-Y": "redo",
	    "Cmd-Home": "goDocStart", "Cmd-Up": "goDocStart", "Cmd-End": "goDocEnd", "Cmd-Down": "goDocEnd", "Alt-Left": "goGroupLeft",
	    "Alt-Right": "goGroupRight", "Cmd-Left": "goLineLeft", "Cmd-Right": "goLineRight", "Alt-Backspace": "delGroupBefore",
	    "Ctrl-Alt-Backspace": "delGroupAfter", "Alt-Delete": "delGroupAfter", "Cmd-S": "save", "Cmd-F": "find",
	    "Cmd-G": "findNext", "Shift-Cmd-G": "findPrev", "Cmd-Alt-F": "replace", "Shift-Cmd-Alt-F": "replaceAll",
	    "Cmd-[": "indentLess", "Cmd-]": "indentMore", "Cmd-Backspace": "delWrappedLineLeft", "Cmd-Delete": "delWrappedLineRight",
	    "Cmd-U": "undoSelection", "Shift-Cmd-U": "redoSelection", "Ctrl-Up": "goDocStart", "Ctrl-Down": "goDocEnd",
	    "fallthrough": ["basic", "emacsy"]
	  };
	  keyMap["default"] = mac ? keyMap.macDefault : keyMap.pcDefault;

	  // KEYMAP DISPATCH

	  function normalizeKeyName(name) {
	    var parts = name.split(/-(?!$)/);
	    name = parts[parts.length - 1];
	    var alt, ctrl, shift, cmd;
	    for (var i = 0; i < parts.length - 1; i++) {
	      var mod = parts[i];
	      if (/^(cmd|meta|m)$/i.test(mod)) { cmd = true; }
	      else if (/^a(lt)?$/i.test(mod)) { alt = true; }
	      else if (/^(c|ctrl|control)$/i.test(mod)) { ctrl = true; }
	      else if (/^s(hift)?$/i.test(mod)) { shift = true; }
	      else { throw new Error("Unrecognized modifier name: " + mod) }
	    }
	    if (alt) { name = "Alt-" + name; }
	    if (ctrl) { name = "Ctrl-" + name; }
	    if (cmd) { name = "Cmd-" + name; }
	    if (shift) { name = "Shift-" + name; }
	    return name
	  }

	  // This is a kludge to keep keymaps mostly working as raw objects
	  // (backwards compatibility) while at the same time support features
	  // like normalization and multi-stroke key bindings. It compiles a
	  // new normalized keymap, and then updates the old object to reflect
	  // this.
	  function normalizeKeyMap(keymap) {
	    var copy = {};
	    for (var keyname in keymap) { if (keymap.hasOwnProperty(keyname)) {
	      var value = keymap[keyname];
	      if (/^(name|fallthrough|(de|at)tach)$/.test(keyname)) { continue }
	      if (value == "...") { delete keymap[keyname]; continue }

	      var keys = map(keyname.split(" "), normalizeKeyName);
	      for (var i = 0; i < keys.length; i++) {
	        var val = (void 0), name = (void 0);
	        if (i == keys.length - 1) {
	          name = keys.join(" ");
	          val = value;
	        } else {
	          name = keys.slice(0, i + 1).join(" ");
	          val = "...";
	        }
	        var prev = copy[name];
	        if (!prev) { copy[name] = val; }
	        else if (prev != val) { throw new Error("Inconsistent bindings for " + name) }
	      }
	      delete keymap[keyname];
	    } }
	    for (var prop in copy) { keymap[prop] = copy[prop]; }
	    return keymap
	  }

	  function lookupKey(key, map, handle, context) {
	    map = getKeyMap(map);
	    var found = map.call ? map.call(key, context) : map[key];
	    if (found === false) { return "nothing" }
	    if (found === "...") { return "multi" }
	    if (found != null && handle(found)) { return "handled" }

	    if (map.fallthrough) {
	      if (Object.prototype.toString.call(map.fallthrough) != "[object Array]")
	        { return lookupKey(key, map.fallthrough, handle, context) }
	      for (var i = 0; i < map.fallthrough.length; i++) {
	        var result = lookupKey(key, map.fallthrough[i], handle, context);
	        if (result) { return result }
	      }
	    }
	  }

	  // Modifier key presses don't count as 'real' key presses for the
	  // purpose of keymap fallthrough.
	  function isModifierKey(value) {
	    var name = typeof value == "string" ? value : keyNames[value.keyCode];
	    return name == "Ctrl" || name == "Alt" || name == "Shift" || name == "Mod"
	  }

	  function addModifierNames(name, event, noShift) {
	    var base = name;
	    if (event.altKey && base != "Alt") { name = "Alt-" + name; }
	    if ((flipCtrlCmd ? event.metaKey : event.ctrlKey) && base != "Ctrl") { name = "Ctrl-" + name; }
	    if ((flipCtrlCmd ? event.ctrlKey : event.metaKey) && base != "Cmd") { name = "Cmd-" + name; }
	    if (!noShift && event.shiftKey && base != "Shift") { name = "Shift-" + name; }
	    return name
	  }

	  // Look up the name of a key as indicated by an event object.
	  function keyName(event, noShift) {
	    if (presto && event.keyCode == 34 && event["char"]) { return false }
	    var name = keyNames[event.keyCode];
	    if (name == null || event.altGraphKey) { return false }
	    // Ctrl-ScrollLock has keyCode 3, same as Ctrl-Pause,
	    // so we'll use event.code when available (Chrome 48+, FF 38+, Safari 10.1+)
	    if (event.keyCode == 3 && event.code) { name = event.code; }
	    return addModifierNames(name, event, noShift)
	  }

	  function getKeyMap(val) {
	    return typeof val == "string" ? keyMap[val] : val
	  }

	  // Helper for deleting text near the selection(s), used to implement
	  // backspace, delete, and similar functionality.
	  function deleteNearSelection(cm, compute) {
	    var ranges = cm.doc.sel.ranges, kill = [];
	    // Build up a set of ranges to kill first, merging overlapping
	    // ranges.
	    for (var i = 0; i < ranges.length; i++) {
	      var toKill = compute(ranges[i]);
	      while (kill.length && cmp(toKill.from, lst(kill).to) <= 0) {
	        var replaced = kill.pop();
	        if (cmp(replaced.from, toKill.from) < 0) {
	          toKill.from = replaced.from;
	          break
	        }
	      }
	      kill.push(toKill);
	    }
	    // Next, remove those actual ranges.
	    runInOp(cm, function () {
	      for (var i = kill.length - 1; i >= 0; i--)
	        { replaceRange(cm.doc, "", kill[i].from, kill[i].to, "+delete"); }
	      ensureCursorVisible(cm);
	    });
	  }

	  function moveCharLogically(line, ch, dir) {
	    var target = skipExtendingChars(line.text, ch + dir, dir);
	    return target < 0 || target > line.text.length ? null : target
	  }

	  function moveLogically(line, start, dir) {
	    var ch = moveCharLogically(line, start.ch, dir);
	    return ch == null ? null : new Pos(start.line, ch, dir < 0 ? "after" : "before")
	  }

	  function endOfLine(visually, cm, lineObj, lineNo, dir) {
	    if (visually) {
	      if (cm.doc.direction == "rtl") { dir = -dir; }
	      var order = getOrder(lineObj, cm.doc.direction);
	      if (order) {
	        var part = dir < 0 ? lst(order) : order[0];
	        var moveInStorageOrder = (dir < 0) == (part.level == 1);
	        var sticky = moveInStorageOrder ? "after" : "before";
	        var ch;
	        // With a wrapped rtl chunk (possibly spanning multiple bidi parts),
	        // it could be that the last bidi part is not on the last visual line,
	        // since visual lines contain content order-consecutive chunks.
	        // Thus, in rtl, we are looking for the first (content-order) character
	        // in the rtl chunk that is on the last line (that is, the same line
	        // as the last (content-order) character).
	        if (part.level > 0 || cm.doc.direction == "rtl") {
	          var prep = prepareMeasureForLine(cm, lineObj);
	          ch = dir < 0 ? lineObj.text.length - 1 : 0;
	          var targetTop = measureCharPrepared(cm, prep, ch).top;
	          ch = findFirst(function (ch) { return measureCharPrepared(cm, prep, ch).top == targetTop; }, (dir < 0) == (part.level == 1) ? part.from : part.to - 1, ch);
	          if (sticky == "before") { ch = moveCharLogically(lineObj, ch, 1); }
	        } else { ch = dir < 0 ? part.to : part.from; }
	        return new Pos(lineNo, ch, sticky)
	      }
	    }
	    return new Pos(lineNo, dir < 0 ? lineObj.text.length : 0, dir < 0 ? "before" : "after")
	  }

	  function moveVisually(cm, line, start, dir) {
	    var bidi = getOrder(line, cm.doc.direction);
	    if (!bidi) { return moveLogically(line, start, dir) }
	    if (start.ch >= line.text.length) {
	      start.ch = line.text.length;
	      start.sticky = "before";
	    } else if (start.ch <= 0) {
	      start.ch = 0;
	      start.sticky = "after";
	    }
	    var partPos = getBidiPartAt(bidi, start.ch, start.sticky), part = bidi[partPos];
	    if (cm.doc.direction == "ltr" && part.level % 2 == 0 && (dir > 0 ? part.to > start.ch : part.from < start.ch)) {
	      // Case 1: We move within an ltr part in an ltr editor. Even with wrapped lines,
	      // nothing interesting happens.
	      return moveLogically(line, start, dir)
	    }

	    var mv = function (pos, dir) { return moveCharLogically(line, pos instanceof Pos ? pos.ch : pos, dir); };
	    var prep;
	    var getWrappedLineExtent = function (ch) {
	      if (!cm.options.lineWrapping) { return {begin: 0, end: line.text.length} }
	      prep = prep || prepareMeasureForLine(cm, line);
	      return wrappedLineExtentChar(cm, line, prep, ch)
	    };
	    var wrappedLineExtent = getWrappedLineExtent(start.sticky == "before" ? mv(start, -1) : start.ch);

	    if (cm.doc.direction == "rtl" || part.level == 1) {
	      var moveInStorageOrder = (part.level == 1) == (dir < 0);
	      var ch = mv(start, moveInStorageOrder ? 1 : -1);
	      if (ch != null && (!moveInStorageOrder ? ch >= part.from && ch >= wrappedLineExtent.begin : ch <= part.to && ch <= wrappedLineExtent.end)) {
	        // Case 2: We move within an rtl part or in an rtl editor on the same visual line
	        var sticky = moveInStorageOrder ? "before" : "after";
	        return new Pos(start.line, ch, sticky)
	      }
	    }

	    // Case 3: Could not move within this bidi part in this visual line, so leave
	    // the current bidi part

	    var searchInVisualLine = function (partPos, dir, wrappedLineExtent) {
	      var getRes = function (ch, moveInStorageOrder) { return moveInStorageOrder
	        ? new Pos(start.line, mv(ch, 1), "before")
	        : new Pos(start.line, ch, "after"); };

	      for (; partPos >= 0 && partPos < bidi.length; partPos += dir) {
	        var part = bidi[partPos];
	        var moveInStorageOrder = (dir > 0) == (part.level != 1);
	        var ch = moveInStorageOrder ? wrappedLineExtent.begin : mv(wrappedLineExtent.end, -1);
	        if (part.from <= ch && ch < part.to) { return getRes(ch, moveInStorageOrder) }
	        ch = moveInStorageOrder ? part.from : mv(part.to, -1);
	        if (wrappedLineExtent.begin <= ch && ch < wrappedLineExtent.end) { return getRes(ch, moveInStorageOrder) }
	      }
	    };

	    // Case 3a: Look for other bidi parts on the same visual line
	    var res = searchInVisualLine(partPos + dir, dir, wrappedLineExtent);
	    if (res) { return res }

	    // Case 3b: Look for other bidi parts on the next visual line
	    var nextCh = dir > 0 ? wrappedLineExtent.end : mv(wrappedLineExtent.begin, -1);
	    if (nextCh != null && !(dir > 0 && nextCh == line.text.length)) {
	      res = searchInVisualLine(dir > 0 ? 0 : bidi.length - 1, dir, getWrappedLineExtent(nextCh));
	      if (res) { return res }
	    }

	    // Case 4: Nowhere to move
	    return null
	  }

	  // Commands are parameter-less actions that can be performed on an
	  // editor, mostly used for keybindings.
	  var commands = {
	    selectAll: selectAll,
	    singleSelection: function (cm) { return cm.setSelection(cm.getCursor("anchor"), cm.getCursor("head"), sel_dontScroll); },
	    killLine: function (cm) { return deleteNearSelection(cm, function (range) {
	      if (range.empty()) {
	        var len = getLine(cm.doc, range.head.line).text.length;
	        if (range.head.ch == len && range.head.line < cm.lastLine())
	          { return {from: range.head, to: Pos(range.head.line + 1, 0)} }
	        else
	          { return {from: range.head, to: Pos(range.head.line, len)} }
	      } else {
	        return {from: range.from(), to: range.to()}
	      }
	    }); },
	    deleteLine: function (cm) { return deleteNearSelection(cm, function (range) { return ({
	      from: Pos(range.from().line, 0),
	      to: clipPos(cm.doc, Pos(range.to().line + 1, 0))
	    }); }); },
	    delLineLeft: function (cm) { return deleteNearSelection(cm, function (range) { return ({
	      from: Pos(range.from().line, 0), to: range.from()
	    }); }); },
	    delWrappedLineLeft: function (cm) { return deleteNearSelection(cm, function (range) {
	      var top = cm.charCoords(range.head, "div").top + 5;
	      var leftPos = cm.coordsChar({left: 0, top: top}, "div");
	      return {from: leftPos, to: range.from()}
	    }); },
	    delWrappedLineRight: function (cm) { return deleteNearSelection(cm, function (range) {
	      var top = cm.charCoords(range.head, "div").top + 5;
	      var rightPos = cm.coordsChar({left: cm.display.lineDiv.offsetWidth + 100, top: top}, "div");
	      return {from: range.from(), to: rightPos }
	    }); },
	    undo: function (cm) { return cm.undo(); },
	    redo: function (cm) { return cm.redo(); },
	    undoSelection: function (cm) { return cm.undoSelection(); },
	    redoSelection: function (cm) { return cm.redoSelection(); },
	    goDocStart: function (cm) { return cm.extendSelection(Pos(cm.firstLine(), 0)); },
	    goDocEnd: function (cm) { return cm.extendSelection(Pos(cm.lastLine())); },
	    goLineStart: function (cm) { return cm.extendSelectionsBy(function (range) { return lineStart(cm, range.head.line); },
	      {origin: "+move", bias: 1}
	    ); },
	    goLineStartSmart: function (cm) { return cm.extendSelectionsBy(function (range) { return lineStartSmart(cm, range.head); },
	      {origin: "+move", bias: 1}
	    ); },
	    goLineEnd: function (cm) { return cm.extendSelectionsBy(function (range) { return lineEnd(cm, range.head.line); },
	      {origin: "+move", bias: -1}
	    ); },
	    goLineRight: function (cm) { return cm.extendSelectionsBy(function (range) {
	      var top = cm.cursorCoords(range.head, "div").top + 5;
	      return cm.coordsChar({left: cm.display.lineDiv.offsetWidth + 100, top: top}, "div")
	    }, sel_move); },
	    goLineLeft: function (cm) { return cm.extendSelectionsBy(function (range) {
	      var top = cm.cursorCoords(range.head, "div").top + 5;
	      return cm.coordsChar({left: 0, top: top}, "div")
	    }, sel_move); },
	    goLineLeftSmart: function (cm) { return cm.extendSelectionsBy(function (range) {
	      var top = cm.cursorCoords(range.head, "div").top + 5;
	      var pos = cm.coordsChar({left: 0, top: top}, "div");
	      if (pos.ch < cm.getLine(pos.line).search(/\S/)) { return lineStartSmart(cm, range.head) }
	      return pos
	    }, sel_move); },
	    goLineUp: function (cm) { return cm.moveV(-1, "line"); },
	    goLineDown: function (cm) { return cm.moveV(1, "line"); },
	    goPageUp: function (cm) { return cm.moveV(-1, "page"); },
	    goPageDown: function (cm) { return cm.moveV(1, "page"); },
	    goCharLeft: function (cm) { return cm.moveH(-1, "char"); },
	    goCharRight: function (cm) { return cm.moveH(1, "char"); },
	    goColumnLeft: function (cm) { return cm.moveH(-1, "column"); },
	    goColumnRight: function (cm) { return cm.moveH(1, "column"); },
	    goWordLeft: function (cm) { return cm.moveH(-1, "word"); },
	    goGroupRight: function (cm) { return cm.moveH(1, "group"); },
	    goGroupLeft: function (cm) { return cm.moveH(-1, "group"); },
	    goWordRight: function (cm) { return cm.moveH(1, "word"); },
	    delCharBefore: function (cm) { return cm.deleteH(-1, "char"); },
	    delCharAfter: function (cm) { return cm.deleteH(1, "char"); },
	    delWordBefore: function (cm) { return cm.deleteH(-1, "word"); },
	    delWordAfter: function (cm) { return cm.deleteH(1, "word"); },
	    delGroupBefore: function (cm) { return cm.deleteH(-1, "group"); },
	    delGroupAfter: function (cm) { return cm.deleteH(1, "group"); },
	    indentAuto: function (cm) { return cm.indentSelection("smart"); },
	    indentMore: function (cm) { return cm.indentSelection("add"); },
	    indentLess: function (cm) { return cm.indentSelection("subtract"); },
	    insertTab: function (cm) { return cm.replaceSelection("\t"); },
	    insertSoftTab: function (cm) {
	      var spaces = [], ranges = cm.listSelections(), tabSize = cm.options.tabSize;
	      for (var i = 0; i < ranges.length; i++) {
	        var pos = ranges[i].from();
	        var col = countColumn(cm.getLine(pos.line), pos.ch, tabSize);
	        spaces.push(spaceStr(tabSize - col % tabSize));
	      }
	      cm.replaceSelections(spaces);
	    },
	    defaultTab: function (cm) {
	      if (cm.somethingSelected()) { cm.indentSelection("add"); }
	      else { cm.execCommand("insertTab"); }
	    },
	    // Swap the two chars left and right of each selection's head.
	    // Move cursor behind the two swapped characters afterwards.
	    //
	    // Doesn't consider line feeds a character.
	    // Doesn't scan more than one line above to find a character.
	    // Doesn't do anything on an empty line.
	    // Doesn't do anything with non-empty selections.
	    transposeChars: function (cm) { return runInOp(cm, function () {
	      var ranges = cm.listSelections(), newSel = [];
	      for (var i = 0; i < ranges.length; i++) {
	        if (!ranges[i].empty()) { continue }
	        var cur = ranges[i].head, line = getLine(cm.doc, cur.line).text;
	        if (line) {
	          if (cur.ch == line.length) { cur = new Pos(cur.line, cur.ch - 1); }
	          if (cur.ch > 0) {
	            cur = new Pos(cur.line, cur.ch + 1);
	            cm.replaceRange(line.charAt(cur.ch - 1) + line.charAt(cur.ch - 2),
	                            Pos(cur.line, cur.ch - 2), cur, "+transpose");
	          } else if (cur.line > cm.doc.first) {
	            var prev = getLine(cm.doc, cur.line - 1).text;
	            if (prev) {
	              cur = new Pos(cur.line, 1);
	              cm.replaceRange(line.charAt(0) + cm.doc.lineSeparator() +
	                              prev.charAt(prev.length - 1),
	                              Pos(cur.line - 1, prev.length - 1), cur, "+transpose");
	            }
	          }
	        }
	        newSel.push(new Range(cur, cur));
	      }
	      cm.setSelections(newSel);
	    }); },
	    newlineAndIndent: function (cm) { return runInOp(cm, function () {
	      var sels = cm.listSelections();
	      for (var i = sels.length - 1; i >= 0; i--)
	        { cm.replaceRange(cm.doc.lineSeparator(), sels[i].anchor, sels[i].head, "+input"); }
	      sels = cm.listSelections();
	      for (var i$1 = 0; i$1 < sels.length; i$1++)
	        { cm.indentLine(sels[i$1].from().line, null, true); }
	      ensureCursorVisible(cm);
	    }); },
	    openLine: function (cm) { return cm.replaceSelection("\n", "start"); },
	    toggleOverwrite: function (cm) { return cm.toggleOverwrite(); }
	  };


	  function lineStart(cm, lineN) {
	    var line = getLine(cm.doc, lineN);
	    var visual = visualLine(line);
	    if (visual != line) { lineN = lineNo(visual); }
	    return endOfLine(true, cm, visual, lineN, 1)
	  }
	  function lineEnd(cm, lineN) {
	    var line = getLine(cm.doc, lineN);
	    var visual = visualLineEnd(line);
	    if (visual != line) { lineN = lineNo(visual); }
	    return endOfLine(true, cm, line, lineN, -1)
	  }
	  function lineStartSmart(cm, pos) {
	    var start = lineStart(cm, pos.line);
	    var line = getLine(cm.doc, start.line);
	    var order = getOrder(line, cm.doc.direction);
	    if (!order || order[0].level == 0) {
	      var firstNonWS = Math.max(start.ch, line.text.search(/\S/));
	      var inWS = pos.line == start.line && pos.ch <= firstNonWS && pos.ch;
	      return Pos(start.line, inWS ? 0 : firstNonWS, start.sticky)
	    }
	    return start
	  }

	  // Run a handler that was bound to a key.
	  function doHandleBinding(cm, bound, dropShift) {
	    if (typeof bound == "string") {
	      bound = commands[bound];
	      if (!bound) { return false }
	    }
	    // Ensure previous input has been read, so that the handler sees a
	    // consistent view of the document
	    cm.display.input.ensurePolled();
	    var prevShift = cm.display.shift, done = false;
	    try {
	      if (cm.isReadOnly()) { cm.state.suppressEdits = true; }
	      if (dropShift) { cm.display.shift = false; }
	      done = bound(cm) != Pass;
	    } finally {
	      cm.display.shift = prevShift;
	      cm.state.suppressEdits = false;
	    }
	    return done
	  }

	  function lookupKeyForEditor(cm, name, handle) {
	    for (var i = 0; i < cm.state.keyMaps.length; i++) {
	      var result = lookupKey(name, cm.state.keyMaps[i], handle, cm);
	      if (result) { return result }
	    }
	    return (cm.options.extraKeys && lookupKey(name, cm.options.extraKeys, handle, cm))
	      || lookupKey(name, cm.options.keyMap, handle, cm)
	  }

	  // Note that, despite the name, this function is also used to check
	  // for bound mouse clicks.

	  var stopSeq = new Delayed;

	  function dispatchKey(cm, name, e, handle) {
	    var seq = cm.state.keySeq;
	    if (seq) {
	      if (isModifierKey(name)) { return "handled" }
	      if (/\'$/.test(name))
	        { cm.state.keySeq = null; }
	      else
	        { stopSeq.set(50, function () {
	          if (cm.state.keySeq == seq) {
	            cm.state.keySeq = null;
	            cm.display.input.reset();
	          }
	        }); }
	      if (dispatchKeyInner(cm, seq + " " + name, e, handle)) { return true }
	    }
	    return dispatchKeyInner(cm, name, e, handle)
	  }

	  function dispatchKeyInner(cm, name, e, handle) {
	    var result = lookupKeyForEditor(cm, name, handle);

	    if (result == "multi")
	      { cm.state.keySeq = name; }
	    if (result == "handled")
	      { signalLater(cm, "keyHandled", cm, name, e); }

	    if (result == "handled" || result == "multi") {
	      e_preventDefault(e);
	      restartBlink(cm);
	    }

	    return !!result
	  }

	  // Handle a key from the keydown event.
	  function handleKeyBinding(cm, e) {
	    var name = keyName(e, true);
	    if (!name) { return false }

	    if (e.shiftKey && !cm.state.keySeq) {
	      // First try to resolve full name (including 'Shift-'). Failing
	      // that, see if there is a cursor-motion command (starting with
	      // 'go') bound to the keyname without 'Shift-'.
	      return dispatchKey(cm, "Shift-" + name, e, function (b) { return doHandleBinding(cm, b, true); })
	          || dispatchKey(cm, name, e, function (b) {
	               if (typeof b == "string" ? /^go[A-Z]/.test(b) : b.motion)
	                 { return doHandleBinding(cm, b) }
	             })
	    } else {
	      return dispatchKey(cm, name, e, function (b) { return doHandleBinding(cm, b); })
	    }
	  }

	  // Handle a key from the keypress event
	  function handleCharBinding(cm, e, ch) {
	    return dispatchKey(cm, "'" + ch + "'", e, function (b) { return doHandleBinding(cm, b, true); })
	  }

	  var lastStoppedKey = null;
	  function onKeyDown(e) {
	    var cm = this;
	    cm.curOp.focus = activeElt();
	    if (signalDOMEvent(cm, e)) { return }
	    // IE does strange things with escape.
	    if (ie && ie_version < 11 && e.keyCode == 27) { e.returnValue = false; }
	    var code = e.keyCode;
	    cm.display.shift = code == 16 || e.shiftKey;
	    var handled = handleKeyBinding(cm, e);
	    if (presto) {
	      lastStoppedKey = handled ? code : null;
	      // Opera has no cut event... we try to at least catch the key combo
	      if (!handled && code == 88 && !hasCopyEvent && (mac ? e.metaKey : e.ctrlKey))
	        { cm.replaceSelection("", null, "cut"); }
	    }
	    if (gecko && !mac && !handled && code == 46 && e.shiftKey && !e.ctrlKey && document.execCommand)
	      { document.execCommand("cut"); }

	    // Turn mouse into crosshair when Alt is held on Mac.
	    if (code == 18 && !/\bCodeMirror-crosshair\b/.test(cm.display.lineDiv.className))
	      { showCrossHair(cm); }
	  }

	  function showCrossHair(cm) {
	    var lineDiv = cm.display.lineDiv;
	    addClass(lineDiv, "CodeMirror-crosshair");

	    function up(e) {
	      if (e.keyCode == 18 || !e.altKey) {
	        rmClass(lineDiv, "CodeMirror-crosshair");
	        off(document, "keyup", up);
	        off(document, "mouseover", up);
	      }
	    }
	    on(document, "keyup", up);
	    on(document, "mouseover", up);
	  }

	  function onKeyUp(e) {
	    if (e.keyCode == 16) { this.doc.sel.shift = false; }
	    signalDOMEvent(this, e);
	  }

	  function onKeyPress(e) {
	    var cm = this;
	    if (eventInWidget(cm.display, e) || signalDOMEvent(cm, e) || e.ctrlKey && !e.altKey || mac && e.metaKey) { return }
	    var keyCode = e.keyCode, charCode = e.charCode;
	    if (presto && keyCode == lastStoppedKey) {lastStoppedKey = null; e_preventDefault(e); return}
	    if ((presto && (!e.which || e.which < 10)) && handleKeyBinding(cm, e)) { return }
	    var ch = String.fromCharCode(charCode == null ? keyCode : charCode);
	    // Some browsers fire keypress events for backspace
	    if (ch == "\x08") { return }
	    if (handleCharBinding(cm, e, ch)) { return }
	    cm.display.input.onKeyPress(e);
	  }

	  var DOUBLECLICK_DELAY = 400;

	  var PastClick = function(time, pos, button) {
	    this.time = time;
	    this.pos = pos;
	    this.button = button;
	  };

	  PastClick.prototype.compare = function (time, pos, button) {
	    return this.time + DOUBLECLICK_DELAY > time &&
	      cmp(pos, this.pos) == 0 && button == this.button
	  };

	  var lastClick, lastDoubleClick;
	  function clickRepeat(pos, button) {
	    var now = +new Date;
	    if (lastDoubleClick && lastDoubleClick.compare(now, pos, button)) {
	      lastClick = lastDoubleClick = null;
	      return "triple"
	    } else if (lastClick && lastClick.compare(now, pos, button)) {
	      lastDoubleClick = new PastClick(now, pos, button);
	      lastClick = null;
	      return "double"
	    } else {
	      lastClick = new PastClick(now, pos, button);
	      lastDoubleClick = null;
	      return "single"
	    }
	  }

	  // A mouse down can be a single click, double click, triple click,
	  // start of selection drag, start of text drag, new cursor
	  // (ctrl-click), rectangle drag (alt-drag), or xwin
	  // middle-click-paste. Or it might be a click on something we should
	  // not interfere with, such as a scrollbar or widget.
	  function onMouseDown(e) {
	    var cm = this, display = cm.display;
	    if (signalDOMEvent(cm, e) || display.activeTouch && display.input.supportsTouch()) { return }
	    display.input.ensurePolled();
	    display.shift = e.shiftKey;

	    if (eventInWidget(display, e)) {
	      if (!webkit) {
	        // Briefly turn off draggability, to allow widgets to do
	        // normal dragging things.
	        display.scroller.draggable = false;
	        setTimeout(function () { return display.scroller.draggable = true; }, 100);
	      }
	      return
	    }
	    if (clickInGutter(cm, e)) { return }
	    var pos = posFromMouse(cm, e), button = e_button(e), repeat = pos ? clickRepeat(pos, button) : "single";
	    window.focus();

	    // #3261: make sure, that we're not starting a second selection
	    if (button == 1 && cm.state.selectingText)
	      { cm.state.selectingText(e); }

	    if (pos && handleMappedButton(cm, button, pos, repeat, e)) { return }

	    if (button == 1) {
	      if (pos) { leftButtonDown(cm, pos, repeat, e); }
	      else if (e_target(e) == display.scroller) { e_preventDefault(e); }
	    } else if (button == 2) {
	      if (pos) { extendSelection(cm.doc, pos); }
	      setTimeout(function () { return display.input.focus(); }, 20);
	    } else if (button == 3) {
	      if (captureRightClick) { cm.display.input.onContextMenu(e); }
	      else { delayBlurEvent(cm); }
	    }
	  }

	  function handleMappedButton(cm, button, pos, repeat, event) {
	    var name = "Click";
	    if (repeat == "double") { name = "Double" + name; }
	    else if (repeat == "triple") { name = "Triple" + name; }
	    name = (button == 1 ? "Left" : button == 2 ? "Middle" : "Right") + name;

	    return dispatchKey(cm,  addModifierNames(name, event), event, function (bound) {
	      if (typeof bound == "string") { bound = commands[bound]; }
	      if (!bound) { return false }
	      var done = false;
	      try {
	        if (cm.isReadOnly()) { cm.state.suppressEdits = true; }
	        done = bound(cm, pos) != Pass;
	      } finally {
	        cm.state.suppressEdits = false;
	      }
	      return done
	    })
	  }

	  function configureMouse(cm, repeat, event) {
	    var option = cm.getOption("configureMouse");
	    var value = option ? option(cm, repeat, event) : {};
	    if (value.unit == null) {
	      var rect = chromeOS ? event.shiftKey && event.metaKey : event.altKey;
	      value.unit = rect ? "rectangle" : repeat == "single" ? "char" : repeat == "double" ? "word" : "line";
	    }
	    if (value.extend == null || cm.doc.extend) { value.extend = cm.doc.extend || event.shiftKey; }
	    if (value.addNew == null) { value.addNew = mac ? event.metaKey : event.ctrlKey; }
	    if (value.moveOnDrag == null) { value.moveOnDrag = !(mac ? event.altKey : event.ctrlKey); }
	    return value
	  }

	  function leftButtonDown(cm, pos, repeat, event) {
	    if (ie) { setTimeout(bind(ensureFocus, cm), 0); }
	    else { cm.curOp.focus = activeElt(); }

	    var behavior = configureMouse(cm, repeat, event);

	    var sel = cm.doc.sel, contained;
	    if (cm.options.dragDrop && dragAndDrop && !cm.isReadOnly() &&
	        repeat == "single" && (contained = sel.contains(pos)) > -1 &&
	        (cmp((contained = sel.ranges[contained]).from(), pos) < 0 || pos.xRel > 0) &&
	        (cmp(contained.to(), pos) > 0 || pos.xRel < 0))
	      { leftButtonStartDrag(cm, event, pos, behavior); }
	    else
	      { leftButtonSelect(cm, event, pos, behavior); }
	  }

	  // Start a text drag. When it ends, see if any dragging actually
	  // happen, and treat as a click if it didn't.
	  function leftButtonStartDrag(cm, event, pos, behavior) {
	    var display = cm.display, moved = false;
	    var dragEnd = operation(cm, function (e) {
	      if (webkit) { display.scroller.draggable = false; }
	      cm.state.draggingText = false;
	      off(display.wrapper.ownerDocument, "mouseup", dragEnd);
	      off(display.wrapper.ownerDocument, "mousemove", mouseMove);
	      off(display.scroller, "dragstart", dragStart);
	      off(display.scroller, "drop", dragEnd);
	      if (!moved) {
	        e_preventDefault(e);
	        if (!behavior.addNew)
	          { extendSelection(cm.doc, pos, null, null, behavior.extend); }
	        // Work around unexplainable focus problem in IE9 (#2127) and Chrome (#3081)
	        if (webkit || ie && ie_version == 9)
	          { setTimeout(function () {display.wrapper.ownerDocument.body.focus(); display.input.focus();}, 20); }
	        else
	          { display.input.focus(); }
	      }
	    });
	    var mouseMove = function(e2) {
	      moved = moved || Math.abs(event.clientX - e2.clientX) + Math.abs(event.clientY - e2.clientY) >= 10;
	    };
	    var dragStart = function () { return moved = true; };
	    // Let the drag handler handle this.
	    if (webkit) { display.scroller.draggable = true; }
	    cm.state.draggingText = dragEnd;
	    dragEnd.copy = !behavior.moveOnDrag;
	    // IE's approach to draggable
	    if (display.scroller.dragDrop) { display.scroller.dragDrop(); }
	    on(display.wrapper.ownerDocument, "mouseup", dragEnd);
	    on(display.wrapper.ownerDocument, "mousemove", mouseMove);
	    on(display.scroller, "dragstart", dragStart);
	    on(display.scroller, "drop", dragEnd);

	    delayBlurEvent(cm);
	    setTimeout(function () { return display.input.focus(); }, 20);
	  }

	  function rangeForUnit(cm, pos, unit) {
	    if (unit == "char") { return new Range(pos, pos) }
	    if (unit == "word") { return cm.findWordAt(pos) }
	    if (unit == "line") { return new Range(Pos(pos.line, 0), clipPos(cm.doc, Pos(pos.line + 1, 0))) }
	    var result = unit(cm, pos);
	    return new Range(result.from, result.to)
	  }

	  // Normal selection, as opposed to text dragging.
	  function leftButtonSelect(cm, event, start, behavior) {
	    var display = cm.display, doc = cm.doc;
	    e_preventDefault(event);

	    var ourRange, ourIndex, startSel = doc.sel, ranges = startSel.ranges;
	    if (behavior.addNew && !behavior.extend) {
	      ourIndex = doc.sel.contains(start);
	      if (ourIndex > -1)
	        { ourRange = ranges[ourIndex]; }
	      else
	        { ourRange = new Range(start, start); }
	    } else {
	      ourRange = doc.sel.primary();
	      ourIndex = doc.sel.primIndex;
	    }

	    if (behavior.unit == "rectangle") {
	      if (!behavior.addNew) { ourRange = new Range(start, start); }
	      start = posFromMouse(cm, event, true, true);
	      ourIndex = -1;
	    } else {
	      var range = rangeForUnit(cm, start, behavior.unit);
	      if (behavior.extend)
	        { ourRange = extendRange(ourRange, range.anchor, range.head, behavior.extend); }
	      else
	        { ourRange = range; }
	    }

	    if (!behavior.addNew) {
	      ourIndex = 0;
	      setSelection(doc, new Selection([ourRange], 0), sel_mouse);
	      startSel = doc.sel;
	    } else if (ourIndex == -1) {
	      ourIndex = ranges.length;
	      setSelection(doc, normalizeSelection(cm, ranges.concat([ourRange]), ourIndex),
	                   {scroll: false, origin: "*mouse"});
	    } else if (ranges.length > 1 && ranges[ourIndex].empty() && behavior.unit == "char" && !behavior.extend) {
	      setSelection(doc, normalizeSelection(cm, ranges.slice(0, ourIndex).concat(ranges.slice(ourIndex + 1)), 0),
	                   {scroll: false, origin: "*mouse"});
	      startSel = doc.sel;
	    } else {
	      replaceOneSelection(doc, ourIndex, ourRange, sel_mouse);
	    }

	    var lastPos = start;
	    function extendTo(pos) {
	      if (cmp(lastPos, pos) == 0) { return }
	      lastPos = pos;

	      if (behavior.unit == "rectangle") {
	        var ranges = [], tabSize = cm.options.tabSize;
	        var startCol = countColumn(getLine(doc, start.line).text, start.ch, tabSize);
	        var posCol = countColumn(getLine(doc, pos.line).text, pos.ch, tabSize);
	        var left = Math.min(startCol, posCol), right = Math.max(startCol, posCol);
	        for (var line = Math.min(start.line, pos.line), end = Math.min(cm.lastLine(), Math.max(start.line, pos.line));
	             line <= end; line++) {
	          var text = getLine(doc, line).text, leftPos = findColumn(text, left, tabSize);
	          if (left == right)
	            { ranges.push(new Range(Pos(line, leftPos), Pos(line, leftPos))); }
	          else if (text.length > leftPos)
	            { ranges.push(new Range(Pos(line, leftPos), Pos(line, findColumn(text, right, tabSize)))); }
	        }
	        if (!ranges.length) { ranges.push(new Range(start, start)); }
	        setSelection(doc, normalizeSelection(cm, startSel.ranges.slice(0, ourIndex).concat(ranges), ourIndex),
	                     {origin: "*mouse", scroll: false});
	        cm.scrollIntoView(pos);
	      } else {
	        var oldRange = ourRange;
	        var range = rangeForUnit(cm, pos, behavior.unit);
	        var anchor = oldRange.anchor, head;
	        if (cmp(range.anchor, anchor) > 0) {
	          head = range.head;
	          anchor = minPos(oldRange.from(), range.anchor);
	        } else {
	          head = range.anchor;
	          anchor = maxPos(oldRange.to(), range.head);
	        }
	        var ranges$1 = startSel.ranges.slice(0);
	        ranges$1[ourIndex] = bidiSimplify(cm, new Range(clipPos(doc, anchor), head));
	        setSelection(doc, normalizeSelection(cm, ranges$1, ourIndex), sel_mouse);
	      }
	    }

	    var editorSize = display.wrapper.getBoundingClientRect();
	    // Used to ensure timeout re-tries don't fire when another extend
	    // happened in the meantime (clearTimeout isn't reliable -- at
	    // least on Chrome, the timeouts still happen even when cleared,
	    // if the clear happens after their scheduled firing time).
	    var counter = 0;

	    function extend(e) {
	      var curCount = ++counter;
	      var cur = posFromMouse(cm, e, true, behavior.unit == "rectangle");
	      if (!cur) { return }
	      if (cmp(cur, lastPos) != 0) {
	        cm.curOp.focus = activeElt();
	        extendTo(cur);
	        var visible = visibleLines(display, doc);
	        if (cur.line >= visible.to || cur.line < visible.from)
	          { setTimeout(operation(cm, function () {if (counter == curCount) { extend(e); }}), 150); }
	      } else {
	        var outside = e.clientY < editorSize.top ? -20 : e.clientY > editorSize.bottom ? 20 : 0;
	        if (outside) { setTimeout(operation(cm, function () {
	          if (counter != curCount) { return }
	          display.scroller.scrollTop += outside;
	          extend(e);
	        }), 50); }
	      }
	    }

	    function done(e) {
	      cm.state.selectingText = false;
	      counter = Infinity;
	      // If e is null or undefined we interpret this as someone trying
	      // to explicitly cancel the selection rather than the user
	      // letting go of the mouse button.
	      if (e) {
	        e_preventDefault(e);
	        display.input.focus();
	      }
	      off(display.wrapper.ownerDocument, "mousemove", move);
	      off(display.wrapper.ownerDocument, "mouseup", up);
	      doc.history.lastSelOrigin = null;
	    }

	    var move = operation(cm, function (e) {
	      if (e.buttons === 0 || !e_button(e)) { done(e); }
	      else { extend(e); }
	    });
	    var up = operation(cm, done);
	    cm.state.selectingText = up;
	    on(display.wrapper.ownerDocument, "mousemove", move);
	    on(display.wrapper.ownerDocument, "mouseup", up);
	  }

	  // Used when mouse-selecting to adjust the anchor to the proper side
	  // of a bidi jump depending on the visual position of the head.
	  function bidiSimplify(cm, range) {
	    var anchor = range.anchor;
	    var head = range.head;
	    var anchorLine = getLine(cm.doc, anchor.line);
	    if (cmp(anchor, head) == 0 && anchor.sticky == head.sticky) { return range }
	    var order = getOrder(anchorLine);
	    if (!order) { return range }
	    var index = getBidiPartAt(order, anchor.ch, anchor.sticky), part = order[index];
	    if (part.from != anchor.ch && part.to != anchor.ch) { return range }
	    var boundary = index + ((part.from == anchor.ch) == (part.level != 1) ? 0 : 1);
	    if (boundary == 0 || boundary == order.length) { return range }

	    // Compute the relative visual position of the head compared to the
	    // anchor (<0 is to the left, >0 to the right)
	    var leftSide;
	    if (head.line != anchor.line) {
	      leftSide = (head.line - anchor.line) * (cm.doc.direction == "ltr" ? 1 : -1) > 0;
	    } else {
	      var headIndex = getBidiPartAt(order, head.ch, head.sticky);
	      var dir = headIndex - index || (head.ch - anchor.ch) * (part.level == 1 ? -1 : 1);
	      if (headIndex == boundary - 1 || headIndex == boundary)
	        { leftSide = dir < 0; }
	      else
	        { leftSide = dir > 0; }
	    }

	    var usePart = order[boundary + (leftSide ? -1 : 0)];
	    var from = leftSide == (usePart.level == 1);
	    var ch = from ? usePart.from : usePart.to, sticky = from ? "after" : "before";
	    return anchor.ch == ch && anchor.sticky == sticky ? range : new Range(new Pos(anchor.line, ch, sticky), head)
	  }


	  // Determines whether an event happened in the gutter, and fires the
	  // handlers for the corresponding event.
	  function gutterEvent(cm, e, type, prevent) {
	    var mX, mY;
	    if (e.touches) {
	      mX = e.touches[0].clientX;
	      mY = e.touches[0].clientY;
	    } else {
	      try { mX = e.clientX; mY = e.clientY; }
	      catch(e) { return false }
	    }
	    if (mX >= Math.floor(cm.display.gutters.getBoundingClientRect().right)) { return false }
	    if (prevent) { e_preventDefault(e); }

	    var display = cm.display;
	    var lineBox = display.lineDiv.getBoundingClientRect();

	    if (mY > lineBox.bottom || !hasHandler(cm, type)) { return e_defaultPrevented(e) }
	    mY -= lineBox.top - display.viewOffset;

	    for (var i = 0; i < cm.display.gutterSpecs.length; ++i) {
	      var g = display.gutters.childNodes[i];
	      if (g && g.getBoundingClientRect().right >= mX) {
	        var line = lineAtHeight(cm.doc, mY);
	        var gutter = cm.display.gutterSpecs[i];
	        signal(cm, type, cm, line, gutter.className, e);
	        return e_defaultPrevented(e)
	      }
	    }
	  }

	  function clickInGutter(cm, e) {
	    return gutterEvent(cm, e, "gutterClick", true)
	  }

	  // CONTEXT MENU HANDLING

	  // To make the context menu work, we need to briefly unhide the
	  // textarea (making it as unobtrusive as possible) to let the
	  // right-click take effect on it.
	  function onContextMenu(cm, e) {
	    if (eventInWidget(cm.display, e) || contextMenuInGutter(cm, e)) { return }
	    if (signalDOMEvent(cm, e, "contextmenu")) { return }
	    if (!captureRightClick) { cm.display.input.onContextMenu(e); }
	  }

	  function contextMenuInGutter(cm, e) {
	    if (!hasHandler(cm, "gutterContextMenu")) { return false }
	    return gutterEvent(cm, e, "gutterContextMenu", false)
	  }

	  function themeChanged(cm) {
	    cm.display.wrapper.className = cm.display.wrapper.className.replace(/\s*cm-s-\S+/g, "") +
	      cm.options.theme.replace(/(^|\s)\s*/g, " cm-s-");
	    clearCaches(cm);
	  }

	  var Init = {toString: function(){return "CodeMirror.Init"}};

	  var defaults = {};
	  var optionHandlers = {};

	  function defineOptions(CodeMirror) {
	    var optionHandlers = CodeMirror.optionHandlers;

	    function option(name, deflt, handle, notOnInit) {
	      CodeMirror.defaults[name] = deflt;
	      if (handle) { optionHandlers[name] =
	        notOnInit ? function (cm, val, old) {if (old != Init) { handle(cm, val, old); }} : handle; }
	    }

	    CodeMirror.defineOption = option;

	    // Passed to option handlers when there is no old value.
	    CodeMirror.Init = Init;

	    // These two are, on init, called from the constructor because they
	    // have to be initialized before the editor can start at all.
	    option("value", "", function (cm, val) { return cm.setValue(val); }, true);
	    option("mode", null, function (cm, val) {
	      cm.doc.modeOption = val;
	      loadMode(cm);
	    }, true);

	    option("indentUnit", 2, loadMode, true);
	    option("indentWithTabs", false);
	    option("smartIndent", true);
	    option("tabSize", 4, function (cm) {
	      resetModeState(cm);
	      clearCaches(cm);
	      regChange(cm);
	    }, true);

	    option("lineSeparator", null, function (cm, val) {
	      cm.doc.lineSep = val;
	      if (!val) { return }
	      var newBreaks = [], lineNo = cm.doc.first;
	      cm.doc.iter(function (line) {
	        for (var pos = 0;;) {
	          var found = line.text.indexOf(val, pos);
	          if (found == -1) { break }
	          pos = found + val.length;
	          newBreaks.push(Pos(lineNo, found));
	        }
	        lineNo++;
	      });
	      for (var i = newBreaks.length - 1; i >= 0; i--)
	        { replaceRange(cm.doc, val, newBreaks[i], Pos(newBreaks[i].line, newBreaks[i].ch + val.length)); }
	    });
	    option("specialChars", /[\u0000-\u001f\u007f-\u009f\u00ad\u061c\u200b-\u200f\u2028\u2029\ufeff\ufff9-\ufffc]/g, function (cm, val, old) {
	      cm.state.specialChars = new RegExp(val.source + (val.test("\t") ? "" : "|\t"), "g");
	      if (old != Init) { cm.refresh(); }
	    });
	    option("specialCharPlaceholder", defaultSpecialCharPlaceholder, function (cm) { return cm.refresh(); }, true);
	    option("electricChars", true);
	    option("inputStyle", mobile ? "contenteditable" : "textarea", function () {
	      throw new Error("inputStyle can not (yet) be changed in a running editor") // FIXME
	    }, true);
	    option("spellcheck", false, function (cm, val) { return cm.getInputField().spellcheck = val; }, true);
	    option("autocorrect", false, function (cm, val) { return cm.getInputField().autocorrect = val; }, true);
	    option("autocapitalize", false, function (cm, val) { return cm.getInputField().autocapitalize = val; }, true);
	    option("rtlMoveVisually", !windows);
	    option("wholeLineUpdateBefore", true);

	    option("theme", "default", function (cm) {
	      themeChanged(cm);
	      updateGutters(cm);
	    }, true);
	    option("keyMap", "default", function (cm, val, old) {
	      var next = getKeyMap(val);
	      var prev = old != Init && getKeyMap(old);
	      if (prev && prev.detach) { prev.detach(cm, next); }
	      if (next.attach) { next.attach(cm, prev || null); }
	    });
	    option("extraKeys", null);
	    option("configureMouse", null);

	    option("lineWrapping", false, wrappingChanged, true);
	    option("gutters", [], function (cm, val) {
	      cm.display.gutterSpecs = getGutters(val, cm.options.lineNumbers);
	      updateGutters(cm);
	    }, true);
	    option("fixedGutter", true, function (cm, val) {
	      cm.display.gutters.style.left = val ? compensateForHScroll(cm.display) + "px" : "0";
	      cm.refresh();
	    }, true);
	    option("coverGutterNextToScrollbar", false, function (cm) { return updateScrollbars(cm); }, true);
	    option("scrollbarStyle", "native", function (cm) {
	      initScrollbars(cm);
	      updateScrollbars(cm);
	      cm.display.scrollbars.setScrollTop(cm.doc.scrollTop);
	      cm.display.scrollbars.setScrollLeft(cm.doc.scrollLeft);
	    }, true);
	    option("lineNumbers", false, function (cm, val) {
	      cm.display.gutterSpecs = getGutters(cm.options.gutters, val);
	      updateGutters(cm);
	    }, true);
	    option("firstLineNumber", 1, updateGutters, true);
	    option("lineNumberFormatter", function (integer) { return integer; }, updateGutters, true);
	    option("showCursorWhenSelecting", false, updateSelection, true);

	    option("resetSelectionOnContextMenu", true);
	    option("lineWiseCopyCut", true);
	    option("pasteLinesPerSelection", true);
	    option("selectionsMayTouch", false);

	    option("readOnly", false, function (cm, val) {
	      if (val == "nocursor") {
	        onBlur(cm);
	        cm.display.input.blur();
	      }
	      cm.display.input.readOnlyChanged(val);
	    });
	    option("disableInput", false, function (cm, val) {if (!val) { cm.display.input.reset(); }}, true);
	    option("dragDrop", true, dragDropChanged);
	    option("allowDropFileTypes", null);

	    option("cursorBlinkRate", 530);
	    option("cursorScrollMargin", 0);
	    option("cursorHeight", 1, updateSelection, true);
	    option("singleCursorHeightPerLine", true, updateSelection, true);
	    option("workTime", 100);
	    option("workDelay", 100);
	    option("flattenSpans", true, resetModeState, true);
	    option("addModeClass", false, resetModeState, true);
	    option("pollInterval", 100);
	    option("undoDepth", 200, function (cm, val) { return cm.doc.history.undoDepth = val; });
	    option("historyEventDelay", 1250);
	    option("viewportMargin", 10, function (cm) { return cm.refresh(); }, true);
	    option("maxHighlightLength", 10000, resetModeState, true);
	    option("moveInputWithCursor", true, function (cm, val) {
	      if (!val) { cm.display.input.resetPosition(); }
	    });

	    option("tabindex", null, function (cm, val) { return cm.display.input.getField().tabIndex = val || ""; });
	    option("autofocus", null);
	    option("direction", "ltr", function (cm, val) { return cm.doc.setDirection(val); }, true);
	    option("phrases", null);
	  }

	  function dragDropChanged(cm, value, old) {
	    var wasOn = old && old != Init;
	    if (!value != !wasOn) {
	      var funcs = cm.display.dragFunctions;
	      var toggle = value ? on : off;
	      toggle(cm.display.scroller, "dragstart", funcs.start);
	      toggle(cm.display.scroller, "dragenter", funcs.enter);
	      toggle(cm.display.scroller, "dragover", funcs.over);
	      toggle(cm.display.scroller, "dragleave", funcs.leave);
	      toggle(cm.display.scroller, "drop", funcs.drop);
	    }
	  }

	  function wrappingChanged(cm) {
	    if (cm.options.lineWrapping) {
	      addClass(cm.display.wrapper, "CodeMirror-wrap");
	      cm.display.sizer.style.minWidth = "";
	      cm.display.sizerWidth = null;
	    } else {
	      rmClass(cm.display.wrapper, "CodeMirror-wrap");
	      findMaxLine(cm);
	    }
	    estimateLineHeights(cm);
	    regChange(cm);
	    clearCaches(cm);
	    setTimeout(function () { return updateScrollbars(cm); }, 100);
	  }

	  // A CodeMirror instance represents an editor. This is the object
	  // that user code is usually dealing with.

	  function CodeMirror(place, options) {
	    var this$1 = this;

	    if (!(this instanceof CodeMirror)) { return new CodeMirror(place, options) }

	    this.options = options = options ? copyObj(options) : {};
	    // Determine effective options based on given values and defaults.
	    copyObj(defaults, options, false);

	    var doc = options.value;
	    if (typeof doc == "string") { doc = new Doc(doc, options.mode, null, options.lineSeparator, options.direction); }
	    else if (options.mode) { doc.modeOption = options.mode; }
	    this.doc = doc;

	    var input = new CodeMirror.inputStyles[options.inputStyle](this);
	    var display = this.display = new Display(place, doc, input, options);
	    display.wrapper.CodeMirror = this;
	    themeChanged(this);
	    if (options.lineWrapping)
	      { this.display.wrapper.className += " CodeMirror-wrap"; }
	    initScrollbars(this);

	    this.state = {
	      keyMaps: [],  // stores maps added by addKeyMap
	      overlays: [], // highlighting overlays, as added by addOverlay
	      modeGen: 0,   // bumped when mode/overlay changes, used to invalidate highlighting info
	      overwrite: false,
	      delayingBlurEvent: false,
	      focused: false,
	      suppressEdits: false, // used to disable editing during key handlers when in readOnly mode
	      pasteIncoming: -1, cutIncoming: -1, // help recognize paste/cut edits in input.poll
	      selectingText: false,
	      draggingText: false,
	      highlight: new Delayed(), // stores highlight worker timeout
	      keySeq: null,  // Unfinished key sequence
	      specialChars: null
	    };

	    if (options.autofocus && !mobile) { display.input.focus(); }

	    // Override magic textarea content restore that IE sometimes does
	    // on our hidden textarea on reload
	    if (ie && ie_version < 11) { setTimeout(function () { return this$1.display.input.reset(true); }, 20); }

	    registerEventHandlers(this);
	    ensureGlobalHandlers();

	    startOperation(this);
	    this.curOp.forceUpdate = true;
	    attachDoc(this, doc);

	    if ((options.autofocus && !mobile) || this.hasFocus())
	      { setTimeout(bind(onFocus, this), 20); }
	    else
	      { onBlur(this); }

	    for (var opt in optionHandlers) { if (optionHandlers.hasOwnProperty(opt))
	      { optionHandlers[opt](this, options[opt], Init); } }
	    maybeUpdateLineNumberWidth(this);
	    if (options.finishInit) { options.finishInit(this); }
	    for (var i = 0; i < initHooks.length; ++i) { initHooks[i](this); }
	    endOperation(this);
	    // Suppress optimizelegibility in Webkit, since it breaks text
	    // measuring on line wrapping boundaries.
	    if (webkit && options.lineWrapping &&
	        getComputedStyle(display.lineDiv).textRendering == "optimizelegibility")
	      { display.lineDiv.style.textRendering = "auto"; }
	  }

	  // The default configuration options.
	  CodeMirror.defaults = defaults;
	  // Functions to run when options are changed.
	  CodeMirror.optionHandlers = optionHandlers;

	  // Attach the necessary event handlers when initializing the editor
	  function registerEventHandlers(cm) {
	    var d = cm.display;
	    on(d.scroller, "mousedown", operation(cm, onMouseDown));
	    // Older IE's will not fire a second mousedown for a double click
	    if (ie && ie_version < 11)
	      { on(d.scroller, "dblclick", operation(cm, function (e) {
	        if (signalDOMEvent(cm, e)) { return }
	        var pos = posFromMouse(cm, e);
	        if (!pos || clickInGutter(cm, e) || eventInWidget(cm.display, e)) { return }
	        e_preventDefault(e);
	        var word = cm.findWordAt(pos);
	        extendSelection(cm.doc, word.anchor, word.head);
	      })); }
	    else
	      { on(d.scroller, "dblclick", function (e) { return signalDOMEvent(cm, e) || e_preventDefault(e); }); }
	    // Some browsers fire contextmenu *after* opening the menu, at
	    // which point we can't mess with it anymore. Context menu is
	    // handled in onMouseDown for these browsers.
	    on(d.scroller, "contextmenu", function (e) { return onContextMenu(cm, e); });
	    on(d.input.getField(), "contextmenu", function (e) {
	      if (!d.scroller.contains(e.target)) { onContextMenu(cm, e); }
	    });

	    // Used to suppress mouse event handling when a touch happens
	    var touchFinished, prevTouch = {end: 0};
	    function finishTouch() {
	      if (d.activeTouch) {
	        touchFinished = setTimeout(function () { return d.activeTouch = null; }, 1000);
	        prevTouch = d.activeTouch;
	        prevTouch.end = +new Date;
	      }
	    }
	    function isMouseLikeTouchEvent(e) {
	      if (e.touches.length != 1) { return false }
	      var touch = e.touches[0];
	      return touch.radiusX <= 1 && touch.radiusY <= 1
	    }
	    function farAway(touch, other) {
	      if (other.left == null) { return true }
	      var dx = other.left - touch.left, dy = other.top - touch.top;
	      return dx * dx + dy * dy > 20 * 20
	    }
	    on(d.scroller, "touchstart", function (e) {
	      if (!signalDOMEvent(cm, e) && !isMouseLikeTouchEvent(e) && !clickInGutter(cm, e)) {
	        d.input.ensurePolled();
	        clearTimeout(touchFinished);
	        var now = +new Date;
	        d.activeTouch = {start: now, moved: false,
	                         prev: now - prevTouch.end <= 300 ? prevTouch : null};
	        if (e.touches.length == 1) {
	          d.activeTouch.left = e.touches[0].pageX;
	          d.activeTouch.top = e.touches[0].pageY;
	        }
	      }
	    });
	    on(d.scroller, "touchmove", function () {
	      if (d.activeTouch) { d.activeTouch.moved = true; }
	    });
	    on(d.scroller, "touchend", function (e) {
	      var touch = d.activeTouch;
	      if (touch && !eventInWidget(d, e) && touch.left != null &&
	          !touch.moved && new Date - touch.start < 300) {
	        var pos = cm.coordsChar(d.activeTouch, "page"), range;
	        if (!touch.prev || farAway(touch, touch.prev)) // Single tap
	          { range = new Range(pos, pos); }
	        else if (!touch.prev.prev || farAway(touch, touch.prev.prev)) // Double tap
	          { range = cm.findWordAt(pos); }
	        else // Triple tap
	          { range = new Range(Pos(pos.line, 0), clipPos(cm.doc, Pos(pos.line + 1, 0))); }
	        cm.setSelection(range.anchor, range.head);
	        cm.focus();
	        e_preventDefault(e);
	      }
	      finishTouch();
	    });
	    on(d.scroller, "touchcancel", finishTouch);

	    // Sync scrolling between fake scrollbars and real scrollable
	    // area, ensure viewport is updated when scrolling.
	    on(d.scroller, "scroll", function () {
	      if (d.scroller.clientHeight) {
	        updateScrollTop(cm, d.scroller.scrollTop);
	        setScrollLeft(cm, d.scroller.scrollLeft, true);
	        signal(cm, "scroll", cm);
	      }
	    });

	    // Listen to wheel events in order to try and update the viewport on time.
	    on(d.scroller, "mousewheel", function (e) { return onScrollWheel(cm, e); });
	    on(d.scroller, "DOMMouseScroll", function (e) { return onScrollWheel(cm, e); });

	    // Prevent wrapper from ever scrolling
	    on(d.wrapper, "scroll", function () { return d.wrapper.scrollTop = d.wrapper.scrollLeft = 0; });

	    d.dragFunctions = {
	      enter: function (e) {if (!signalDOMEvent(cm, e)) { e_stop(e); }},
	      over: function (e) {if (!signalDOMEvent(cm, e)) { onDragOver(cm, e); e_stop(e); }},
	      start: function (e) { return onDragStart(cm, e); },
	      drop: operation(cm, onDrop),
	      leave: function (e) {if (!signalDOMEvent(cm, e)) { clearDragCursor(cm); }}
	    };

	    var inp = d.input.getField();
	    on(inp, "keyup", function (e) { return onKeyUp.call(cm, e); });
	    on(inp, "keydown", operation(cm, onKeyDown));
	    on(inp, "keypress", operation(cm, onKeyPress));
	    on(inp, "focus", function (e) { return onFocus(cm, e); });
	    on(inp, "blur", function (e) { return onBlur(cm, e); });
	  }

	  var initHooks = [];
	  CodeMirror.defineInitHook = function (f) { return initHooks.push(f); };

	  // Indent the given line. The how parameter can be "smart",
	  // "add"/null, "subtract", or "prev". When aggressive is false
	  // (typically set to true for forced single-line indents), empty
	  // lines are not indented, and places where the mode returns Pass
	  // are left alone.
	  function indentLine(cm, n, how, aggressive) {
	    var doc = cm.doc, state;
	    if (how == null) { how = "add"; }
	    if (how == "smart") {
	      // Fall back to "prev" when the mode doesn't have an indentation
	      // method.
	      if (!doc.mode.indent) { how = "prev"; }
	      else { state = getContextBefore(cm, n).state; }
	    }

	    var tabSize = cm.options.tabSize;
	    var line = getLine(doc, n), curSpace = countColumn(line.text, null, tabSize);
	    if (line.stateAfter) { line.stateAfter = null; }
	    var curSpaceString = line.text.match(/^\s*/)[0], indentation;
	    if (!aggressive && !/\S/.test(line.text)) {
	      indentation = 0;
	      how = "not";
	    } else if (how == "smart") {
	      indentation = doc.mode.indent(state, line.text.slice(curSpaceString.length), line.text);
	      if (indentation == Pass || indentation > 150) {
	        if (!aggressive) { return }
	        how = "prev";
	      }
	    }
	    if (how == "prev") {
	      if (n > doc.first) { indentation = countColumn(getLine(doc, n-1).text, null, tabSize); }
	      else { indentation = 0; }
	    } else if (how == "add") {
	      indentation = curSpace + cm.options.indentUnit;
	    } else if (how == "subtract") {
	      indentation = curSpace - cm.options.indentUnit;
	    } else if (typeof how == "number") {
	      indentation = curSpace + how;
	    }
	    indentation = Math.max(0, indentation);

	    var indentString = "", pos = 0;
	    if (cm.options.indentWithTabs)
	      { for (var i = Math.floor(indentation / tabSize); i; --i) {pos += tabSize; indentString += "\t";} }
	    if (pos < indentation) { indentString += spaceStr(indentation - pos); }

	    if (indentString != curSpaceString) {
	      replaceRange(doc, indentString, Pos(n, 0), Pos(n, curSpaceString.length), "+input");
	      line.stateAfter = null;
	      return true
	    } else {
	      // Ensure that, if the cursor was in the whitespace at the start
	      // of the line, it is moved to the end of that space.
	      for (var i$1 = 0; i$1 < doc.sel.ranges.length; i$1++) {
	        var range = doc.sel.ranges[i$1];
	        if (range.head.line == n && range.head.ch < curSpaceString.length) {
	          var pos$1 = Pos(n, curSpaceString.length);
	          replaceOneSelection(doc, i$1, new Range(pos$1, pos$1));
	          break
	        }
	      }
	    }
	  }

	  // This will be set to a {lineWise: bool, text: [string]} object, so
	  // that, when pasting, we know what kind of selections the copied
	  // text was made out of.
	  var lastCopied = null;

	  function setLastCopied(newLastCopied) {
	    lastCopied = newLastCopied;
	  }

	  function applyTextInput(cm, inserted, deleted, sel, origin) {
	    var doc = cm.doc;
	    cm.display.shift = false;
	    if (!sel) { sel = doc.sel; }

	    var recent = +new Date - 200;
	    var paste = origin == "paste" || cm.state.pasteIncoming > recent;
	    var textLines = splitLinesAuto(inserted), multiPaste = null;
	    // When pasting N lines into N selections, insert one line per selection
	    if (paste && sel.ranges.length > 1) {
	      if (lastCopied && lastCopied.text.join("\n") == inserted) {
	        if (sel.ranges.length % lastCopied.text.length == 0) {
	          multiPaste = [];
	          for (var i = 0; i < lastCopied.text.length; i++)
	            { multiPaste.push(doc.splitLines(lastCopied.text[i])); }
	        }
	      } else if (textLines.length == sel.ranges.length && cm.options.pasteLinesPerSelection) {
	        multiPaste = map(textLines, function (l) { return [l]; });
	      }
	    }

	    var updateInput = cm.curOp.updateInput;
	    // Normal behavior is to insert the new text into every selection
	    for (var i$1 = sel.ranges.length - 1; i$1 >= 0; i$1--) {
	      var range = sel.ranges[i$1];
	      var from = range.from(), to = range.to();
	      if (range.empty()) {
	        if (deleted && deleted > 0) // Handle deletion
	          { from = Pos(from.line, from.ch - deleted); }
	        else if (cm.state.overwrite && !paste) // Handle overwrite
	          { to = Pos(to.line, Math.min(getLine(doc, to.line).text.length, to.ch + lst(textLines).length)); }
	        else if (paste && lastCopied && lastCopied.lineWise && lastCopied.text.join("\n") == inserted)
	          { from = to = Pos(from.line, 0); }
	      }
	      var changeEvent = {from: from, to: to, text: multiPaste ? multiPaste[i$1 % multiPaste.length] : textLines,
	                         origin: origin || (paste ? "paste" : cm.state.cutIncoming > recent ? "cut" : "+input")};
	      makeChange(cm.doc, changeEvent);
	      signalLater(cm, "inputRead", cm, changeEvent);
	    }
	    if (inserted && !paste)
	      { triggerElectric(cm, inserted); }

	    ensureCursorVisible(cm);
	    if (cm.curOp.updateInput < 2) { cm.curOp.updateInput = updateInput; }
	    cm.curOp.typing = true;
	    cm.state.pasteIncoming = cm.state.cutIncoming = -1;
	  }

	  function handlePaste(e, cm) {
	    var pasted = e.clipboardData && e.clipboardData.getData("Text");
	    if (pasted) {
	      e.preventDefault();
	      if (!cm.isReadOnly() && !cm.options.disableInput)
	        { runInOp(cm, function () { return applyTextInput(cm, pasted, 0, null, "paste"); }); }
	      return true
	    }
	  }

	  function triggerElectric(cm, inserted) {
	    // When an 'electric' character is inserted, immediately trigger a reindent
	    if (!cm.options.electricChars || !cm.options.smartIndent) { return }
	    var sel = cm.doc.sel;

	    for (var i = sel.ranges.length - 1; i >= 0; i--) {
	      var range = sel.ranges[i];
	      if (range.head.ch > 100 || (i && sel.ranges[i - 1].head.line == range.head.line)) { continue }
	      var mode = cm.getModeAt(range.head);
	      var indented = false;
	      if (mode.electricChars) {
	        for (var j = 0; j < mode.electricChars.length; j++)
	          { if (inserted.indexOf(mode.electricChars.charAt(j)) > -1) {
	            indented = indentLine(cm, range.head.line, "smart");
	            break
	          } }
	      } else if (mode.electricInput) {
	        if (mode.electricInput.test(getLine(cm.doc, range.head.line).text.slice(0, range.head.ch)))
	          { indented = indentLine(cm, range.head.line, "smart"); }
	      }
	      if (indented) { signalLater(cm, "electricInput", cm, range.head.line); }
	    }
	  }

	  function copyableRanges(cm) {
	    var text = [], ranges = [];
	    for (var i = 0; i < cm.doc.sel.ranges.length; i++) {
	      var line = cm.doc.sel.ranges[i].head.line;
	      var lineRange = {anchor: Pos(line, 0), head: Pos(line + 1, 0)};
	      ranges.push(lineRange);
	      text.push(cm.getRange(lineRange.anchor, lineRange.head));
	    }
	    return {text: text, ranges: ranges}
	  }

	  function disableBrowserMagic(field, spellcheck, autocorrect, autocapitalize) {
	    field.setAttribute("autocorrect", autocorrect ? "" : "off");
	    field.setAttribute("autocapitalize", autocapitalize ? "" : "off");
	    field.setAttribute("spellcheck", !!spellcheck);
	  }

	  function hiddenTextarea() {
	    var te = elt("textarea", null, null, "position: absolute; bottom: -1em; padding: 0; width: 1px; height: 1em; outline: none");
	    var div = elt("div", [te], null, "overflow: hidden; position: relative; width: 3px; height: 0px;");
	    // The textarea is kept positioned near the cursor to prevent the
	    // fact that it'll be scrolled into view on input from scrolling
	    // our fake cursor out of view. On webkit, when wrap=off, paste is
	    // very slow. So make the area wide instead.
	    if (webkit) { te.style.width = "1000px"; }
	    else { te.setAttribute("wrap", "off"); }
	    // If border: 0; -- iOS fails to open keyboard (issue #1287)
	    if (ios) { te.style.border = "1px solid black"; }
	    disableBrowserMagic(te);
	    return div
	  }

	  // The publicly visible API. Note that methodOp(f) means
	  // 'wrap f in an operation, performed on its `this` parameter'.

	  // This is not the complete set of editor methods. Most of the
	  // methods defined on the Doc type are also injected into
	  // CodeMirror.prototype, for backwards compatibility and
	  // convenience.

	  function addEditorMethods(CodeMirror) {
	    var optionHandlers = CodeMirror.optionHandlers;

	    var helpers = CodeMirror.helpers = {};

	    CodeMirror.prototype = {
	      constructor: CodeMirror,
	      focus: function(){window.focus(); this.display.input.focus();},

	      setOption: function(option, value) {
	        var options = this.options, old = options[option];
	        if (options[option] == value && option != "mode") { return }
	        options[option] = value;
	        if (optionHandlers.hasOwnProperty(option))
	          { operation(this, optionHandlers[option])(this, value, old); }
	        signal(this, "optionChange", this, option);
	      },

	      getOption: function(option) {return this.options[option]},
	      getDoc: function() {return this.doc},

	      addKeyMap: function(map, bottom) {
	        this.state.keyMaps[bottom ? "push" : "unshift"](getKeyMap(map));
	      },
	      removeKeyMap: function(map) {
	        var maps = this.state.keyMaps;
	        for (var i = 0; i < maps.length; ++i)
	          { if (maps[i] == map || maps[i].name == map) {
	            maps.splice(i, 1);
	            return true
	          } }
	      },

	      addOverlay: methodOp(function(spec, options) {
	        var mode = spec.token ? spec : CodeMirror.getMode(this.options, spec);
	        if (mode.startState) { throw new Error("Overlays may not be stateful.") }
	        insertSorted(this.state.overlays,
	                     {mode: mode, modeSpec: spec, opaque: options && options.opaque,
	                      priority: (options && options.priority) || 0},
	                     function (overlay) { return overlay.priority; });
	        this.state.modeGen++;
	        regChange(this);
	      }),
	      removeOverlay: methodOp(function(spec) {
	        var overlays = this.state.overlays;
	        for (var i = 0; i < overlays.length; ++i) {
	          var cur = overlays[i].modeSpec;
	          if (cur == spec || typeof spec == "string" && cur.name == spec) {
	            overlays.splice(i, 1);
	            this.state.modeGen++;
	            regChange(this);
	            return
	          }
	        }
	      }),

	      indentLine: methodOp(function(n, dir, aggressive) {
	        if (typeof dir != "string" && typeof dir != "number") {
	          if (dir == null) { dir = this.options.smartIndent ? "smart" : "prev"; }
	          else { dir = dir ? "add" : "subtract"; }
	        }
	        if (isLine(this.doc, n)) { indentLine(this, n, dir, aggressive); }
	      }),
	      indentSelection: methodOp(function(how) {
	        var ranges = this.doc.sel.ranges, end = -1;
	        for (var i = 0; i < ranges.length; i++) {
	          var range = ranges[i];
	          if (!range.empty()) {
	            var from = range.from(), to = range.to();
	            var start = Math.max(end, from.line);
	            end = Math.min(this.lastLine(), to.line - (to.ch ? 0 : 1)) + 1;
	            for (var j = start; j < end; ++j)
	              { indentLine(this, j, how); }
	            var newRanges = this.doc.sel.ranges;
	            if (from.ch == 0 && ranges.length == newRanges.length && newRanges[i].from().ch > 0)
	              { replaceOneSelection(this.doc, i, new Range(from, newRanges[i].to()), sel_dontScroll); }
	          } else if (range.head.line > end) {
	            indentLine(this, range.head.line, how, true);
	            end = range.head.line;
	            if (i == this.doc.sel.primIndex) { ensureCursorVisible(this); }
	          }
	        }
	      }),

	      // Fetch the parser token for a given character. Useful for hacks
	      // that want to inspect the mode state (say, for completion).
	      getTokenAt: function(pos, precise) {
	        return takeToken(this, pos, precise)
	      },

	      getLineTokens: function(line, precise) {
	        return takeToken(this, Pos(line), precise, true)
	      },

	      getTokenTypeAt: function(pos) {
	        pos = clipPos(this.doc, pos);
	        var styles = getLineStyles(this, getLine(this.doc, pos.line));
	        var before = 0, after = (styles.length - 1) / 2, ch = pos.ch;
	        var type;
	        if (ch == 0) { type = styles[2]; }
	        else { for (;;) {
	          var mid = (before + after) >> 1;
	          if ((mid ? styles[mid * 2 - 1] : 0) >= ch) { after = mid; }
	          else if (styles[mid * 2 + 1] < ch) { before = mid + 1; }
	          else { type = styles[mid * 2 + 2]; break }
	        } }
	        var cut = type ? type.indexOf("overlay ") : -1;
	        return cut < 0 ? type : cut == 0 ? null : type.slice(0, cut - 1)
	      },

	      getModeAt: function(pos) {
	        var mode = this.doc.mode;
	        if (!mode.innerMode) { return mode }
	        return CodeMirror.innerMode(mode, this.getTokenAt(pos).state).mode
	      },

	      getHelper: function(pos, type) {
	        return this.getHelpers(pos, type)[0]
	      },

	      getHelpers: function(pos, type) {
	        var found = [];
	        if (!helpers.hasOwnProperty(type)) { return found }
	        var help = helpers[type], mode = this.getModeAt(pos);
	        if (typeof mode[type] == "string") {
	          if (help[mode[type]]) { found.push(help[mode[type]]); }
	        } else if (mode[type]) {
	          for (var i = 0; i < mode[type].length; i++) {
	            var val = help[mode[type][i]];
	            if (val) { found.push(val); }
	          }
	        } else if (mode.helperType && help[mode.helperType]) {
	          found.push(help[mode.helperType]);
	        } else if (help[mode.name]) {
	          found.push(help[mode.name]);
	        }
	        for (var i$1 = 0; i$1 < help._global.length; i$1++) {
	          var cur = help._global[i$1];
	          if (cur.pred(mode, this) && indexOf(found, cur.val) == -1)
	            { found.push(cur.val); }
	        }
	        return found
	      },

	      getStateAfter: function(line, precise) {
	        var doc = this.doc;
	        line = clipLine(doc, line == null ? doc.first + doc.size - 1: line);
	        return getContextBefore(this, line + 1, precise).state
	      },

	      cursorCoords: function(start, mode) {
	        var pos, range = this.doc.sel.primary();
	        if (start == null) { pos = range.head; }
	        else if (typeof start == "object") { pos = clipPos(this.doc, start); }
	        else { pos = start ? range.from() : range.to(); }
	        return cursorCoords(this, pos, mode || "page")
	      },

	      charCoords: function(pos, mode) {
	        return charCoords(this, clipPos(this.doc, pos), mode || "page")
	      },

	      coordsChar: function(coords, mode) {
	        coords = fromCoordSystem(this, coords, mode || "page");
	        return coordsChar(this, coords.left, coords.top)
	      },

	      lineAtHeight: function(height, mode) {
	        height = fromCoordSystem(this, {top: height, left: 0}, mode || "page").top;
	        return lineAtHeight(this.doc, height + this.display.viewOffset)
	      },
	      heightAtLine: function(line, mode, includeWidgets) {
	        var end = false, lineObj;
	        if (typeof line == "number") {
	          var last = this.doc.first + this.doc.size - 1;
	          if (line < this.doc.first) { line = this.doc.first; }
	          else if (line > last) { line = last; end = true; }
	          lineObj = getLine(this.doc, line);
	        } else {
	          lineObj = line;
	        }
	        return intoCoordSystem(this, lineObj, {top: 0, left: 0}, mode || "page", includeWidgets || end).top +
	          (end ? this.doc.height - heightAtLine(lineObj) : 0)
	      },

	      defaultTextHeight: function() { return textHeight(this.display) },
	      defaultCharWidth: function() { return charWidth(this.display) },

	      getViewport: function() { return {from: this.display.viewFrom, to: this.display.viewTo}},

	      addWidget: function(pos, node, scroll, vert, horiz) {
	        var display = this.display;
	        pos = cursorCoords(this, clipPos(this.doc, pos));
	        var top = pos.bottom, left = pos.left;
	        node.style.position = "absolute";
	        node.setAttribute("cm-ignore-events", "true");
	        this.display.input.setUneditable(node);
	        display.sizer.appendChild(node);
	        if (vert == "over") {
	          top = pos.top;
	        } else if (vert == "above" || vert == "near") {
	          var vspace = Math.max(display.wrapper.clientHeight, this.doc.height),
	          hspace = Math.max(display.sizer.clientWidth, display.lineSpace.clientWidth);
	          // Default to positioning above (if specified and possible); otherwise default to positioning below
	          if ((vert == 'above' || pos.bottom + node.offsetHeight > vspace) && pos.top > node.offsetHeight)
	            { top = pos.top - node.offsetHeight; }
	          else if (pos.bottom + node.offsetHeight <= vspace)
	            { top = pos.bottom; }
	          if (left + node.offsetWidth > hspace)
	            { left = hspace - node.offsetWidth; }
	        }
	        node.style.top = top + "px";
	        node.style.left = node.style.right = "";
	        if (horiz == "right") {
	          left = display.sizer.clientWidth - node.offsetWidth;
	          node.style.right = "0px";
	        } else {
	          if (horiz == "left") { left = 0; }
	          else if (horiz == "middle") { left = (display.sizer.clientWidth - node.offsetWidth) / 2; }
	          node.style.left = left + "px";
	        }
	        if (scroll)
	          { scrollIntoView(this, {left: left, top: top, right: left + node.offsetWidth, bottom: top + node.offsetHeight}); }
	      },

	      triggerOnKeyDown: methodOp(onKeyDown),
	      triggerOnKeyPress: methodOp(onKeyPress),
	      triggerOnKeyUp: onKeyUp,
	      triggerOnMouseDown: methodOp(onMouseDown),

	      execCommand: function(cmd) {
	        if (commands.hasOwnProperty(cmd))
	          { return commands[cmd].call(null, this) }
	      },

	      triggerElectric: methodOp(function(text) { triggerElectric(this, text); }),

	      findPosH: function(from, amount, unit, visually) {
	        var dir = 1;
	        if (amount < 0) { dir = -1; amount = -amount; }
	        var cur = clipPos(this.doc, from);
	        for (var i = 0; i < amount; ++i) {
	          cur = findPosH(this.doc, cur, dir, unit, visually);
	          if (cur.hitSide) { break }
	        }
	        return cur
	      },

	      moveH: methodOp(function(dir, unit) {
	        var this$1 = this;

	        this.extendSelectionsBy(function (range) {
	          if (this$1.display.shift || this$1.doc.extend || range.empty())
	            { return findPosH(this$1.doc, range.head, dir, unit, this$1.options.rtlMoveVisually) }
	          else
	            { return dir < 0 ? range.from() : range.to() }
	        }, sel_move);
	      }),

	      deleteH: methodOp(function(dir, unit) {
	        var sel = this.doc.sel, doc = this.doc;
	        if (sel.somethingSelected())
	          { doc.replaceSelection("", null, "+delete"); }
	        else
	          { deleteNearSelection(this, function (range) {
	            var other = findPosH(doc, range.head, dir, unit, false);
	            return dir < 0 ? {from: other, to: range.head} : {from: range.head, to: other}
	          }); }
	      }),

	      findPosV: function(from, amount, unit, goalColumn) {
	        var dir = 1, x = goalColumn;
	        if (amount < 0) { dir = -1; amount = -amount; }
	        var cur = clipPos(this.doc, from);
	        for (var i = 0; i < amount; ++i) {
	          var coords = cursorCoords(this, cur, "div");
	          if (x == null) { x = coords.left; }
	          else { coords.left = x; }
	          cur = findPosV(this, coords, dir, unit);
	          if (cur.hitSide) { break }
	        }
	        return cur
	      },

	      moveV: methodOp(function(dir, unit) {
	        var this$1 = this;

	        var doc = this.doc, goals = [];
	        var collapse = !this.display.shift && !doc.extend && doc.sel.somethingSelected();
	        doc.extendSelectionsBy(function (range) {
	          if (collapse)
	            { return dir < 0 ? range.from() : range.to() }
	          var headPos = cursorCoords(this$1, range.head, "div");
	          if (range.goalColumn != null) { headPos.left = range.goalColumn; }
	          goals.push(headPos.left);
	          var pos = findPosV(this$1, headPos, dir, unit);
	          if (unit == "page" && range == doc.sel.primary())
	            { addToScrollTop(this$1, charCoords(this$1, pos, "div").top - headPos.top); }
	          return pos
	        }, sel_move);
	        if (goals.length) { for (var i = 0; i < doc.sel.ranges.length; i++)
	          { doc.sel.ranges[i].goalColumn = goals[i]; } }
	      }),

	      // Find the word at the given position (as returned by coordsChar).
	      findWordAt: function(pos) {
	        var doc = this.doc, line = getLine(doc, pos.line).text;
	        var start = pos.ch, end = pos.ch;
	        if (line) {
	          var helper = this.getHelper(pos, "wordChars");
	          if ((pos.sticky == "before" || end == line.length) && start) { --start; } else { ++end; }
	          var startChar = line.charAt(start);
	          var check = isWordChar(startChar, helper)
	            ? function (ch) { return isWordChar(ch, helper); }
	            : /\s/.test(startChar) ? function (ch) { return /\s/.test(ch); }
	            : function (ch) { return (!/\s/.test(ch) && !isWordChar(ch)); };
	          while (start > 0 && check(line.charAt(start - 1))) { --start; }
	          while (end < line.length && check(line.charAt(end))) { ++end; }
	        }
	        return new Range(Pos(pos.line, start), Pos(pos.line, end))
	      },

	      toggleOverwrite: function(value) {
	        if (value != null && value == this.state.overwrite) { return }
	        if (this.state.overwrite = !this.state.overwrite)
	          { addClass(this.display.cursorDiv, "CodeMirror-overwrite"); }
	        else
	          { rmClass(this.display.cursorDiv, "CodeMirror-overwrite"); }

	        signal(this, "overwriteToggle", this, this.state.overwrite);
	      },
	      hasFocus: function() { return this.display.input.getField() == activeElt() },
	      isReadOnly: function() { return !!(this.options.readOnly || this.doc.cantEdit) },

	      scrollTo: methodOp(function (x, y) { scrollToCoords(this, x, y); }),
	      getScrollInfo: function() {
	        var scroller = this.display.scroller;
	        return {left: scroller.scrollLeft, top: scroller.scrollTop,
	                height: scroller.scrollHeight - scrollGap(this) - this.display.barHeight,
	                width: scroller.scrollWidth - scrollGap(this) - this.display.barWidth,
	                clientHeight: displayHeight(this), clientWidth: displayWidth(this)}
	      },

	      scrollIntoView: methodOp(function(range, margin) {
	        if (range == null) {
	          range = {from: this.doc.sel.primary().head, to: null};
	          if (margin == null) { margin = this.options.cursorScrollMargin; }
	        } else if (typeof range == "number") {
	          range = {from: Pos(range, 0), to: null};
	        } else if (range.from == null) {
	          range = {from: range, to: null};
	        }
	        if (!range.to) { range.to = range.from; }
	        range.margin = margin || 0;

	        if (range.from.line != null) {
	          scrollToRange(this, range);
	        } else {
	          scrollToCoordsRange(this, range.from, range.to, range.margin);
	        }
	      }),

	      setSize: methodOp(function(width, height) {
	        var this$1 = this;

	        var interpret = function (val) { return typeof val == "number" || /^\d+$/.test(String(val)) ? val + "px" : val; };
	        if (width != null) { this.display.wrapper.style.width = interpret(width); }
	        if (height != null) { this.display.wrapper.style.height = interpret(height); }
	        if (this.options.lineWrapping) { clearLineMeasurementCache(this); }
	        var lineNo = this.display.viewFrom;
	        this.doc.iter(lineNo, this.display.viewTo, function (line) {
	          if (line.widgets) { for (var i = 0; i < line.widgets.length; i++)
	            { if (line.widgets[i].noHScroll) { regLineChange(this$1, lineNo, "widget"); break } } }
	          ++lineNo;
	        });
	        this.curOp.forceUpdate = true;
	        signal(this, "refresh", this);
	      }),

	      operation: function(f){return runInOp(this, f)},
	      startOperation: function(){return startOperation(this)},
	      endOperation: function(){return endOperation(this)},

	      refresh: methodOp(function() {
	        var oldHeight = this.display.cachedTextHeight;
	        regChange(this);
	        this.curOp.forceUpdate = true;
	        clearCaches(this);
	        scrollToCoords(this, this.doc.scrollLeft, this.doc.scrollTop);
	        updateGutterSpace(this.display);
	        if (oldHeight == null || Math.abs(oldHeight - textHeight(this.display)) > .5)
	          { estimateLineHeights(this); }
	        signal(this, "refresh", this);
	      }),

	      swapDoc: methodOp(function(doc) {
	        var old = this.doc;
	        old.cm = null;
	        // Cancel the current text selection if any (#5821)
	        if (this.state.selectingText) { this.state.selectingText(); }
	        attachDoc(this, doc);
	        clearCaches(this);
	        this.display.input.reset();
	        scrollToCoords(this, doc.scrollLeft, doc.scrollTop);
	        this.curOp.forceScroll = true;
	        signalLater(this, "swapDoc", this, old);
	        return old
	      }),

	      phrase: function(phraseText) {
	        var phrases = this.options.phrases;
	        return phrases && Object.prototype.hasOwnProperty.call(phrases, phraseText) ? phrases[phraseText] : phraseText
	      },

	      getInputField: function(){return this.display.input.getField()},
	      getWrapperElement: function(){return this.display.wrapper},
	      getScrollerElement: function(){return this.display.scroller},
	      getGutterElement: function(){return this.display.gutters}
	    };
	    eventMixin(CodeMirror);

	    CodeMirror.registerHelper = function(type, name, value) {
	      if (!helpers.hasOwnProperty(type)) { helpers[type] = CodeMirror[type] = {_global: []}; }
	      helpers[type][name] = value;
	    };
	    CodeMirror.registerGlobalHelper = function(type, name, predicate, value) {
	      CodeMirror.registerHelper(type, name, value);
	      helpers[type]._global.push({pred: predicate, val: value});
	    };
	  }

	  // Used for horizontal relative motion. Dir is -1 or 1 (left or
	  // right), unit can be "char", "column" (like char, but doesn't
	  // cross line boundaries), "word" (across next word), or "group" (to
	  // the start of next group of word or non-word-non-whitespace
	  // chars). The visually param controls whether, in right-to-left
	  // text, direction 1 means to move towards the next index in the
	  // string, or towards the character to the right of the current
	  // position. The resulting position will have a hitSide=true
	  // property if it reached the end of the document.
	  function findPosH(doc, pos, dir, unit, visually) {
	    var oldPos = pos;
	    var origDir = dir;
	    var lineObj = getLine(doc, pos.line);
	    var lineDir = visually && doc.direction == "rtl" ? -dir : dir;
	    function findNextLine() {
	      var l = pos.line + lineDir;
	      if (l < doc.first || l >= doc.first + doc.size) { return false }
	      pos = new Pos(l, pos.ch, pos.sticky);
	      return lineObj = getLine(doc, l)
	    }
	    function moveOnce(boundToLine) {
	      var next;
	      if (visually) {
	        next = moveVisually(doc.cm, lineObj, pos, dir);
	      } else {
	        next = moveLogically(lineObj, pos, dir);
	      }
	      if (next == null) {
	        if (!boundToLine && findNextLine())
	          { pos = endOfLine(visually, doc.cm, lineObj, pos.line, lineDir); }
	        else
	          { return false }
	      } else {
	        pos = next;
	      }
	      return true
	    }

	    if (unit == "char") {
	      moveOnce();
	    } else if (unit == "column") {
	      moveOnce(true);
	    } else if (unit == "word" || unit == "group") {
	      var sawType = null, group = unit == "group";
	      var helper = doc.cm && doc.cm.getHelper(pos, "wordChars");
	      for (var first = true;; first = false) {
	        if (dir < 0 && !moveOnce(!first)) { break }
	        var cur = lineObj.text.charAt(pos.ch) || "\n";
	        var type = isWordChar(cur, helper) ? "w"
	          : group && cur == "\n" ? "n"
	          : !group || /\s/.test(cur) ? null
	          : "p";
	        if (group && !first && !type) { type = "s"; }
	        if (sawType && sawType != type) {
	          if (dir < 0) {dir = 1; moveOnce(); pos.sticky = "after";}
	          break
	        }

	        if (type) { sawType = type; }
	        if (dir > 0 && !moveOnce(!first)) { break }
	      }
	    }
	    var result = skipAtomic(doc, pos, oldPos, origDir, true);
	    if (equalCursorPos(oldPos, result)) { result.hitSide = true; }
	    return result
	  }

	  // For relative vertical movement. Dir may be -1 or 1. Unit can be
	  // "page" or "line". The resulting position will have a hitSide=true
	  // property if it reached the end of the document.
	  function findPosV(cm, pos, dir, unit) {
	    var doc = cm.doc, x = pos.left, y;
	    if (unit == "page") {
	      var pageSize = Math.min(cm.display.wrapper.clientHeight, window.innerHeight || document.documentElement.clientHeight);
	      var moveAmount = Math.max(pageSize - .5 * textHeight(cm.display), 3);
	      y = (dir > 0 ? pos.bottom : pos.top) + dir * moveAmount;

	    } else if (unit == "line") {
	      y = dir > 0 ? pos.bottom + 3 : pos.top - 3;
	    }
	    var target;
	    for (;;) {
	      target = coordsChar(cm, x, y);
	      if (!target.outside) { break }
	      if (dir < 0 ? y <= 0 : y >= doc.height) { target.hitSide = true; break }
	      y += dir * 5;
	    }
	    return target
	  }

	  // CONTENTEDITABLE INPUT STYLE

	  var ContentEditableInput = function(cm) {
	    this.cm = cm;
	    this.lastAnchorNode = this.lastAnchorOffset = this.lastFocusNode = this.lastFocusOffset = null;
	    this.polling = new Delayed();
	    this.composing = null;
	    this.gracePeriod = false;
	    this.readDOMTimeout = null;
	  };

	  ContentEditableInput.prototype.init = function (display) {
	      var this$1 = this;

	    var input = this, cm = input.cm;
	    var div = input.div = display.lineDiv;
	    disableBrowserMagic(div, cm.options.spellcheck, cm.options.autocorrect, cm.options.autocapitalize);

	    on(div, "paste", function (e) {
	      if (signalDOMEvent(cm, e) || handlePaste(e, cm)) { return }
	      // IE doesn't fire input events, so we schedule a read for the pasted content in this way
	      if (ie_version <= 11) { setTimeout(operation(cm, function () { return this$1.updateFromDOM(); }), 20); }
	    });

	    on(div, "compositionstart", function (e) {
	      this$1.composing = {data: e.data, done: false};
	    });
	    on(div, "compositionupdate", function (e) {
	      if (!this$1.composing) { this$1.composing = {data: e.data, done: false}; }
	    });
	    on(div, "compositionend", function (e) {
	      if (this$1.composing) {
	        if (e.data != this$1.composing.data) { this$1.readFromDOMSoon(); }
	        this$1.composing.done = true;
	      }
	    });

	    on(div, "touchstart", function () { return input.forceCompositionEnd(); });

	    on(div, "input", function () {
	      if (!this$1.composing) { this$1.readFromDOMSoon(); }
	    });

	    function onCopyCut(e) {
	      if (signalDOMEvent(cm, e)) { return }
	      if (cm.somethingSelected()) {
	        setLastCopied({lineWise: false, text: cm.getSelections()});
	        if (e.type == "cut") { cm.replaceSelection("", null, "cut"); }
	      } else if (!cm.options.lineWiseCopyCut) {
	        return
	      } else {
	        var ranges = copyableRanges(cm);
	        setLastCopied({lineWise: true, text: ranges.text});
	        if (e.type == "cut") {
	          cm.operation(function () {
	            cm.setSelections(ranges.ranges, 0, sel_dontScroll);
	            cm.replaceSelection("", null, "cut");
	          });
	        }
	      }
	      if (e.clipboardData) {
	        e.clipboardData.clearData();
	        var content = lastCopied.text.join("\n");
	        // iOS exposes the clipboard API, but seems to discard content inserted into it
	        e.clipboardData.setData("Text", content);
	        if (e.clipboardData.getData("Text") == content) {
	          e.preventDefault();
	          return
	        }
	      }
	      // Old-fashioned briefly-focus-a-textarea hack
	      var kludge = hiddenTextarea(), te = kludge.firstChild;
	      cm.display.lineSpace.insertBefore(kludge, cm.display.lineSpace.firstChild);
	      te.value = lastCopied.text.join("\n");
	      var hadFocus = document.activeElement;
	      selectInput(te);
	      setTimeout(function () {
	        cm.display.lineSpace.removeChild(kludge);
	        hadFocus.focus();
	        if (hadFocus == div) { input.showPrimarySelection(); }
	      }, 50);
	    }
	    on(div, "copy", onCopyCut);
	    on(div, "cut", onCopyCut);
	  };

	  ContentEditableInput.prototype.prepareSelection = function () {
	    var result = prepareSelection(this.cm, false);
	    result.focus = this.cm.state.focused;
	    return result
	  };

	  ContentEditableInput.prototype.showSelection = function (info, takeFocus) {
	    if (!info || !this.cm.display.view.length) { return }
	    if (info.focus || takeFocus) { this.showPrimarySelection(); }
	    this.showMultipleSelections(info);
	  };

	  ContentEditableInput.prototype.getSelection = function () {
	    return this.cm.display.wrapper.ownerDocument.getSelection()
	  };

	  ContentEditableInput.prototype.showPrimarySelection = function () {
	    var sel = this.getSelection(), cm = this.cm, prim = cm.doc.sel.primary();
	    var from = prim.from(), to = prim.to();

	    if (cm.display.viewTo == cm.display.viewFrom || from.line >= cm.display.viewTo || to.line < cm.display.viewFrom) {
	      sel.removeAllRanges();
	      return
	    }

	    var curAnchor = domToPos(cm, sel.anchorNode, sel.anchorOffset);
	    var curFocus = domToPos(cm, sel.focusNode, sel.focusOffset);
	    if (curAnchor && !curAnchor.bad && curFocus && !curFocus.bad &&
	        cmp(minPos(curAnchor, curFocus), from) == 0 &&
	        cmp(maxPos(curAnchor, curFocus), to) == 0)
	      { return }

	    var view = cm.display.view;
	    var start = (from.line >= cm.display.viewFrom && posToDOM(cm, from)) ||
	        {node: view[0].measure.map[2], offset: 0};
	    var end = to.line < cm.display.viewTo && posToDOM(cm, to);
	    if (!end) {
	      var measure = view[view.length - 1].measure;
	      var map = measure.maps ? measure.maps[measure.maps.length - 1] : measure.map;
	      end = {node: map[map.length - 1], offset: map[map.length - 2] - map[map.length - 3]};
	    }

	    if (!start || !end) {
	      sel.removeAllRanges();
	      return
	    }

	    var old = sel.rangeCount && sel.getRangeAt(0), rng;
	    try { rng = range(start.node, start.offset, end.offset, end.node); }
	    catch(e) {} // Our model of the DOM might be outdated, in which case the range we try to set can be impossible
	    if (rng) {
	      if (!gecko && cm.state.focused) {
	        sel.collapse(start.node, start.offset);
	        if (!rng.collapsed) {
	          sel.removeAllRanges();
	          sel.addRange(rng);
	        }
	      } else {
	        sel.removeAllRanges();
	        sel.addRange(rng);
	      }
	      if (old && sel.anchorNode == null) { sel.addRange(old); }
	      else if (gecko) { this.startGracePeriod(); }
	    }
	    this.rememberSelection();
	  };

	  ContentEditableInput.prototype.startGracePeriod = function () {
	      var this$1 = this;

	    clearTimeout(this.gracePeriod);
	    this.gracePeriod = setTimeout(function () {
	      this$1.gracePeriod = false;
	      if (this$1.selectionChanged())
	        { this$1.cm.operation(function () { return this$1.cm.curOp.selectionChanged = true; }); }
	    }, 20);
	  };

	  ContentEditableInput.prototype.showMultipleSelections = function (info) {
	    removeChildrenAndAdd(this.cm.display.cursorDiv, info.cursors);
	    removeChildrenAndAdd(this.cm.display.selectionDiv, info.selection);
	  };

	  ContentEditableInput.prototype.rememberSelection = function () {
	    var sel = this.getSelection();
	    this.lastAnchorNode = sel.anchorNode; this.lastAnchorOffset = sel.anchorOffset;
	    this.lastFocusNode = sel.focusNode; this.lastFocusOffset = sel.focusOffset;
	  };

	  ContentEditableInput.prototype.selectionInEditor = function () {
	    var sel = this.getSelection();
	    if (!sel.rangeCount) { return false }
	    var node = sel.getRangeAt(0).commonAncestorContainer;
	    return contains(this.div, node)
	  };

	  ContentEditableInput.prototype.focus = function () {
	    if (this.cm.options.readOnly != "nocursor") {
	      if (!this.selectionInEditor())
	        { this.showSelection(this.prepareSelection(), true); }
	      this.div.focus();
	    }
	  };
	  ContentEditableInput.prototype.blur = function () { this.div.blur(); };
	  ContentEditableInput.prototype.getField = function () { return this.div };

	  ContentEditableInput.prototype.supportsTouch = function () { return true };

	  ContentEditableInput.prototype.receivedFocus = function () {
	    var input = this;
	    if (this.selectionInEditor())
	      { this.pollSelection(); }
	    else
	      { runInOp(this.cm, function () { return input.cm.curOp.selectionChanged = true; }); }

	    function poll() {
	      if (input.cm.state.focused) {
	        input.pollSelection();
	        input.polling.set(input.cm.options.pollInterval, poll);
	      }
	    }
	    this.polling.set(this.cm.options.pollInterval, poll);
	  };

	  ContentEditableInput.prototype.selectionChanged = function () {
	    var sel = this.getSelection();
	    return sel.anchorNode != this.lastAnchorNode || sel.anchorOffset != this.lastAnchorOffset ||
	      sel.focusNode != this.lastFocusNode || sel.focusOffset != this.lastFocusOffset
	  };

	  ContentEditableInput.prototype.pollSelection = function () {
	    if (this.readDOMTimeout != null || this.gracePeriod || !this.selectionChanged()) { return }
	    var sel = this.getSelection(), cm = this.cm;
	    // On Android Chrome (version 56, at least), backspacing into an
	    // uneditable block element will put the cursor in that element,
	    // and then, because it's not editable, hide the virtual keyboard.
	    // Because Android doesn't allow us to actually detect backspace
	    // presses in a sane way, this code checks for when that happens
	    // and simulates a backspace press in this case.
	    if (android && chrome && this.cm.display.gutterSpecs.length && isInGutter(sel.anchorNode)) {
	      this.cm.triggerOnKeyDown({type: "keydown", keyCode: 8, preventDefault: Math.abs});
	      this.blur();
	      this.focus();
	      return
	    }
	    if (this.composing) { return }
	    this.rememberSelection();
	    var anchor = domToPos(cm, sel.anchorNode, sel.anchorOffset);
	    var head = domToPos(cm, sel.focusNode, sel.focusOffset);
	    if (anchor && head) { runInOp(cm, function () {
	      setSelection(cm.doc, simpleSelection(anchor, head), sel_dontScroll);
	      if (anchor.bad || head.bad) { cm.curOp.selectionChanged = true; }
	    }); }
	  };

	  ContentEditableInput.prototype.pollContent = function () {
	    if (this.readDOMTimeout != null) {
	      clearTimeout(this.readDOMTimeout);
	      this.readDOMTimeout = null;
	    }

	    var cm = this.cm, display = cm.display, sel = cm.doc.sel.primary();
	    var from = sel.from(), to = sel.to();
	    if (from.ch == 0 && from.line > cm.firstLine())
	      { from = Pos(from.line - 1, getLine(cm.doc, from.line - 1).length); }
	    if (to.ch == getLine(cm.doc, to.line).text.length && to.line < cm.lastLine())
	      { to = Pos(to.line + 1, 0); }
	    if (from.line < display.viewFrom || to.line > display.viewTo - 1) { return false }

	    var fromIndex, fromLine, fromNode;
	    if (from.line == display.viewFrom || (fromIndex = findViewIndex(cm, from.line)) == 0) {
	      fromLine = lineNo(display.view[0].line);
	      fromNode = display.view[0].node;
	    } else {
	      fromLine = lineNo(display.view[fromIndex].line);
	      fromNode = display.view[fromIndex - 1].node.nextSibling;
	    }
	    var toIndex = findViewIndex(cm, to.line);
	    var toLine, toNode;
	    if (toIndex == display.view.length - 1) {
	      toLine = display.viewTo - 1;
	      toNode = display.lineDiv.lastChild;
	    } else {
	      toLine = lineNo(display.view[toIndex + 1].line) - 1;
	      toNode = display.view[toIndex + 1].node.previousSibling;
	    }

	    if (!fromNode) { return false }
	    var newText = cm.doc.splitLines(domTextBetween(cm, fromNode, toNode, fromLine, toLine));
	    var oldText = getBetween(cm.doc, Pos(fromLine, 0), Pos(toLine, getLine(cm.doc, toLine).text.length));
	    while (newText.length > 1 && oldText.length > 1) {
	      if (lst(newText) == lst(oldText)) { newText.pop(); oldText.pop(); toLine--; }
	      else if (newText[0] == oldText[0]) { newText.shift(); oldText.shift(); fromLine++; }
	      else { break }
	    }

	    var cutFront = 0, cutEnd = 0;
	    var newTop = newText[0], oldTop = oldText[0], maxCutFront = Math.min(newTop.length, oldTop.length);
	    while (cutFront < maxCutFront && newTop.charCodeAt(cutFront) == oldTop.charCodeAt(cutFront))
	      { ++cutFront; }
	    var newBot = lst(newText), oldBot = lst(oldText);
	    var maxCutEnd = Math.min(newBot.length - (newText.length == 1 ? cutFront : 0),
	                             oldBot.length - (oldText.length == 1 ? cutFront : 0));
	    while (cutEnd < maxCutEnd &&
	           newBot.charCodeAt(newBot.length - cutEnd - 1) == oldBot.charCodeAt(oldBot.length - cutEnd - 1))
	      { ++cutEnd; }
	    // Try to move start of change to start of selection if ambiguous
	    if (newText.length == 1 && oldText.length == 1 && fromLine == from.line) {
	      while (cutFront && cutFront > from.ch &&
	             newBot.charCodeAt(newBot.length - cutEnd - 1) == oldBot.charCodeAt(oldBot.length - cutEnd - 1)) {
	        cutFront--;
	        cutEnd++;
	      }
	    }

	    newText[newText.length - 1] = newBot.slice(0, newBot.length - cutEnd).replace(/^\u200b+/, "");
	    newText[0] = newText[0].slice(cutFront).replace(/\u200b+$/, "");

	    var chFrom = Pos(fromLine, cutFront);
	    var chTo = Pos(toLine, oldText.length ? lst(oldText).length - cutEnd : 0);
	    if (newText.length > 1 || newText[0] || cmp(chFrom, chTo)) {
	      replaceRange(cm.doc, newText, chFrom, chTo, "+input");
	      return true
	    }
	  };

	  ContentEditableInput.prototype.ensurePolled = function () {
	    this.forceCompositionEnd();
	  };
	  ContentEditableInput.prototype.reset = function () {
	    this.forceCompositionEnd();
	  };
	  ContentEditableInput.prototype.forceCompositionEnd = function () {
	    if (!this.composing) { return }
	    clearTimeout(this.readDOMTimeout);
	    this.composing = null;
	    this.updateFromDOM();
	    this.div.blur();
	    this.div.focus();
	  };
	  ContentEditableInput.prototype.readFromDOMSoon = function () {
	      var this$1 = this;

	    if (this.readDOMTimeout != null) { return }
	    this.readDOMTimeout = setTimeout(function () {
	      this$1.readDOMTimeout = null;
	      if (this$1.composing) {
	        if (this$1.composing.done) { this$1.composing = null; }
	        else { return }
	      }
	      this$1.updateFromDOM();
	    }, 80);
	  };

	  ContentEditableInput.prototype.updateFromDOM = function () {
	      var this$1 = this;

	    if (this.cm.isReadOnly() || !this.pollContent())
	      { runInOp(this.cm, function () { return regChange(this$1.cm); }); }
	  };

	  ContentEditableInput.prototype.setUneditable = function (node) {
	    node.contentEditable = "false";
	  };

	  ContentEditableInput.prototype.onKeyPress = function (e) {
	    if (e.charCode == 0 || this.composing) { return }
	    e.preventDefault();
	    if (!this.cm.isReadOnly())
	      { operation(this.cm, applyTextInput)(this.cm, String.fromCharCode(e.charCode == null ? e.keyCode : e.charCode), 0); }
	  };

	  ContentEditableInput.prototype.readOnlyChanged = function (val) {
	    this.div.contentEditable = String(val != "nocursor");
	  };

	  ContentEditableInput.prototype.onContextMenu = function () {};
	  ContentEditableInput.prototype.resetPosition = function () {};

	  ContentEditableInput.prototype.needsContentAttribute = true;

	  function posToDOM(cm, pos) {
	    var view = findViewForLine(cm, pos.line);
	    if (!view || view.hidden) { return null }
	    var line = getLine(cm.doc, pos.line);
	    var info = mapFromLineView(view, line, pos.line);

	    var order = getOrder(line, cm.doc.direction), side = "left";
	    if (order) {
	      var partPos = getBidiPartAt(order, pos.ch);
	      side = partPos % 2 ? "right" : "left";
	    }
	    var result = nodeAndOffsetInLineMap(info.map, pos.ch, side);
	    result.offset = result.collapse == "right" ? result.end : result.start;
	    return result
	  }

	  function isInGutter(node) {
	    for (var scan = node; scan; scan = scan.parentNode)
	      { if (/CodeMirror-gutter-wrapper/.test(scan.className)) { return true } }
	    return false
	  }

	  function badPos(pos, bad) { if (bad) { pos.bad = true; } return pos }

	  function domTextBetween(cm, from, to, fromLine, toLine) {
	    var text = "", closing = false, lineSep = cm.doc.lineSeparator(), extraLinebreak = false;
	    function recognizeMarker(id) { return function (marker) { return marker.id == id; } }
	    function close() {
	      if (closing) {
	        text += lineSep;
	        if (extraLinebreak) { text += lineSep; }
	        closing = extraLinebreak = false;
	      }
	    }
	    function addText(str) {
	      if (str) {
	        close();
	        text += str;
	      }
	    }
	    function walk(node) {
	      if (node.nodeType == 1) {
	        var cmText = node.getAttribute("cm-text");
	        if (cmText) {
	          addText(cmText);
	          return
	        }
	        var markerID = node.getAttribute("cm-marker"), range;
	        if (markerID) {
	          var found = cm.findMarks(Pos(fromLine, 0), Pos(toLine + 1, 0), recognizeMarker(+markerID));
	          if (found.length && (range = found[0].find(0)))
	            { addText(getBetween(cm.doc, range.from, range.to).join(lineSep)); }
	          return
	        }
	        if (node.getAttribute("contenteditable") == "false") { return }
	        var isBlock = /^(pre|div|p|li|table|br)$/i.test(node.nodeName);
	        if (!/^br$/i.test(node.nodeName) && node.textContent.length == 0) { return }

	        if (isBlock) { close(); }
	        for (var i = 0; i < node.childNodes.length; i++)
	          { walk(node.childNodes[i]); }

	        if (/^(pre|p)$/i.test(node.nodeName)) { extraLinebreak = true; }
	        if (isBlock) { closing = true; }
	      } else if (node.nodeType == 3) {
	        addText(node.nodeValue.replace(/\u200b/g, "").replace(/\u00a0/g, " "));
	      }
	    }
	    for (;;) {
	      walk(from);
	      if (from == to) { break }
	      from = from.nextSibling;
	      extraLinebreak = false;
	    }
	    return text
	  }

	  function domToPos(cm, node, offset) {
	    var lineNode;
	    if (node == cm.display.lineDiv) {
	      lineNode = cm.display.lineDiv.childNodes[offset];
	      if (!lineNode) { return badPos(cm.clipPos(Pos(cm.display.viewTo - 1)), true) }
	      node = null; offset = 0;
	    } else {
	      for (lineNode = node;; lineNode = lineNode.parentNode) {
	        if (!lineNode || lineNode == cm.display.lineDiv) { return null }
	        if (lineNode.parentNode && lineNode.parentNode == cm.display.lineDiv) { break }
	      }
	    }
	    for (var i = 0; i < cm.display.view.length; i++) {
	      var lineView = cm.display.view[i];
	      if (lineView.node == lineNode)
	        { return locateNodeInLineView(lineView, node, offset) }
	    }
	  }

	  function locateNodeInLineView(lineView, node, offset) {
	    var wrapper = lineView.text.firstChild, bad = false;
	    if (!node || !contains(wrapper, node)) { return badPos(Pos(lineNo(lineView.line), 0), true) }
	    if (node == wrapper) {
	      bad = true;
	      node = wrapper.childNodes[offset];
	      offset = 0;
	      if (!node) {
	        var line = lineView.rest ? lst(lineView.rest) : lineView.line;
	        return badPos(Pos(lineNo(line), line.text.length), bad)
	      }
	    }

	    var textNode = node.nodeType == 3 ? node : null, topNode = node;
	    if (!textNode && node.childNodes.length == 1 && node.firstChild.nodeType == 3) {
	      textNode = node.firstChild;
	      if (offset) { offset = textNode.nodeValue.length; }
	    }
	    while (topNode.parentNode != wrapper) { topNode = topNode.parentNode; }
	    var measure = lineView.measure, maps = measure.maps;

	    function find(textNode, topNode, offset) {
	      for (var i = -1; i < (maps ? maps.length : 0); i++) {
	        var map = i < 0 ? measure.map : maps[i];
	        for (var j = 0; j < map.length; j += 3) {
	          var curNode = map[j + 2];
	          if (curNode == textNode || curNode == topNode) {
	            var line = lineNo(i < 0 ? lineView.line : lineView.rest[i]);
	            var ch = map[j] + offset;
	            if (offset < 0 || curNode != textNode) { ch = map[j + (offset ? 1 : 0)]; }
	            return Pos(line, ch)
	          }
	        }
	      }
	    }
	    var found = find(textNode, topNode, offset);
	    if (found) { return badPos(found, bad) }

	    // FIXME this is all really shaky. might handle the few cases it needs to handle, but likely to cause problems
	    for (var after = topNode.nextSibling, dist = textNode ? textNode.nodeValue.length - offset : 0; after; after = after.nextSibling) {
	      found = find(after, after.firstChild, 0);
	      if (found)
	        { return badPos(Pos(found.line, found.ch - dist), bad) }
	      else
	        { dist += after.textContent.length; }
	    }
	    for (var before = topNode.previousSibling, dist$1 = offset; before; before = before.previousSibling) {
	      found = find(before, before.firstChild, -1);
	      if (found)
	        { return badPos(Pos(found.line, found.ch + dist$1), bad) }
	      else
	        { dist$1 += before.textContent.length; }
	    }
	  }

	  // TEXTAREA INPUT STYLE

	  var TextareaInput = function(cm) {
	    this.cm = cm;
	    // See input.poll and input.reset
	    this.prevInput = "";

	    // Flag that indicates whether we expect input to appear real soon
	    // now (after some event like 'keypress' or 'input') and are
	    // polling intensively.
	    this.pollingFast = false;
	    // Self-resetting timeout for the poller
	    this.polling = new Delayed();
	    // Used to work around IE issue with selection being forgotten when focus moves away from textarea
	    this.hasSelection = false;
	    this.composing = null;
	  };

	  TextareaInput.prototype.init = function (display) {
	      var this$1 = this;

	    var input = this, cm = this.cm;
	    this.createField(display);
	    var te = this.textarea;

	    display.wrapper.insertBefore(this.wrapper, display.wrapper.firstChild);

	    // Needed to hide big blue blinking cursor on Mobile Safari (doesn't seem to work in iOS 8 anymore)
	    if (ios) { te.style.width = "0px"; }

	    on(te, "input", function () {
	      if (ie && ie_version >= 9 && this$1.hasSelection) { this$1.hasSelection = null; }
	      input.poll();
	    });

	    on(te, "paste", function (e) {
	      if (signalDOMEvent(cm, e) || handlePaste(e, cm)) { return }

	      cm.state.pasteIncoming = +new Date;
	      input.fastPoll();
	    });

	    function prepareCopyCut(e) {
	      if (signalDOMEvent(cm, e)) { return }
	      if (cm.somethingSelected()) {
	        setLastCopied({lineWise: false, text: cm.getSelections()});
	      } else if (!cm.options.lineWiseCopyCut) {
	        return
	      } else {
	        var ranges = copyableRanges(cm);
	        setLastCopied({lineWise: true, text: ranges.text});
	        if (e.type == "cut") {
	          cm.setSelections(ranges.ranges, null, sel_dontScroll);
	        } else {
	          input.prevInput = "";
	          te.value = ranges.text.join("\n");
	          selectInput(te);
	        }
	      }
	      if (e.type == "cut") { cm.state.cutIncoming = +new Date; }
	    }
	    on(te, "cut", prepareCopyCut);
	    on(te, "copy", prepareCopyCut);

	    on(display.scroller, "paste", function (e) {
	      if (eventInWidget(display, e) || signalDOMEvent(cm, e)) { return }
	      if (!te.dispatchEvent) {
	        cm.state.pasteIncoming = +new Date;
	        input.focus();
	        return
	      }

	      // Pass the `paste` event to the textarea so it's handled by its event listener.
	      var event = new Event("paste");
	      event.clipboardData = e.clipboardData;
	      te.dispatchEvent(event);
	    });

	    // Prevent normal selection in the editor (we handle our own)
	    on(display.lineSpace, "selectstart", function (e) {
	      if (!eventInWidget(display, e)) { e_preventDefault(e); }
	    });

	    on(te, "compositionstart", function () {
	      var start = cm.getCursor("from");
	      if (input.composing) { input.composing.range.clear(); }
	      input.composing = {
	        start: start,
	        range: cm.markText(start, cm.getCursor("to"), {className: "CodeMirror-composing"})
	      };
	    });
	    on(te, "compositionend", function () {
	      if (input.composing) {
	        input.poll();
	        input.composing.range.clear();
	        input.composing = null;
	      }
	    });
	  };

	  TextareaInput.prototype.createField = function (_display) {
	    // Wraps and hides input textarea
	    this.wrapper = hiddenTextarea();
	    // The semihidden textarea that is focused when the editor is
	    // focused, and receives input.
	    this.textarea = this.wrapper.firstChild;
	  };

	  TextareaInput.prototype.prepareSelection = function () {
	    // Redraw the selection and/or cursor
	    var cm = this.cm, display = cm.display, doc = cm.doc;
	    var result = prepareSelection(cm);

	    // Move the hidden textarea near the cursor to prevent scrolling artifacts
	    if (cm.options.moveInputWithCursor) {
	      var headPos = cursorCoords(cm, doc.sel.primary().head, "div");
	      var wrapOff = display.wrapper.getBoundingClientRect(), lineOff = display.lineDiv.getBoundingClientRect();
	      result.teTop = Math.max(0, Math.min(display.wrapper.clientHeight - 10,
	                                          headPos.top + lineOff.top - wrapOff.top));
	      result.teLeft = Math.max(0, Math.min(display.wrapper.clientWidth - 10,
	                                           headPos.left + lineOff.left - wrapOff.left));
	    }

	    return result
	  };

	  TextareaInput.prototype.showSelection = function (drawn) {
	    var cm = this.cm, display = cm.display;
	    removeChildrenAndAdd(display.cursorDiv, drawn.cursors);
	    removeChildrenAndAdd(display.selectionDiv, drawn.selection);
	    if (drawn.teTop != null) {
	      this.wrapper.style.top = drawn.teTop + "px";
	      this.wrapper.style.left = drawn.teLeft + "px";
	    }
	  };

	  // Reset the input to correspond to the selection (or to be empty,
	  // when not typing and nothing is selected)
	  TextareaInput.prototype.reset = function (typing) {
	    if (this.contextMenuPending || this.composing) { return }
	    var cm = this.cm;
	    if (cm.somethingSelected()) {
	      this.prevInput = "";
	      var content = cm.getSelection();
	      this.textarea.value = content;
	      if (cm.state.focused) { selectInput(this.textarea); }
	      if (ie && ie_version >= 9) { this.hasSelection = content; }
	    } else if (!typing) {
	      this.prevInput = this.textarea.value = "";
	      if (ie && ie_version >= 9) { this.hasSelection = null; }
	    }
	  };

	  TextareaInput.prototype.getField = function () { return this.textarea };

	  TextareaInput.prototype.supportsTouch = function () { return false };

	  TextareaInput.prototype.focus = function () {
	    if (this.cm.options.readOnly != "nocursor" && (!mobile || activeElt() != this.textarea)) {
	      try { this.textarea.focus(); }
	      catch (e) {} // IE8 will throw if the textarea is display: none or not in DOM
	    }
	  };

	  TextareaInput.prototype.blur = function () { this.textarea.blur(); };

	  TextareaInput.prototype.resetPosition = function () {
	    this.wrapper.style.top = this.wrapper.style.left = 0;
	  };

	  TextareaInput.prototype.receivedFocus = function () { this.slowPoll(); };

	  // Poll for input changes, using the normal rate of polling. This
	  // runs as long as the editor is focused.
	  TextareaInput.prototype.slowPoll = function () {
	      var this$1 = this;

	    if (this.pollingFast) { return }
	    this.polling.set(this.cm.options.pollInterval, function () {
	      this$1.poll();
	      if (this$1.cm.state.focused) { this$1.slowPoll(); }
	    });
	  };

	  // When an event has just come in that is likely to add or change
	  // something in the input textarea, we poll faster, to ensure that
	  // the change appears on the screen quickly.
	  TextareaInput.prototype.fastPoll = function () {
	    var missed = false, input = this;
	    input.pollingFast = true;
	    function p() {
	      var changed = input.poll();
	      if (!changed && !missed) {missed = true; input.polling.set(60, p);}
	      else {input.pollingFast = false; input.slowPoll();}
	    }
	    input.polling.set(20, p);
	  };

	  // Read input from the textarea, and update the document to match.
	  // When something is selected, it is present in the textarea, and
	  // selected (unless it is huge, in which case a placeholder is
	  // used). When nothing is selected, the cursor sits after previously
	  // seen text (can be empty), which is stored in prevInput (we must
	  // not reset the textarea when typing, because that breaks IME).
	  TextareaInput.prototype.poll = function () {
	      var this$1 = this;

	    var cm = this.cm, input = this.textarea, prevInput = this.prevInput;
	    // Since this is called a *lot*, try to bail out as cheaply as
	    // possible when it is clear that nothing happened. hasSelection
	    // will be the case when there is a lot of text in the textarea,
	    // in which case reading its value would be expensive.
	    if (this.contextMenuPending || !cm.state.focused ||
	        (hasSelection(input) && !prevInput && !this.composing) ||
	        cm.isReadOnly() || cm.options.disableInput || cm.state.keySeq)
	      { return false }

	    var text = input.value;
	    // If nothing changed, bail.
	    if (text == prevInput && !cm.somethingSelected()) { return false }
	    // Work around nonsensical selection resetting in IE9/10, and
	    // inexplicable appearance of private area unicode characters on
	    // some key combos in Mac (#2689).
	    if (ie && ie_version >= 9 && this.hasSelection === text ||
	        mac && /[\uf700-\uf7ff]/.test(text)) {
	      cm.display.input.reset();
	      return false
	    }

	    if (cm.doc.sel == cm.display.selForContextMenu) {
	      var first = text.charCodeAt(0);
	      if (first == 0x200b && !prevInput) { prevInput = "\u200b"; }
	      if (first == 0x21da) { this.reset(); return this.cm.execCommand("undo") }
	    }
	    // Find the part of the input that is actually new
	    var same = 0, l = Math.min(prevInput.length, text.length);
	    while (same < l && prevInput.charCodeAt(same) == text.charCodeAt(same)) { ++same; }

	    runInOp(cm, function () {
	      applyTextInput(cm, text.slice(same), prevInput.length - same,
	                     null, this$1.composing ? "*compose" : null);

	      // Don't leave long text in the textarea, since it makes further polling slow
	      if (text.length > 1000 || text.indexOf("\n") > -1) { input.value = this$1.prevInput = ""; }
	      else { this$1.prevInput = text; }

	      if (this$1.composing) {
	        this$1.composing.range.clear();
	        this$1.composing.range = cm.markText(this$1.composing.start, cm.getCursor("to"),
	                                           {className: "CodeMirror-composing"});
	      }
	    });
	    return true
	  };

	  TextareaInput.prototype.ensurePolled = function () {
	    if (this.pollingFast && this.poll()) { this.pollingFast = false; }
	  };

	  TextareaInput.prototype.onKeyPress = function () {
	    if (ie && ie_version >= 9) { this.hasSelection = null; }
	    this.fastPoll();
	  };

	  TextareaInput.prototype.onContextMenu = function (e) {
	    var input = this, cm = input.cm, display = cm.display, te = input.textarea;
	    if (input.contextMenuPending) { input.contextMenuPending(); }
	    var pos = posFromMouse(cm, e), scrollPos = display.scroller.scrollTop;
	    if (!pos || presto) { return } // Opera is difficult.

	    // Reset the current text selection only if the click is done outside of the selection
	    // and 'resetSelectionOnContextMenu' option is true.
	    var reset = cm.options.resetSelectionOnContextMenu;
	    if (reset && cm.doc.sel.contains(pos) == -1)
	      { operation(cm, setSelection)(cm.doc, simpleSelection(pos), sel_dontScroll); }

	    var oldCSS = te.style.cssText, oldWrapperCSS = input.wrapper.style.cssText;
	    var wrapperBox = input.wrapper.offsetParent.getBoundingClientRect();
	    input.wrapper.style.cssText = "position: static";
	    te.style.cssText = "position: absolute; width: 30px; height: 30px;\n      top: " + (e.clientY - wrapperBox.top - 5) + "px; left: " + (e.clientX - wrapperBox.left - 5) + "px;\n      z-index: 1000; background: " + (ie ? "rgba(255, 255, 255, .05)" : "transparent") + ";\n      outline: none; border-width: 0; outline: none; overflow: hidden; opacity: .05; filter: alpha(opacity=5);";
	    var oldScrollY;
	    if (webkit) { oldScrollY = window.scrollY; } // Work around Chrome issue (#2712)
	    display.input.focus();
	    if (webkit) { window.scrollTo(null, oldScrollY); }
	    display.input.reset();
	    // Adds "Select all" to context menu in FF
	    if (!cm.somethingSelected()) { te.value = input.prevInput = " "; }
	    input.contextMenuPending = rehide;
	    display.selForContextMenu = cm.doc.sel;
	    clearTimeout(display.detectingSelectAll);

	    // Select-all will be greyed out if there's nothing to select, so
	    // this adds a zero-width space so that we can later check whether
	    // it got selected.
	    function prepareSelectAllHack() {
	      if (te.selectionStart != null) {
	        var selected = cm.somethingSelected();
	        var extval = "\u200b" + (selected ? te.value : "");
	        te.value = "\u21da"; // Used to catch context-menu undo
	        te.value = extval;
	        input.prevInput = selected ? "" : "\u200b";
	        te.selectionStart = 1; te.selectionEnd = extval.length;
	        // Re-set this, in case some other handler touched the
	        // selection in the meantime.
	        display.selForContextMenu = cm.doc.sel;
	      }
	    }
	    function rehide() {
	      if (input.contextMenuPending != rehide) { return }
	      input.contextMenuPending = false;
	      input.wrapper.style.cssText = oldWrapperCSS;
	      te.style.cssText = oldCSS;
	      if (ie && ie_version < 9) { display.scrollbars.setScrollTop(display.scroller.scrollTop = scrollPos); }

	      // Try to detect the user choosing select-all
	      if (te.selectionStart != null) {
	        if (!ie || (ie && ie_version < 9)) { prepareSelectAllHack(); }
	        var i = 0, poll = function () {
	          if (display.selForContextMenu == cm.doc.sel && te.selectionStart == 0 &&
	              te.selectionEnd > 0 && input.prevInput == "\u200b") {
	            operation(cm, selectAll)(cm);
	          } else if (i++ < 10) {
	            display.detectingSelectAll = setTimeout(poll, 500);
	          } else {
	            display.selForContextMenu = null;
	            display.input.reset();
	          }
	        };
	        display.detectingSelectAll = setTimeout(poll, 200);
	      }
	    }

	    if (ie && ie_version >= 9) { prepareSelectAllHack(); }
	    if (captureRightClick) {
	      e_stop(e);
	      var mouseup = function () {
	        off(window, "mouseup", mouseup);
	        setTimeout(rehide, 20);
	      };
	      on(window, "mouseup", mouseup);
	    } else {
	      setTimeout(rehide, 50);
	    }
	  };

	  TextareaInput.prototype.readOnlyChanged = function (val) {
	    if (!val) { this.reset(); }
	    this.textarea.disabled = val == "nocursor";
	  };

	  TextareaInput.prototype.setUneditable = function () {};

	  TextareaInput.prototype.needsContentAttribute = false;

	  function fromTextArea(textarea, options) {
	    options = options ? copyObj(options) : {};
	    options.value = textarea.value;
	    if (!options.tabindex && textarea.tabIndex)
	      { options.tabindex = textarea.tabIndex; }
	    if (!options.placeholder && textarea.placeholder)
	      { options.placeholder = textarea.placeholder; }
	    // Set autofocus to true if this textarea is focused, or if it has
	    // autofocus and no other element is focused.
	    if (options.autofocus == null) {
	      var hasFocus = activeElt();
	      options.autofocus = hasFocus == textarea ||
	        textarea.getAttribute("autofocus") != null && hasFocus == document.body;
	    }

	    function save() {textarea.value = cm.getValue();}

	    var realSubmit;
	    if (textarea.form) {
	      on(textarea.form, "submit", save);
	      // Deplorable hack to make the submit method do the right thing.
	      if (!options.leaveSubmitMethodAlone) {
	        var form = textarea.form;
	        realSubmit = form.submit;
	        try {
	          var wrappedSubmit = form.submit = function () {
	            save();
	            form.submit = realSubmit;
	            form.submit();
	            form.submit = wrappedSubmit;
	          };
	        } catch(e) {}
	      }
	    }

	    options.finishInit = function (cm) {
	      cm.save = save;
	      cm.getTextArea = function () { return textarea; };
	      cm.toTextArea = function () {
	        cm.toTextArea = isNaN; // Prevent this from being ran twice
	        save();
	        textarea.parentNode.removeChild(cm.getWrapperElement());
	        textarea.style.display = "";
	        if (textarea.form) {
	          off(textarea.form, "submit", save);
	          if (!options.leaveSubmitMethodAlone && typeof textarea.form.submit == "function")
	            { textarea.form.submit = realSubmit; }
	        }
	      };
	    };

	    textarea.style.display = "none";
	    var cm = CodeMirror(function (node) { return textarea.parentNode.insertBefore(node, textarea.nextSibling); },
	      options);
	    return cm
	  }

	  function addLegacyProps(CodeMirror) {
	    CodeMirror.off = off;
	    CodeMirror.on = on;
	    CodeMirror.wheelEventPixels = wheelEventPixels;
	    CodeMirror.Doc = Doc;
	    CodeMirror.splitLines = splitLinesAuto;
	    CodeMirror.countColumn = countColumn;
	    CodeMirror.findColumn = findColumn;
	    CodeMirror.isWordChar = isWordCharBasic;
	    CodeMirror.Pass = Pass;
	    CodeMirror.signal = signal;
	    CodeMirror.Line = Line;
	    CodeMirror.changeEnd = changeEnd;
	    CodeMirror.scrollbarModel = scrollbarModel;
	    CodeMirror.Pos = Pos;
	    CodeMirror.cmpPos = cmp;
	    CodeMirror.modes = modes;
	    CodeMirror.mimeModes = mimeModes;
	    CodeMirror.resolveMode = resolveMode;
	    CodeMirror.getMode = getMode;
	    CodeMirror.modeExtensions = modeExtensions;
	    CodeMirror.extendMode = extendMode;
	    CodeMirror.copyState = copyState;
	    CodeMirror.startState = startState;
	    CodeMirror.innerMode = innerMode;
	    CodeMirror.commands = commands;
	    CodeMirror.keyMap = keyMap;
	    CodeMirror.keyName = keyName;
	    CodeMirror.isModifierKey = isModifierKey;
	    CodeMirror.lookupKey = lookupKey;
	    CodeMirror.normalizeKeyMap = normalizeKeyMap;
	    CodeMirror.StringStream = StringStream;
	    CodeMirror.SharedTextMarker = SharedTextMarker;
	    CodeMirror.TextMarker = TextMarker;
	    CodeMirror.LineWidget = LineWidget;
	    CodeMirror.e_preventDefault = e_preventDefault;
	    CodeMirror.e_stopPropagation = e_stopPropagation;
	    CodeMirror.e_stop = e_stop;
	    CodeMirror.addClass = addClass;
	    CodeMirror.contains = contains;
	    CodeMirror.rmClass = rmClass;
	    CodeMirror.keyNames = keyNames;
	  }

	  // EDITOR CONSTRUCTOR

	  defineOptions(CodeMirror);

	  addEditorMethods(CodeMirror);

	  // Set up methods on CodeMirror's prototype to redirect to the editor's document.
	  var dontDelegate = "iter insert remove copy getEditor constructor".split(" ");
	  for (var prop in Doc.prototype) { if (Doc.prototype.hasOwnProperty(prop) && indexOf(dontDelegate, prop) < 0)
	    { CodeMirror.prototype[prop] = (function(method) {
	      return function() {return method.apply(this.doc, arguments)}
	    })(Doc.prototype[prop]); } }

	  eventMixin(Doc);
	  CodeMirror.inputStyles = {"textarea": TextareaInput, "contenteditable": ContentEditableInput};

	  // Extra arguments are stored as the mode's dependencies, which is
	  // used by (legacy) mechanisms like loadmode.js to automatically
	  // load a mode. (Preferred mechanism is the require/define calls.)
	  CodeMirror.defineMode = function(name/*, mode, …*/) {
	    if (!CodeMirror.defaults.mode && name != "null") { CodeMirror.defaults.mode = name; }
	    defineMode.apply(this, arguments);
	  };

	  CodeMirror.defineMIME = defineMIME;

	  // Minimal default mode.
	  CodeMirror.defineMode("null", function () { return ({token: function (stream) { return stream.skipToEnd(); }}); });
	  CodeMirror.defineMIME("text/plain", "null");

	  // EXTENSIONS

	  CodeMirror.defineExtension = function (name, func) {
	    CodeMirror.prototype[name] = func;
	  };
	  CodeMirror.defineDocExtension = function (name, func) {
	    Doc.prototype[name] = func;
	  };

	  CodeMirror.fromTextArea = fromTextArea;

	  addLegacyProps(CodeMirror);

	  CodeMirror.version = "5.52.0";

	  return CodeMirror;

	})));
	});

	var Modals;

	var Modals$1 = Modals = {
	    init: function () {
	        $$('[data-modal]').forEach(function (element) {
	            element.addEventListener('click', function () {
	                var modal = this.getAttribute('data-modal');
	                var action = this.getAttribute('data-modal-action');
	                if (action) {
	                    Modals.show(modal, action);
	                } else {
	                    Modals.show(modal);
	                }
	            });
	        });

	        $$('.modal [data-dismiss]').forEach(function (element) {
	            element.addEventListener('click', function () {
	                var valid;
	                if (this.hasAttribute('data-validate')) {
	                    valid = Modals.validate(this.getAttribute('data-dismiss'));
	                    if (!valid) {
	                        return;
	                    }
	                }
	                Modals.hide(this.getAttribute('data-dismiss'));
	            });
	        });

	        $$('.modal').forEach(function (element) {
	            element.addEventListener('click', function (event) {
	                if (event.target === this) {
	                    Modals.hide();
	                }
	            });
	        });

	        document.addEventListener('keyup', function (event) {
	            // ESC key
	            if (event.which === 27) {
	                Modals.hide();
	            }
	        });
	    },

	    show: function (id, action, callback) {
	        var modal = document.getElementById(id);
	        if (!modal) {
	            return;
	        }
	        modal.classList.add('show');
	        if (action) {
	            $('form', modal).setAttribute('action', action);
	        }
	        if ($('[autofocus]', modal)) {
	            Utils.triggerEvent($('[autofocus]', modal), 'focus'); // Firefox bug
	        }
	        if (typeof callback === 'function') {
	            callback(modal);
	        }
	        $$('.tooltip').forEach(function (element) {
	            element.parentNode.removeChild(element);
	        });
	        this.createBackdrop();
	    },

	    hide: function (id) {
	        if (typeof id !== 'undefined') {
	            document.getElementById(id).classList.remove('show');
	        } else {
	            $$('.modal').forEach(function (element) {
	                element.classList.remove('show');
	            });
	        }
	        this.removeBackdrop();
	    },

	    createBackdrop: function () {
	        var backdrop;
	        if (!$('.modal-backdrop')) {
	            backdrop = document.createElement('div');
	            backdrop.className = 'modal-backdrop';
	            document.body.appendChild(backdrop);
	        }
	    },

	    removeBackdrop: function () {
	        var backdrop = $('.modal-backdrop');
	        if (backdrop) {
	            backdrop.parentNode.removeChild(backdrop);
	        }
	    },

	    validate: function (id) {
	        var valid = false;
	        var modal = document.getElementById(id);
	        $$('[required]', id).forEach(function (element) {
	            if (element.value === '') {
	                element.classList('input-invalid');
	                Utils.triggerEvent(element, 'focus');
	                $('.modal-error', modal).style.display = 'block';
	                valid = false;
	                return false;
	            }
	            valid = true;
	        });
	        return valid;
	    }
	};

	var xml = createCommonjsModule(function (module, exports) {
	// CodeMirror, copyright (c) by Marijn Haverbeke and others
	// Distributed under an MIT license: https://codemirror.net/LICENSE

	(function(mod) {
	  mod(codemirror);
	})(function(CodeMirror) {

	var htmlConfig = {
	  autoSelfClosers: {'area': true, 'base': true, 'br': true, 'col': true, 'command': true,
	                    'embed': true, 'frame': true, 'hr': true, 'img': true, 'input': true,
	                    'keygen': true, 'link': true, 'meta': true, 'param': true, 'source': true,
	                    'track': true, 'wbr': true, 'menuitem': true},
	  implicitlyClosed: {'dd': true, 'li': true, 'optgroup': true, 'option': true, 'p': true,
	                     'rp': true, 'rt': true, 'tbody': true, 'td': true, 'tfoot': true,
	                     'th': true, 'tr': true},
	  contextGrabbers: {
	    'dd': {'dd': true, 'dt': true},
	    'dt': {'dd': true, 'dt': true},
	    'li': {'li': true},
	    'option': {'option': true, 'optgroup': true},
	    'optgroup': {'optgroup': true},
	    'p': {'address': true, 'article': true, 'aside': true, 'blockquote': true, 'dir': true,
	          'div': true, 'dl': true, 'fieldset': true, 'footer': true, 'form': true,
	          'h1': true, 'h2': true, 'h3': true, 'h4': true, 'h5': true, 'h6': true,
	          'header': true, 'hgroup': true, 'hr': true, 'menu': true, 'nav': true, 'ol': true,
	          'p': true, 'pre': true, 'section': true, 'table': true, 'ul': true},
	    'rp': {'rp': true, 'rt': true},
	    'rt': {'rp': true, 'rt': true},
	    'tbody': {'tbody': true, 'tfoot': true},
	    'td': {'td': true, 'th': true},
	    'tfoot': {'tbody': true},
	    'th': {'td': true, 'th': true},
	    'thead': {'tbody': true, 'tfoot': true},
	    'tr': {'tr': true}
	  },
	  doNotIndent: {"pre": true},
	  allowUnquoted: true,
	  allowMissing: true,
	  caseFold: true
	};

	var xmlConfig = {
	  autoSelfClosers: {},
	  implicitlyClosed: {},
	  contextGrabbers: {},
	  doNotIndent: {},
	  allowUnquoted: false,
	  allowMissing: false,
	  allowMissingTagName: false,
	  caseFold: false
	};

	CodeMirror.defineMode("xml", function(editorConf, config_) {
	  var indentUnit = editorConf.indentUnit;
	  var config = {};
	  var defaults = config_.htmlMode ? htmlConfig : xmlConfig;
	  for (var prop in defaults) config[prop] = defaults[prop];
	  for (var prop in config_) config[prop] = config_[prop];

	  // Return variables for tokenizers
	  var type, setStyle;

	  function inText(stream, state) {
	    function chain(parser) {
	      state.tokenize = parser;
	      return parser(stream, state);
	    }

	    var ch = stream.next();
	    if (ch == "<") {
	      if (stream.eat("!")) {
	        if (stream.eat("[")) {
	          if (stream.match("CDATA[")) return chain(inBlock("atom", "]]>"));
	          else return null;
	        } else if (stream.match("--")) {
	          return chain(inBlock("comment", "-->"));
	        } else if (stream.match("DOCTYPE", true, true)) {
	          stream.eatWhile(/[\w\._\-]/);
	          return chain(doctype(1));
	        } else {
	          return null;
	        }
	      } else if (stream.eat("?")) {
	        stream.eatWhile(/[\w\._\-]/);
	        state.tokenize = inBlock("meta", "?>");
	        return "meta";
	      } else {
	        type = stream.eat("/") ? "closeTag" : "openTag";
	        state.tokenize = inTag;
	        return "tag bracket";
	      }
	    } else if (ch == "&") {
	      var ok;
	      if (stream.eat("#")) {
	        if (stream.eat("x")) {
	          ok = stream.eatWhile(/[a-fA-F\d]/) && stream.eat(";");
	        } else {
	          ok = stream.eatWhile(/[\d]/) && stream.eat(";");
	        }
	      } else {
	        ok = stream.eatWhile(/[\w\.\-:]/) && stream.eat(";");
	      }
	      return ok ? "atom" : "error";
	    } else {
	      stream.eatWhile(/[^&<]/);
	      return null;
	    }
	  }
	  inText.isInText = true;

	  function inTag(stream, state) {
	    var ch = stream.next();
	    if (ch == ">" || (ch == "/" && stream.eat(">"))) {
	      state.tokenize = inText;
	      type = ch == ">" ? "endTag" : "selfcloseTag";
	      return "tag bracket";
	    } else if (ch == "=") {
	      type = "equals";
	      return null;
	    } else if (ch == "<") {
	      state.tokenize = inText;
	      state.state = baseState;
	      state.tagName = state.tagStart = null;
	      var next = state.tokenize(stream, state);
	      return next ? next + " tag error" : "tag error";
	    } else if (/[\'\"]/.test(ch)) {
	      state.tokenize = inAttribute(ch);
	      state.stringStartCol = stream.column();
	      return state.tokenize(stream, state);
	    } else {
	      stream.match(/^[^\s\u00a0=<>\"\']*[^\s\u00a0=<>\"\'\/]/);
	      return "word";
	    }
	  }

	  function inAttribute(quote) {
	    var closure = function(stream, state) {
	      while (!stream.eol()) {
	        if (stream.next() == quote) {
	          state.tokenize = inTag;
	          break;
	        }
	      }
	      return "string";
	    };
	    closure.isInAttribute = true;
	    return closure;
	  }

	  function inBlock(style, terminator) {
	    return function(stream, state) {
	      while (!stream.eol()) {
	        if (stream.match(terminator)) {
	          state.tokenize = inText;
	          break;
	        }
	        stream.next();
	      }
	      return style;
	    }
	  }

	  function doctype(depth) {
	    return function(stream, state) {
	      var ch;
	      while ((ch = stream.next()) != null) {
	        if (ch == "<") {
	          state.tokenize = doctype(depth + 1);
	          return state.tokenize(stream, state);
	        } else if (ch == ">") {
	          if (depth == 1) {
	            state.tokenize = inText;
	            break;
	          } else {
	            state.tokenize = doctype(depth - 1);
	            return state.tokenize(stream, state);
	          }
	        }
	      }
	      return "meta";
	    };
	  }

	  function Context(state, tagName, startOfLine) {
	    this.prev = state.context;
	    this.tagName = tagName;
	    this.indent = state.indented;
	    this.startOfLine = startOfLine;
	    if (config.doNotIndent.hasOwnProperty(tagName) || (state.context && state.context.noIndent))
	      this.noIndent = true;
	  }
	  function popContext(state) {
	    if (state.context) state.context = state.context.prev;
	  }
	  function maybePopContext(state, nextTagName) {
	    var parentTagName;
	    while (true) {
	      if (!state.context) {
	        return;
	      }
	      parentTagName = state.context.tagName;
	      if (!config.contextGrabbers.hasOwnProperty(parentTagName) ||
	          !config.contextGrabbers[parentTagName].hasOwnProperty(nextTagName)) {
	        return;
	      }
	      popContext(state);
	    }
	  }

	  function baseState(type, stream, state) {
	    if (type == "openTag") {
	      state.tagStart = stream.column();
	      return tagNameState;
	    } else if (type == "closeTag") {
	      return closeTagNameState;
	    } else {
	      return baseState;
	    }
	  }
	  function tagNameState(type, stream, state) {
	    if (type == "word") {
	      state.tagName = stream.current();
	      setStyle = "tag";
	      return attrState;
	    } else if (config.allowMissingTagName && type == "endTag") {
	      setStyle = "tag bracket";
	      return attrState(type, stream, state);
	    } else {
	      setStyle = "error";
	      return tagNameState;
	    }
	  }
	  function closeTagNameState(type, stream, state) {
	    if (type == "word") {
	      var tagName = stream.current();
	      if (state.context && state.context.tagName != tagName &&
	          config.implicitlyClosed.hasOwnProperty(state.context.tagName))
	        popContext(state);
	      if ((state.context && state.context.tagName == tagName) || config.matchClosing === false) {
	        setStyle = "tag";
	        return closeState;
	      } else {
	        setStyle = "tag error";
	        return closeStateErr;
	      }
	    } else if (config.allowMissingTagName && type == "endTag") {
	      setStyle = "tag bracket";
	      return closeState(type, stream, state);
	    } else {
	      setStyle = "error";
	      return closeStateErr;
	    }
	  }

	  function closeState(type, _stream, state) {
	    if (type != "endTag") {
	      setStyle = "error";
	      return closeState;
	    }
	    popContext(state);
	    return baseState;
	  }
	  function closeStateErr(type, stream, state) {
	    setStyle = "error";
	    return closeState(type, stream, state);
	  }

	  function attrState(type, _stream, state) {
	    if (type == "word") {
	      setStyle = "attribute";
	      return attrEqState;
	    } else if (type == "endTag" || type == "selfcloseTag") {
	      var tagName = state.tagName, tagStart = state.tagStart;
	      state.tagName = state.tagStart = null;
	      if (type == "selfcloseTag" ||
	          config.autoSelfClosers.hasOwnProperty(tagName)) {
	        maybePopContext(state, tagName);
	      } else {
	        maybePopContext(state, tagName);
	        state.context = new Context(state, tagName, tagStart == state.indented);
	      }
	      return baseState;
	    }
	    setStyle = "error";
	    return attrState;
	  }
	  function attrEqState(type, stream, state) {
	    if (type == "equals") return attrValueState;
	    if (!config.allowMissing) setStyle = "error";
	    return attrState(type, stream, state);
	  }
	  function attrValueState(type, stream, state) {
	    if (type == "string") return attrContinuedState;
	    if (type == "word" && config.allowUnquoted) {setStyle = "string"; return attrState;}
	    setStyle = "error";
	    return attrState(type, stream, state);
	  }
	  function attrContinuedState(type, stream, state) {
	    if (type == "string") return attrContinuedState;
	    return attrState(type, stream, state);
	  }

	  return {
	    startState: function(baseIndent) {
	      var state = {tokenize: inText,
	                   state: baseState,
	                   indented: baseIndent || 0,
	                   tagName: null, tagStart: null,
	                   context: null};
	      if (baseIndent != null) state.baseIndent = baseIndent;
	      return state
	    },

	    token: function(stream, state) {
	      if (!state.tagName && stream.sol())
	        state.indented = stream.indentation();

	      if (stream.eatSpace()) return null;
	      type = null;
	      var style = state.tokenize(stream, state);
	      if ((style || type) && style != "comment") {
	        setStyle = null;
	        state.state = state.state(type || style, stream, state);
	        if (setStyle)
	          style = setStyle == "error" ? style + " error" : setStyle;
	      }
	      return style;
	    },

	    indent: function(state, textAfter, fullLine) {
	      var context = state.context;
	      // Indent multi-line strings (e.g. css).
	      if (state.tokenize.isInAttribute) {
	        if (state.tagStart == state.indented)
	          return state.stringStartCol + 1;
	        else
	          return state.indented + indentUnit;
	      }
	      if (context && context.noIndent) return CodeMirror.Pass;
	      if (state.tokenize != inTag && state.tokenize != inText)
	        return fullLine ? fullLine.match(/^(\s*)/)[0].length : 0;
	      // Indent the starts of attribute names.
	      if (state.tagName) {
	        if (config.multilineTagIndentPastTag !== false)
	          return state.tagStart + state.tagName.length + 2;
	        else
	          return state.tagStart + indentUnit * (config.multilineTagIndentFactor || 1);
	      }
	      if (config.alignCDATA && /<!\[CDATA\[/.test(textAfter)) return 0;
	      var tagAfter = textAfter && /^<(\/)?([\w_:\.-]*)/.exec(textAfter);
	      if (tagAfter && tagAfter[1]) { // Closing tag spotted
	        while (context) {
	          if (context.tagName == tagAfter[2]) {
	            context = context.prev;
	            break;
	          } else if (config.implicitlyClosed.hasOwnProperty(context.tagName)) {
	            context = context.prev;
	          } else {
	            break;
	          }
	        }
	      } else if (tagAfter) { // Opening tag spotted
	        while (context) {
	          var grabbers = config.contextGrabbers[context.tagName];
	          if (grabbers && grabbers.hasOwnProperty(tagAfter[2]))
	            context = context.prev;
	          else
	            break;
	        }
	      }
	      while (context && context.prev && !context.startOfLine)
	        context = context.prev;
	      if (context) return context.indent + indentUnit;
	      else return state.baseIndent || 0;
	    },

	    electricInput: /<\/[\s\w:]+>$/,
	    blockCommentStart: "<!--",
	    blockCommentEnd: "-->",

	    configuration: config.htmlMode ? "html" : "xml",
	    helperType: config.htmlMode ? "html" : "xml",

	    skipAttribute: function(state) {
	      if (state.state == attrValueState)
	        state.state = attrState;
	    },

	    xmlCurrentTag: function(state) {
	      return state.tagName ? {name: state.tagName, close: state.type == "closeTag"} : null
	    },

	    xmlCurrentContext: function(state) {
	      var context = [];
	      for (var cx = state.context; cx; cx = cx.prev)
	        if (cx.tagName) context.push(cx.tagName);
	      return context.reverse()
	    }
	  };
	});

	CodeMirror.defineMIME("text/xml", "xml");
	CodeMirror.defineMIME("application/xml", "xml");
	if (!CodeMirror.mimeModes.hasOwnProperty("text/html"))
	  CodeMirror.defineMIME("text/html", {name: "xml", htmlMode: true});

	});
	});

	var meta = createCommonjsModule(function (module, exports) {
	// CodeMirror, copyright (c) by Marijn Haverbeke and others
	// Distributed under an MIT license: https://codemirror.net/LICENSE

	(function(mod) {
	  mod(codemirror);
	})(function(CodeMirror) {

	  CodeMirror.modeInfo = [
	    {name: "APL", mime: "text/apl", mode: "apl", ext: ["dyalog", "apl"]},
	    {name: "PGP", mimes: ["application/pgp", "application/pgp-encrypted", "application/pgp-keys", "application/pgp-signature"], mode: "asciiarmor", ext: ["asc", "pgp", "sig"]},
	    {name: "ASN.1", mime: "text/x-ttcn-asn", mode: "asn.1", ext: ["asn", "asn1"]},
	    {name: "Asterisk", mime: "text/x-asterisk", mode: "asterisk", file: /^extensions\.conf$/i},
	    {name: "Brainfuck", mime: "text/x-brainfuck", mode: "brainfuck", ext: ["b", "bf"]},
	    {name: "C", mime: "text/x-csrc", mode: "clike", ext: ["c", "h", "ino"]},
	    {name: "C++", mime: "text/x-c++src", mode: "clike", ext: ["cpp", "c++", "cc", "cxx", "hpp", "h++", "hh", "hxx"], alias: ["cpp"]},
	    {name: "Cobol", mime: "text/x-cobol", mode: "cobol", ext: ["cob", "cpy"]},
	    {name: "C#", mime: "text/x-csharp", mode: "clike", ext: ["cs"], alias: ["csharp", "cs"]},
	    {name: "Clojure", mime: "text/x-clojure", mode: "clojure", ext: ["clj", "cljc", "cljx"]},
	    {name: "ClojureScript", mime: "text/x-clojurescript", mode: "clojure", ext: ["cljs"]},
	    {name: "Closure Stylesheets (GSS)", mime: "text/x-gss", mode: "css", ext: ["gss"]},
	    {name: "CMake", mime: "text/x-cmake", mode: "cmake", ext: ["cmake", "cmake.in"], file: /^CMakeLists.txt$/},
	    {name: "CoffeeScript", mimes: ["application/vnd.coffeescript", "text/coffeescript", "text/x-coffeescript"], mode: "coffeescript", ext: ["coffee"], alias: ["coffee", "coffee-script"]},
	    {name: "Common Lisp", mime: "text/x-common-lisp", mode: "commonlisp", ext: ["cl", "lisp", "el"], alias: ["lisp"]},
	    {name: "Cypher", mime: "application/x-cypher-query", mode: "cypher", ext: ["cyp", "cypher"]},
	    {name: "Cython", mime: "text/x-cython", mode: "python", ext: ["pyx", "pxd", "pxi"]},
	    {name: "Crystal", mime: "text/x-crystal", mode: "crystal", ext: ["cr"]},
	    {name: "CSS", mime: "text/css", mode: "css", ext: ["css"]},
	    {name: "CQL", mime: "text/x-cassandra", mode: "sql", ext: ["cql"]},
	    {name: "D", mime: "text/x-d", mode: "d", ext: ["d"]},
	    {name: "Dart", mimes: ["application/dart", "text/x-dart"], mode: "dart", ext: ["dart"]},
	    {name: "diff", mime: "text/x-diff", mode: "diff", ext: ["diff", "patch"]},
	    {name: "Django", mime: "text/x-django", mode: "django"},
	    {name: "Dockerfile", mime: "text/x-dockerfile", mode: "dockerfile", file: /^Dockerfile$/},
	    {name: "DTD", mime: "application/xml-dtd", mode: "dtd", ext: ["dtd"]},
	    {name: "Dylan", mime: "text/x-dylan", mode: "dylan", ext: ["dylan", "dyl", "intr"]},
	    {name: "EBNF", mime: "text/x-ebnf", mode: "ebnf"},
	    {name: "ECL", mime: "text/x-ecl", mode: "ecl", ext: ["ecl"]},
	    {name: "edn", mime: "application/edn", mode: "clojure", ext: ["edn"]},
	    {name: "Eiffel", mime: "text/x-eiffel", mode: "eiffel", ext: ["e"]},
	    {name: "Elm", mime: "text/x-elm", mode: "elm", ext: ["elm"]},
	    {name: "Embedded Javascript", mime: "application/x-ejs", mode: "htmlembedded", ext: ["ejs"]},
	    {name: "Embedded Ruby", mime: "application/x-erb", mode: "htmlembedded", ext: ["erb"]},
	    {name: "Erlang", mime: "text/x-erlang", mode: "erlang", ext: ["erl"]},
	    {name: "Esper", mime: "text/x-esper", mode: "sql"},
	    {name: "Factor", mime: "text/x-factor", mode: "factor", ext: ["factor"]},
	    {name: "FCL", mime: "text/x-fcl", mode: "fcl"},
	    {name: "Forth", mime: "text/x-forth", mode: "forth", ext: ["forth", "fth", "4th"]},
	    {name: "Fortran", mime: "text/x-fortran", mode: "fortran", ext: ["f", "for", "f77", "f90", "f95"]},
	    {name: "F#", mime: "text/x-fsharp", mode: "mllike", ext: ["fs"], alias: ["fsharp"]},
	    {name: "Gas", mime: "text/x-gas", mode: "gas", ext: ["s"]},
	    {name: "Gherkin", mime: "text/x-feature", mode: "gherkin", ext: ["feature"]},
	    {name: "GitHub Flavored Markdown", mime: "text/x-gfm", mode: "gfm", file: /^(readme|contributing|history).md$/i},
	    {name: "Go", mime: "text/x-go", mode: "go", ext: ["go"]},
	    {name: "Groovy", mime: "text/x-groovy", mode: "groovy", ext: ["groovy", "gradle"], file: /^Jenkinsfile$/},
	    {name: "HAML", mime: "text/x-haml", mode: "haml", ext: ["haml"]},
	    {name: "Haskell", mime: "text/x-haskell", mode: "haskell", ext: ["hs"]},
	    {name: "Haskell (Literate)", mime: "text/x-literate-haskell", mode: "haskell-literate", ext: ["lhs"]},
	    {name: "Haxe", mime: "text/x-haxe", mode: "haxe", ext: ["hx"]},
	    {name: "HXML", mime: "text/x-hxml", mode: "haxe", ext: ["hxml"]},
	    {name: "ASP.NET", mime: "application/x-aspx", mode: "htmlembedded", ext: ["aspx"], alias: ["asp", "aspx"]},
	    {name: "HTML", mime: "text/html", mode: "htmlmixed", ext: ["html", "htm", "handlebars", "hbs"], alias: ["xhtml"]},
	    {name: "HTTP", mime: "message/http", mode: "http"},
	    {name: "IDL", mime: "text/x-idl", mode: "idl", ext: ["pro"]},
	    {name: "Pug", mime: "text/x-pug", mode: "pug", ext: ["jade", "pug"], alias: ["jade"]},
	    {name: "Java", mime: "text/x-java", mode: "clike", ext: ["java"]},
	    {name: "Java Server Pages", mime: "application/x-jsp", mode: "htmlembedded", ext: ["jsp"], alias: ["jsp"]},
	    {name: "JavaScript", mimes: ["text/javascript", "text/ecmascript", "application/javascript", "application/x-javascript", "application/ecmascript"],
	     mode: "javascript", ext: ["js"], alias: ["ecmascript", "js", "node"]},
	    {name: "JSON", mimes: ["application/json", "application/x-json"], mode: "javascript", ext: ["json", "map"], alias: ["json5"]},
	    {name: "JSON-LD", mime: "application/ld+json", mode: "javascript", ext: ["jsonld"], alias: ["jsonld"]},
	    {name: "JSX", mime: "text/jsx", mode: "jsx", ext: ["jsx"]},
	    {name: "Jinja2", mime: "text/jinja2", mode: "jinja2", ext: ["j2", "jinja", "jinja2"]},
	    {name: "Julia", mime: "text/x-julia", mode: "julia", ext: ["jl"]},
	    {name: "Kotlin", mime: "text/x-kotlin", mode: "clike", ext: ["kt"]},
	    {name: "LESS", mime: "text/x-less", mode: "css", ext: ["less"]},
	    {name: "LiveScript", mime: "text/x-livescript", mode: "livescript", ext: ["ls"], alias: ["ls"]},
	    {name: "Lua", mime: "text/x-lua", mode: "lua", ext: ["lua"]},
	    {name: "Markdown", mime: "text/x-markdown", mode: "markdown", ext: ["markdown", "md", "mkd"]},
	    {name: "mIRC", mime: "text/mirc", mode: "mirc"},
	    {name: "MariaDB SQL", mime: "text/x-mariadb", mode: "sql"},
	    {name: "Mathematica", mime: "text/x-mathematica", mode: "mathematica", ext: ["m", "nb", "wl", "wls"]},
	    {name: "Modelica", mime: "text/x-modelica", mode: "modelica", ext: ["mo"]},
	    {name: "MUMPS", mime: "text/x-mumps", mode: "mumps", ext: ["mps"]},
	    {name: "MS SQL", mime: "text/x-mssql", mode: "sql"},
	    {name: "mbox", mime: "application/mbox", mode: "mbox", ext: ["mbox"]},
	    {name: "MySQL", mime: "text/x-mysql", mode: "sql"},
	    {name: "Nginx", mime: "text/x-nginx-conf", mode: "nginx", file: /nginx.*\.conf$/i},
	    {name: "NSIS", mime: "text/x-nsis", mode: "nsis", ext: ["nsh", "nsi"]},
	    {name: "NTriples", mimes: ["application/n-triples", "application/n-quads", "text/n-triples"],
	     mode: "ntriples", ext: ["nt", "nq"]},
	    {name: "Objective-C", mime: "text/x-objectivec", mode: "clike", ext: ["m"], alias: ["objective-c", "objc"]},
	    {name: "Objective-C++", mime: "text/x-objectivec++", mode: "clike", ext: ["mm"], alias: ["objective-c++", "objc++"]},
	    {name: "OCaml", mime: "text/x-ocaml", mode: "mllike", ext: ["ml", "mli", "mll", "mly"]},
	    {name: "Octave", mime: "text/x-octave", mode: "octave", ext: ["m"]},
	    {name: "Oz", mime: "text/x-oz", mode: "oz", ext: ["oz"]},
	    {name: "Pascal", mime: "text/x-pascal", mode: "pascal", ext: ["p", "pas"]},
	    {name: "PEG.js", mime: "null", mode: "pegjs", ext: ["jsonld"]},
	    {name: "Perl", mime: "text/x-perl", mode: "perl", ext: ["pl", "pm"]},
	    {name: "PHP", mimes: ["text/x-php", "application/x-httpd-php", "application/x-httpd-php-open"], mode: "php", ext: ["php", "php3", "php4", "php5", "php7", "phtml"]},
	    {name: "Pig", mime: "text/x-pig", mode: "pig", ext: ["pig"]},
	    {name: "Plain Text", mime: "text/plain", mode: "null", ext: ["txt", "text", "conf", "def", "list", "log"]},
	    {name: "PLSQL", mime: "text/x-plsql", mode: "sql", ext: ["pls"]},
	    {name: "PostgreSQL", mime: "text/x-pgsql", mode: "sql"},
	    {name: "PowerShell", mime: "application/x-powershell", mode: "powershell", ext: ["ps1", "psd1", "psm1"]},
	    {name: "Properties files", mime: "text/x-properties", mode: "properties", ext: ["properties", "ini", "in"], alias: ["ini", "properties"]},
	    {name: "ProtoBuf", mime: "text/x-protobuf", mode: "protobuf", ext: ["proto"]},
	    {name: "Python", mime: "text/x-python", mode: "python", ext: ["BUILD", "bzl", "py", "pyw"], file: /^(BUCK|BUILD)$/},
	    {name: "Puppet", mime: "text/x-puppet", mode: "puppet", ext: ["pp"]},
	    {name: "Q", mime: "text/x-q", mode: "q", ext: ["q"]},
	    {name: "R", mime: "text/x-rsrc", mode: "r", ext: ["r", "R"], alias: ["rscript"]},
	    {name: "reStructuredText", mime: "text/x-rst", mode: "rst", ext: ["rst"], alias: ["rst"]},
	    {name: "RPM Changes", mime: "text/x-rpm-changes", mode: "rpm"},
	    {name: "RPM Spec", mime: "text/x-rpm-spec", mode: "rpm", ext: ["spec"]},
	    {name: "Ruby", mime: "text/x-ruby", mode: "ruby", ext: ["rb"], alias: ["jruby", "macruby", "rake", "rb", "rbx"]},
	    {name: "Rust", mime: "text/x-rustsrc", mode: "rust", ext: ["rs"]},
	    {name: "SAS", mime: "text/x-sas", mode: "sas", ext: ["sas"]},
	    {name: "Sass", mime: "text/x-sass", mode: "sass", ext: ["sass"]},
	    {name: "Scala", mime: "text/x-scala", mode: "clike", ext: ["scala"]},
	    {name: "Scheme", mime: "text/x-scheme", mode: "scheme", ext: ["scm", "ss"]},
	    {name: "SCSS", mime: "text/x-scss", mode: "css", ext: ["scss"]},
	    {name: "Shell", mimes: ["text/x-sh", "application/x-sh"], mode: "shell", ext: ["sh", "ksh", "bash"], alias: ["bash", "sh", "zsh"], file: /^PKGBUILD$/},
	    {name: "Sieve", mime: "application/sieve", mode: "sieve", ext: ["siv", "sieve"]},
	    {name: "Slim", mimes: ["text/x-slim", "application/x-slim"], mode: "slim", ext: ["slim"]},
	    {name: "Smalltalk", mime: "text/x-stsrc", mode: "smalltalk", ext: ["st"]},
	    {name: "Smarty", mime: "text/x-smarty", mode: "smarty", ext: ["tpl"]},
	    {name: "Solr", mime: "text/x-solr", mode: "solr"},
	    {name: "SML", mime: "text/x-sml", mode: "mllike", ext: ["sml", "sig", "fun", "smackspec"]},
	    {name: "Soy", mime: "text/x-soy", mode: "soy", ext: ["soy"], alias: ["closure template"]},
	    {name: "SPARQL", mime: "application/sparql-query", mode: "sparql", ext: ["rq", "sparql"], alias: ["sparul"]},
	    {name: "Spreadsheet", mime: "text/x-spreadsheet", mode: "spreadsheet", alias: ["excel", "formula"]},
	    {name: "SQL", mime: "text/x-sql", mode: "sql", ext: ["sql"]},
	    {name: "SQLite", mime: "text/x-sqlite", mode: "sql"},
	    {name: "Squirrel", mime: "text/x-squirrel", mode: "clike", ext: ["nut"]},
	    {name: "Stylus", mime: "text/x-styl", mode: "stylus", ext: ["styl"]},
	    {name: "Swift", mime: "text/x-swift", mode: "swift", ext: ["swift"]},
	    {name: "sTeX", mime: "text/x-stex", mode: "stex"},
	    {name: "LaTeX", mime: "text/x-latex", mode: "stex", ext: ["text", "ltx", "tex"], alias: ["tex"]},
	    {name: "SystemVerilog", mime: "text/x-systemverilog", mode: "verilog", ext: ["v", "sv", "svh"]},
	    {name: "Tcl", mime: "text/x-tcl", mode: "tcl", ext: ["tcl"]},
	    {name: "Textile", mime: "text/x-textile", mode: "textile", ext: ["textile"]},
	    {name: "TiddlyWiki ", mime: "text/x-tiddlywiki", mode: "tiddlywiki"},
	    {name: "Tiki wiki", mime: "text/tiki", mode: "tiki"},
	    {name: "TOML", mime: "text/x-toml", mode: "toml", ext: ["toml"]},
	    {name: "Tornado", mime: "text/x-tornado", mode: "tornado"},
	    {name: "troff", mime: "text/troff", mode: "troff", ext: ["1", "2", "3", "4", "5", "6", "7", "8", "9"]},
	    {name: "TTCN", mime: "text/x-ttcn", mode: "ttcn", ext: ["ttcn", "ttcn3", "ttcnpp"]},
	    {name: "TTCN_CFG", mime: "text/x-ttcn-cfg", mode: "ttcn-cfg", ext: ["cfg"]},
	    {name: "Turtle", mime: "text/turtle", mode: "turtle", ext: ["ttl"]},
	    {name: "TypeScript", mime: "application/typescript", mode: "javascript", ext: ["ts"], alias: ["ts"]},
	    {name: "TypeScript-JSX", mime: "text/typescript-jsx", mode: "jsx", ext: ["tsx"], alias: ["tsx"]},
	    {name: "Twig", mime: "text/x-twig", mode: "twig"},
	    {name: "Web IDL", mime: "text/x-webidl", mode: "webidl", ext: ["webidl"]},
	    {name: "VB.NET", mime: "text/x-vb", mode: "vb", ext: ["vb"]},
	    {name: "VBScript", mime: "text/vbscript", mode: "vbscript", ext: ["vbs"]},
	    {name: "Velocity", mime: "text/velocity", mode: "velocity", ext: ["vtl"]},
	    {name: "Verilog", mime: "text/x-verilog", mode: "verilog", ext: ["v"]},
	    {name: "VHDL", mime: "text/x-vhdl", mode: "vhdl", ext: ["vhd", "vhdl"]},
	    {name: "Vue.js Component", mimes: ["script/x-vue", "text/x-vue"], mode: "vue", ext: ["vue"]},
	    {name: "XML", mimes: ["application/xml", "text/xml"], mode: "xml", ext: ["xml", "xsl", "xsd", "svg"], alias: ["rss", "wsdl", "xsd"]},
	    {name: "XQuery", mime: "application/xquery", mode: "xquery", ext: ["xy", "xquery"]},
	    {name: "Yacas", mime: "text/x-yacas", mode: "yacas", ext: ["ys"]},
	    {name: "YAML", mimes: ["text/x-yaml", "text/yaml"], mode: "yaml", ext: ["yaml", "yml"], alias: ["yml"]},
	    {name: "Z80", mime: "text/x-z80", mode: "z80", ext: ["z80"]},
	    {name: "mscgen", mime: "text/x-mscgen", mode: "mscgen", ext: ["mscgen", "mscin", "msc"]},
	    {name: "xu", mime: "text/x-xu", mode: "mscgen", ext: ["xu"]},
	    {name: "msgenny", mime: "text/x-msgenny", mode: "mscgen", ext: ["msgenny"]}
	  ];
	  // Ensure all modes have a mime property for backwards compatibility
	  for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
	    var info = CodeMirror.modeInfo[i];
	    if (info.mimes) info.mime = info.mimes[0];
	  }

	  CodeMirror.findModeByMIME = function(mime) {
	    mime = mime.toLowerCase();
	    for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
	      var info = CodeMirror.modeInfo[i];
	      if (info.mime == mime) return info;
	      if (info.mimes) for (var j = 0; j < info.mimes.length; j++)
	        if (info.mimes[j] == mime) return info;
	    }
	    if (/\+xml$/.test(mime)) return CodeMirror.findModeByMIME("application/xml")
	    if (/\+json$/.test(mime)) return CodeMirror.findModeByMIME("application/json")
	  };

	  CodeMirror.findModeByExtension = function(ext) {
	    ext = ext.toLowerCase();
	    for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
	      var info = CodeMirror.modeInfo[i];
	      if (info.ext) for (var j = 0; j < info.ext.length; j++)
	        if (info.ext[j] == ext) return info;
	    }
	  };

	  CodeMirror.findModeByFileName = function(filename) {
	    for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
	      var info = CodeMirror.modeInfo[i];
	      if (info.file && info.file.test(filename)) return info;
	    }
	    var dot = filename.lastIndexOf(".");
	    var ext = dot > -1 && filename.substring(dot + 1, filename.length);
	    if (ext) return CodeMirror.findModeByExtension(ext);
	  };

	  CodeMirror.findModeByName = function(name) {
	    name = name.toLowerCase();
	    for (var i = 0; i < CodeMirror.modeInfo.length; i++) {
	      var info = CodeMirror.modeInfo[i];
	      if (info.name.toLowerCase() == name) return info;
	      if (info.alias) for (var j = 0; j < info.alias.length; j++)
	        if (info.alias[j].toLowerCase() == name) return info;
	    }
	  };
	});
	});

	var markdown = createCommonjsModule(function (module, exports) {
	// CodeMirror, copyright (c) by Marijn Haverbeke and others
	// Distributed under an MIT license: https://codemirror.net/LICENSE

	(function(mod) {
	  mod(codemirror, xml, meta);
	})(function(CodeMirror) {

	CodeMirror.defineMode("markdown", function(cmCfg, modeCfg) {

	  var htmlMode = CodeMirror.getMode(cmCfg, "text/html");
	  var htmlModeMissing = htmlMode.name == "null";

	  function getMode(name) {
	    if (CodeMirror.findModeByName) {
	      var found = CodeMirror.findModeByName(name);
	      if (found) name = found.mime || found.mimes[0];
	    }
	    var mode = CodeMirror.getMode(cmCfg, name);
	    return mode.name == "null" ? null : mode;
	  }

	  // Should characters that affect highlighting be highlighted separate?
	  // Does not include characters that will be output (such as `1.` and `-` for lists)
	  if (modeCfg.highlightFormatting === undefined)
	    modeCfg.highlightFormatting = false;

	  // Maximum number of nested blockquotes. Set to 0 for infinite nesting.
	  // Excess `>` will emit `error` token.
	  if (modeCfg.maxBlockquoteDepth === undefined)
	    modeCfg.maxBlockquoteDepth = 0;

	  // Turn on task lists? ("- [ ] " and "- [x] ")
	  if (modeCfg.taskLists === undefined) modeCfg.taskLists = false;

	  // Turn on strikethrough syntax
	  if (modeCfg.strikethrough === undefined)
	    modeCfg.strikethrough = false;

	  if (modeCfg.emoji === undefined)
	    modeCfg.emoji = false;

	  if (modeCfg.fencedCodeBlockHighlighting === undefined)
	    modeCfg.fencedCodeBlockHighlighting = true;

	  if (modeCfg.xml === undefined)
	    modeCfg.xml = true;

	  // Allow token types to be overridden by user-provided token types.
	  if (modeCfg.tokenTypeOverrides === undefined)
	    modeCfg.tokenTypeOverrides = {};

	  var tokenTypes = {
	    header: "header",
	    code: "comment",
	    quote: "quote",
	    list1: "variable-2",
	    list2: "variable-3",
	    list3: "keyword",
	    hr: "hr",
	    image: "image",
	    imageAltText: "image-alt-text",
	    imageMarker: "image-marker",
	    formatting: "formatting",
	    linkInline: "link",
	    linkEmail: "link",
	    linkText: "link",
	    linkHref: "string",
	    em: "em",
	    strong: "strong",
	    strikethrough: "strikethrough",
	    emoji: "builtin"
	  };

	  for (var tokenType in tokenTypes) {
	    if (tokenTypes.hasOwnProperty(tokenType) && modeCfg.tokenTypeOverrides[tokenType]) {
	      tokenTypes[tokenType] = modeCfg.tokenTypeOverrides[tokenType];
	    }
	  }

	  var hrRE = /^([*\-_])(?:\s*\1){2,}\s*$/
	  ,   listRE = /^(?:[*\-+]|^[0-9]+([.)]))\s+/
	  ,   taskListRE = /^\[(x| )\](?=\s)/i // Must follow listRE
	  ,   atxHeaderRE = modeCfg.allowAtxHeaderWithoutSpace ? /^(#+)/ : /^(#+)(?: |$)/
	  ,   setextHeaderRE = /^ *(?:\={1,}|-{1,})\s*$/
	  ,   textRE = /^[^#!\[\]*_\\<>` "'(~:]+/
	  ,   fencedCodeRE = /^(~~~+|```+)[ \t]*([\w+#-]*)[^\n`]*$/
	  ,   linkDefRE = /^\s*\[[^\]]+?\]:.*$/ // naive link-definition
	  ,   punctuation = /[!"#$%&'()*+,\-.\/:;<=>?@\[\\\]^_`{|}~\xA1\xA7\xAB\xB6\xB7\xBB\xBF\u037E\u0387\u055A-\u055F\u0589\u058A\u05BE\u05C0\u05C3\u05C6\u05F3\u05F4\u0609\u060A\u060C\u060D\u061B\u061E\u061F\u066A-\u066D\u06D4\u0700-\u070D\u07F7-\u07F9\u0830-\u083E\u085E\u0964\u0965\u0970\u0AF0\u0DF4\u0E4F\u0E5A\u0E5B\u0F04-\u0F12\u0F14\u0F3A-\u0F3D\u0F85\u0FD0-\u0FD4\u0FD9\u0FDA\u104A-\u104F\u10FB\u1360-\u1368\u1400\u166D\u166E\u169B\u169C\u16EB-\u16ED\u1735\u1736\u17D4-\u17D6\u17D8-\u17DA\u1800-\u180A\u1944\u1945\u1A1E\u1A1F\u1AA0-\u1AA6\u1AA8-\u1AAD\u1B5A-\u1B60\u1BFC-\u1BFF\u1C3B-\u1C3F\u1C7E\u1C7F\u1CC0-\u1CC7\u1CD3\u2010-\u2027\u2030-\u2043\u2045-\u2051\u2053-\u205E\u207D\u207E\u208D\u208E\u2308-\u230B\u2329\u232A\u2768-\u2775\u27C5\u27C6\u27E6-\u27EF\u2983-\u2998\u29D8-\u29DB\u29FC\u29FD\u2CF9-\u2CFC\u2CFE\u2CFF\u2D70\u2E00-\u2E2E\u2E30-\u2E42\u3001-\u3003\u3008-\u3011\u3014-\u301F\u3030\u303D\u30A0\u30FB\uA4FE\uA4FF\uA60D-\uA60F\uA673\uA67E\uA6F2-\uA6F7\uA874-\uA877\uA8CE\uA8CF\uA8F8-\uA8FA\uA8FC\uA92E\uA92F\uA95F\uA9C1-\uA9CD\uA9DE\uA9DF\uAA5C-\uAA5F\uAADE\uAADF\uAAF0\uAAF1\uABEB\uFD3E\uFD3F\uFE10-\uFE19\uFE30-\uFE52\uFE54-\uFE61\uFE63\uFE68\uFE6A\uFE6B\uFF01-\uFF03\uFF05-\uFF0A\uFF0C-\uFF0F\uFF1A\uFF1B\uFF1F\uFF20\uFF3B-\uFF3D\uFF3F\uFF5B\uFF5D\uFF5F-\uFF65]|\uD800[\uDD00-\uDD02\uDF9F\uDFD0]|\uD801\uDD6F|\uD802[\uDC57\uDD1F\uDD3F\uDE50-\uDE58\uDE7F\uDEF0-\uDEF6\uDF39-\uDF3F\uDF99-\uDF9C]|\uD804[\uDC47-\uDC4D\uDCBB\uDCBC\uDCBE-\uDCC1\uDD40-\uDD43\uDD74\uDD75\uDDC5-\uDDC9\uDDCD\uDDDB\uDDDD-\uDDDF\uDE38-\uDE3D\uDEA9]|\uD805[\uDCC6\uDDC1-\uDDD7\uDE41-\uDE43\uDF3C-\uDF3E]|\uD809[\uDC70-\uDC74]|\uD81A[\uDE6E\uDE6F\uDEF5\uDF37-\uDF3B\uDF44]|\uD82F\uDC9F|\uD836[\uDE87-\uDE8B]/
	  ,   expandedTab = "    "; // CommonMark specifies tab as 4 spaces

	  function switchInline(stream, state, f) {
	    state.f = state.inline = f;
	    return f(stream, state);
	  }

	  function switchBlock(stream, state, f) {
	    state.f = state.block = f;
	    return f(stream, state);
	  }

	  function lineIsEmpty(line) {
	    return !line || !/\S/.test(line.string)
	  }

	  // Blocks

	  function blankLine(state) {
	    // Reset linkTitle state
	    state.linkTitle = false;
	    state.linkHref = false;
	    state.linkText = false;
	    // Reset EM state
	    state.em = false;
	    // Reset STRONG state
	    state.strong = false;
	    // Reset strikethrough state
	    state.strikethrough = false;
	    // Reset state.quote
	    state.quote = 0;
	    // Reset state.indentedCode
	    state.indentedCode = false;
	    if (state.f == htmlBlock) {
	      var exit = htmlModeMissing;
	      if (!exit) {
	        var inner = CodeMirror.innerMode(htmlMode, state.htmlState);
	        exit = inner.mode.name == "xml" && inner.state.tagStart === null &&
	          (!inner.state.context && inner.state.tokenize.isInText);
	      }
	      if (exit) {
	        state.f = inlineNormal;
	        state.block = blockNormal;
	        state.htmlState = null;
	      }
	    }
	    // Reset state.trailingSpace
	    state.trailingSpace = 0;
	    state.trailingSpaceNewLine = false;
	    // Mark this line as blank
	    state.prevLine = state.thisLine;
	    state.thisLine = {stream: null};
	    return null;
	  }

	  function blockNormal(stream, state) {
	    var firstTokenOnLine = stream.column() === state.indentation;
	    var prevLineLineIsEmpty = lineIsEmpty(state.prevLine.stream);
	    var prevLineIsIndentedCode = state.indentedCode;
	    var prevLineIsHr = state.prevLine.hr;
	    var prevLineIsList = state.list !== false;
	    var maxNonCodeIndentation = (state.listStack[state.listStack.length - 1] || 0) + 3;

	    state.indentedCode = false;

	    var lineIndentation = state.indentation;
	    // compute once per line (on first token)
	    if (state.indentationDiff === null) {
	      state.indentationDiff = state.indentation;
	      if (prevLineIsList) {
	        state.list = null;
	        // While this list item's marker's indentation is less than the deepest
	        //  list item's content's indentation,pop the deepest list item
	        //  indentation off the stack, and update block indentation state
	        while (lineIndentation < state.listStack[state.listStack.length - 1]) {
	          state.listStack.pop();
	          if (state.listStack.length) {
	            state.indentation = state.listStack[state.listStack.length - 1];
	          // less than the first list's indent -> the line is no longer a list
	          } else {
	            state.list = false;
	          }
	        }
	        if (state.list !== false) {
	          state.indentationDiff = lineIndentation - state.listStack[state.listStack.length - 1];
	        }
	      }
	    }

	    // not comprehensive (currently only for setext detection purposes)
	    var allowsInlineContinuation = (
	        !prevLineLineIsEmpty && !prevLineIsHr && !state.prevLine.header &&
	        (!prevLineIsList || !prevLineIsIndentedCode) &&
	        !state.prevLine.fencedCodeEnd
	    );

	    var isHr = (state.list === false || prevLineIsHr || prevLineLineIsEmpty) &&
	      state.indentation <= maxNonCodeIndentation && stream.match(hrRE);

	    var match = null;
	    if (state.indentationDiff >= 4 && (prevLineIsIndentedCode || state.prevLine.fencedCodeEnd ||
	         state.prevLine.header || prevLineLineIsEmpty)) {
	      stream.skipToEnd();
	      state.indentedCode = true;
	      return tokenTypes.code;
	    } else if (stream.eatSpace()) {
	      return null;
	    } else if (firstTokenOnLine && state.indentation <= maxNonCodeIndentation && (match = stream.match(atxHeaderRE)) && match[1].length <= 6) {
	      state.quote = 0;
	      state.header = match[1].length;
	      state.thisLine.header = true;
	      if (modeCfg.highlightFormatting) state.formatting = "header";
	      state.f = state.inline;
	      return getType(state);
	    } else if (state.indentation <= maxNonCodeIndentation && stream.eat('>')) {
	      state.quote = firstTokenOnLine ? 1 : state.quote + 1;
	      if (modeCfg.highlightFormatting) state.formatting = "quote";
	      stream.eatSpace();
	      return getType(state);
	    } else if (!isHr && !state.setext && firstTokenOnLine && state.indentation <= maxNonCodeIndentation && (match = stream.match(listRE))) {
	      var listType = match[1] ? "ol" : "ul";

	      state.indentation = lineIndentation + stream.current().length;
	      state.list = true;
	      state.quote = 0;

	      // Add this list item's content's indentation to the stack
	      state.listStack.push(state.indentation);
	      // Reset inline styles which shouldn't propagate aross list items
	      state.em = false;
	      state.strong = false;
	      state.code = false;
	      state.strikethrough = false;

	      if (modeCfg.taskLists && stream.match(taskListRE, false)) {
	        state.taskList = true;
	      }
	      state.f = state.inline;
	      if (modeCfg.highlightFormatting) state.formatting = ["list", "list-" + listType];
	      return getType(state);
	    } else if (firstTokenOnLine && state.indentation <= maxNonCodeIndentation && (match = stream.match(fencedCodeRE, true))) {
	      state.quote = 0;
	      state.fencedEndRE = new RegExp(match[1] + "+ *$");
	      // try switching mode
	      state.localMode = modeCfg.fencedCodeBlockHighlighting && getMode(match[2]);
	      if (state.localMode) state.localState = CodeMirror.startState(state.localMode);
	      state.f = state.block = local;
	      if (modeCfg.highlightFormatting) state.formatting = "code-block";
	      state.code = -1;
	      return getType(state);
	    // SETEXT has lowest block-scope precedence after HR, so check it after
	    //  the others (code, blockquote, list...)
	    } else if (
	      // if setext set, indicates line after ---/===
	      state.setext || (
	        // line before ---/===
	        (!allowsInlineContinuation || !prevLineIsList) && !state.quote && state.list === false &&
	        !state.code && !isHr && !linkDefRE.test(stream.string) &&
	        (match = stream.lookAhead(1)) && (match = match.match(setextHeaderRE))
	      )
	    ) {
	      if ( !state.setext ) {
	        state.header = match[0].charAt(0) == '=' ? 1 : 2;
	        state.setext = state.header;
	      } else {
	        state.header = state.setext;
	        // has no effect on type so we can reset it now
	        state.setext = 0;
	        stream.skipToEnd();
	        if (modeCfg.highlightFormatting) state.formatting = "header";
	      }
	      state.thisLine.header = true;
	      state.f = state.inline;
	      return getType(state);
	    } else if (isHr) {
	      stream.skipToEnd();
	      state.hr = true;
	      state.thisLine.hr = true;
	      return tokenTypes.hr;
	    } else if (stream.peek() === '[') {
	      return switchInline(stream, state, footnoteLink);
	    }

	    return switchInline(stream, state, state.inline);
	  }

	  function htmlBlock(stream, state) {
	    var style = htmlMode.token(stream, state.htmlState);
	    if (!htmlModeMissing) {
	      var inner = CodeMirror.innerMode(htmlMode, state.htmlState);
	      if ((inner.mode.name == "xml" && inner.state.tagStart === null &&
	           (!inner.state.context && inner.state.tokenize.isInText)) ||
	          (state.md_inside && stream.current().indexOf(">") > -1)) {
	        state.f = inlineNormal;
	        state.block = blockNormal;
	        state.htmlState = null;
	      }
	    }
	    return style;
	  }

	  function local(stream, state) {
	    var currListInd = state.listStack[state.listStack.length - 1] || 0;
	    var hasExitedList = state.indentation < currListInd;
	    var maxFencedEndInd = currListInd + 3;
	    if (state.fencedEndRE && state.indentation <= maxFencedEndInd && (hasExitedList || stream.match(state.fencedEndRE))) {
	      if (modeCfg.highlightFormatting) state.formatting = "code-block";
	      var returnType;
	      if (!hasExitedList) returnType = getType(state);
	      state.localMode = state.localState = null;
	      state.block = blockNormal;
	      state.f = inlineNormal;
	      state.fencedEndRE = null;
	      state.code = 0;
	      state.thisLine.fencedCodeEnd = true;
	      if (hasExitedList) return switchBlock(stream, state, state.block);
	      return returnType;
	    } else if (state.localMode) {
	      return state.localMode.token(stream, state.localState);
	    } else {
	      stream.skipToEnd();
	      return tokenTypes.code;
	    }
	  }

	  // Inline
	  function getType(state) {
	    var styles = [];

	    if (state.formatting) {
	      styles.push(tokenTypes.formatting);

	      if (typeof state.formatting === "string") state.formatting = [state.formatting];

	      for (var i = 0; i < state.formatting.length; i++) {
	        styles.push(tokenTypes.formatting + "-" + state.formatting[i]);

	        if (state.formatting[i] === "header") {
	          styles.push(tokenTypes.formatting + "-" + state.formatting[i] + "-" + state.header);
	        }

	        // Add `formatting-quote` and `formatting-quote-#` for blockquotes
	        // Add `error` instead if the maximum blockquote nesting depth is passed
	        if (state.formatting[i] === "quote") {
	          if (!modeCfg.maxBlockquoteDepth || modeCfg.maxBlockquoteDepth >= state.quote) {
	            styles.push(tokenTypes.formatting + "-" + state.formatting[i] + "-" + state.quote);
	          } else {
	            styles.push("error");
	          }
	        }
	      }
	    }

	    if (state.taskOpen) {
	      styles.push("meta");
	      return styles.length ? styles.join(' ') : null;
	    }
	    if (state.taskClosed) {
	      styles.push("property");
	      return styles.length ? styles.join(' ') : null;
	    }

	    if (state.linkHref) {
	      styles.push(tokenTypes.linkHref, "url");
	    } else { // Only apply inline styles to non-url text
	      if (state.strong) { styles.push(tokenTypes.strong); }
	      if (state.em) { styles.push(tokenTypes.em); }
	      if (state.strikethrough) { styles.push(tokenTypes.strikethrough); }
	      if (state.emoji) { styles.push(tokenTypes.emoji); }
	      if (state.linkText) { styles.push(tokenTypes.linkText); }
	      if (state.code) { styles.push(tokenTypes.code); }
	      if (state.image) { styles.push(tokenTypes.image); }
	      if (state.imageAltText) { styles.push(tokenTypes.imageAltText, "link"); }
	      if (state.imageMarker) { styles.push(tokenTypes.imageMarker); }
	    }

	    if (state.header) { styles.push(tokenTypes.header, tokenTypes.header + "-" + state.header); }

	    if (state.quote) {
	      styles.push(tokenTypes.quote);

	      // Add `quote-#` where the maximum for `#` is modeCfg.maxBlockquoteDepth
	      if (!modeCfg.maxBlockquoteDepth || modeCfg.maxBlockquoteDepth >= state.quote) {
	        styles.push(tokenTypes.quote + "-" + state.quote);
	      } else {
	        styles.push(tokenTypes.quote + "-" + modeCfg.maxBlockquoteDepth);
	      }
	    }

	    if (state.list !== false) {
	      var listMod = (state.listStack.length - 1) % 3;
	      if (!listMod) {
	        styles.push(tokenTypes.list1);
	      } else if (listMod === 1) {
	        styles.push(tokenTypes.list2);
	      } else {
	        styles.push(tokenTypes.list3);
	      }
	    }

	    if (state.trailingSpaceNewLine) {
	      styles.push("trailing-space-new-line");
	    } else if (state.trailingSpace) {
	      styles.push("trailing-space-" + (state.trailingSpace % 2 ? "a" : "b"));
	    }

	    return styles.length ? styles.join(' ') : null;
	  }

	  function handleText(stream, state) {
	    if (stream.match(textRE, true)) {
	      return getType(state);
	    }
	    return undefined;
	  }

	  function inlineNormal(stream, state) {
	    var style = state.text(stream, state);
	    if (typeof style !== 'undefined')
	      return style;

	    if (state.list) { // List marker (*, +, -, 1., etc)
	      state.list = null;
	      return getType(state);
	    }

	    if (state.taskList) {
	      var taskOpen = stream.match(taskListRE, true)[1] === " ";
	      if (taskOpen) state.taskOpen = true;
	      else state.taskClosed = true;
	      if (modeCfg.highlightFormatting) state.formatting = "task";
	      state.taskList = false;
	      return getType(state);
	    }

	    state.taskOpen = false;
	    state.taskClosed = false;

	    if (state.header && stream.match(/^#+$/, true)) {
	      if (modeCfg.highlightFormatting) state.formatting = "header";
	      return getType(state);
	    }

	    var ch = stream.next();

	    // Matches link titles present on next line
	    if (state.linkTitle) {
	      state.linkTitle = false;
	      var matchCh = ch;
	      if (ch === '(') {
	        matchCh = ')';
	      }
	      matchCh = (matchCh+'').replace(/([.?*+^\[\]\\(){}|-])/g, "\\$1");
	      var regex = '^\\s*(?:[^' + matchCh + '\\\\]+|\\\\\\\\|\\\\.)' + matchCh;
	      if (stream.match(new RegExp(regex), true)) {
	        return tokenTypes.linkHref;
	      }
	    }

	    // If this block is changed, it may need to be updated in GFM mode
	    if (ch === '`') {
	      var previousFormatting = state.formatting;
	      if (modeCfg.highlightFormatting) state.formatting = "code";
	      stream.eatWhile('`');
	      var count = stream.current().length;
	      if (state.code == 0 && (!state.quote || count == 1)) {
	        state.code = count;
	        return getType(state)
	      } else if (count == state.code) { // Must be exact
	        var t = getType(state);
	        state.code = 0;
	        return t
	      } else {
	        state.formatting = previousFormatting;
	        return getType(state)
	      }
	    } else if (state.code) {
	      return getType(state);
	    }

	    if (ch === '\\') {
	      stream.next();
	      if (modeCfg.highlightFormatting) {
	        var type = getType(state);
	        var formattingEscape = tokenTypes.formatting + "-escape";
	        return type ? type + " " + formattingEscape : formattingEscape;
	      }
	    }

	    if (ch === '!' && stream.match(/\[[^\]]*\] ?(?:\(|\[)/, false)) {
	      state.imageMarker = true;
	      state.image = true;
	      if (modeCfg.highlightFormatting) state.formatting = "image";
	      return getType(state);
	    }

	    if (ch === '[' && state.imageMarker && stream.match(/[^\]]*\](\(.*?\)| ?\[.*?\])/, false)) {
	      state.imageMarker = false;
	      state.imageAltText = true;
	      if (modeCfg.highlightFormatting) state.formatting = "image";
	      return getType(state);
	    }

	    if (ch === ']' && state.imageAltText) {
	      if (modeCfg.highlightFormatting) state.formatting = "image";
	      var type = getType(state);
	      state.imageAltText = false;
	      state.image = false;
	      state.inline = state.f = linkHref;
	      return type;
	    }

	    if (ch === '[' && !state.image) {
	      if (state.linkText && stream.match(/^.*?\]/)) return getType(state)
	      state.linkText = true;
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      return getType(state);
	    }

	    if (ch === ']' && state.linkText) {
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      var type = getType(state);
	      state.linkText = false;
	      state.inline = state.f = stream.match(/\(.*?\)| ?\[.*?\]/, false) ? linkHref : inlineNormal;
	      return type;
	    }

	    if (ch === '<' && stream.match(/^(https?|ftps?):\/\/(?:[^\\>]|\\.)+>/, false)) {
	      state.f = state.inline = linkInline;
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      var type = getType(state);
	      if (type){
	        type += " ";
	      } else {
	        type = "";
	      }
	      return type + tokenTypes.linkInline;
	    }

	    if (ch === '<' && stream.match(/^[^> \\]+@(?:[^\\>]|\\.)+>/, false)) {
	      state.f = state.inline = linkInline;
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      var type = getType(state);
	      if (type){
	        type += " ";
	      } else {
	        type = "";
	      }
	      return type + tokenTypes.linkEmail;
	    }

	    if (modeCfg.xml && ch === '<' && stream.match(/^(!--|\?|!\[CDATA\[|[a-z][a-z0-9-]*(?:\s+[a-z_:.\-]+(?:\s*=\s*[^>]+)?)*\s*(?:>|$))/i, false)) {
	      var end = stream.string.indexOf(">", stream.pos);
	      if (end != -1) {
	        var atts = stream.string.substring(stream.start, end);
	        if (/markdown\s*=\s*('|"){0,1}1('|"){0,1}/.test(atts)) state.md_inside = true;
	      }
	      stream.backUp(1);
	      state.htmlState = CodeMirror.startState(htmlMode);
	      return switchBlock(stream, state, htmlBlock);
	    }

	    if (modeCfg.xml && ch === '<' && stream.match(/^\/\w*?>/)) {
	      state.md_inside = false;
	      return "tag";
	    } else if (ch === "*" || ch === "_") {
	      var len = 1, before = stream.pos == 1 ? " " : stream.string.charAt(stream.pos - 2);
	      while (len < 3 && stream.eat(ch)) len++;
	      var after = stream.peek() || " ";
	      // See http://spec.commonmark.org/0.27/#emphasis-and-strong-emphasis
	      var leftFlanking = !/\s/.test(after) && (!punctuation.test(after) || /\s/.test(before) || punctuation.test(before));
	      var rightFlanking = !/\s/.test(before) && (!punctuation.test(before) || /\s/.test(after) || punctuation.test(after));
	      var setEm = null, setStrong = null;
	      if (len % 2) { // Em
	        if (!state.em && leftFlanking && (ch === "*" || !rightFlanking || punctuation.test(before)))
	          setEm = true;
	        else if (state.em == ch && rightFlanking && (ch === "*" || !leftFlanking || punctuation.test(after)))
	          setEm = false;
	      }
	      if (len > 1) { // Strong
	        if (!state.strong && leftFlanking && (ch === "*" || !rightFlanking || punctuation.test(before)))
	          setStrong = true;
	        else if (state.strong == ch && rightFlanking && (ch === "*" || !leftFlanking || punctuation.test(after)))
	          setStrong = false;
	      }
	      if (setStrong != null || setEm != null) {
	        if (modeCfg.highlightFormatting) state.formatting = setEm == null ? "strong" : setStrong == null ? "em" : "strong em";
	        if (setEm === true) state.em = ch;
	        if (setStrong === true) state.strong = ch;
	        var t = getType(state);
	        if (setEm === false) state.em = false;
	        if (setStrong === false) state.strong = false;
	        return t
	      }
	    } else if (ch === ' ') {
	      if (stream.eat('*') || stream.eat('_')) { // Probably surrounded by spaces
	        if (stream.peek() === ' ') { // Surrounded by spaces, ignore
	          return getType(state);
	        } else { // Not surrounded by spaces, back up pointer
	          stream.backUp(1);
	        }
	      }
	    }

	    if (modeCfg.strikethrough) {
	      if (ch === '~' && stream.eatWhile(ch)) {
	        if (state.strikethrough) {// Remove strikethrough
	          if (modeCfg.highlightFormatting) state.formatting = "strikethrough";
	          var t = getType(state);
	          state.strikethrough = false;
	          return t;
	        } else if (stream.match(/^[^\s]/, false)) {// Add strikethrough
	          state.strikethrough = true;
	          if (modeCfg.highlightFormatting) state.formatting = "strikethrough";
	          return getType(state);
	        }
	      } else if (ch === ' ') {
	        if (stream.match(/^~~/, true)) { // Probably surrounded by space
	          if (stream.peek() === ' ') { // Surrounded by spaces, ignore
	            return getType(state);
	          } else { // Not surrounded by spaces, back up pointer
	            stream.backUp(2);
	          }
	        }
	      }
	    }

	    if (modeCfg.emoji && ch === ":" && stream.match(/^(?:[a-z_\d+][a-z_\d+-]*|\-[a-z_\d+][a-z_\d+-]*):/)) {
	      state.emoji = true;
	      if (modeCfg.highlightFormatting) state.formatting = "emoji";
	      var retType = getType(state);
	      state.emoji = false;
	      return retType;
	    }

	    if (ch === ' ') {
	      if (stream.match(/^ +$/, false)) {
	        state.trailingSpace++;
	      } else if (state.trailingSpace) {
	        state.trailingSpaceNewLine = true;
	      }
	    }

	    return getType(state);
	  }

	  function linkInline(stream, state) {
	    var ch = stream.next();

	    if (ch === ">") {
	      state.f = state.inline = inlineNormal;
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      var type = getType(state);
	      if (type){
	        type += " ";
	      } else {
	        type = "";
	      }
	      return type + tokenTypes.linkInline;
	    }

	    stream.match(/^[^>]+/, true);

	    return tokenTypes.linkInline;
	  }

	  function linkHref(stream, state) {
	    // Check if space, and return NULL if so (to avoid marking the space)
	    if(stream.eatSpace()){
	      return null;
	    }
	    var ch = stream.next();
	    if (ch === '(' || ch === '[') {
	      state.f = state.inline = getLinkHrefInside(ch === "(" ? ")" : "]");
	      if (modeCfg.highlightFormatting) state.formatting = "link-string";
	      state.linkHref = true;
	      return getType(state);
	    }
	    return 'error';
	  }

	  var linkRE = {
	    ")": /^(?:[^\\\(\)]|\\.|\((?:[^\\\(\)]|\\.)*\))*?(?=\))/,
	    "]": /^(?:[^\\\[\]]|\\.|\[(?:[^\\\[\]]|\\.)*\])*?(?=\])/
	  };

	  function getLinkHrefInside(endChar) {
	    return function(stream, state) {
	      var ch = stream.next();

	      if (ch === endChar) {
	        state.f = state.inline = inlineNormal;
	        if (modeCfg.highlightFormatting) state.formatting = "link-string";
	        var returnState = getType(state);
	        state.linkHref = false;
	        return returnState;
	      }

	      stream.match(linkRE[endChar]);
	      state.linkHref = true;
	      return getType(state);
	    };
	  }

	  function footnoteLink(stream, state) {
	    if (stream.match(/^([^\]\\]|\\.)*\]:/, false)) {
	      state.f = footnoteLinkInside;
	      stream.next(); // Consume [
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      state.linkText = true;
	      return getType(state);
	    }
	    return switchInline(stream, state, inlineNormal);
	  }

	  function footnoteLinkInside(stream, state) {
	    if (stream.match(/^\]:/, true)) {
	      state.f = state.inline = footnoteUrl;
	      if (modeCfg.highlightFormatting) state.formatting = "link";
	      var returnType = getType(state);
	      state.linkText = false;
	      return returnType;
	    }

	    stream.match(/^([^\]\\]|\\.)+/, true);

	    return tokenTypes.linkText;
	  }

	  function footnoteUrl(stream, state) {
	    // Check if space, and return NULL if so (to avoid marking the space)
	    if(stream.eatSpace()){
	      return null;
	    }
	    // Match URL
	    stream.match(/^[^\s]+/, true);
	    // Check for link title
	    if (stream.peek() === undefined) { // End of line, set flag to check next line
	      state.linkTitle = true;
	    } else { // More content on line, check if link title
	      stream.match(/^(?:\s+(?:"(?:[^"\\]|\\\\|\\.)+"|'(?:[^'\\]|\\\\|\\.)+'|\((?:[^)\\]|\\\\|\\.)+\)))?/, true);
	    }
	    state.f = state.inline = inlineNormal;
	    return tokenTypes.linkHref + " url";
	  }

	  var mode = {
	    startState: function() {
	      return {
	        f: blockNormal,

	        prevLine: {stream: null},
	        thisLine: {stream: null},

	        block: blockNormal,
	        htmlState: null,
	        indentation: 0,

	        inline: inlineNormal,
	        text: handleText,

	        formatting: false,
	        linkText: false,
	        linkHref: false,
	        linkTitle: false,
	        code: 0,
	        em: false,
	        strong: false,
	        header: 0,
	        setext: 0,
	        hr: false,
	        taskList: false,
	        list: false,
	        listStack: [],
	        quote: 0,
	        trailingSpace: 0,
	        trailingSpaceNewLine: false,
	        strikethrough: false,
	        emoji: false,
	        fencedEndRE: null
	      };
	    },

	    copyState: function(s) {
	      return {
	        f: s.f,

	        prevLine: s.prevLine,
	        thisLine: s.thisLine,

	        block: s.block,
	        htmlState: s.htmlState && CodeMirror.copyState(htmlMode, s.htmlState),
	        indentation: s.indentation,

	        localMode: s.localMode,
	        localState: s.localMode ? CodeMirror.copyState(s.localMode, s.localState) : null,

	        inline: s.inline,
	        text: s.text,
	        formatting: false,
	        linkText: s.linkText,
	        linkTitle: s.linkTitle,
	        linkHref: s.linkHref,
	        code: s.code,
	        em: s.em,
	        strong: s.strong,
	        strikethrough: s.strikethrough,
	        emoji: s.emoji,
	        header: s.header,
	        setext: s.setext,
	        hr: s.hr,
	        taskList: s.taskList,
	        list: s.list,
	        listStack: s.listStack.slice(0),
	        quote: s.quote,
	        indentedCode: s.indentedCode,
	        trailingSpace: s.trailingSpace,
	        trailingSpaceNewLine: s.trailingSpaceNewLine,
	        md_inside: s.md_inside,
	        fencedEndRE: s.fencedEndRE
	      };
	    },

	    token: function(stream, state) {

	      // Reset state.formatting
	      state.formatting = false;

	      if (stream != state.thisLine.stream) {
	        state.header = 0;
	        state.hr = false;

	        if (stream.match(/^\s*$/, true)) {
	          blankLine(state);
	          return null;
	        }

	        state.prevLine = state.thisLine;
	        state.thisLine = {stream: stream};

	        // Reset state.taskList
	        state.taskList = false;

	        // Reset state.trailingSpace
	        state.trailingSpace = 0;
	        state.trailingSpaceNewLine = false;

	        if (!state.localState) {
	          state.f = state.block;
	          if (state.f != htmlBlock) {
	            var indentation = stream.match(/^\s*/, true)[0].replace(/\t/g, expandedTab).length;
	            state.indentation = indentation;
	            state.indentationDiff = null;
	            if (indentation > 0) return null;
	          }
	        }
	      }
	      return state.f(stream, state);
	    },

	    innerMode: function(state) {
	      if (state.block == htmlBlock) return {state: state.htmlState, mode: htmlMode};
	      if (state.localState) return {state: state.localState, mode: state.localMode};
	      return {state: state, mode: mode};
	    },

	    indent: function(state, textAfter, line) {
	      if (state.block == htmlBlock && htmlMode.indent) return htmlMode.indent(state.htmlState, textAfter, line)
	      if (state.localState && state.localMode.indent) return state.localMode.indent(state.localState, textAfter, line)
	      return CodeMirror.Pass
	    },

	    blankLine: blankLine,

	    getType: getType,

	    blockCommentStart: "<!--",
	    blockCommentEnd: "-->",
	    closeBrackets: "()[]{}''\"\"``",
	    fold: "markdown"
	  };
	  return mode;
	}, "xml");

	CodeMirror.defineMIME("text/markdown", "markdown");

	CodeMirror.defineMIME("text/x-markdown", "markdown");

	});
	});

	function Editor(textarea) {
	    var editor = codemirror.fromTextArea(textarea, {
	        mode: 'markdown',
	        theme: 'formwork',
	        indentUnit: 4,
	        lineWrapping: true,
	        addModeClass: true,
	        extraKeys: {'Enter': 'newlineAndIndentContinueMarkdownList'}
	    });

	    var toolbar = $('.editor-toolbar[data-for=' + textarea.id + ']');

	    $('[data-command=bold]', toolbar).addEventListener('click', function () {
	        insertAtCursor('**');
	    });

	    $('[data-command=italic]', toolbar).addEventListener('click', function () {
	        insertAtCursor('_');
	    });

	    $('[data-command=ul]', toolbar).addEventListener('click', function () {
	        insertAtCursor(prependSequence() + '- ', '');
	    });

	    $('[data-command=ol]', toolbar).addEventListener('click', function () {
	        var num = /^\d+\./.exec(lastLine(editor.getValue()));
	        if (num) {
	            insertAtCursor('\n' + (parseInt(num) + 1) + '. ', '');
	        } else {
	            insertAtCursor(prependSequence() + '1. ', '');
	        }
	    });

	    $('[data-command=quote]', toolbar).addEventListener('click', function () {
	        insertAtCursor(prependSequence() + '> ', '');
	    });

	    $('[data-command=link]', toolbar).addEventListener('click', function () {
	        var selection = editor.getSelection();
	        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
	            insertAtCursor('[', '](' + selection + ')', true);
	        } else if (selection !== '') {
	            insertAtCursor('[' + selection + '](http://', ')', true);
	        } else {
	            insertAtCursor('[', '](http://)');
	        }
	    });

	    $('[data-command=image]', toolbar).addEventListener('click', function () {
	        Modals$1.show('imagesModal', null, function (modal) {
	            var selected = $('.image-picker-thumbnail.selected', modal);
	            if (selected) {
	                selected.classList.remove('selected');
	            }
	            function confirmImage() {
	                var filename = $('.image-picker-thumbnail.selected', $('#imagesModal')).getAttribute('data-filename');
	                if (filename !== undefined) {
	                    insertAtCursor(prependSequence() + '![', '](' + filename + ')');
	                } else {
	                    insertAtCursor(prependSequence() + '![](', ')');
	                }
	                this.removeEventListener('click', confirmImage);
	            }
	            $('.image-picker-confirm', modal).addEventListener('click', confirmImage);
	        });
	    });

	    $('[data-command=summary]', toolbar).addEventListener('click', function () {
	        var prevChar, prepend;
	        if (!hasSummarySequence()) {
	            prevChar = prevCursorChar();
	            prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
	            insertAtCursor(prepend + '\n===\n\n', '');
	            this.setAttribute('disabled', '');
	        }
	    });

	    $('[data-command=undo]', toolbar).addEventListener('click', function () {
	        editor.undo();
	        editor.focus();
	    });

	    $('[data-command=redo]', toolbar).addEventListener('click', function () {
	        editor.redo();
	        editor.focus();
	    });

	    disableSummaryCommand();

	    editor.on('changes', Utils.debounce(function () {
	        textarea.value = editor.getValue();
	        disableSummaryCommand();
	        if (editor.historySize().undo < 1) {
	            $('[data-command=undo]').setAttribute('disabled', '');
	        } else {
	            $('[data-command=undo]').removeAttribute('disabled');
	        }
	        if (editor.historySize().redo < 1) {
	            $('[data-command=redo]').setAttribute('disabled', '');
	        } else {
	            $('[data-command=redo]').removeAttribute('disabled');
	        }
	    }, 500));

	    document.addEventListener('keydown', function (event) {
	        if (!event.altKey && (event.ctrlKey || event.metaKey)) {
	            switch (event.which) {
	            case 66: // ctrl/cmd + B
	                $('[data-command=bold]', toolbar).click();
	                event.preventDefault();
	                break;
	            case 73: // ctrl/cmd + I
	                $('[data-command=italic]', toolbar).click();
	                event.preventDefault();
	                break;
	            case 75: // ctrl/cmd + K
	                $('[data-command=link]', toolbar).click();
	                event.preventDefault();
	                break;
	            }
	        }
	    });

	    function hasSummarySequence() {
	        return /\n+===\n+/.test(editor.getValue());
	    }

	    function disableSummaryCommand() {
	        if (hasSummarySequence()) {
	            $('[data-command=summary]', toolbar).setAttribute('disabled', '');
	        } else {
	            $('[data-command=summary]', toolbar).removeAttribute('disabled');
	        }
	    }

	    function lastLine(text) {
	        var index = text.lastIndexOf('\n');
	        if (index === -1) {
	            return text;
	        }
	        return text.substring(index + 1);
	    }

	    function prevCursorChar() {
	        var line = editor.getLine(editor.getCursor().line);
	        return line.length === 0 ? undefined : line.slice(-1);
	    }

	    function prependSequence() {
	        switch (prevCursorChar()) {
	        case undefined:
	            return '';
	        case '\n':
	            return '\n';
	        default:
	            return '\n\n';
	        }
	    }

	    function insertAtCursor(leftValue, rightValue, dropSelection) {
	        var selection, cursor, lineBreaks;
	        if (rightValue === undefined) {
	            rightValue = leftValue;
	        }
	        selection = dropSelection === true ? '' : editor.getSelection();
	        cursor = editor.getCursor();
	        lineBreaks = leftValue.split('\n').length - 1;
	        editor.replaceSelection(leftValue + selection + rightValue);
	        editor.setCursor(cursor.line + lineBreaks, cursor.ch + leftValue.length - lineBreaks);
	        editor.focus();
	    }
	}

	function FileInput(input) {
	    var label = $('label[for="' + input.id + '"]');

	    input.setAttribute('data-label', $('label[for="' + input.id + '"] span').innerHTML);
	    input.addEventListener('change', updateLabel);
	    input.addEventListener('input', updateLabel);

	    label.addEventListener('drag', preventDefault);
	    label.addEventListener('dragstart', preventDefault);
	    label.addEventListener('dragend', preventDefault);
	    label.addEventListener('dragover', handleDragenter);
	    label.addEventListener('dragenter', handleDragenter);
	    label.addEventListener('dragleave', handleDragleave);

	    label.addEventListener('drop', function (event) {
	        input.files = event.dataTransfer.files;
	        // Firefox won't trigger a change event, so we explicitly do that
	        Utils.triggerEvent(input, 'change');
	        event.preventDefault();
	    });

	    function updateLabel() {
	        var span = $('label[for="' + this.id + '"] span');
	        if (this.files.length > 0) {
	            span.innerHTML = this.files[0].name;
	        } else {
	            span.innerHTML = this.getAttribute('data-label');
	        }
	    }

	    function preventDefault(event) {
	        event.preventDefault();
	    }

	    function handleDragenter(event) {
	        this.classList.add('drag');
	        event.preventDefault();
	    }

	    function handleDragleave(event) {
	        this.classList.remove('drag');
	        event.preventDefault();
	    }
	}

	function Form(form) {
	    var originalData = Utils.serializeForm(form);

	    window.addEventListener('beforeunload', handleBeforeunload);

	    form.addEventListener('submit', removeBeforeUnload);

	    $$('a[href]:not([href^="#"]):not([target="_blank"])').forEach(function (element) {
	        element.addEventListener('click', function (event) {
	            if (hasChanged()) {
	                event.preventDefault();
	                Modals$1.show('changesModal', null, function (modal) {
	                    $('[data-command=continue]', modal).setAttribute('data-href', element.href);
	                });
	            }
	        });
	    });

	    $$('input[type=file][data-auto-upload]', form).forEach(function (element) {
	        element.addEventListener('change', function () {
	            if (!hasChanged(false)) {
	                removeBeforeUnload();
	                form.submit();
	            }
	        });
	    });

	    registerModalExceptions();

	    function handleBeforeunload(event) {
	        if (hasChanged()) {
	            event.preventDefault();
	            event.returnValue = '';
	        }
	    }

	    function removeBeforeUnload() {
	        window.removeEventListener('beforeunload', handleBeforeunload);
	    }

	    function registerModalExceptions() {
	        var changesModal = document.getElementById('changesModal');
	        var deletePageModal = document.getElementById('deletePageModal');
	        var deleteUserModal = document.getElementById('deleteUserModal');

	        if (changesModal) {
	            $('[data-command=continue]', changesModal).addEventListener('click', function () {
	                removeBeforeUnload();
	                window.location.href = this.getAttribute('data-href');
	            });
	        }

	        if (deletePageModal) {
	            $('[data-command=delete]', deletePageModal).addEventListener('click', removeBeforeUnload);
	        }

	        if (deleteUserModal) {
	            $('[data-command=delete]', deleteUserModal).addEventListener('click', removeBeforeUnload);
	        }
	    }

	    function hasChanged(checkFileInputs) {
	        var fileInputs, i;
	        if (typeof checkFileInputs === 'undefined') {
	            checkFileInputs = true;
	        }
	        fileInputs = $$('input[type=file]', form);
	        if (checkFileInputs === true && fileInputs.length > 0) {
	            for (i = 0; i < fileInputs.length; i++) {
	                if (fileInputs[i].files.length > 0) {
	                    return true;
	                }
	            }
	        }
	        return Utils.serializeForm(form) !== originalData;
	    }
	}

	function ImagePicker(element) {
	    var options = $$('option', element);
	    var confirmCommand = $('.image-picker-confirm', element.parentNode);
	    var uploadCommand = $('[data-command=upload]', element.parentNode);

	    var container, thumbnail, i;

	    element.style.display = 'none';

	    if (options.length > 0) {
	        container = document.createElement('div');
	        container.className = 'image-picker-thumbnails';

	        for (i = 0; i < options.length; i++) {
	            thumbnail = document.createElement('div');
	            thumbnail.className = 'image-picker-thumbnail';
	            thumbnail.style.backgroundImage = 'url(' + options[i].value + ')';
	            thumbnail.setAttribute('data-uri', options[i].value);
	            thumbnail.setAttribute('data-filename', options[i].text);
	            thumbnail.addEventListener('click', handleThumbnailClick);
	            thumbnail.addEventListener('dblclick', handleThumbnailDblclick);
	            container.appendChild(thumbnail);
	        }

	        element.parentNode.insertBefore(container, element);
	        $('.image-picker-empty-state').style.display = 'none';
	    }

	    confirmCommand.addEventListener('click', function () {
	        var selectedThumbnail = $('.image-picker-thumbnail.selected');
	        var target = document.getElementById(this.getAttribute('data-target'));
	        if (selectedThumbnail && target) {
	            target.value = selectedThumbnail.getAttribute('data-filename');
	        }
	    });

	    uploadCommand.addEventListener('click', function () {
	        document.getElementById(this.getAttribute('data-upload-target')).click();
	    });

	    function handleThumbnailClick() {
	        var target = document.getElementById($('.image-picker-confirm').getAttribute('data-target'));
	        if (target) {
	            target.value = this.getAttribute('data-filename');
	        }
	        $$('.image-picker-thumbnail').forEach(function (element) {
	            element.classList.remove('selected');
	        });
	        this.classList.add('selected');
	    }

	    function handleThumbnailDblclick() {
	        this.click();
	        $('.image-picker-confirm').click();
	    }
	}

	function RangeInput(input) {
	    input.addEventListener('change', updateValueLabel);
	    input.addEventListener('input', updateValueLabel);

	    function updateValueLabel() {
	        $('.range-input-value', this.parentNode).innerHTML = this.value;
	    }
	}

	function TagInput(input) {
	    var options = {addKeyCodes: [32]};
	    var tags = [];
	    var field, innerInput, hiddenInput, placeholder, dropdown;

	    createField();
	    createDropdown();

	    registerInputEvents();

	    function createField() {
	        var isRequired = input.hasAttribute('required');
	        var isDisabled = input.hasAttribute('disabled');

	        field = document.createElement('div');
	        field.className = 'tag-input';

	        innerInput = document.createElement('input');
	        innerInput.className = 'tag-inner-input';
	        innerInput.id = input.id;
	        innerInput.type = 'text';
	        innerInput.placeholder = input.placeholder;

	        innerInput.setAttribute('size', '');

	        hiddenInput = document.createElement('input');
	        hiddenInput.className = 'tag-hidden-input';
	        hiddenInput.name = input.name;
	        hiddenInput.id = input.id;
	        hiddenInput.type = 'text';
	        hiddenInput.value = input.value;
	        hiddenInput.readOnly = true;
	        hiddenInput.hidden = true;

	        if (isRequired) {
	            hiddenInput.required = true;
	        }

	        if (isDisabled) {
	            field.disabled = true;
	            innerInput.disabled = true;
	            hiddenInput.disabled = true;
	        }

	        input.parentNode.replaceChild(field, input);
	        field.appendChild(innerInput);
	        field.appendChild(hiddenInput);

	        if (hiddenInput.value) {
	            tags = hiddenInput.value.split(', ');
	            tags.forEach(function (value, index) {
	                value = value.trim();
	                tags[index] = value;
	                insertTag(value);
	            });
	        }

	        if (innerInput.placeholder) {
	            placeholder = innerInput.placeholder;
	            updatePlaceholder();
	        } else {
	            placeholder = '';
	        }

	        field.addEventListener('mousedown', function (event) {
	            innerInput.focus();
	            event.preventDefault();
	        });
	    }

	    function createDropdown() {
	        var list, key, item;

	        if (input.hasAttribute('data-options')) {

	            list = JSON.parse(input.getAttribute('data-options'));

	            dropdown = document.createElement('div');
	            dropdown.className = 'dropdown-list';

	            for (key in list) {
	                item = document.createElement('div');
	                item.className = 'dropdown-item';
	                item.innerHTML = list[key];
	                item.setAttribute('data-value', key);
	                item.addEventListener('click', function () {
	                    addTag(this.getAttribute('data-value'));
	                });
	                dropdown.appendChild(item);
	            }

	            field.appendChild(dropdown);

	            innerInput.addEventListener('focus', function () {
	                if (getComputedStyle(dropdown).display === 'none') {
	                    updateDropdown();
	                    dropdown.scrollTop = 0;
	                    dropdown.style.display = 'block';
	                }
	            });

	            innerInput.addEventListener('blur', function () {
	                if (getComputedStyle(dropdown).display !== 'none') {
	                    updateDropdown();
	                    dropdown.style.display = 'none';
	                }
	            });

	            innerInput.addEventListener('keydown', function (event) {
	                switch (event.which) {
	                case 8: // backspace
	                    updateDropdown();
	                    break;
	                case 13: // enter
	                    if (getComputedStyle(dropdown).display !== 'none') {
	                        addTagFromSelectedDropdownItem();
	                        event.preventDefault();
	                    }
	                    break;
	                case 38: // up arrow
	                    if (getComputedStyle(dropdown).display !== 'none') {
	                        selectPrevDropdownItem();
	                        event.preventDefault();
	                    }
	                    break;
	                case 40: // down arrow
	                    if (getComputedStyle(dropdown).display !== 'none') {
	                        selectNextDropdownItem();
	                        event.preventDefault();
	                    }
	                    break;
	                default:
	                    if (options.addKeyCodes.indexOf(event.which) > -1) {
	                        addTagFromSelectedDropdownItem();
	                        event.preventDefault();
	                    }
	                }
	            });

	            innerInput.addEventListener('keyup', Utils.debounce(function (event) {
	                var value = innerInput.value.trim();
	                switch (event.which) {
	                case 27: // escape
	                    dropdown.style.display = 'none';
	                    break;
	                case 38: // up arrow
	                case 40: // down arrow
	                    return true;
	                default:
	                    dropdown.style.display = 'block';
	                    filterDropdown(value);
	                    if (value.length > 0) {
	                        selectFirstDropdownItem();
	                    }
	                }
	            }, 100));
	        }
	    }

	    function registerInputEvents() {
	        innerInput.addEventListener('focus', function () {
	            field.classList.add('focused');
	        });

	        innerInput.addEventListener('blur', function () {
	            var value = innerInput.value.trim();
	            if (value !== '') {
	                addTag(value);
	            }
	            field.classList.remove('focused');
	        });

	        innerInput.addEventListener('keydown', function () {
	            var value = innerInput.value.trim();
	            switch (event.which) {
	            case 8: // backspace
	                if (value === '') {
	                    removeTag(tags[tags.length - 1]);
	                    if (innerInput.previousSibling){
	                        innerInput.parentNode.removeChild(innerInput.previousSibling);
	                    }
	                    event.preventDefault();
	                } else {
	                    innerInput.size = Math.max(innerInput.value.length, innerInput.placeholder.length, 1);
	                }
	                break;
	            case 13: // enter
	            case 188: // comma
	                if (value !== '') {
	                    addTag(value);
	                }
	                event.preventDefault();
	                break;
	            case 27: // escape
	                clearInput();
	                innerInput.blur();
	                event.preventDefault();
	                break;
	            default:
	                if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
	                    addTag(value);
	                    event.preventDefault();
	                    break;
	                }
	                if (value.length > 0) {
	                    innerInput.size = innerInput.value.length + 2;
	                }
	                break;
	            }
	        });
	    }

	    function updateTags() {
	        hiddenInput.value = tags.join(', ');
	        updatePlaceholder();
	    }

	    function updatePlaceholder() {
	        if (placeholder.length > 0) {
	            if (tags.length === 0) {
	                innerInput.placeholder = placeholder;
	                innerInput.size = placeholder.length;
	            } else {
	                innerInput.placeholder = '';
	                innerInput.size = 1;
	            }
	        }
	    }

	    function validateTag(value) {
	        if (tags.indexOf(value) === -1) {
	            if (dropdown) {
	                return $('[data-value="' + value + '"]', dropdown) !== null;
	            }
	            return true;
	        }
	        return false;
	    }

	    function insertTag(value) {
	        var tag = document.createElement('span');
	        var tagRemove = document.createElement('i');
	        tag.className = 'tag';
	        tag.innerHTML = value;
	        tag.style.marginRight = '.25rem';
	        innerInput.parentNode.insertBefore(tag, innerInput);

	        tagRemove.className = 'tag-remove';
	        tagRemove.setAttribute('role', 'button');
	        tagRemove.addEventListener('mousedown', function (event) {
	            removeTag(value);
	            tag.parentNode.removeChild(tag);
	            event.preventDefault();
	        });
	        tag.appendChild(tagRemove);
	    }

	    function addTag(value) {
	        if (validateTag(value)) {
	            tags.push(value);
	            insertTag(value);
	            updateTags();
	        } else {
	            updatePlaceholder();
	        }
	        innerInput.value = '';
	        if (dropdown) {
	            updateDropdown();
	        }
	    }

	    function removeTag(value) {
	        var index = tags.indexOf(value);
	        if (index > -1) {
	            tags.splice(index, 1);
	            updateTags();
	        }
	        if (dropdown) {
	            updateDropdown();
	        }
	    }

	    function clearInput() {
	        innerInput.value = '';
	        updatePlaceholder();
	    }

	    function updateDropdown() {
	        var visibleItems = 0;
	        $$('.dropdown-item', dropdown).forEach(function (element) {
	            if (getComputedStyle(element).display !== 'none') {
	                visibleItems++;
	            }
	            if (tags.indexOf(element.getAttribute('data-value')) === -1) {
	                element.style.display = 'block';
	            } else {
	                element.style.display = 'none';
	            }
	            element.classList.remove('selected');
	        });
	        if (visibleItems > 0) {
	            dropdown.style.display = 'block';
	        } else {
	            dropdown.style.display = 'none';
	        }
	    }

	    function filterDropdown(value) {
	        var visibleItems = 0;
	        dropdown.style.display = 'block';
	        $$('.dropdown-item', dropdown).forEach(function (element) {
	            var text = element.textContent;
	            var regexp = new RegExp(Utils.makeDiacriticsRegExp(Utils.escapeRegExp(value)), 'i');
	            if (text.match(regexp) !== null && element.style.display !== 'none') {
	                element.style.display = 'block';
	                visibleItems++;
	            } else {
	                element.style.display = 'none';
	            }
	        });
	        if (visibleItems > 0) {
	            dropdown.style.display = 'block';
	        } else {
	            dropdown.style.display = 'none';
	        }
	    }

	    function scrollToDropdownItem(item) {
	        var dropdownScrollTop = dropdown.scrollTop;
	        var dropdownHeight = dropdown.clientHeight;
	        var dropdownScrollBottom = dropdownScrollTop + dropdownHeight;
	        var dropdownStyle = getComputedStyle(dropdown);
	        var dropdownPaddingTop = parseInt(dropdownStyle.paddingTop);
	        var dropdownPaddingBottom = parseInt(dropdownStyle.paddingBottom);
	        var itemTop = item.offsetTop;
	        var itemHeight = item.clientHeight;
	        var itemBottom = itemTop + itemHeight;
	        if (itemTop < dropdownScrollTop) {
	            dropdown.scrollTop = itemTop - dropdownPaddingTop;
	        } else if (itemBottom > dropdownScrollBottom) {
	            dropdown.scrollTop = itemBottom - dropdownHeight + dropdownPaddingBottom;
	        }
	    }

	    function addTagFromSelectedDropdownItem() {
	        var selectedItem = $('.dropdown-item.selected', dropdown);
	        if (getComputedStyle(selectedItem).display !== 'none') {
	            innerInput.value = selectedItem.getAttribute('data-value');
	        }
	    }

	    function selectDropdownItem(item) {
	        var selectedItem = $('.dropdown-item.selected', dropdown);
	        if (selectedItem) {
	            selectedItem.classList.remove('selected');
	        }
	        if (item) {
	            item.classList.add('selected');
	            scrollToDropdownItem(item);
	        }
	    }

	    function selectFirstDropdownItem() {
	        var items = $$('.dropdown-item', dropdown);
	        var i;
	        for (i = 0; i < items.length; i++) {
	            if (getComputedStyle(items[i]).display !== 'none') {
	                selectDropdownItem(items[i]);
	                return;
	            }
	        }
	    }

	    function selectLastDropdownItem() {
	        var items = $$('.dropdown-item', dropdown);
	        var i;
	        for (i = items.length - 1; i >= 0; i--) {
	            if (getComputedStyle(items[i]).display !== 'none') {
	                selectDropdownItem(items[i]);
	                return;
	            }
	        }
	    }

	    function selectPrevDropdownItem() {
	        var selectedItem = $('.dropdown-item.selected', dropdown);
	        var previousItem;
	        if (selectedItem) {
	            previousItem = selectedItem.previousSibling;
	            while (previousItem && previousItem.style.display === 'none') {
	                previousItem = previousItem.previousSibling;
	            }
	            if (previousItem) {
	                return selectDropdownItem(previousItem);
	            }
	            selectDropdownItem(selectedItem.previousSibling);
	        }
	        selectLastDropdownItem();
	    }

	    function selectNextDropdownItem() {
	        var selectedItem = $('.dropdown-item.selected', dropdown);
	        var nextItem;
	        if (selectedItem) {
	            nextItem = selectedItem.nextSibling;
	            while (nextItem && nextItem.style.display === 'none') {
	                nextItem = nextItem.nextSibling;
	            }
	            if (nextItem) {
	                return selectDropdownItem(nextItem);
	            }
	        }
	        selectFirstDropdownItem();
	    }
	}

	var Forms = {
	    init: function () {

	        $$('[data-form]').forEach(function (element) {
	            Form(element);
	        });

	        $$('input[data-enable]').forEach(function (element) {
	            element.addEventListener('change', function () {
	                var i, input;
	                var inputs = this.getAttribute('data-enable').split(',');
	                for (i = 0; i < inputs.length; i++) {
	                    input = $('input[name="' + inputs[i] + '"]');
	                    if (!this.checked) {
	                        input.setAttribute('disabled', '');
	                    } else {
	                        input.removeAttribute('disabled');
	                    }
	                }
	            });
	        });

	        $$('.input-reset').forEach(function (element) {
	            element.addEventListener('click', function () {
	                var target = document.getElementById(this.getAttribute('data-reset'));
	                target.value = '';
	                Utils.triggerEvent(target, 'change');
	            });
	        });

	        $$('.date-input').forEach(function (element) {
	            DatePicker(element, Formwork.config.DatePicker);
	        });

	        $$('.image-input').forEach(function (element) {
	            element.addEventListener('click', function () {
	                Modals$1.show('imagesModal', null, function (modal) {
	                    var selected = $('.image-picker-thumbnail.selected', modal);
	                    if (selected) {
	                        selected.classList.remove('selected');
	                    }
	                    if (this.value) {
	                        $('.image-picker-thumbnail[data-filename="' + this.value + '"]', modal).classList.add('selected');
	                    }
	                    $('.image-picker-confirm', modal).setAttribute('data-target', element.id);
	                });
	            });
	        });

	        $$('.image-picker').forEach(function (element) {
	            ImagePicker(element);
	        });

	        $$('.editor-textarea').forEach(function (element) {
	            Editor(element);
	        });

	        $$('input[type=file]').forEach(function (element) {
	            FileInput(element);
	        });

	        $$('input[data-field=tags]').forEach(function (element) {
	            TagInput(element);
	        });

	        $$('input[type=range]').forEach(function (element) {
	            RangeInput(element);
	        });

	        $$('.array-input').forEach(function (element) {
	            ArrayInput(element);
	        });
	    }
	};

	var Pages = {
	    init: function () {

	        var commandExpandAllPages = $('[data-command=expand-all-pages]');
	        var commandCollapseAllPages = $('[data-command=collapse-all-pages]');
	        var commandReorderPages = $('[data-command=reorder-pages]');

	        var searchInput = $('.page-search');

	        var newPageModal = document.getElementById('newPageModal');
	        var slugModal = document.getElementById('slugModal');

	        $$('.pages-list').forEach(function (element) {
	            if (element.getAttribute('data-sortable-children') === 'true') {
	                initSortable(element);
	            }
	        });

	        $$('.page-details').forEach(function (element) {
	            var toggle = $('.page-children-toggle', element);
	            if (toggle) {
	                element.addEventListener('click', function () {
	                    toggle.click();
	                });
	            }
	        });

	        $$('.page-details a').forEach(function (element) {
	            element.addEventListener('click', function (event) {
	                event.stopPropagation();
	            });
	        });

	        $$('.page-children-toggle').forEach(function (element) {
	            element.addEventListener('click', function (event) {
	                togglePagesList(this);
	                event.stopPropagation();
	            });
	        });

	        if (commandExpandAllPages) {
	            commandExpandAllPages.addEventListener('click', function () {
	                expandAllPages();
	                this.blur();
	            });
	        }

	        if (commandCollapseAllPages) {
	            commandCollapseAllPages.addEventListener('click', function () {
	                collapseAllPages();
	                this.blur();
	            });
	        }

	        if (commandReorderPages) {
	            commandReorderPages.addEventListener('click', function () {
	                this.classList.toggle('active');
	                $$('.pages-list .sort-handle').forEach(function (element) {
	                    Utils.toggleElement(element, 'inline');
	                });
	                this.blur();
	            });
	        }

	        if (searchInput) {
	            searchInput.addEventListener('focus', function () {
	                $$('.pages-children').forEach(function (element) {
	                    element.setAttribute('data-display', getComputedStyle(element).display);
	                });
	            });

	            searchInput.addEventListener('keyup', Utils.debounce(handleSearch, 100));
	            searchInput.addEventListener('search', handleSearch);

	            document.addEventListener('keydown', function (event) {
	                if (event.ctrlKey || event.metaKey) {
	                    // ctrl/cmd + F
	                    if (event.which === 70 && document.activeElement !== searchInput) {
	                        searchInput.focus();
	                        event.preventDefault();
	                    }
	                }
	            });
	        }

	        if (newPageModal) {
	            $('#page-title', newPageModal).addEventListener('keyup', function () {
	                $('#page-slug', newPageModal).value = Utils.slug(this.value);
	            });

	            $('#page-slug', newPageModal).addEventListener('keyup', handleSlugChange);
	            $('#page-slug', newPageModal).addEventListener('blur', handleSlugChange);

	            $('#page-parent', newPageModal).addEventListener('change', function () {
	                var option = this.options[this.selectedIndex];
	                var pageTemplate = $('#page-template', newPageModal);
	                var allowedTemplates = option.getAttribute('data-allowed-templates');
	                var i = 0;

	                if (allowedTemplates !== null) {
	                    allowedTemplates = allowedTemplates.split(', ');
	                    pageTemplate.setAttribute('data-previous-value', pageTemplate.value);
	                    pageTemplate.value = allowedTemplates[0];
	                    for (i = 0; i < pageTemplate.options.length; i++) {
	                        if (allowedTemplates.indexOf(pageTemplate.options[i].value) === -1) {
	                            pageTemplate.options[i].setAttribute('disabled', '');
	                        }
	                    }
	                } else {
	                    pageTemplate.value = pageTemplate.getAttribute('data-previous-value');
	                    pageTemplate.removeAttribute('data-previous-value');
	                    for (i = 0; i < pageTemplate.options.length; i++) {
	                        pageTemplate.options[i].disabled = false;
	                    }
	                }
	            });
	        }

	        if (slugModal) {
	            $('[data-command=change-slug]').addEventListener('click', function () {
	                Modals$1.show('slugModal', null, function (modal) {
	                    var slug = document.getElementById('slug').value;
	                    var slugInput = $('#page-slug', modal);
	                    slugInput.value = slug;
	                    slugInput.setAttribute('placeholder', slug);
	                    slugInput.focus();
	                });
	            });

	            $('#page-slug', slugModal).addEventListener('keydown', function (event) {
	                // enter
	                if (event.which === 13) {
	                    $('[data-command=continue]', slugModal).click();
	                }
	            });

	            $('#page-slug', slugModal).addEventListener('keyup', handleSlugChange);
	            $('#page-slug', slugModal).addEventListener('blur', handleSlugChange);

	            $('[data-command=generate-slug]', slugModal).addEventListener('click', function () {
	                var slug = Utils.slug(document.getElementById('title').value);
	                $('#page-slug', slugModal).value = slug;
	                $('#page-slug', slugModal).focus();
	            });

	            $('[data-command=continue]', slugModal).addEventListener('click', function () {
	                var slug = $('#page-slug', slugModal).value.replace(/^-+|-+$/, '');
	                var route;
	                if (slug.length > 0) {
	                    route = $('.page-route span').innerHTML;
	                    $$('#page-slug, #slug').forEach(function (element) {
	                        element.value = slug;
	                    });
	                    $('#page-slug', slugModal).value = slug;
	                    document.getElementById('slug').value = slug;
	                    $('.page-route span').innerHTML = route.replace(/\/[a-z0-9-]+\/$/, '/' + slug + '/');
	                }
	                Modals$1.hide('slugModal');
	            });
	        }

	        function expandAllPages() {
	            $$('.pages-children').forEach(function (element) {
	                element.style.display = 'block';
	            });
	            $$('.pages-list .page-children-toggle').forEach(function (element) {
	                element.classList.remove('toggle-collapsed');
	                element.classList.add('toggle-expanded');
	            });
	        }

	        function collapseAllPages() {
	            $$('.pages-children').forEach(function (element) {
	                element.style.display = 'none';
	            });
	            $$('.pages-list .page-children-toggle').forEach(function (element) {
	                element.classList.remove('toggle-expanded');
	                element.classList.add('toggle-collapsed');
	            });
	        }

	        function togglePagesList(list) {
	            $$('.pages-list', list.closest('li')).forEach(function (element) {
	                Utils.toggleElement(element);
	            });
	            list.classList.toggle('toggle-expanded');
	            list.classList.toggle('toggle-collapsed');
	        }

	        function initSortable(element) {
	            var originalOrder = [];

	            var sortable = Sortable.create(element, {
	                handle: '.sort-handle',
	                filter: '[data-sortable=false]',
	                forceFallback: true,

	                onClone: function (event) {
	                    event.item.closest('.pages-list').classList.add('dragging');

	                    $$('.pages-children', event.item).forEach(function (element) {
	                        element.style.display = 'none';
	                    });
	                    $$('.page-children-toggle').forEach(function (element) {
	                        element.classList.remove('toggle-expanded');
	                        element.classList.add('toggle-collapsed');
	                        element.style.opacity = '0.5';
	                    });
	                },

	                onMove: function (event) {
	                    if (event.related.getAttribute('data-sortable') === 'false') {
	                        return false;
	                    }
	                    $$('.pages-children', event.related).forEach(function (element) {
	                        element.style.display = 'none';
	                    });
	                },

	                onEnd: function (event) {
	                    var data, notification;

	                    event.item.closest('.pages-list').classList.remove('dragging');

	                    $$('.page-children-toggle').forEach(function (element) {
	                        element.style.opacity = '';
	                    });

	                    if (event.newIndex === event.oldIndex) {
	                        return;
	                    }

	                    sortable.option('disabled', true);

	                    data = {
	                        'csrf-token': $('meta[name=csrf-token]').getAttribute('content'),
	                        parent: element.getAttribute('data-parent'),
	                        from: event.oldIndex,
	                        to: event.newIndex
	                    };

	                    Request({
	                        method: 'POST',
	                        url: Formwork.config.baseUri + 'pages/reorder/',
	                        data: data
	                    }, function (response) {
	                        if (response.status) {
	                            notification = new Notification(response.message, response.status, 5000);
	                            notification.show();
	                        }
	                        if (!response.status || response.status === 'error') {
	                            sortable.sort(originalOrder);
	                        }
	                        sortable.option('disabled', false);
	                        originalOrder = sortable.toArray();
	                    });

	                }
	            });

	            originalOrder = sortable.toArray();
	        }

	        function handleSearch() {
	            var value = this.value;
	            var regexp;
	            if (value.length === 0) {
	                $$('.pages-children').forEach(function (element) {
	                    element.style.display = element.getAttribute('data-display');
	                });
	                $$('.pages-item, .page-children-toggle').forEach(function (element) {
	                    element.style.display = '';
	                });
	                $$('.page-details').forEach(function (element) {
	                    element.style.paddingLeft = '';
	                });
	                $$('.page-title a').forEach(function (element) {
	                    element.innerHTML = element.textContent;
	                });
	            } else {
	                regexp = new RegExp(Utils.makeDiacriticsRegExp(Utils.escapeRegExp(value)), 'gi');
	                $$('.pages-children').forEach(function (element) {
	                    element.style.display = 'block';
	                });
	                $$('.page-children-toggle').forEach(function (element) {
	                    element.style.display = 'none';
	                });
	                $$('.page-details').forEach(function (element) {
	                    element.style.paddingLeft = '0';
	                });
	                $$('.page-title a').forEach(function (element) {
	                    var pagesItem = element.closest('.pages-item');
	                    var text = element.textContent;
	                    if (text.match(regexp) !== null) {
	                        element.innerHTML = text.replace(regexp, '<mark>$&</mark>');
	                        pagesItem.style.display = '';
	                    } else {
	                        pagesItem.style.display = 'none';
	                    }
	                });
	            }
	        }

	        function handleSlugChange() {
	            this.value = Utils.validateSlug(this.value);
	        }
	    }
	};

	var Tooltips = {
	    init: function () {
	        $$('[title]').forEach(function (element) {
	            element.setAttribute('data-tooltip', element.getAttribute('title'));
	            element.removeAttribute('title');
	        });

	        $$('[data-tooltip]').forEach(function (element) {
	            element.addEventListener('mouseover', function () {
	                var tooltip = new Tooltip(this.getAttribute('data-tooltip'), {
	                    referenceElement: this,
	                    position: 'bottom',
	                    offset: {
	                        x: 0, y: 4
	                    }
	                });
	                tooltip.show();
	            });
	        });

	        $$('[data-overflow-tooltip="true"]').forEach(function (element) {
	            element.addEventListener('mouseover', function () {
	                var tooltip;
	                if (this.offsetWidth < this.scrollWidth) {
	                    tooltip = new Tooltip(this.textContent.trim(), {
	                        referenceElement: this,
	                        position: 'bottom',
	                        offset: {
	                            x: 0, y: 4
	                        }
	                    });
	                    tooltip.show();
	                }
	            });
	        });
	    }
	};

	var Updates = {
	    init: function () {
	        var updaterComponent = document.getElementById('updater-component');
	        var updateStatus, spinner,
	            currentVersion, currentVersionName,
	            newVersion, newVersionName;

	        if (updaterComponent) {
	            updateStatus = $('.update-status');
	            spinner = $('.spinner');
	            currentVersion = $('.current-version');
	            currentVersionName = $('.current-version-name');
	            newVersion = $('.new-version');
	            newVersionName = $('.new-version-name');

	            setTimeout(function () {
	                var data = {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')};

	                Request({
	                    method: 'POST',
	                    url: Formwork.config.baseUri + 'updates/check/',
	                    data: data
	                }, function (response) {
	                    updateStatus.innerHTML = response.message;

	                    if (response.status === 'success') {
	                        if (response.data.uptodate === false) {
	                            showNewVersion(response.data.release.name);
	                        } else {
	                            showCurrentVersion();
	                        }
	                    } else {
	                        spinner.classList.add('spinner-error');
	                    }
	                });
	            }, 1000);

	            $('[data-command=install-updates]').addEventListener('click', function () {
	                newVersion.style.display = 'none';
	                spinner.classList.remove('spinner-info');
	                updateStatus.innerHTML = updateStatus.getAttribute('data-installing-text');

	                Request({
	                    method: 'POST',
	                    url: Formwork.config.baseUri + 'updates/update/',
	                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
	                }, function (response) {
	                    var notification = new Notification(response.message, response.status, 5000);
	                    notification.show();

	                    updateStatus.innerHTML = response.data.status;

	                    if (response.status === 'success') {
	                        showInstalledVersion();
	                    } else {
	                        spinner.classList.add('spinner-error');
	                    }
	                });
	            });
	        }

	        function showNewVersion(name) {
	            spinner.classList.add('spinner-info');
	            newVersionName.innerHTML = name;
	            newVersion.style.display = 'block';
	        }

	        function showCurrentVersion() {
	            spinner.classList.add('spinner-success');
	            currentVersion.style.display = 'block';
	        }

	        function showInstalledVersion() {
	            spinner.classList.add('spinner-success');
	            currentVersionName.innerHTML = newVersionName.innerHTML;
	            currentVersion.style.display = 'block';
	        }
	    }
	};

	var Formwork$1;

	var main = Formwork$1 = {
	    config: {},
	    init: function () {
	        Modals$1.init();
	        Forms.init();
	        Dropdowns.init();
	        Tooltips.init();

	        Dashboard.init();
	        Pages.init();
	        Updates.init();

	        $('.toggle-navigation').addEventListener('click', function () {
	            $('.sidebar').classList.toggle('show');
	        });

	        $$('[data-chart-data]').forEach(function (element) {
	            var data = JSON.parse(element.getAttribute('data-chart-data'));
	            Chart(element, data);
	        });

	        $$('meta[name=notification]').forEach(function (element) {
	            var notification = new Notification(element.getAttribute('content'), element.getAttribute('data-type'), element.getAttribute('data-interval'));
	            notification.show();
	            element.parentNode.removeChild(element);
	        });

	        if ($('[data-command=save]')) {
	            document.addEventListener('keydown', function (event) {
	                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
	                    if (event.which === 83) { // ctrl/cmd + S
	                        $('[data-command=save]').click();
	                        event.preventDefault();
	                    }
	                }
	            });
	        }

	    },

	    initGlobals: function (global) {
	        global.$ = function (selector, parent) {
	            if (typeof parent === 'undefined') {
	                parent = document;
	            }
	            return parent.querySelector(selector);
	        };

	        global.$$ = function (selector, parent) {
	            if (typeof parent === 'undefined') {
	                parent = document;
	            }
	            return parent.querySelectorAll(selector);
	        };

	        // NodeList.prototype.forEach polyfill
	        if (!('forEach' in global.NodeList.prototype)) {
	            global.NodeList.prototype.forEach = global.Array.prototype.forEach;
	        }

	        // Element.prototype.matches polyfill
	        if (!('matches' in global.Element.prototype)) {
	            global.Element.prototype.matches = global.Element.prototype.msMatchesSelector || global.Element.prototype.webkitMatchesSelector;
	        }

	        // Element.prototype.closest polyfill
	        if (!('closest' in global.Element.prototype)) {
	            global.Element.prototype.closest = function (selectors) {
	                var element = this;
	                do {
	                    if (element.matches(selectors)) {
	                        return element;
	                    }
	                    element = element.parentElement || element.parentNode;
	                } while (element !== null && element.nodeType === 1);
	                return null;
	            };
	        }
	    }
	};

	document.addEventListener('DOMContentLoaded', function () {
	    Formwork$1.init();
	});

	Formwork$1.initGlobals(window);

	return main;

}());
