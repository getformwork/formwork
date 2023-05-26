export default {
    escapeRegExp: function (string) {
        return string.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&');
    },

    makeDiacriticsRegExp: function (string) {
        const diacritics = {
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
            'z': '[zźẑžżẓ]',
        };
        for (const char in diacritics) {
            string = string.split(char).join(diacritics[char]);
            string = string.split(char.toUpperCase()).join(diacritics[char].toUpperCase());
        }
        return string;
    },

    slug: function (string) {
        const translate = {
            '\t': '',
            '\r': '',
            '!': '',
            '"': '',
            '#': '',
            '$': '',
            '%': '',
            '\'': '-',
            '(': '',
            ')': '',
            '*': '',
            '+': '',
            ',': '',
            '.': '',
            ':': '',
            ';': '',
            '<': '',
            '=': '',
            '>': '',
            '?': '',
            '@': '',
            '[': '',
            ']': '',
            '^': '',
            '`': '',
            '{': '',
            '|': '',
            '}': '',
            '¡': '',
            '£': '',
            '¤': '',
            '¥': '',
            '¦': '',
            '§': '',
            '«': '',
            '°': '',
            '»': '',
            '‘': '',
            '’': '',
            '“': '',
            '”': '',
            '\n': '-',
            ' ': '-',
            '-': '-',
            '–': '-',
            '—': '-',
            '/': '-',
            '\\': '-',
            '_': '-',
            '~': '-',
            'À': 'A',
            'Á': 'A',
            'Â': 'A',
            'Ã': 'A',
            'Ä': 'A',
            'Å': 'A',
            'Æ': 'Ae',
            'Ç': 'C',
            'Ð': 'D',
            'È': 'E',
            'É': 'E',
            'Ê': 'E',
            'Ë': 'E',
            'Ì': 'I',
            'Í': 'I',
            'Î': 'I',
            'Ï': 'I',
            'Ñ': 'N',
            'Ò': 'O',
            'Ó': 'O',
            'Ô': 'O',
            'Õ': 'O',
            'Ö': 'O',
            'Ø': 'O',
            'Œ': 'Oe',
            'Š': 'S',
            'Þ': 'Th',
            'Ù': 'U',
            'Ú': 'U',
            'Û': 'U',
            'Ü': 'U',
            'Ý': 'Y',
            'à': 'a',
            'á': 'a',
            'â': 'a',
            'ã': 'a',
            'ä': 'ae',
            'å': 'a',
            'æ': 'ae',
            '¢': 'c',
            'ç': 'c',
            'ð': 'd',
            'è': 'e',
            'é': 'e',
            'ê': 'e',
            'ë': 'e',
            'ì': 'i',
            'í': 'i',
            'î': 'i',
            'ï': 'i',
            'ñ': 'n',
            'ò': 'o',
            'ó': 'o',
            'ô': 'o',
            'õ': 'o',
            'ö': 'oe',
            'ø': 'o',
            'œ': 'oe',
            'š': 's',
            'ß': 'ss',
            'þ': 'th',
            'ù': 'u',
            'ú': 'u',
            'û': 'u',
            'ü': 'ue',
            'ý': 'y',
            'ÿ': 'y',
            'Ÿ': 'y',
        };
        string = string.toLowerCase();
        for (const char in translate) {
            string = string.split(char).join(translate[char]);
        }
        return string.replace(/[^a-z0-9-]/g, '').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
    },

    validateSlug: function (slug) {
        return slug.toLowerCase().replace(' ', '-').replace(/[^a-z0-9-]/g, '');
    },

    debounce: function (callback, delay, leading) {
        let result;
        let timer = null;

        function wrapper() {
            const context = this;
            const args = arguments;
            if (timer) {
                clearTimeout(timer);
            }
            if (leading && !timer) {
                result = callback.apply(context, args);
            }
            timer = setTimeout(() => {
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
        let result;
        let previous = 0;
        let timer = null;

        function wrapper() {
            const now = Date.now();
            if (previous === 0) {
                previous = now;
            }
            const remaining = (previous + delay) - now;
            const context = this;
            const args = arguments;
            if (remaining <= 0 || remaining > delay) {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
                previous = now;
                result = callback.apply(context, args);
            } else if (!timer){
                timer = setTimeout(() => {
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
        const style = getComputedStyle(element);
        return element.offsetWidth + parseInt(style.marginLeft) + parseInt(style.marginRight);
    },

    outerHeight: function (element) {
        const style = getComputedStyle(element);
        return element.offsetHeight + parseInt(style.marginTop) + parseInt(style.marginBottom);
    },

    toggleElement: function (element, type = 'block') {
        const display = element.style.display || getComputedStyle(element).display;
        if (display === 'none') {
            element.style.display = type;
        } else {
            element.style.display = 'none';
        }
    },

    sameArray: function (array1, array2) {
        if (array1.length !== array2.length) {
            return false;
        }
        for (let i = 0; i < array1.length; i++) {
            if (array1[i] !== array2[i]) {
                return false;
            }
        }
        return true;
    },

    extendObject: function (target) {
        target = target || {};
        for (let i = 1; i < arguments.length; i++) {
            const source = arguments[i];
            for (const property in source) {
                target[property] = source[property];
            }
        }
        return target;
    },

    serializeObject: function (object) {
        const serialized = [];
        for (const property in object) {
            serialized.push(`${encodeURIComponent(property)}=${encodeURIComponent(object[property])}`);
        }
        return serialized.join('&');
    },

    serializeForm: function (form) {
        const serialized = [];
        for (const field of form.elements) {
            if (field.name && !field.disabled && field.getAttribute('data-form-ignore') !== 'true' && field.type !== 'file' && field.type !== 'reset' && field.type !== 'submit' && field.type !== 'button') {
                if (field.type === 'select-multiple') {
                    for (const option of field.options) {
                        if (option.selected) {
                            serialized.push(`${encodeURIComponent(field.name)}=${encodeURIComponent(option.value)}`);
                        }
                    }
                } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                    serialized.push(`${encodeURIComponent(field.name)}=${encodeURIComponent(field.value)}`);
                }
            }
        }
        return serialized.join('&');
    },

    triggerEvent: function (target, type) {
        let event;
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
        const form = document.createElement('form');
        form.action = uri;
        form.method = 'post';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf-token';
        input.value = csrfToken;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    },

    longClick: function (element, callback, timeout, interval) {
        let timer;
        function clear() {
            clearTimeout(timer);
        }
        element.addEventListener('mousedown', function (event) {
            const context = this;
            if (event.which !== 1) {
                clear();
            } else {
                callback.call(context, event);
                timer = setTimeout(() => {
                    timer = setInterval(callback.bind(context, event), interval);
                }, timeout);
            }
        });
        element.addEventListener('mouseout', clear);
        window.addEventListener('mouseup', clear);
    },

    firstFocusableElement: function (parent = document.body) {
        return parent.querySelector('button, .button, input:not([type=hidden]), select, textarea') || parent;
    },

    getCookies: function () {
        const result = [];
        const cookies = document.cookie.split(';');
        for (const cookie of cookies) {
            const nameAndValue = cookie.split('=', 2);
            if (nameAndValue.length === 2) {
                result[nameAndValue[0].trim()] = decodeURIComponent(nameAndValue[1].trim());
            }
        }
        return result;
    },

    setCookie: function (name, value, options) {
        let cookie = `${name}=${value}`;
        for (const option in options) {
            cookie += `;${option}=${options[option]}`;
        }
        document.cookie = cookie;
    },

    getMaxSafeInteger: function () {
        Number.MAX_SAFE_INTEGER || 9007199254740991;
    },

    toSafeInteger: function (value) {
        const max = this.getMaxSafeInteger();
        const min = -max;
        if (value > max) {
            return max;
        }
        if (value < min) {
            return min;
        }
        return parseInt(value, 10) || 0;
    },
};
