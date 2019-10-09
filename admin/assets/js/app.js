var Formwork = {
    baseUri: $('meta[name=base-uri]').attr('content'),
    init: function () {
        Formwork.Modals.init();
        Formwork.Forms.init();
        Formwork.Dropdowns.init();
        Formwork.Tooltips.init();

        Formwork.Dashboard.init();
        Formwork.Pages.init();
        Formwork.Updates.init();

        $('.toggle-navigation').on('click', function () {
            $('.sidebar').toggleClass('show');
        });

        $('[data-chart-data]').each(function () {
            // Use $.data() instead of $.attr() to parse JSON string
            var data = $(this).data('chart-data');
            new Formwork.Chart(this, data);
        });

        $('meta[name=notification]').each(function () {
            var $this = $(this);
            new Formwork.Notification($this.attr('content'), $this.attr('data-type'), $this.attr('data-interval'));
            $this.remove();
        });

        if ($('[data-command=save]').length > 0) {
            $(document).on('keydown', function () {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.which === 83) { // ctrl/cmd + S
                        $('[data-command=save]').trigger('click');
                        return false;
                    }
                }
            });
        }

    }
};

$(function () {
    Formwork.init();
});

Formwork.Chart = function (element, data) {
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

    /* global Chartist:false */
    var chart = new Chartist.Line(element, data, options);

    var isFirefox = navigator.userAgent.indexOf('Firefox') !== -1;

    $(chart.container).on('mouseover', '.ct-point', function () {
        var $this = $(this);
        var tooltipOffset = {
            x: 0, y: -8
        };

        if (isFirefox) {
            var strokeWidth = parseFloat($this.css('stroke-width'));
            tooltipOffset.x += strokeWidth / 2;
            tooltipOffset.y += strokeWidth / 2;
        }

        var tooltip = new Formwork.Tooltip($this.attr('ct:value'), {
            referenceElement: $this, offset: tooltipOffset
        });
        tooltip.show();
    });
};

Formwork.Dashboard = {
    init: function () {
        $('[data-command=clear-cache]').on('click', function () {
            new Formwork.Request({
                method: 'POST',
                url: Formwork.baseUri + 'cache/clear/',
                data: {'csrf-token': $('meta[name=csrf-token]').attr('content')}
            }, function (response) {
                Formwork.Notification(response.message, response.status, 5000);
            });
        });

        $('[data-command=make-backup]').on('click', function () {
            var $button = $(this);
            $button.attr('disabled', true);
            new Formwork.Request({
                method: 'POST',
                url: Formwork.baseUri + 'backup/make/',
                data: {'csrf-token': $('meta[name=csrf-token]').attr('content')}
            }, function (response) {
                Formwork.Notification(response.message, response.status, 5000);
                setTimeout(function () {
                    if (response.status === 'success') {
                        var csrfToken = $('meta[name=csrf-token]').attr('content');
                        Formwork.Utils.download(response.data.uri, csrfToken);
                    }
                    $button.removeAttr('disabled');
                }, 1000);
            });
        });
    }
};

Formwork.Dropdowns = {
    init: function () {
        if ($('.dropdown').length > 0) {
            $(document).on('click', function (event) {
                var $button = $(event.target).closest('.dropdown-button');
                if ($button.length > 0) {
                    var $dropdown = $('#' + $button.attr('data-dropdown'), '.dropdown');
                    var isVisible = $dropdown.is(':visible');
                    event.preventDefault();
                }
                $('.dropdown-menu').hide();
                if ($dropdown !== undefined && !isVisible) {
                    $dropdown.show();
                }
            });
        }
    }
};

