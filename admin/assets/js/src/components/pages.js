Formwork.Pages = {
    init: function() {
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
                var $this = $(this);
                $this.data('visible', $this.is(':visible'));
            });
        });

        $('.page-search').keyup(Formwork.Utils.debounce(function() {
            var value = $(this).val();
            if (value.length === 0) {
                $('.pages-children').each(function() {
                    $(this).toggle($(this).data('visible'));
                });
                $('.page-details').css('padding-left', '');
                $('.pages-item, .page-children-toggle').show();
            } else {
                var regexp = new RegExp(Formwork.Utils.escapeRegExp(value), 'i');
                var matches = 0;
                $('.pages-children').show();
                $('.page-children-toggle').hide();
                $('.page-details').css('padding-left', '0');
                $('.page-title a').each(function() {
                    var $pagesItem = $(this).closest('.pages-item');
                    var matched = !!$(this).text().match(regexp);
                    if (matched) {
                        matches++;
                    }
                    $pagesItem.toggle(matched);
                });
            }
        }, 100));

        $('.page-details').click(function() {
            var $toggle = $(this).find('.page-children-toggle').first();
            if ($toggle.length) {
                $toggle.click();
            }
        });

        $('#page-title', '#newPageModal').keyup(function() {
            $('#page-slug', '#newPageModal').val(Formwork.Utils.slug($(this).val()));
        });

        $('#page-slug', '#newPageModal').keyup(function() {
            $(this).val($(this).val().replace(' ', '-').replace(/[^A-Za-z0-9\-]/g, ''));
        }).blur(function() {
            if ($(this).val() === '') {
                $('#page-title', '#newPageModal').trigger('keyup');
            }
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

        $('.pages-list').each(function() {
            var $this = $(this);

            if ($this.data('sortable-children') === false) {
                return;
            }

            var sortable = Sortable.create(this, {
                filter: '[data-sortable=false]',
                forceFallback: true,
                onStart: function(event) {
                    $(event.item).closest('.pages-list').addClass('dragging');
                    $('.pages-children', event.item).hide();
                    $('.page-children-toggle').removeClass('toggle-expanded')
                    .addClass('toggle-collapsed').css('opacity', '0.5');
                },
                onMove: function(event) {
                    if ($(event.related).data('sortable') === false) {
                        return false;
                    }
                    $('.pages-children', event.related).hide();
                },
                onEnd: function (event) {
                    $(event.item).closest('.pages-list').removeClass('dragging');
                    $('.page-children-toggle').css('opacity', '');

                    if (event.newIndex == event.oldIndex) {
                        return;
                    }

                    sortable.option('disabled', true);

                    var data = {
                        'csrf-token': $('meta[name=csrf-token]').attr('content'),
                        parent: $(this.el).data('parent'),
                        from: event.oldIndex,
                        to: event.newIndex
                    };

                    new Formwork.Request({
                        method: 'POST',
                        url: Formwork.Utils.uriPrependBase('/admin/pages/reorder/', location.pathname),
                        data: data
                    }, function(response) {
                        if (response.status) {
                            Formwork.Notification(response.message, response.status, 5000);
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

        $(document).keydown(function(event) {
            if (event.ctrlKey || event.metaKey) {
                // ctrl/cmd + F
                if (event.which == 70 && $('.page-search:not(:focus)').length) {
                    $('.page-search').focus();
                    return false;
                }
            }
        });
    }
};
