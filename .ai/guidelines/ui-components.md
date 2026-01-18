# UI Components Guidelines

## Overview

This project uses **shadcn/ui** for UI components. Before creating any custom component, follow the search order below.

## Search Order

1. **Check existing components** - Look in `resources/js/components/ui/` for existing components
2. **Check shadcn/ui** - Browse [ui.shadcn.com/docs/components](https://ui.shadcn.com/docs/components) for available components
3. **Create custom** - Only if neither option exists

## Installing shadcn Components

```bash
npx shadcn@latest add <component-name>
```

Examples:

```bash
npx shadcn@latest add table
npx shadcn@latest add tabs
npx shadcn@latest add calendar
```

## Configuration

- Style: `new-york`
- Base color: `neutral`
- Icon library: `lucide`
- Components path: `@/components/ui`
- Utils path: `@/lib/utils`

## Rules

1. **Never recreate** what shadcn already provides
2. **Prefer composition** - Combine existing shadcn components before building custom ones
3. **Follow conventions** - Match existing component patterns in `resources/js/components/ui/`
4. **Use the alias** - Import from `@/components/ui/<component>`