Formwork.Editor = function (id) {
    var textarea = document.getElementById(id);

    /* global CodeMirror:false */
    var editor = CodeMirror.fromTextArea(textarea, {
        mode: 'markdown',
        theme: 'formwork',
        indentUnit: 4,
        lineWrapping: true,
        addModeClass: true,
        extraKeys: {'Enter': 'newlineAndIndentContinueMarkdownList'}
    });

    var $toolbar = '.editor-toolbar[data-for=' + id + ']';

    $('[data-command=bold]', $toolbar).on('click', function () {
        insertAtCursor('**');
    });

    $('[data-command=italic]', $toolbar).on('click', function () {
        insertAtCursor('_');
    });

    $('[data-command=ul]', $toolbar).on('click', function () {
        insertAtCursor(prependSequence() + '- ', '');
    });

    $('[data-command=ol]', $toolbar).on('click', function () {
        var num = /^\d+\./.exec(lastLine(editor.getValue()));
        if (num) {
            insertAtCursor('\n' + (parseInt(num) + 1) + '. ', '');
        } else {
            insertAtCursor(prependSequence() + '1. ', '');
        }
    });

    $('[data-command=quote]', $toolbar).on('click', function () {
        insertAtCursor(prependSequence() + '> ', '');
    });

    $('[data-command=link]', $toolbar).on('click', function () {
        var selection = editor.getSelection();
        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
            insertAtCursor('[', '](' + selection + ')', true);
        } else if (selection !== '') {
            insertAtCursor('[' + selection + '](http://', ')', true);
        } else {
            insertAtCursor('[', '](http://)');
        }
    });

    $('[data-command=image]', $toolbar).on('click', function () {
        Formwork.Modals.show('imagesModal', null, function ($modal) {
            $('.image-picker-thumbnail.selected', $modal).removeClass('selected');
            $('.image-picker-confirm', $modal).data('target', function (filename) {
                if (filename !== undefined) {
                    insertAtCursor(prependSequence() + '![', '](' + filename + ')');
                } else {
                    insertAtCursor(prependSequence() + '![](', ')');
                }
            });
        });
    });

    $('[data-command=summary]', $toolbar).on('click', function () {
        if (!hasSummarySequence()) {
            var prevChar = prevCursorChar();
            var prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
            insertAtCursor(prepend + '\n===\n\n', '');
            $(this).attr('disabled', true);
        }
    });

    $('[data-command=undo]', $toolbar).on('click', function () {
        editor.undo();
        editor.focus();
    });

    $('[data-command=redo]', $toolbar).on('click', function () {
        editor.redo();
        editor.focus();
    });

    disableSummaryCommand();

    editor.on('changes', Formwork.Utils.debounce(function () {
        textarea.value = editor.getValue();
        disableSummaryCommand();
        $('[data-command=undo]').attr('disabled', editor.historySize().undo < 1);
        $('[data-command=redo]').attr('disabled', editor.historySize().redo < 1);
    }, 500));

    $(document).on('keydown', function (event) {
        if (!event.altKey && (event.ctrlKey || event.metaKey)) {
            switch (event.which) {
            case 66: // ctrl/cmd + B
                $('[data-command=bold]', $toolbar).trigger('click');
                return false;
            case 73: // ctrl/cmd + I
                $('[data-command=italic]', $toolbar).trigger('click');
                return false;
            case 75: // ctrl/cmd + K
                $('[data-command=link]', $toolbar).trigger('click');
                return false;
            }
        }
    });

    function hasSummarySequence() {
        return /\n+===\n+/.test(editor.getValue());
    }

    function disableSummaryCommand() {
        $('[data-command=summary]', $toolbar).attr('disabled', hasSummarySequence());
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
        if (rightValue === undefined) {
            rightValue = leftValue;
        }
        var selection = dropSelection === true ? '' : editor.getSelection();
        var cursor = editor.getCursor();
        var lineBreaks = leftValue.split('\n').length - 1;
        editor.replaceSelection(leftValue + selection + rightValue);
        editor.setCursor(cursor.line + lineBreaks, cursor.ch + leftValue.length - lineBreaks);
        editor.focus();
    }
};

Formwork.Form = function (form) {
    var $window = $(window);
    var $form = $(form);

    $form.data('originalData', $form.serialize());

    $window.on('beforeunload', function () {
        if (hasChanged()) {
            return true;
        }
    });

    $form.on('submit', function () {
        $window.off('beforeunload');
    });

    $('input:file[data-auto-upload]', $form).on('change', function () {
        if (!hasChanged(false)) {
            $form.trigger('submit');
        }
    });

    $('[data-command=continue]', '#changesModal').on('click', function () {
        $window.off('beforeunload');
        window.location.href = $(this).attr('data-href');
    });

    $('a[href]:not([href^="#"]):not([target="_blank"])').on('click', function (event) {
        if (hasChanged()) {
            var link = this;
            event.preventDefault();
            Formwork.Modals.show('changesModal', null, function ($modal) {
                $('[data-command=continue]', $modal).attr('data-href', link.href);
            });
        }
    });

    function hasChanged(checkFileInputs) {
        if (typeof checkFileInputs === 'undefined') {
            checkFileInputs = true;
        }
        var $fileInputs = $(':file', $form);
        if (checkFileInputs === true && $fileInputs.length > 0) {
            for (var i = 0; i < $fileInputs.length; i++) {
                if ($fileInputs[i].files.length > 0) {
                    return true;
                }
            }
        }
        return $form.serialize() !== $form.data('originalData');
    }
};

