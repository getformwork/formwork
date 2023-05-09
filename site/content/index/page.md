---
title: Home
canonicalRoute: /
icon: page-home
---
# Formwork is Installed!
If you can see this page your Formwork installation is working.
Now you can add some content and customize the website as you want. There are two ways to do that: using the **Administration Panel** or **manually**.

### Discover the Administration Panel
The [Administration Panel](/panel) makes easy editing pages, changing options and creating users. You can always access it by visiting the [`/panel/`](/panel) page right at the site root. At the first access you’ll be requested to register a new user.

![](/assets/images/panel.png)

### Manage Pages Manually
If you prefer to manage the content manually, just locate the pages in the subdirectories of `content` folder. Each subfolder is named by its slug optionally prepended by an ordering number, e.g., `01-about`. Page content is stored in [Markdown](https://daringfireball.net/projects/markdown/syntax) text files named with its template followed by `.md` extension. For example, a page called *About*, using the `page` template and accessibile from `https://yourdomain/about/` would result in a `/content/01-about/page.md` file.

As you'll see, each page is structured in this way:

```
---
title: Page Title
---
Page content here, using Markdown syntax.
```

Code between `---` characters defines the page [YAML](https://yaml.org) Frontmatter containing the fields and metadata of the page. In the example above `title` is a field with `"Page Title"` as value.