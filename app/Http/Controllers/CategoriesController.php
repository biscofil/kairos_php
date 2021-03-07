<?php


namespace App\Http\Controllers;


use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Class CategoriesController
 * @package App\Http\Controllers
 */
class CategoriesController extends Controller
{

    /**
     * @return Category[]|Collection
     */
    public function index()
    {
        return Category::all();
    }

    /**
     * @param Request $request
     * @return Category
     */
    public function store(Request $request): Category
    {

        $data = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
        ]);

        return Category::create($data);

    }

    /**
     * @param Category $category
     * @return Category[]|Collection
     * @throws \Exception
     */
    public function destroy(Category $category)
    {
        // TODO
        $category->delete();
        return Category::all();
    }

}