Formwork.Forms = {
    init: function () {
        $('[data-form]').each(function () {
            new Formwork.Form($(this));
        });

        $('input[data-enable]').on('change', function () {
            var $this = $(this);
            var checked = $this.is(':checked');
            $.each($this.attr('data-enable').split(','), function (index, value) {
                $('input[name="' + value + '"]').attr('disabled', !checked);
            });
        });

        $('.input-reset').on('click', function () {
            var $target = $('#' + $(this).attr('data-reset'));
            $target.val('');
            $target.trigger('change');
        });

        $('input:file').each(function () {
            var $this = $(this);
            var $span = $('label[for="' + $this.attr('id') + '"] span');
            var labelHTML = $span.html();
            $this.data('originalLabel', labelHTML);
        }).on('change input', function () {
            var $this = $(this);
            var $span = $('label[for="' + $this.attr('id') + '"] span');
            var files = $this.prop('files');
            if (files.length) {
                $span.text(files[0].name);
            } else {
                $span.html($this.data('originalLabel'));
            }
        });

        $('.file-input-label').on('drag dragstart dragend dragover dragenter dragleave drop', function (event) {
            event.preventDefault();
        }).on('drop', function (event) {
            var $target = $('#' + $(this).attr('for'));
            $target.prop('files', event.originalEvent.dataTransfer.files);
            // Firefox won't trigger a change event, so we explicitly do that
            $target.trigger('change');
        }).on('dragover dragenter', function () {
            $(this).addClass('drag');
        }).on('dragleave drop', function () {
            $(this).removeClass('drag');
        });

        $('.tag-input').tagInput();

        $('.image-input').on('click', function () {
            var $this = $(this);
            var value = $this.val();
            Formwork.Modals.show('imagesModal', null, function ($modal) {
                $('.image-picker-confirm', $modal).data('target', $this);
                $('.image-picker-thumbnail.selected', $modal).removeClass('selected');
                if (value) {
                    $('.image-picker-thumbnail[data-filename="' + value + '"]', $modal).addClass('selected');
                }
            });
        });

        $('.image-picker').each(function () {
            var $this = $(this);
            var options = $this.children('option');
            if (options.length > 0) {
                var container = $('<div>', {class: 'image-picker-thumbnails'});
                for (var i = 0; i < options.length; i++) {
                    $('<div>', {
                        class: 'image-picker-thumbnail',
                        'data-uri': options[i].value,
                        'data-filename': options[i].text
                    }).css({'background-image': 'url(' + options[i].value + ')'}).appendTo(container);
                }
                $this.before(container);
                $('.image-picker-empty-state').hide();
            }
            $this.hide();
        });

        $('.image-picker-confirm').on('click', function () {
            var $this = $(this);
            var target = $this.data('target');
            var filename = $('.image-picker-thumbnail.selected', $this.parent()).attr('data-filename');
            if (typeof target === 'function') {
                target(filename);
            } else {
                $this.data('target').val(filename);
            }
        });

        $('.image-picker-thumbnail').on('click', function () {
            var $this = $(this);
            $this.siblings().removeClass('selected');
            $this.addClass('selected');
            $this.parent().siblings('.image-input').val($this.attr('data-uri'));
        });

        $('.image-picker-thumbnail').on('dblclick', function () {
            $(this).trigger('click');
            $('.image-picker-confirm').trigger('click');
        });

        $('[data-command=upload]').on('click', function () {
            var $target = $('#' + $(this).attr('data-upload-target'));
            $target.trigger('click');
        });

        $('.editor-textarea').each(function () {
            new Formwork.Editor($(this).attr('id'));
        });

        $('input[type=range]').on('change input', function () {
            var $this = $(this);
            $this.next('.range-input-value').text($this.val());
        });

        $('.array-input').each(function () {
            $('.array-input-add', this).on('click', function () {
                var $row = $(this).closest('.array-input-row');
                var $clone = $row.clone(true);
                $clone.find('.array-input-key, .array-input-value').val('');
                $row.after($clone);
            });

            $('.array-input-remove', this).on('click', function () {
                var $row = $(this).closest('.array-input-row');
                if ($row.siblings('.array-input-row').length > 0) {
                    $row.remove();
                } else {
                    $row.find('.array-input-key, .array-input-value').val('');
                    $(this).siblings('.array-input-key').trigger('keyup');
                }
            });

            if ($(this).hasClass('array-input-associative')) {
                var inputName = $(this).attr('data-name');
                $('.array-input-key', this).on('keyup', function () {
                    $(this).siblings('.array-input-value').attr('name', inputName + '[' + $(this).val() + ']');
                });
                $('.array-input-value', this).on('keyup', function () {
                    $(this).attr('name', inputName + '[' + $(this).siblings('.array-input-key').val() + ']');
                });
            }

            /* global Sortable:false */
            Sortable.create(this, {
                handle: '.array-input-handle',
                forceFallback: true
            });
        });
    }
};

Formwork.Modals = {
    init: function () {
        $('[data-modal]').on('click', function () {
            var $this = $(this);
            var modal = $this.attr('data-modal');
            var action = $this.attr('data-modal-action');
            if (action) {
                Formwork.Modals.show(modal, action);
            } else {
                Formwork.Modals.show(modal);
            }
        });

        $('.modal [data-dismiss]').on('click', function () {
            var $this = $(this);
            if ($this.is('[data-validate]')) {
                var valid = Formwork.Modals.validate($this.attr('data-dismiss'));
                if (!valid) {
                    return;
                }
            }
            Formwork.Modals.hide($this.attr('data-dismiss'));
        });

        $('.modal').on('click', function (event) {
            if (event.target === this) {
                Formwork.Modals.hide();
            }
        });

        $(document).on('keyup', function (event) {
            // ESC key
            if (event.which === 27) {
                Formwork.Modals.hide();
            }
        });
    },

    show: function (id, action, callback) {
        var $modal = $('#' + id);
        if (!$modal.length) {
            return;
        }
        $modal.addClass('show');
        if (action !== null) {
            $('form', $modal).attr('action', action);
        }
        $('[autofocus]', $modal).first().trigger('focus'); // Firefox bug
        if (typeof callback === 'function') {
            callback($modal);
        }
        $('.tooltip').remove();
        this.createBackdrop();
    },

    hide: function (id) {
        var $modal = id === undefined ? $('.modal') : $('#' + id);
        $modal.removeClass('show');
        this.removeBackdrop();
    },

    createBackdrop: function () {
        if (!$('.modal-backdrop').length) {
            $('<div>', {class: 'modal-backdrop'}).appendTo('body');
        }
    },

    removeBackdrop: function () {
        $('.modal-backdrop').remove();
    },

    validate: function (id) {
        var valid = false;
        var $modal = $('#' + id);
        $('[required]', $modal).each(function () {
            var $this = $(this);
            if ($this.val() === '') {
                $this.addClass('input-invalid').trigger('focus');
                $('.modal-error', $modal).show();
                valid = false;
                return false;
            }
            valid = true;
        });
        return valid;
    }
};

