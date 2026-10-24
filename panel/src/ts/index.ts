/**
 * Entry point of the panel script, loaded by the PHP views
 *
 * It registers the services, the components acting on the whole page and the views, which are
 * started by `app.load()` in this order
 */

import { ColorScheme } from "./components/ui/color-scheme";
import { Dropdowns } from "./components/ui/dropdowns";
import { Navigation } from "./components/ui/navigation";
import { Notifications } from "./components/ui/notifications";
import { Sections } from "./components/ui/sections";
import { initStatisticsChart } from "./components/ui/statistics-chart";
import { Tabs } from "./components/ui/tabs";
import { Tooltips } from "./components/ui/tooltips";
import { app } from "./services/app";
import { Forms } from "./services/forms";
import { Modals } from "./services/modals";
import { Translation } from "./services/translation";
import { initBackups } from "./views/backups";
import { initDashboard } from "./views/dashboard";
import { initFileForm, initFilesView } from "./views/files";
import { initLogin } from "./views/login";
import { initDuplicatePageModal, initNewPageModal, initPagePreview, initPagesTree, initSortable } from "./views/pages";
import { initPluginToggle } from "./views/plugins";
import { initUpdates } from "./views/updates";

// The services come first: the other components use them while they start
app.service("translation", Translation);
app.service("modals", Modals);
app.service("forms", Forms);

// Behaviors of the whole page: they look for what they need in the document
app.component(Tabs);
app.component(Dropdowns);
app.component(Tooltips);
app.component(Navigation);
app.component(ColorScheme);
app.component(Notifications);
app.component(Sections);

// Views: each one starts only if the page contains the element it works on
app.view<HTMLFormElement>("[data-form=login-form]", initLogin);
app.view("[data-view=dashboard]", initDashboard);
app.view(".pages-tree", initPagesTree);
app.views(".pages-tree[data-orderable-children=true]", initSortable);
app.modal("newPageModal", initNewPageModal);
app.modal("duplicatePageModal", initDuplicatePageModal);
app.view("[data-form=page-editor-form]", initPagePreview);
app.view("[data-view=files]", initFilesView);
app.view("[data-form=file-form]", initFileForm);
app.view(".statistics-chart", initStatisticsChart);
app.view("[data-view=backups]", initBackups);
app.view("#updater-component", initUpdates);
app.views<HTMLInputElement>(".plugin-status-toggle", initPluginToggle);

export { app };
