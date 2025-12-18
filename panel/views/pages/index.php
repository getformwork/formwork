<?php $this->layout('panel') ?>

<?php $this->modals()->add('deletePageItem') ?>
<?php $this->modals()->add('duplicatePage') ?>

<header class="panel-header">
    <div class="panel-header-breadcrumb">
        <?= $this->icon('pages') ?>
        <span><?= $this->translate('panel.pages.pages') ?></span>
        <?php foreach ($parent->ancestors()->reverse()->with($parent) as $page) : ?>
            <span class="breadcrumb-separator">/</span>
            <?php if ($page->isSite()) : ?>
                <a href="<?= $panel->uri('/pages/') ?>" class="breadcrumb-link">
                    <?= $this->icon('globe') ?>
                    <span><?= $this->translate('panel.options.site') ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/tree/') ?>" class="breadcrumb-link">
                    <?= $this->icon($page->icon()) ?>
                    <span><?= $this->escape($page->title()) ?></span>
                </a>
            <?php endif ?>
        <?php endforeach ?>
    </div>

    <div class="panel-header-search">
        <div class="form-input-wrap">
            <span class="form-input-icon"><?= $this->icon('search') ?></span>
            <input class="form-input page-search" id="pages.search" type="search" placeholder="<?= $this->translate('panel.pages.pages.search') ?>">
        </div>
        <div class="pages-tree-controls">
            <button type="button" class="button button-secondary" data-command="expand-all-pages" disabled><?= $this->icon('chevron-down') ?></button>
            <button type="button" class="button button-secondary" data-command="collapse-all-pages" disabled><?= $this->icon('chevron-up') ?></button>
            <button type="button" class="button button-secondary" data-command="reorder-pages" disabled><?= $this->icon('reorder-v') ?></button>
        </div>
    </div>

    <div class="panel-header-actions">
        <div class="pages-view-toggle">
            <button type="button" class="button" data-view="cards" data-command="toggle-view" title="<?= $this->translate('panel.pages.view.cards') ?>">
                <?= $this->icon('cards') ?>
            </button>
            <button type="button" class="button active" data-view="tree" data-command="toggle-view" title="<?= $this->translate('panel.pages.view.tree') ?>">
                <?= $this->icon('list') ?>
            </button>
        </div>
        <?php if ($panel->user()->permissions()->has('panel.pages.create')) : ?>
            <button type="button" class="button button-accent" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
        <?php endif ?>
    </div>
</header>

<section class="section">
    <!-- Card View -->
    <div class="pages-view pages-view-cards" style="display: none;">
        <div class="mb-4" style="padding: 0.75rem 1rem; border-radius: 0.5rem; background: rgba(59, 130, 246, 0.1); border-left: 3px solid rgba(59, 130, 246, 0.5);">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <?= $this->icon('info-circle') ?>
                <span class="text-size-sm"><?= $this->translate('panel.pages.view.cards.reorderNote') ?></span>
            </div>
        </div>
        <?= $pagesCards ?? '' ?>
    </div>
    
    <!-- Tree View (default) -->
    <div class="pages-view pages-view-tree">
        <?= $pagesTree ?>
    </div>
</section>

<script>
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const viewToggles = document.querySelectorAll('[data-command="toggle-view"]');
        const cardView = document.querySelector('.pages-view-cards');
        const treeView = document.querySelector('.pages-view-tree');
        const treeControls = document.querySelector('.pages-tree-controls');
        const searchInput = document.getElementById('pages.search');
        
        // Load saved preference
        const savedView = localStorage.getItem('formwork-pages-view') || 'tree';
        
        viewToggles.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                setView(view);
                localStorage.setItem('formwork-pages-view', view);
            });
        });
        
        function setView(view) {
            viewToggles.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            if (view === 'cards') {
                cardView.style.display = 'block';
                treeView.style.display = 'none';
                if (treeControls) {
                    treeControls.style.display = 'none';
                    // Disable tree control buttons in card view
                    treeControls.querySelectorAll('button').forEach(btn => {
                        btn.disabled = true;
                    });
                }
                initializeCardDropdowns();
            } else {
                cardView.style.display = 'none';
                treeView.style.display = 'block';
                if (treeControls) {
                    treeControls.style.display = 'flex';
                    // Re-enable tree control buttons (TypeScript will manage their state)
                    treeControls.querySelectorAll('button').forEach(btn => {
                        btn.disabled = false;
                    });
                }
            }
        }
        
        function initializeCardDropdowns() {
            // Remove any existing listeners
            const dropdownButtons = cardView.querySelectorAll('.dropdown-button');
            
            dropdownButtons.forEach(button => {
                // Clone to remove old listeners
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const menuId = this.getAttribute('data-dropdown');
                    const menu = document.getElementById(menuId);
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(m => {
                        if (m.id !== menuId) {
                            m.style.display = 'none';
                        }
                    });
                    
                    // Toggle this dropdown
                    if (menu) {
                        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                    }
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.pages-view-cards .dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });
        }
        
        // Search functionality for card view only
        // Tree view search is handled by the main TypeScript app
        if (searchInput) {
            let cardSearchHandler = null;
            
            function setupCardSearch() {
                const currentView = localStorage.getItem('formwork-pages-view') || 'tree';
                
                if (currentView === 'cards') {
                    // Add card search handler
                    cardSearchHandler = function() {
                        const searchTerm = this.value.toLowerCase();
                        const cards = cardView.querySelectorAll('.page-card');
                        
                        cards.forEach(card => {
                            const title = card.querySelector('.page-card-title')?.textContent.toLowerCase() || '';
                            const slug = card.querySelector('.page-card-slug')?.textContent.toLowerCase() || '';
                            const matches = title.includes(searchTerm) || slug.includes(searchTerm);
                            card.style.display = matches ? '' : 'none';
                        });
                    };
                    
                    searchInput.addEventListener('input', cardSearchHandler);
                    searchInput.addEventListener('keyup', cardSearchHandler);
                } else {
                    // Remove card search handler when in tree view
                    if (cardSearchHandler) {
                        searchInput.removeEventListener('input', cardSearchHandler);
                        searchInput.removeEventListener('keyup', cardSearchHandler);
                        cardSearchHandler = null;
                    }
                }
            }
            
            // Setup search based on current view
            setupCardSearch();
            
            // Re-setup search when view changes
            viewToggles.forEach(btn => {
                btn.addEventListener('click', function() {
                    setTimeout(setupCardSearch, 100);
                });
            });
        }
        
        // Initialize the saved view
        setView(savedView);
    });
})();
</script>