Formwork.Notification = function (text, type, interval) {
    var $notification = $('<div>', {class: 'notification'}).text(text);

    if ($('.notification').length > 0) {
        var $last = $('.notification:not(.fadeout)').last();
        var top = $last.offset().top + $last.outerHeight(true) - $(window).scrollTop();
        $notification.css('top', top);
    }

    if (type) {
        $notification.addClass('notification-' + type);
    }

    $notification.appendTo('body');

    var timer = setTimeout(remove, interval);

    $notification.on('click', remove);

    $notification.on('mouseenter', function () {
        clearTimeout(timer);
    });

    $notification.on('mouseleave', function () {
        timer = setTimeout(remove, 1000);
    });

    function remove() {
        var found = false;
        var offset = $notification.outerHeight(true);

        $('.notification').each(function () {
            var $this = $(this);
            if ($this.is($notification)) {
                found = true;
                $this.addClass('fadeout');
            } else if (found) {
                $this.css('top', '-=' + offset);
            }
        });

        setTimeout(function () {
            $notification.remove();
        }, 400);

    }

};

Formwork.Pages = {
    init: function () {
        $('.page-children-toggle').on('click', function (event) {
            event.stopPropagation();
            var $this = $(this);
            $this.closest('li').children('.pages-list').toggle();
            $this.toggleClass('toggle-expanded toggle-collapsed');
        });

        $('.page-details a').on('click', function (event) {
            event.stopPropagation();
        });

        $('[data-command=expand-all-pages]').on('click', function () {
            $(this).trigger('blur');
            $('.pages-children').show();
            $('.page-children-toggle', '.pages-list').removeClass('toggle-collapsed').addClass('toggle-expanded');
        });

        $('[data-command=collapse-all-pages]').on('click', function () {
            $(this).trigger('blur');
            $('.pages-children').hide();
            $('.page-children-toggle', '.pages-list').removeClass('toggle-expanded').addClass('toggle-collapsed');
        });

        $('.page-search').on('focus', function () {
            $('.pages-children').each(function () {
                var $this = $(this);
                $this.data('visible', $this.is(':visible'));
            });
        });

        $('.page-search').on('keyup', Formwork.Utils.debounce(function () {
            var value = $(this).val();
            if (value.length === 0) {
                $('.pages-children').each(function () {
                    var $this = $(this);
                    $this.toggle($this.data('visible'));
                });
                $('.page-details').css('padding-left', '');
                $('.pages-item, .page-children-toggle').show();
            } else {
                var regexp = new RegExp(Formwork.Utils.escapeRegExp(value), 'i');
                $('.pages-children').show();
                $('.page-children-toggle').hide();
                $('.page-details').css('padding-left', '0');
                $('.page-title a').each(function () {
                    var $this = $(this);
                    var $pagesItem = $this.closest('.pages-item');
                    var matched = !!$this.text().match(regexp);
                    $pagesItem.toggle(matched);
                });
            }
        }, 100));

        $('.page-details').on('click', function () {
            var $toggle = $('.page-children-toggle', this).first();
            if ($toggle.length) {
                $toggle.trigger('click');
            }
        });

        $('#page-title', '#newPageModal').on('keyup', function () {
            $('#page-slug', '#newPageModal').val(Formwork.Utils.slug($(this).val()));
        });

        $('#page-slug', '#newPageModal, #slugModal').on('keyup', function () {
            var $this = $(this);
            $this.val($this.val().toLowerCase().replace(' ', '-').replace(/[^a-z0-9-]/g, ''));
        }).on('blur', function () {
            if ($(this).val() === '') {
                $('#page-title', '#newPageModal').trigger('keyup');
            }
        });

        $('#page-parent', '#newPageModal').on('change', function () {
            var $option = $('option:selected', this);
            var $pageTemplate = $('#page-template', '#newPageModal');
            var allowedTemplates = $option.attr('data-allowed-templates');
            if (allowedTemplates) {
                allowedTemplates = allowedTemplates.split(', ');
                $pageTemplate
                    .data('previousValue', $pageTemplate.val())
                    .val(allowedTemplates[0])
                    .find('option').each(function () {
                        var $this = $(this);
                        if (allowedTemplates.indexOf($this.val()) === -1) {
                            $this.attr('disabled', true);
                        }
                    });
            } else if ($('option[disabled]', $pageTemplate).length) {
                $pageTemplate
                    .val($pageTemplate.data('previousValue'))
                    .removeData('previousValue')
                    .find('option').removeAttr('disabled');
            }
        });

        $('[data-command=change-slug]').on('click', function () {
            Formwork.Modals.show('slugModal', null, function ($modal) {
                var slug = $('#slug').val();
                $('#page-slug', $modal).val(slug).attr('placeholder', slug).trigger('focus');
            });
        });

        $('#page-slug', '#slugModal').on('keydown', function (event) {
            if (event.which === 13) {
                $('[data-command=continue]', '#slugModal').trigger('click');
            }
        });

        $('[data-command=generate-slug]', '#slugModal').on('click', function () {
            var slug = Formwork.Utils.slug($('#title').val());
            $('#page-slug', '#slugModal').val(slug).trigger('focus');
        });

        $('[data-command=continue]', '#slugModal').on('click', function () {
            var slug = $('#page-slug').val().replace(/^-+|-+$/, '');
            if (slug.length > 0) {
                var route = $('.page-route span').text();
                $('#page-slug, #slug').val(slug);
                $('.page-route span').text(route.replace(/\/[a-z0-9-]+\/$/, '/' + slug + '/'));
            }
            Formwork.Modals.hide('slugModal');
        });

        $('.pages-list').each(function () {
            var $this = $(this);

            if ($this.attr('data-sortable-children') === 'false') {
                return;
            }

            /* global Sortable:false */
            var sortable = Sortable.create(this, {
                filter: '[data-sortable=false]',
                forceFallback: true,
                onStart: function (event) {
                    $(event.item).closest('.pages-list').addClass('dragging');
                    $('.pages-children', event.item).hide();
                    $('.page-children-toggle').removeClass('toggle-expanded')
                        .addClass('toggle-collapsed').css('opacity', '0.5');
                },
                onMove: function (event) {
                    if ($(event.related).attr('data-sortable') === 'false') {
                        return false;
                    }
                    $('.pages-children', event.related).hide();
                },
                onEnd: function (event) {
                    $(event.item).closest('.pages-list').removeClass('dragging');
                    $('.page-children-toggle').css('opacity', '');

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option('disabled', true);

                    var data = {
                        'csrf-token': $('meta[name=csrf-token]').attr('content'),
                        parent: $(this.el).attr('data-parent'),
                        from: event.oldIndex,
                        to: event.newIndex
                    };

                    new Formwork.Request({
                        method: 'POST',
                        url: Formwork.baseUri + 'pages/reorder/',
                        data: data
                    }, function (response) {
                        if (response.status) {
                            Formwork.Notification(response.message, response.status, 5000);
                        }
                        if (!response.status || response.status === 'error') {
                            sortable.sort($(event.from).data('originalOrder'));
                        }
                        sortable.option('disabled', false);
                        $(event.from).data('originalOrder', sortable.toArray());
                    });

                }
            });

            $this.data('originalOrder', sortable.toArray());
        });

        $(document).on('keydown', function (event) {
            if (event.ctrlKey || event.metaKey) {
                // ctrl/cmd + F
                if (event.which === 70 && $('.page-search:not(:focus)').length) {
                    $('.page-search').trigger('focus');
                    return false;
                }
            }
        });
    }
};

