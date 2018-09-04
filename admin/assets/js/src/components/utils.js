Formwork.Utils = {
    debounce: function(callback, delay, leading) {
        var timer = null;
        var context;
        var args;

        function wrapper() {
            context = this;
            args = arguments;

            if (timer) {
                clearTimeout(timer);
            }

            if (leading && !timer) {
                callback.apply(context, args);
            }

            timer = setTimeout(function() {
                if (!leading) {
                    callback.apply(context, args);
                }
                timer = null;
            }, delay);
        }

        return wrapper;
    },
    
    escapeRegExp: function(string) {
        return string.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
    },

    slug: function(string) {
        var translate = {'\t': '', '\r': '', '!': '', '"': '', '#': '', '$': '', '%': '', '\'': '', '(': '', ')': '', '*': '', '+': '', ',': '', '.': '', ':': '', ';': '', '<': '', '=': '', '>': '', '?': '', '@': '', '[': '', ']': '', '^': '', '`': '', '{': '', '|': '', '}': '', '¡': '', '£': '', '¤': '', '¥': '', '¦': '', '§': '', '«': '', '°': '', '»': '', '‘': '', '’': '', '“': '', '”': '', '\n': '-', ' ': '-', '-': '-', '–': '-', '—': '-', '\/': '-', '\\': '-', '_': '-', '~': '-', 'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A', 'Å': 'A', 'Æ': 'Ae', 'Ç': 'C', 'Ð': 'D', 'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E', 'Ì': 'I', 'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ñ': 'N', 'Ò': 'O', 'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'O', 'Ø': 'O', 'Œ': 'Oe', 'Š': 'S', 'Þ': 'Th', 'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'U', 'Ý': 'Y', 'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'ae', 'å': 'a', 'æ': 'ae', '¢': 'c', 'ç': 'c', 'ð': 'd', 'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e', 'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i', 'ñ': 'n', 'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'oe', 'ø': 'o', 'œ': 'oe', 'š': 's', 'ß': 'ss', 'þ': 'th', 'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'ue', 'ý': 'y', 'ÿ': 'y', 'Ÿ': 'y'};
        var char;
        string = string.toLowerCase();
        for (char in translate) {
            if (translate.hasOwnProperty(char)) {
                string = string.split(char).join(translate[char]);
            }
        }
        return string.replace(/[^a-z0-9-]/g, '').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
    },

    throttle: function(callback, delay) {
        var timer = null;
        var context;
        var args;

        function wrapper() {
            context = this;
            args = arguments;

            if (timer) {
                return;
            }

            callback.apply(context, args);

            timer = setTimeout(function() {
                wrapper.apply(context, args);
                timer = null;
            }, delay);
        }

        return wrapper;
    },

    uriPrependBase: function(path, base) {
        var regexp = /^\/+|\/+$/gm;
        path = path.replace(regexp, '').split('/');
        base = base.replace(regexp, '').split('/');
        for (var i = 0; i < base.length; i++) {
            if (base[i] === path[0] && base[i + 1] !== path[0]) {
                base = base.slice(0, i);
            }
        }
        return '/' + base.concat(path).join('/') + '/';
    }
};
