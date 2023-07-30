import Modals from './modals';
import Notification from './notification';
import Request from './request';
import { Sortable } from 'sortablejs';
import Utils from './utils';

export default {
    init: function () {
        const commandExpandAllPages = $('[data-command=expand-all-pages]');
        const commandCollapseAllPages = $('[data-command=collapse-all-pages]');
        const commandReorderPages = $('[data-command=reorder-pages]');
        const commandChangeSlug = $('[data-command=change-slug]');

        const searchInput = $('.page-search');

        const newPageModal = document.getElementById('newPageModal');
        const slugModal = document.getElementById('slugModal');

        $$('.pages-list').forEach((element) => {
            if (element.getAttribute('data-orderable-children') === 'true') {
                initSortable(element);
            }
        });

        $$('.page-details').forEach((element) => {
            if ($('.page-children-toggle', element)) {
                element.addEventListener('click', function (event) {
                    togglePageItem(this);
                    event.stopPropagation();
                });
            }
        });

        $$('.page-details a').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        $$('.pages-list .sort-handle').forEach((element) => {
            element.addEventListener('click', (event) => {
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
            searchInput.addEventListener('focus', () => {
                $$('.pages-item').forEach((element) => {
                    element.setAttribute('data-expanded', element.classList.contains('expanded') ? 'true' : 'false');
                });
            });

            searchInput.addEventListener('keyup', Utils.debounce(handleSearch, 100));
            searchInput.addEventListener('search', handleSearch);

            document.addEventListener('keydown', (event) => {
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
                const option = this.options[this.selectedIndex];
                const pageTemplate = $('#page-template', newPageModal);
                let allowedTemplates = option.getAttribute('data-allowed-templates');

                if (allowedTemplates !== null) {
                    allowedTemplates = allowedTemplates.split(', ');
                    pageTemplate.setAttribute('data-previous-value', pageTemplate.value);
                    pageTemplate.value = allowedTemplates[0];

                    for (const option of pageTemplate.options) {
                        if (!allowedTemplates.includes(option.value)) {
                            option.setAttribute('disabled', '');
                        }
                    }

                } else {
                    if (pageTemplate.hasAttribute('data-previous-value')) {
                        pageTemplate.value = pageTemplate.getAttribute('data-previous-value');
                        pageTemplate.removeAttribute('data-previous-value');
                    }

                    for (const option of pageTemplate.options) {
                        option.disabled = false;
                    }
                }
            });
        }

        if (slugModal && commandChangeSlug) {
            commandChangeSlug.addEventListener('click', () => {
                Modals.show('slugModal', null, (modal) => {
                    const slug = document.getElementById('slug').value;
                    const slugInput = $('#page-slug', modal);
                    slugInput.value = slug;
                    slugInput.setAttribute('placeholder', slug);
                });
            });

            $('#page-slug', slugModal).addEventListener('keydown', (event) => {
                // enter
                if (event.which === 13) {
                    $('[data-command=continue]', slugModal).click();
                }
            });

            $('#page-slug', slugModal).addEventListener('keyup', handleSlugChange);
            $('#page-slug', slugModal).addEventListener('blur', handleSlugChange);

            $('[data-command=generate-slug]', slugModal).addEventListener('click', () => {
                const slug = Utils.slug(document.getElementById('title').value);
                $('#page-slug', slugModal).value = slug;
                $('#page-slug', slugModal).focus();
            });

            $('[data-command=continue]', slugModal).addEventListener('click', () => {
                const slug = $('#page-slug', slugModal).value.replace(/^-+|-+$/, '');

                if (slug.length > 0) {
                    const route = $('.page-slug-change').innerHTML;
                    $$('#page-slug, #slug').forEach((element) => {
                        element.value = slug;
                    });
                    $('#page-slug', slugModal).value = slug;
                    document.getElementById('slug').value = slug;
                    $('.page-slug-change').innerHTML = route.replace(/\/[a-z0-9-]+\/$/, `/${slug}/`);
                }

                Modals.hide('slugModal');
            });
        }

        $$(['[data-modal=renameFileModal]']).forEach((element) => {
            element.addEventListener('click', () => {
                const modal = document.getElementById('renameFileModal');
                const input = $('#file-name', modal);
                input.value = element.getAttribute('data-filename');
                input.setSelectionRange(0, input.value.lastIndexOf('.'));
            });
        });

        function expandAllPages() {
            $$('.pages-item').forEach((element) => {
                element.classList.add('is-expanded');
            });
        }

        function collapseAllPages() {
            $$('.pages-item').forEach((element) => {
                element.classList.remove('is-expanded');
            });
        }

        function togglePageItem(list) {
            const element = list.closest('.pages-item');
            element.classList.toggle('is-expanded');
        }

        function initSortable(element) {
            let originalOrder = [];

            const sortable = Sortable.create(element, {
                handle: '.sort-handle',
                filter: '.is-not-orderable',
                forceFallback: true,
                swapThreshold: 0.75,
                invertSwap: true,
                animation: 150,

                onChoose: function () {
                    const height = document.body.offsetHeight;
                    document.body.style.height = `${height}px`;

                    const e = window.addEventListener('scroll', function () {
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

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option('disabled', true);

                    const data = {
                        'csrf-token': $('meta[name=csrf-token]').getAttribute('content'),
                        page: element.children[event.newIndex].getAttribute('data-route'),
                        before: element.children[event.oldIndex].getAttribute('data-route'),
                        parent: element.getAttribute('data-parent'),
                    };

                    Request({
                        method: 'POST',
                        url: `${Formwork.config.baseUri}pages/reorder/`,
                        data: data,
                    }, (response) => {
                        if (response.status) {
                            const notification = new Notification(response.message, response.status, { icon: 'check-circle' });
                            notification.show();
                        }
                        if (!response.status || response.status === 'error') {
                            sortable.sort(originalOrder);
                        }
                        sortable.option('disabled', false);
                        originalOrder = sortable.toArray();
                    });

                },
            });

            originalOrder = sortable.toArray();
        }

        function handleSearch() {
            const value = this.value;
            if (value.length === 0) {
                $('.pages-list-root').classList.remove('is-filtered');

                $$('.pages-item').forEach((element) => {
                    const title = $('.page-title a', element);
                    title.innerHTML = title.textContent;
                    $('.pages-item-row', element).style.display = '';
                    element.classList.toggle('is-expanded', element.getAttribute('data-expanded') === true);
                });
            } else {
                $('.pages-list-root').classList.add('is-filtered');

                const regexp = new RegExp(Utils.makeDiacriticsRegExp(Utils.escapeRegExp(value)), 'gi');

                $$('.pages-item').forEach((element) => {
                    const title = $('.page-title a', element);
                    const text = title.textContent;
                    const pagesItem = $('.pages-item-row', element);

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
    },
};