Formwork.Request = function (options, callback) {
    var request = $.ajax(options);

    if (typeof callback === 'function') {
        request.always(function () {
            var response = request.responseJSON || {};
            var code = response.code || request.status;
            if (parseInt(code) === 400) {
                location.reload();
            } else {
                callback(response, request);
            }
        });
    }

    return request;
};

Formwork.Tooltip = function (text, options) {
    var defaults = {
        container: document.body,
        referenceElement: document.body,
        position: 'top',
        offset: {
            x: 0, y: 0
        },
        delay: 500
    };

    var $referenceElement = $(options.referenceElement);
    var $tooltip;
    var timer;

    options = $.extend({}, defaults, options);

    $referenceElement.on('mouseout', _remove);

    // Remove tooltip when clicking on buttons
    if ($referenceElement.is('button, .button')) {
        $referenceElement.on('click', _remove);
    }

    function _tooltipPosition($tooltip) {
        var offset = $referenceElement.offset();

        if (offset.top === 0 && offset.left === 0) {
            var rect = $referenceElement[0].getBoundingClientRect();
            offset.top = rect.top + window.pageYOffset;
            offset.left = rect.left + window.pageXOffset;
        }

        var top = offset.top;
        var left = offset.left;

        var hw = ($referenceElement.outerWidth() - $tooltip.outerWidth()) / 2;
        var hh = ($referenceElement.outerHeight() - $tooltip.outerHeight()) / 2;
        switch (options.position) {
        case 'top':
            return {
                top: Math.round(top - $tooltip.outerHeight() + options.offset.y),
                left: Math.round(left + hw + options.offset.x)
            };
        case 'right':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left + $referenceElement.outerWidth() + options.offset.x)
            };
        case 'bottom':
            return {
                top: Math.round(top + $referenceElement.outerHeight() + options.offset.y),
                left: Math.round(left + hw + options.offset.x)
            };
        case 'left':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left - $tooltip.outerWidth() + options.offset.x)
            };
        }
    }

    function _show() {
        timer = setTimeout(function () {
            $tooltip = $('<div class="tooltip" role="tooltip">')
                .appendTo(options.container);

            $tooltip.text(text)
                .css(_tooltipPosition($tooltip))
                .fadeIn(200);
        }, options.delay);
    }

    function _remove() {
        clearTimeout(timer);
        if ($tooltip !== undefined) {
            $tooltip.fadeOut(100, function () {
                $tooltip.remove();
            });
        }
    }

    return {
        show: _show,
        remove: _remove
    };
};

Formwork.Tooltips = {
    init: function () {
        $('[title]').each(function () {
            var $this = $(this);
            $this.attr('data-tooltip', $this.attr('title'))
                .removeAttr('title');
        });

        $('[data-tooltip]').on('mouseover', function () {
            var $this = $(this);
            var tooltip = new Formwork.Tooltip($this.attr('data-tooltip'), {
                referenceElement: $this,
                position: 'bottom',
                offset: {
                    x: 0, y: 4
                }
            });
            tooltip.show();
        });

        $('[data-overflow-tooltip="true"]').on('mouseover', function () {
            var $this = $(this);
            if ($this.prop('offsetWidth') < $this.prop('scrollWidth')) {
                var tooltip = new Formwork.Tooltip($this.text().trim(), {
                    referenceElement: $this,
                    position: 'bottom',
                    offset: {
                        x: 0, y: 4
                    }
                });
                tooltip.show();
            }
        });
    }
};

