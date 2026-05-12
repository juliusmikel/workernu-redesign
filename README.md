# workernu — WordPress codebase

This repo tracks the custom theme, plugins, and mu-plugins for the workernu redesign. Everything else (WP core, uploads, cache, third-party plugins) lives outside the repo.

## Layout

```
wp-content/
├── themes/
│   └── workernu/             ← the only theme (custom, no parent)
├── plugins/
│   ├── workernu-lang/        ← URL-prefix multilingual layer (LT default, /en/ for English)
│   ├── workernu-sections/    ← section composition engine + admin builder
│   ├── workernu-page-theme/  ← per-page color theme picker
│   └── workernu-seo/         ← meta box, OG, JSON-LD, robots.txt, llms.txt
└── mu-plugins/               ← (empty for now)
```

## Where to read next

- **Frontend developer:** start with `themes/workernu/FRONTEND.md` — your daily reference.
- **Framework internals (PHP):** each plugin has its own `README.md` documenting field types, hooks, filters.

## Branches

- `main` — production. Deployed to live (deploy pipeline TBD).
- `dev` — local working branch.
- `frontend/<name>/<feature>` — frontend dev's working branches.

PR into `main` from feature branches.

## Local development

WordPress runs in Local by Flywheel at `workernu-redesign.local`. The repo lives at `wp-content/` of that site.

## What's NOT in this repo

- WordPress core (managed by Local)
- `wp-content/uploads/` — media library files (per-site, not source)
- `wp-content/upgrade/`, `cache/` — WP runtime artifacts
- Third-party plugins (Polylang, Classic Editor, etc.) — installed via wp-admin per environment
- Default themes (twentytwentyfive, etc.)
