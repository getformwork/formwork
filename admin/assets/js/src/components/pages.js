import Modals from './modals';
import Notification from './notification';
import Request from './request';
import Sortable from 'sortablejs';
import Utils from './utils';

export default {
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
                Modals.show('slugModal', null, function (modal) {
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
                Modals.hide('slugModal');
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