Formwork.Updates = {
    init: function () {
        if ($('#updater-component').length > 0) {
            setTimeout(function () {
                var data = {'csrf-token': $('meta[name=csrf-token]').attr('content')};
                new Formwork.Request({
                    method: 'POST',
                    url: Formwork.baseUri + 'updates/check/',
                    data: data
                }, function (response) {
                    $('.update-status').html(response.message);
                    if (response.data.uptodate === false) {
                        $('.spinner').addClass('spinner-info');
                        $('.new-version-name').text(response.data.release.name);
                        $('.new-version').show();
                    } else {
                        $('.spinner').addClass('spinner-success');
                        $('.current-version').show();
                    }
                });
            }, 1000);

            $('[data-command=install-updates]').on('click', function () {
                $('.new-version').hide();
                $('.spinner').removeClass('spinner-info');
                $('.update-status').text($('.update-status').attr('data-installing-text'));
                var data = {'csrf-token': $('meta[name=csrf-token]').attr('content')};
                new Formwork.Request({
                    method: 'POST',
                    url: Formwork.baseUri + 'updates/update/',
                    data: data
                }, function (response) {
                    $('.update-status').text(response.data.status);
                    new Formwork.Notification(response.message, response.status, 5000);
                    if (response.status === 'success') {
                        $('.spinner').addClass('spinner-success');
                        $('.current-version-name').text($('.new-version-name').text());
                        $('.current-version').show();
                    } else {
                        $('.spinner').addClass('spinner-error');
                    }
                });
            });
        }
    }
};

