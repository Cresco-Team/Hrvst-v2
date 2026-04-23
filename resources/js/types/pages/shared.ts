
// ─── /categories ──────────────────────────────────────────────────────────

export interface SharedCategoriesProps {
    categories: {
        id: number
        name: string
        slug: string
    }[]
}

// ─── /categories/vegetables ─────────────────────────────────────────────────────────

export interface SharedCategoryProps {
        id: number
        name: string
        slug: string
}