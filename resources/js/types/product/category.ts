/** Base category shape. */
export interface Category {
    id: number
    name: string
}

/**
 * Structurally identical to Category — aliased for semantic clarity at
 * call sites that populate filter dropdowns.
 */
export type CategoryOption = Category