Formwork.Utils = {
    debounce: function (callback, delay, leading) {
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

            timer = setTimeout(function () {
                if (!leading) {
                    callback.apply(context, args);
                }
                timer = null;
            }, delay);
        }

        return wrapper;
    },

    download: function (uri, csrfToken) {
        var $form = $('<form>').attr({
            action: uri, method: 'post'
        });
        $form.append($('<input>').attr({
            type: 'hidden', name: 'csrf-token', value: csrfToken
        }));
        $form.appendTo('body').trigger('submit').remove();
    },

    escapeRegExp: function (string) {
        return string.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&');
    },

    slug: function (string) {
        var translate = {
            '\t': '', '\r': '', '!': '', '"': '', '#': '', '$': '', '%': '', '\'': '-', '(': '', ')': '', '*': '', '+': '', ',': '', '.': '', ':': '', ';': '', '<': '', '=': '', '>': '', '?': '', '@': '', '[': '', ']': '', '^': '', '`': '', '{': '', '|': '', '}': '', '¡': '', '£': '', '¤': '', '¥': '', '¦': '', '§': '', '«': '', '°': '', '»': '', '‘': '', '’': '', '“': '', '”': '', '\n': '-', ' ': '-', '-': '-', '–': '-', '—': '-', '/': '-', '\\': '-', '_': '-', '~': '-', 'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A', 'Å': 'A', 'Æ': 'Ae', 'Ç': 'C', 'Ð': 'D', 'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E', 'Ì': 'I', 'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ñ': 'N', 'Ò': 'O', 'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'O', 'Ø': 'O', 'Œ': 'Oe', 'Š': 'S', 'Þ': 'Th', 'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'U', 'Ý': 'Y', 'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'ae', 'å': 'a', 'æ': 'ae', '¢': 'c', 'ç': 'c', 'ð': 'd', 'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e', 'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i', 'ñ': 'n', 'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'oe', 'ø': 'o', 'œ': 'oe', 'š': 's', 'ß': 'ss', 'þ': 'th', 'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'ue', 'ý': 'y', 'ÿ': 'y', 'Ÿ': 'y'
        };
        string = string.toLowerCase();
        for (var char in translate) {
            if (translate.hasOwnProperty(char)) {
                string = string.split(char).join(translate[char]);
            }
        }
        return string.replace(/[^a-z0-9-]/g, '').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
    },

    throttle: function (callback, delay) {
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

            timer = setTimeout(function () {
                wrapper.apply(context, args);
                timer = null;
            }, delay);
        }

        return wrapper;
    },

    uriPrependBase: function (path, base) {
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

(function ($) {
    $.fn.datePicker = function (options) {
        var $input;
        var $calendar;

        var today = new Date();

        var calendar = {
            year: today.getFullYear(),
            month: today.getMonth(),
            day: today.getDate(),
            setDate: function (date) {
                this.year = date.getFullYear();
                this.month = date.getMonth();
                this.day = date.getDate();
            },
            lastDay: function () {
                this.day = helpers.daysInMonth(this.month, this.year);
            },
            prevYear: function () {
                this.year--;
            },
            nextYear: function () {
                this.year++;
            },
            prevMonth: function () {
                this.month = helpers.mod(this.month - 1, 12);
                if (this.month === 11) {
                    this.prevYear();
                }
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.lastDay();
                }
            },
            nextMonth: function () {
                this.month = helpers.mod(this.month + 1, 12);
                if (this.month === 0) {
                    this.nextYear();
                }
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.lastDay();
                }
            },
            prevWeek: function () {
                this.day -= 7;
                if (this.day < 1) {
                    this.prevMonth();
                    this.day += helpers.daysInMonth(this.month, this.year);
                }
            },
            nextWeek: function () {
                this.day += 7;
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.day -= helpers.daysInMonth(this.month, this.year);
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
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.nextMonth();
                    this.day = 1;
                }
            }
        };

        var helpers = {
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
                    hours = helpers.mod(hours, 12) > 0 ? helpers.mod(hours, 12) : 12;
                }
                return format.replace('YYYY', year)
                    .replace('YY', year.toString().substr(-2))
                    .replace('MM', helpers.pad(month))
                    .replace('DD', helpers.pad(day))
                    .replace('hh', helpers.pad(hours))
                    .replace('mm', helpers.pad(minutes))
                    .replace('ss', helpers.pad(seconds))
                    .replace('a', am ? 'AM' : 'PM');
            }
        };

        options = $.extend({}, $.fn.datePicker.defaults, options);

        this.each(function () {
            var $this = $(this);
            var value = $this.val();
            $this.prop('readonly', true);
            $this.prop('size', options.format.length);
            if (helpers.isValidDate(value)) {
                value = new Date(value);
                $this.data('date', value);
                $this.val(helpers.formatDateTime(value));
            }
            $this.on('change', function () {
                if ($this.val() === '') {
                    $this.data('date', '');
                } else {
                    $this.val(helpers.formatDateTime($this.data('date')));
                }
            });
            $this.on('keydown', function (event) {
                var date = $(this).data('date');
                calendar.setDate(helpers.isValidDate(date) ? date : new Date());
                switch (event.which) {
                case 13: // enter
                    $('.calendar-day.selected').trigger('click');
                    $calendar.hide();
                    return false;
                case 8: // backspace
                    $this.val('');
                    $input.trigger('blur');
                    $calendar.hide();
                    return false;
                case 27: // escape
                    $input.trigger('blur');
                    $calendar.hide();
                    return false;
                case 37: // left arrow
                    if (event.ctrlKey || event.metaKey) {
                        if (event.shiftKey) {
                            calendar.prevYear();
                        } else {
                            calendar.prevMonth();
                        }
                    } else {
                        calendar.prevDay();
                    }
                    break;
                case 38: // up arrow
                    calendar.prevWeek();
                    break;
                case 39: // right arrow
                    if (event.ctrlKey || event.metaKey) {
                        if (event.shiftKey) {
                            calendar.nextYear();
                        } else {
                            calendar.nextMonth();
                        }
                    } else {
                        calendar.nextDay();
                    }
                    break;
                case 40: // down arrow
                    calendar.nextWeek();
                    break;
                case 48: // 0
                    if (event.ctrlKey || event.metaKey) {
                        var today = new Date();
                        calendar.setDate(today);
                    }
                    break;
                default:
                    return true;
                }
                updateInput();
                return false;
            });
        });

        $calendar = $('<div class="calendar"><div class="calendar-buttons"><button type="button" class="prevMonth"><i class="i-chevron-left"></i></button><button type="button" class="currentMonth">' + options.todayLabel + '</button><button type="button" class="nextMonth"><i class="i-chevron-right"></i></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>').appendTo('body');

        $('.currentMonth').on('click', function () {
            var today = new Date();
            calendar.setDate(today);
            updateInput();
            $input.trigger('blur');
        });

        $('.prevMonth').longclick(function () {
            calendar.prevMonth();
            generateTable(calendar.year, calendar.month);
        }, 750, 500);

        $('.nextMonth').longclick(function () {
            calendar.nextMonth();
            generateTable(calendar.year, calendar.month);
        }, 750, 500);

        $('.prevMonth, .currentMonth, .nextMonth').on('mousedown', function () {
            return false;
        });

        function updateInput() {
            var date = new Date(calendar.year, calendar.month, calendar.day);
            generateTable(calendar.year, calendar.month, calendar.day);
            $input.val(helpers.formatDateTime(date));
            $input.data('date', date);
        }

        $calendar.on('mousedown', '.calendar-day', false);

        $calendar.on('click', '.calendar-day', function () {
            var date = new Date(calendar.year, calendar.month, parseInt($(this).text()));
            $input.data('date', date);
            $input.val(helpers.formatDateTime(date));
            $input.trigger('blur');
        });

        function generateTable(year, month, day) {
            var num = 1;
            var firstDay = new Date(year, month, 1).getDay();
            var monthLength = helpers.daysInMonth(month, year);
            var monthName = options.monthLabels[month];
            var start = helpers.mod(firstDay - options.weekStarts, 7);
            var html = '<table class="calendar-table">';
            html += '<tr><th class="calendar-header" colspan="7">';
            html += monthName + '&nbsp;' + year;
            html += '</th></tr>';
            html += '<tr>';
            for(var i = 0; i < 7; i++ ){
                html += '<td class="calendar-header-day">';
                html += options.dayLabels[helpers.mod(i + options.weekStarts, 7)];
                html += '</td>';
            }
            html += '</tr><tr>';
            for (i = 0; i < 6; i++) {
                for (var j = 0; j < 7; j++) {
                    if (num <= monthLength && (i > 0 || j >= start)) {
                        if (num === day) {
                            html += '<td class="calendar-day selected">';
                        } else {
                            html += '<td class="calendar-day">';
                        }
                        html += num++;
                    } else if (num === 1) {
                        html += '<td class="calendar-prev-month-day">';
                        html += helpers.daysInMonth(helpers.mod(month - 1, 12), year) - start + j + 1;
                    } else {
                        html += '<td class="calendar-next-month-day">';
                        html += num++ - monthLength;
                    }
                    html += '</td>';
                }
                html += '</tr><tr>';
            }
            html += '</tr></table>';
            $('.calendar-table').replaceWith(html);
        }

        $('.date-input').on('blur', function () {
            $calendar.hide();
        });

        $('.date-input').on('focus', function () {
            $input = $(this);
            var date = helpers.isValidDate($input.data('date')) ? new Date($input.data('date')) : new Date();
            calendar.setDate(date);
            generateTable(calendar.year, calendar.month, calendar.day);
            $calendar.show();
            setPosition();
        });

        $(window).on('touchstart', function () {
            var $eventTarget = $(event.target);
            if (!$eventTarget.is('.date-input') && !$eventTarget.parents('.calendar, .date-input').length) {
                $input.trigger('blur');
            }
        });

        $(window).on('resize', Formwork.Utils.throttle(setPosition, 100));

        function setPosition() {
            if (!$input || !$calendar.is(':visible')) {
                return;
            }
            $calendar.css({
                top: $input.offset().top + $input.outerHeight(),
                left: $input.offset().left
            });
            if ($calendar.offset().left + $calendar.outerWidth(true) > $(window).width()) {
                $calendar.css('left', $(window).width() - $calendar.outerWidth(true));
            }
            if ($(window).scrollTop() + $(window).height() < $calendar.position().top + $calendar.outerHeight(true)) {
                $(window).scrollTop($calendar.position().top + $calendar.outerHeight(true) - $(window).height());
            }
        }

    };

    $.fn.datePicker.defaults = {
        dayLabels:  ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        monthLabels: ['January', 'February', 'March', 'April', 'May', 'June', 'July' ,'August', 'September', 'October', 'November', 'December'],
        weekStarts: 0,
        todayLabel: 'Today',
        format: 'YYYY-MM-DD'
    };

}(jQuery));

