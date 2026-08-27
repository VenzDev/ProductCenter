<?php

declare(strict_types=1);

namespace App\Product\Controller;

use App\Ai\AttachmentEmbeddingsGeneration\Answerer\AttachmentQuestionAnswerer;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AskProductController extends Controller
{
    public function __construct(private readonly AttachmentQuestionAnswerer $answerer) {}

    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->answerer->answer($product, $data['question']);

        return response()->json([
            'answer' => $result->answer,
            'sources' => $result->sources,
        ]);
    }
}
