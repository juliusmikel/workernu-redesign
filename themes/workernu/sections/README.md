# Sections

Each subfolder here is one section type, auto-discovered by the `workernu-sections` plugin.

Drop a folder with this shape:

```
<section-name>/
├── section.php       ← declares fields + label (required)
├── template.php      ← renders the HTML (required)
├── style.css         ← scoped CSS (optional)
└── animations.js     ← GSAP hooks (optional, later phase)
```

See `wp-content/plugins/workernu-sections/README.md` for the full contract.
