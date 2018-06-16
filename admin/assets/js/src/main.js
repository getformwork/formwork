$(function() {

    $('[data-modal]').click(function() {
        var $this = $(this);
        var modal = $this.data('modal');
        var action = $this.data('modal-action');
        if (action) {
            Modal.show(modal, action);
        } else {
            Modal.show(modal);
        }
    });

    $('.input-reset').click(function() {
        var $target = $('#' + $(this).data('reset'));
        $target.val('');
        $target.change();
    });

    $('[data-auto-upload]').change(function() {
        $(this).closest('form').submit();
    });

    $('.file-input-label').on('drag dragstart dragend dragover dragenter dragleave drop', function(event) {
        event.preventDefault();
    }).on('drop', function(event) {
        var $target = $('#' + $(this).attr('for'));
        $target.prop('files', event.originalEvent.dataTransfer.files);
        // Firefox won't trigger a change event, so we explicitly do that
        $target.change();
    }).on('dragover dragenter', function() {
        $(this).addClass('drag');
    }).on('dragleave drop', function() {
        $(this).removeClass('drag');
    });

    $('input:file').change(function() {
        var files = $(this).prop('files');
        if (files.length) {
            $('label[for="' + $(this).attr('id') + '"] span').text(files[0].name);
        }
    });

    $('.page-children-toggle').click(function(event) {
        event.stopPropagation();
        $(this).closest('li').children('.pages-list').toggle();
        $(this).toggleClass('toggle-expanded toggle-collapsed');
    });

    $('.page-details a').click(function(event) {
        event.stopPropagation();
    });

    $('#expand-all-pages').click(function() {
        $(this).blur();
        $('.pages-children').show();
        $('.pages-list').find('.page-children-toggle').removeClass('toggle-collapsed').addClass('toggle-expanded');
    });

    $('#collapse-all-pages').click(function() {
        $(this).blur();
        $('.pages-children').hide();
        $('.pages-list').find('.page-children-toggle').removeClass('toggle-expanded').addClass('toggle-collapsed');
    });

    $('.page-search').focus(function() {
        $('.pages-children').each(function() {
            $(this).data('visible', $(this).is(':visible'));
        });
    });

    $('.page-search').keyup(Utils.debounce(function() {
        var value = $(this).val();
            if (value.length == 0) {
                $('.pages-children').each(function() {
                    $(this).toggle($(this).data('visible'));
                });
                $('.page-details').css('padding-left', '');
                $('.pages-item, .page-children-toggle').show();
            } else {
                $('.pages-children').show();
                $('.page-children-toggle').hide();
                $('.page-details').css('padding-left', '0');
                var regexp = new RegExp(Utils.escapeRegExp(value), 'i');
                var matches = 0;
                $('.page-title a').each(function() {
                    var $pagesItem = $(this).closest('.pages-item');
                    var matched = !!$(this).text().match(regexp);
                    matched && matches++;
                    $pagesItem.toggle(matched);
                });
            }
    }, 100));

    $('.page-details').click(function() {
        var $toggle = $(this).find('.page-children-toggle').first();
        if ($toggle.length) $toggle.click();
    });

    $('#page-title', '#newPageModal').keyup(function() {
        $('#page-slug', '#newPageModal').val(Utils.slug($(this).val()));
    });

    $('#page-slug', '#newPageModal').keyup(function() {
        $(this).val($(this).val().replace(' ', '-').replace(/[^A-Za-z0-9\-]/g, ''));
    }).blur(function() {
        if ($(this).val() == '') $('#page-title', '#newPageModal').trigger('keyup');
    });

    $('#page-parent', '#newPageModal').change(function() {
        var $option = $(this).find('option:selected');
        var $pageTemplate = $('#page-template', '#newPageModal');
        var allowedTemplates = $option.data('allowed-templates');
        if (allowedTemplates) {
            allowedTemplates = allowedTemplates.split(', ');
            $pageTemplate
                .data('previous-value', $pageTemplate.val())
                .val(allowedTemplates[0])
                .find('option').each(function () {
                    if (allowedTemplates.indexOf($(this).val()) == -1) {
                        $(this).attr('disabled', true);
                    }
                });
        } else if ($pageTemplate.find('option[disabled]').length) {
            $pageTemplate
                .val($pageTemplate.data('previous-value'))
                .removeData('previous-value')
                .find('option').removeAttr('disabled');
        }
    });

    $(document).keyup(function(event) {
        // ESC key
        if (event.which == 27) Modal.hide();
    }).keydown(function(event) {
        if (event.ctrlKey || event.metaKey) {
            // ctrl/cmd + F
            if (event.which == 70 && $('.page-search:not(:focus)').length) {
                $('.page-search').focus();
                return false;
            }
        }
    });

    $('.tabs-tab[data-tab]').click(function() {
        $(this).addClass('active').siblings().removeClass('active');
    });

    $('.tag-input').tagInput();

    $('input[data-enable]').change(function() {
        var checked = $(this).is(':checked');
        $.each($(this).data('enable').split(','), function(index, value) {
            $('input[name="' + value + '"]').attr('disabled', !checked);
        });
    });

    $('.toggle-navigation').click(function() {
        $('.sidebar').toggleClass('show');
    });

    $('.overflow-title').mouseover(function() {
        var $this = $(this);
        if ($this.prop('offsetWidth') < $this.prop('scrollWidth')) {
            $this.attr('title', $this.text().trim());
        } else {
            $this.removeAttr('title');
        }
    });

    $('.pages-list').each(function() {
        var $this = $(this);

        if ($this.data('sortable') === false) return;

        var sortable = Sortable.create(this, {
            filter: '.no-reorder',
            forceFallback: true,
            onStart: function(event) {
                $('.pages-children').each(function() {
                    $(this).data('visible', $(this).is(':visible')).hide();
                });

                $('.page-children-toggle').each(function(){
                    $(this).data('expanded', $(this).hasClass('toggle-expanded'));
                    $(this).removeClass('toggle-expanded').addClass('toggle-collapsed');
                }).css('opacity', '0.5');
            },
            onMove: function(event) {
                return !$(event.related).hasClass('no-reorder');
            },
            onEnd: function (event) {
                $('.pages-children').each(function() {
                    $(this).toggle($(this).data('visible'));
                });

                $('.page-children-toggle').each(function() {
                    if ($(this).data('expanded')) {
                        $(this).removeClass('toggle-collapsed').addClass('toggle-expanded');
                    }
                }).css('opacity', '');

                if (event.newIndex == event.oldIndex) return;

                sortable.option('disabled', true);

                var data = {
                    'csrf-token': $('body').data('csrf-token'),
                    parent: $(this.el).data('parent'),
                    from: event.oldIndex,
                    to: event.newIndex
                };

                new Request({
                    method: 'POST',
                    url: Utils.uriPrependBase(location.pathname, '/admin/pages/reorder/'),
                    data: data
                }, function(response) {
                    if (response.status) {
                        Notification(response.message, response.status, 5000);
                    }
                    if (!response.status || response.status == 'error') {
                        sortable.sort($(event.from).data('originalOrder'));
                    }
                    sortable.option('disabled', false);
                    $(event.from).data('originalOrder', sortable.toArray());
                });

            }
        });

        $this.data('originalOrder', sortable.toArray());
    });

    $('#clear-cache').click(function() {
        new Request({
            method: 'POST',
            url: Utils.uriPrependBase(location.pathname, '/admin/cache/clear/'),
            data: {'csrf-token': $('body').data('csrf-token')}
        }, function(response) {
            Notification(response.message, response.status, 5000);
        });
    });

    $('.editor-textarea').each(function() {
        new Editor($(this).attr('id'));
    });

});
