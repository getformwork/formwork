export default {
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
