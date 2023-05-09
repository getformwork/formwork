import Modals from './modals';
import Notification from './notification';
import Request from './request';
import {Sortable} from 'sortablejs';
import Utils from './utils';

export default {
    init: function () {

        var commandExpandAllPages = $('[data-command=expand-all-pages]');
        var commandCollapseAllPages = $('[data-command=collapse-all-pages]');
        var commandReorderPages = $('[data-command=reorder-pages]');
        var commandChangeSlug = $('[data-command=change-slug]');

        var searchInput = $('.page-search');

        var newPageModal = document.getElementById('newPageModal');
        var slugModal = document.getElementById('slugModal');

        $$('.pages-list').forEach(function (element) {
            if (element.getAttribute('data-orderable-children') === 'true') {
                initSortable(element);
            }
        });

        $$('.page-details').forEach(function (element) {
            if ($('.page-children-toggle', element)) {
                element.addEventListener('click', function (event) {
                    togglePageItem(this);
                    event.stopPropagation();
                });
            }
        });

        $$('.page-details a').forEach(function (element) {
            element.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        $$('.pages-list .sort-handle').forEach(function (element) {
            element.addEventListener('click', function (event) {
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
                $('.pages-list').classList.toggle('is-reordering');
                this.blur();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('focus', function () {
                $$('.pages-item').forEach(function (element) {
                    element.setAttribute('data-expanded', element.classList.contains('expanded') ? 'true' : 'false');
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
                    if (pageTemplate.hasAttribute('data-previous-value')) {
                        pageTemplate.value = pageTemplate.getAttribute('data-previous-value');
                        pageTemplate.removeAttribute('data-previous-value');
                    }
                    for (i = 0; i < pageTemplate.options.length; i++) {
                        pageTemplate.options[i].disabled = false;
                    }
                }
            });
        }

        if (slugModal && commandChangeSlug) {
            commandChangeSlug.addEventListener('click', function () {
                Modals.show('slugModal', null, function (modal) {
                    var slug = document.getElementById('slug').value;
                    var slugInput = $('#page-slug', modal);
                    slugInput.value = slug;
                    slugInput.setAttribute('placeholder', slug);
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
                    route = $('.page-slug-change').innerHTML;
                    $$('#page-slug, #slug').forEach(function (element) {
                        element.value = slug;
                    });
                    $('#page-slug', slugModal).value = slug;
                    document.getElementById('slug').value = slug;
                    $('.page-slug-change').innerHTML = route.replace(/\/[a-z0-9-]+\/$/, '/' + slug + '/');
                }
                Modals.hide('slugModal');
            });
        }

        function expandAllPages() {
            $$('.pages-item').forEach(function (element) {
                element.classList.add('is-expanded');
            });
        }

        function collapseAllPages() {
            $$('.pages-item').forEach(function (element) {
                element.classList.remove('is-expanded');
            });
        }

        function togglePageItem(list) {
            var element = list.closest('.pages-item');
            element.classList.toggle('is-expanded');
        }

        function initSortable(element) {
            var originalOrder = [];

            var sortable = Sortable.create(element, {
                handle: '.sort-handle',
                filter: '.is-not-orderable',
                forceFallback: true,
                swapThreshold: 0.75,
                invertSwap: true,
                animation: 150,

                onChoose: function () {
                    var height = document.body.offsetHeight;
                    document.body.style.height = height + 'px';

                    var e = window.addEventListener('scroll', function () {
                        this.document.body.style.height = '';
                        this.removeEventListener('scroll', e);
                    });
                },

                onStart: function () {
                    element.classList.add('is-dragging');
                },

                onMove: function (event) {
                    if (event.related.classList.contains('is-not-orderable')) {
                        return false;
                    }
                },

                onEnd: function (event) {
                    element.classList.remove('is-dragging');

                    document.body.style.height = '';

                    var data, notification;

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option('disabled', true);

                    data = {
                        'csrf-token': $('meta[name=csrf-token]').getAttribute('content'),
                        page: element.children[event.newIndex].getAttribute('data-route'),
                        before: element.children[event.oldIndex].getAttribute('data-route'),
                        parent: element.getAttribute('data-parent')
                    };

                    Request({
                        method: 'POST',
                        url: Formwork.config.baseUri + 'pages/reorder/',
                        data: data
                    }, function (response) {
                        if (response.status) {
                            notification = new Notification(response.message, response.status, {icon: 'check-circle'});
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
                $('.pages-list-root').classList.remove('is-filtered');

                $$('.pages-item').forEach(function (element) {
                    var title = $('.page-title a', element);
                    title.innerHTML = title.textContent;
                    $('.pages-item-row', element).style.display = '';
                    element.classList.toggle('is-expanded', element.getAttribute('data-expanded') === true);
                });
            } else {
                $('.pages-list-root').classList.add('is-filtered');

                regexp = new RegExp(Utils.makeDiacriticsRegExp(Utils.escapeRegExp(value)), 'gi');

                $$('.pages-item').forEach(function (element) {
                    var title = $('.page-title a', element);
                    var text = title.textContent;
                    var pagesItem = $('.pages-item-row', element);
                    if (text.match(regexp) !== null) {
                        title.innerHTML = text.replace(regexp, '<mark>$&</mark>');
                        pagesItem.style.display = '';
                    } else {
                        pagesItem.style.display = 'none';
                    }
                    element.classList.add('is-expanded');
                });

            }
        }

        function handleSlugChange() {
            this.value = Utils.validateSlug(this.value);
        }
    }
};
