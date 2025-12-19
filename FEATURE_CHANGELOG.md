# Feature Changelog - Pages View Toggle

## Overview
Added tree/card view toggle functionality for pages list in the admin panel. The toggle buttons only appear when `subtreeGridDisplay` is enabled in the parent page's scheme.

## Date
December 19, 2025

## Changes Made

### 1. Frontend - View Toggle Buttons
**File:** `panel/views/pages/index.php`
- Added two view mode toggle buttons (tree and card) in the header
- Buttons only display when `$gridDisplayEnabled` is true
- Active state styling based on current `$viewMode`
- Positioned before the "New Page" button

### 2. Backend - Controller Logic
**File:** `formwork/src/Panel/Controllers/PagesController.php`
- Modified `tree()` action to check for `children.subtreeGridDisplay` option in parent page scheme
- Added view mode validation from query parameter `?view=`
- Card view only allowed when `subtreeGridDisplay: true` is set
- Passes `gridDisplayEnabled` and `viewMode` variables to the view

### 3. JavaScript - View Mode Handlers
**File:** `panel/src/ts/components/views/pages.ts`
- Added constants for view mode buttons: `commandViewModeTree` and `commandViewModeCard`
- Implemented `setViewMode()` function that:
  - Saves preference to localStorage
  - Updates button active states
  - Reloads page with view parameter
- Added click event listeners for both buttons

### 4. Styling - CSS Import
**File:** `panel/src/scss/panel.scss`
- Added import for `@use "components/pages-cards"`
- Ensures card view styles are included in compiled CSS

### 5. Translations - English
**File:** `panel/translations/en.yaml`
- Added `panel.pages.pages.viewTree: Tree view`
- Added `panel.pages.pages.viewCard: Card view`
- Added `panel.pages.pages.empty: No pages found`

### 6. Example Configuration
**File:** `site/schemes/pages/blog.yaml`
- Added `subtree: true` to children options
- Added `subtreeGridDisplay: true` to enable card view toggle
- Includes documentation comment

## How to Use

### Enable Card View for a Page Type

Add to your page scheme YAML file:

```yaml
options:
    children:
        subtree: true
        subtreeGridDisplay: true  # Enables the card view toggle
```

### User Experience

1. Navigate to a page that has `subtreeGridDisplay: true` in its scheme
2. Two toggle buttons appear in the header (list icon for tree, grid icon for card)
3. Click a button to switch between views
4. View preference is stored in localStorage
5. Page reloads with the selected view

## Technical Details

### View Mode Parameter
- Query parameter: `?view=tree` or `?view=card`
- Default: `tree`
- Validated on server-side

### Scheme Option
- Path: `options.children.subtreeGridDisplay`
- Type: boolean
- Default: `false`

### Button States
- Active button has `.active` class
- Uses existing button styling from `_buttons.scss`

## Files Modified

1. `panel/views/pages/index.php` - View template
2. `formwork/src/Panel/Controllers/PagesController.php` - Controller logic
3. `panel/src/ts/components/views/pages.ts` - JavaScript handlers
4. `panel/src/scss/panel.scss` - CSS imports
5. `panel/translations/en.yaml` - Translation keys
6. `site/schemes/pages/blog.yaml` - Example configuration

## Files Built

1. `panel/assets/js/chunks/*.js` - Compiled TypeScript
2. `panel/assets/css/panel.min.css` - Compiled CSS

## Dependencies

- Existing `pages-cards.scss` component (already in project)
- Existing `cards.php` view template (already in project)
- No new npm packages required

## Testing Checklist

- [ ] View toggle buttons appear when `subtreeGridDisplay: true`
- [ ] View toggle buttons hidden when `subtreeGridDisplay: false` or not set
- [ ] Tree view displays correctly
- [ ] Card view displays correctly
- [ ] Active button state updates on click
- [ ] View preference persists on page reload
- [ ] Query parameter `?view=` works correctly
- [ ] Fallback to tree view when card view not allowed
- [ ] Translations display correctly
- [ ] No console errors

## Future Enhancements

- Add view mode preference per page type (not just localStorage)
- Add animation/transition when switching views
- Add keyboard shortcuts for view toggle
- Support for additional view modes (compact, detailed, etc.)
