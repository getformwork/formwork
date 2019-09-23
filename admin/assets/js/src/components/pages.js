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
