<?php

namespace App\Features\Landing\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Faq;
use App\Support\ApiResponse;
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
