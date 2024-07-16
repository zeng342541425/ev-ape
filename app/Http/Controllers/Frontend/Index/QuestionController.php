<?php

namespace App\Http\Controllers\Frontend\Index;

use App\Http\Controllers\Frontend\BaseController;
use App\Models\Common\QuestionCategory;
use App\Models\Common\Questions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class QuestionController extends BaseController
{

    /**
     * 車用品牌
     * @return Response
     */
    public function categories(): Response
    {

        $model = QuestionCategory::query();

        // 關聯子級地區
        $data = $model->select('id', 'name')->get()->toArray();

        return $this->success(['list' => $data]);

    }

    public function list(Request $request): Response
    {

        $category_id = $request->get('category_id', 0);
        $search_words = $request->get('search_words', '');
        $model = Questions::query()->where('status', 1)->select('title', 'answer');

        if (intval($category_id) > 0) {
            $model->where('category_id', $category_id);
        }

        if (!empty($search_words)) {
            $model->where(function ($q) use($search_words) {
                $q->where('title', 'like', "%{$search_words}%");
                $q->orWhere('answer', 'like', "%{$search_words}%");
            });
        }

        $model->orderBy('sort');

        $list = $model->get()->toArray();

        return $this->success(['list' => $list]);
    }

}
