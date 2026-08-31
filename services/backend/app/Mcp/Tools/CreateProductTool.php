<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Product\Action\CreateProduct;
use App\Product\ObjectValue\NewProduct;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('create-product')]
#[IsOpenWorld]
#[Description(
    'Creates a catalog product. Needs the id of an existing category, a name, a price in minor '.
    'units (e.g. 4999 = 49.99 PLN), and a public URL of a JPEG/PNG/WebP image that is downloaded '.
    'and set as the main image. The name/description are stored in the default locale.'
)]
class CreateProductTool extends Tool
{
    private const int MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    public function handle(Request $request, CreateProduct $action): Response
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['string', 'size:3'],
            'image_url' => ['required', 'url'],
            'description' => ['string', 'max:2000'],
        ], [
            'category_id.exists' => 'No category has that id. Look up the category list first and pass a real id.',
            'price_cents.integer' => 'price_cents is an integer amount in minor units, e.g. 4999 for 49.99.',
            'image_url.url' => 'image_url must be a fully-qualified http(s) URL pointing directly at an image file.',
        ]);

        $imagePath = $this->downloadImage((string) $validated['image_url']);

        if ($imagePath === null) {
            return Response::error(
                'Could not fetch a usable image from image_url. It must point directly at a '.
                'JPEG, PNG or WebP file smaller than 10 MB.'
            );
        }

        $fallback = (string) config('app.fallback_locale');
        $categoryId = (int) $validated['category_id'];
        $name = (string) $validated['name'];
        $description = isset($validated['description']) ? (string) $validated['description'] : null;

        $product = $action->handle(new NewProduct(
            categoryId: $categoryId,
            name: [$fallback => $name],
            priceCents: (int) $validated['price_cents'],
            mainImage: $imagePath,
            currency: strtoupper((string) ($validated['currency'] ?? 'PLN')),
            description: $description === null ? [] : [$fallback => $description],
        ));

        return Response::text(
            "Created product #{$product->id} \"{$name}\" in category #{$categoryId}. Its image is being "
            .'converted to WebP renditions and the product is being indexed for search.'
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category_id' => $schema->integer()
                ->description('Id of an existing category the product belongs to.')
                ->required(),
            'name' => $schema->string()
                ->description('Product name, in the default locale.')
                ->required(),
            'price_cents' => $schema->integer()
                ->description('Price in minor units (4999 = 49.99).')
                ->required(),
            'currency' => $schema->string()
                ->description('ISO 4217 currency code. Defaults to PLN.')
                ->default('PLN'),
            'image_url' => $schema->string()
                ->format('uri')
                ->description('Public URL of a JPEG/PNG/WebP image to use as the main product image.')
                ->required(),
            'description' => $schema->string()
                ->description('Optional product description, in the default locale. Omit to leave it blank.'),
        ];
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $extension = match (trim(Str::before((string) $response->header('Content-Type'), ';'))) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extension === null || strlen($response->body()) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        $path = 'product-images/tmp/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk('s3')->put($path, $response->body());

        return $path;
    }
}
