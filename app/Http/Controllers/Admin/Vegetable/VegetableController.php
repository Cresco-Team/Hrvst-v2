public function index(Request $request): Response|RedirectResponse
{
    if (! $request->filled('category')) {
        return redirect()->route('admin.categories.index');
    }

    $slug = $request->query('category');
    $category = Category::where('slug', $slug)->firstOrFail();

    return Inertia::render('admin/vegetables/Index', [
        'vegetables' => Inertia::defer(fn () => VegetableAdminData::collect(
            $this->vegetableService->paginated(
                search: $request->query('search'),
                categoryId: $category->id,
            )->paginate(20)->withQueryString(),
        )),
        'summary' => Inertia::defer(fn () => $this->vegetableService->summary()),
        'filters' => ['search' => $request->query('search', null)],
        'category' => $category,
    ]);
}