(function ($) {
    $.fn.longclick = function (callback, timeout, interval) {
        var timer;
        function clear() {
            clearTimeout(timer);
        }
        $(window).on('mouseup', clear);
        $(this).on('mousedown', function (event) {
            if (event.which !== 1) {
                clear();
            } else {
                callback();
                timer = window.setTimeout(function () {
                    timer = window.setInterval(callback, interval ? interval : 250);
                }, timeout ? timeout : 500);
            }
        }).on('mouseout', clear);
    };
}(jQuery));

(function ($) {
    $.fn.tagInput = function () {

        function _update($input) {
            var $parent = $input.parent();
            $('.tag-hidden-input', $parent).val($parent.data('tags').join(', '));
            _placeholder($input);
        }

        function _placeholder($input) {
            var $parent = $input.parent();
            var placeholder = $input.data('placeholder');
            if (placeholder.length > 0) {
                if ($parent.data('tags').length === 0) {
                    $input.attr('placeholder', placeholder);
                    $input.prop('size', placeholder.length);
                } else {
                    $input.attr('placeholder', '');
                    $input.prop('size', 1);
                }
            }
        }

        function _createTag($input, value) {
            $input.before('\n<span class="tag">' + value + '<i class="tag-remove"></i></span>');
        }

        function addTag($input, value) {
            if ($input.parent().data('tags').indexOf(value) === -1) {
                $input.parent().data('tags').push(value);
                _createTag($input, value);
                _update($input);
            }
            $input.val('');
        }

        function removeTag($input, value) {
            var tags = $input.parent().data('tags');
            var index = tags.indexOf(value);
            if (index > -1) {
                tags.splice(index, 1);
                $input.parent().data('tags', tags);
                _update($input);
            }
        }

        this.each(function () {
            var $this = $(this);
            var $target = $('.tag-hidden-input', $this);
            var $input = $('.tag-inner-input', $this);
            var tags = [];

            if ($target.val()) {
                tags = $target.val().split(', ');
                $.each(tags, function (index, value) {
                    value = value.trim();
                    tags[index] = value;
                    _createTag($input, value);
                });
            }

            $this.data('tags', tags)
                .on('mousedown', '.tag-remove', false)
                .on('click', '.tag-remove', function () {
                    var $tag = $(this).parent();
                    removeTag($input, $tag.text());
                    $tag.remove();
                    return false;
                });

            if ($input.attr('placeholder') !== undefined) {
                $input.data('placeholder', $input.attr('placeholder'));
                _placeholder($input);
            } else {
                $input.data('placeholder', '');
            }
        });

        this.on('mousedown', function () {
            $('.tag-inner-input', this).trigger('focus');
            return false;
        });

        $('.tag-inner-input', this).on('focus', function () {
            $(this).parent().addClass('focused');
        }).on('blur', function () {
            var $this = $(this);
            var value = $this.val().trim();
            if (value !== '') {
                addTag($this, value);
            }
            $this.parent().removeClass('focused');
        }).on('keydown', function (event) {
            var options = {addKeyCodes: [32]};
            var $this = $(this);
            var value = $this.val().trim();

            switch (event.which) {
            case 8: // backspace
                if (value === '') {
                    removeTag($this, $this.prev().text());
                    $this.prev().remove();
                    return false;
                }
                $this.prop('size', Math.max($this.val().length, $this.attr('placeholder').length, 1));
                return true;
            case 13: // enter
            case 188: // comma
                if (value !== '') {
                    addTag($this, value);
                }
                return false;
            default:
                if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
                    addTag($this, value);
                    return false;
                }
                if (value.length > 0) {
                    $this.prop('size', $this.val().length + 2);
                }
                break;
            }

        });
    };
}(jQuery));
