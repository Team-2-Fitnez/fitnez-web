<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Support\ApiResponse;
use App\Support\QueryLimit;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $faqs = Faq::query()
            ->where('is_active', true)
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(QueryLimit::limit($request, 50, 100))
            ->get();

        return ApiResponse::success('FAQs loaded.', $faqs);
    }

    public function categories()
    {
        $categories = Faq::query()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->pluck('category');

        return ApiResponse::success('FAQ categories loaded.', $categories);
    }
}
