Formwork.Utils = {
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

    download: function (uri, csrfToken) {
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

    escapeRegExp: function (string) {
        return string.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&');
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

    uriPrependBase: function (path, base) {
        var regexp = /^\/+|\/+$/gm;
        var i;
        path = path.replace(regexp, '').split('/');
        base = base.replace(regexp, '').split('/');
        for (i = 0; i < base.length; i++) {
            if (base[i] === path[0] && base[i + 1] !== path[0]) {
                base = base.slice(0, i);
            }
        }
        return '/' + base.concat(path).join('/') + '/';
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

    toggleElement: function (element) {
        var visibility = element.style.display || getComputedStyle(element).display;
        if (visibility === 'none') {
            element.style.display = element.tagName.toLowerCase() === 'span' ? 'inline' : 'block';
        } else {
            element.style.display = 'none';
        }
    },

    extendObject: function (target) {
        var i, source, prop;
        target = target || {};
        for (i = 1; i < arguments.length; i++) {
            source = arguments[i];
            for (prop in source) {
                target[prop] = source[prop];
            }
        }
        return target;
    },

    serializeObject: function (obj) {
        var query = '';
        var key;
        for (key in obj) {
            if (obj.hasOwnProperty(key)) {
                if (query.length > 0) {
                    query += '&';
                }
                query += key + '=' + encodeURIComponent(obj[key]);
            }
        }
        return query;
    },

    serializeForm: function (form) {
        var field, i, j;
        var s = [];
        var len = form.elements.length;
        for (i = 0; i < len; i++) {
            field = form.elements[i];
            if (field.name && !field.disabled && field.type !== 'file' && field.type !== 'reset' && field.type !== 'submit' && field.type !== 'button') {
                if (field.type === 'select-multiple') {
                    for (j = form.elements[i].options.length - 1; j >= 0; j--) {
                        if (field.options[j].selected) {
                            s[s.length] = encodeURIComponent(field.name) + '=' + encodeURIComponent(field.options[j].value);
                        }
                    }
                } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                    s[s.length] = encodeURIComponent(field.name) + '=' + encodeURIComponent(field.value);
                }
            }
        }
        return s.join('&');
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
