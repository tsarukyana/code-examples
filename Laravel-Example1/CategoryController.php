<?php

namespace App\Http\Controllers\Api;

use App\Models\Craft;
use App\Models\InstagramTrack;
use App\Models\ManageAccount;
use App\Models\Topics;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEditCategoryRequest;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * @param $brandId
     * @return JsonResponse
     */
    public function getCategories($brandId): JsonResponse
    {
        if (!$brandId || !is_numeric($brandId)) return response()->json(['Wrong data.'], 400);

        $categories = Categories::where('brand_id', $brandId)
            ->where('created_by', auth()->id())
            ->where('status', 'active')
            ->withCount('planedTopics')
            ->orderByDesc('created_at')->get();

        $account = ManageAccount::where(['user_id' => Auth::id(), 'brand_id' => $brandId])->whereNotNull('instagram_account')->first();
        $trackAccountExist = !!$account;
        $trackData = false;

        if($trackAccountExist) {
            $pageId = $account->instagram_account->instagram_business_account->id;
            $trackData = InstagramTrack::where('ig_user_id', $pageId)->first();
        }
        return response()->json(['categories' => $categories, 'trackCount' => (int)$trackAccountExist, 'trackDataExist' => !!$trackData]);
    }

    /**
     * @param CreateEditCategoryRequest $request
     * @return JsonResponse
     */
    public function addEditCategory(CreateEditCategoryRequest $request): JsonResponse
    {
        $input = $request->validated();

        $newCategory = Categories::updateOrCreate(
            [
                'id' => $input['category_id']
            ],
            [
                'brand_id' => $input['brand_id'],
                'name' => $input['category_name'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'lang_code' => 'en',
                'slug' => str_slug($input['category_name']),
                'keywords' => implode(',', $input['category_keywords'])
            ]
        );

        $newCategory = Categories::where('id', $newCategory->id)->withCount('planedTopics')->first();
        $message = $input['category_id'] ? 'updated' : 'create';

        return response()->json(['new_category' => $newCategory, 'message' => 'Category ' . $message . ' successfully.']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * When user delete their Category we should keep all data connected with them 10 days.
     */
    public function deleteCategory(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        if (is_numeric($categoryId)) {
            if (Categories::whereId($categoryId)->update(['status' => 'inactive'])) {
                $allCraftTopics = Craft::where('category_id', $categoryId)->get();
                $allCraftTopicsIds = [];

                if ($allCraftTopics->count()) {
                    Craft::where('category_id', $categoryId)->update(['status' => 'inactive']);

                    foreach ($allCraftTopics as $topic) {
                        $allCraftTopicsIds[] = $topic->topic_id;
                    }

                    Topics::whereIn('id', $allCraftTopicsIds)->update(['status' => 'inactive']);
                    deleteScheduls($allCraftTopics->pluck('id'));
                }

                return response()->json(['status' => true, 'message' => 'Category deleted successfully']);
            }
            return response()->json(['status' => true, 'message' => 'Something went wrong']);
        }
        return response()->json(['status' => false, 'message' => 'Something went wrong']);
    }
}
