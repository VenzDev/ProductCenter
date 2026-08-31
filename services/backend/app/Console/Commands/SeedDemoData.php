<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Category\Support\CategorySlugger;
use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Storage\StorageDisk;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Populates categories, attributes, products, and blog posts with dummy content — for local dev
 * and e2e only, never production. Categories/attributes/blog posts are idempotent (safe to rerun);
 * each run adds `--products` more products on top of whatever already exists.
 */
class SeedDemoData extends Command
{
    protected $signature = 'demo:seed {--products=24 : Total number of demo products to create, spread across the subcategories}';

    protected $description = 'Seeds demo categories, attributes, products, and blog posts with dummy images (local/e2e only)';

    /**
     * @var array<int, array{name: array<string, string>, children: array<int, array{name: array<string, string>, attributes: list<string>}>}>
     */
    private const CATEGORY_TREE = [
        [
            'name' => ['en' => 'Electronics', 'pl' => 'Elektronika'],
            'children' => [
                ['name' => ['en' => 'Phones', 'pl' => 'Telefony'], 'attributes' => ['color', 'weight_kg', 'storage_gb', 'screen_size_inch', 'brand']],
                ['name' => ['en' => 'Laptops', 'pl' => 'Laptopy'], 'attributes' => ['color', 'weight_kg', 'ram_gb', 'storage_gb', 'brand']],
                ['name' => ['en' => 'Audio', 'pl' => 'Audio'], 'attributes' => ['color', 'weight_kg', 'battery_life_h', 'materials', 'brand']],
            ],
        ],
        [
            'name' => ['en' => 'Home & Garden', 'pl' => 'Dom i ogród'],
            'children' => [
                ['name' => ['en' => 'Furniture', 'pl' => 'Meble'], 'attributes' => ['color', 'materials', 'weight_kg', 'dimensions_cm', 'assembly_required']],
                ['name' => ['en' => 'Kitchen', 'pl' => 'Kuchnia'], 'attributes' => ['materials', 'capacity_l', 'color', 'dishwasher_safe', 'care_instructions']],
                ['name' => ['en' => 'Garden Tools', 'pl' => 'Narzędzia ogrodowe'], 'attributes' => ['materials', 'weight_kg', 'power_source', 'length_cm', 'brand']],
            ],
        ],
    ];

    /**
     * @var array<string, array{type: AttributeType, name: array<string, string>, options?: list<array{key: string, name: array<string, string>}>}>
     */
    private const ATTRIBUTES = [
        'color' => ['type' => AttributeType::Select, 'name' => ['en' => 'Color', 'pl' => 'Kolor'], 'options' => [
            ['key' => 'red', 'name' => ['en' => 'Red', 'pl' => 'Czerwony']],
            ['key' => 'blue', 'name' => ['en' => 'Blue', 'pl' => 'Niebieski']],
            ['key' => 'black', 'name' => ['en' => 'Black', 'pl' => 'Czarny']],
            ['key' => 'white', 'name' => ['en' => 'White', 'pl' => 'Biały']],
            ['key' => 'silver', 'name' => ['en' => 'Silver', 'pl' => 'Srebrny']],
        ]],
        'weight_kg' => ['type' => AttributeType::Number, 'name' => ['en' => 'Weight (kg)', 'pl' => 'Waga (kg)']],
        'storage_gb' => ['type' => AttributeType::Select, 'name' => ['en' => 'Storage (GB)', 'pl' => 'Pamięć (GB)'], 'options' => [
            ['key' => '64', 'name' => ['en' => '64 GB', 'pl' => '64 GB']],
            ['key' => '128', 'name' => ['en' => '128 GB', 'pl' => '128 GB']],
            ['key' => '256', 'name' => ['en' => '256 GB', 'pl' => '256 GB']],
            ['key' => '512', 'name' => ['en' => '512 GB', 'pl' => '512 GB']],
        ]],
        'screen_size_inch' => ['type' => AttributeType::Number, 'name' => ['en' => 'Screen size (in)', 'pl' => 'Przekątna ekranu (cale)']],
        'brand' => ['type' => AttributeType::Text, 'name' => ['en' => 'Brand', 'pl' => 'Marka']],
        'ram_gb' => ['type' => AttributeType::Select, 'name' => ['en' => 'RAM (GB)', 'pl' => 'RAM (GB)'], 'options' => [
            ['key' => '8', 'name' => ['en' => '8 GB', 'pl' => '8 GB']],
            ['key' => '16', 'name' => ['en' => '16 GB', 'pl' => '16 GB']],
            ['key' => '32', 'name' => ['en' => '32 GB', 'pl' => '32 GB']],
            ['key' => '64', 'name' => ['en' => '64 GB', 'pl' => '64 GB']],
        ]],
        'battery_life_h' => ['type' => AttributeType::Number, 'name' => ['en' => 'Battery life (h)', 'pl' => 'Czas pracy baterii (h)']],
        'materials' => ['type' => AttributeType::MultiSelect, 'name' => ['en' => 'Materials', 'pl' => 'Materiały'], 'options' => [
            ['key' => 'wood', 'name' => ['en' => 'Wood', 'pl' => 'Drewno']],
            ['key' => 'metal', 'name' => ['en' => 'Metal', 'pl' => 'Metal']],
            ['key' => 'plastic', 'name' => ['en' => 'Plastic', 'pl' => 'Plastik']],
            ['key' => 'glass', 'name' => ['en' => 'Glass', 'pl' => 'Szkło']],
            ['key' => 'fabric', 'name' => ['en' => 'Fabric', 'pl' => 'Tkanina']],
        ]],
        'dimensions_cm' => ['type' => AttributeType::Text, 'name' => ['en' => 'Dimensions (cm)', 'pl' => 'Wymiary (cm)']],
        'assembly_required' => ['type' => AttributeType::Select, 'name' => ['en' => 'Assembly required', 'pl' => 'Wymaga montażu'], 'options' => [
            ['key' => 'yes', 'name' => ['en' => 'Yes', 'pl' => 'Tak']],
            ['key' => 'no', 'name' => ['en' => 'No', 'pl' => 'Nie']],
        ]],
        'capacity_l' => ['type' => AttributeType::Number, 'name' => ['en' => 'Capacity (l)', 'pl' => 'Pojemność (l)']],
        'dishwasher_safe' => ['type' => AttributeType::Select, 'name' => ['en' => 'Dishwasher safe', 'pl' => 'Można myć w zmywarce'], 'options' => [
            ['key' => 'yes', 'name' => ['en' => 'Yes', 'pl' => 'Tak']],
            ['key' => 'no', 'name' => ['en' => 'No', 'pl' => 'Nie']],
        ]],
        'care_instructions' => ['type' => AttributeType::TextTranslatable, 'name' => ['en' => 'Care instructions', 'pl' => 'Instrukcja pielęgnacji']],
        'power_source' => ['type' => AttributeType::Select, 'name' => ['en' => 'Power source', 'pl' => 'Źródło zasilania'], 'options' => [
            ['key' => 'manual', 'name' => ['en' => 'Manual', 'pl' => 'Ręczne']],
            ['key' => 'electric', 'name' => ['en' => 'Electric', 'pl' => 'Elektryczne']],
            ['key' => 'battery', 'name' => ['en' => 'Battery', 'pl' => 'Akumulatorowe']],
        ]],
        'length_cm' => ['type' => AttributeType::Number, 'name' => ['en' => 'Length (cm)', 'pl' => 'Długość (cm)']],
    ];

    // 'Hello World' is a fixed title, not just a placeholder: e2e's blog.spec.ts browses to it by
    // its exact title/slug, so renaming or dropping it here breaks that test.
    private const BLOG_POST_TITLES = [
        'Hello World',
        'Five Tips For a Greener Garden This Spring',
        'How We Pick Products For The Catalog',
    ];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('demo:seed only runs in the local or testing environments.');

            return self::FAILURE;
        }

        $attributes = $this->seedAttributes();

        /** @var list<array{category: Category, attributes: Collection<int, Attribute>}> $subcategories */
        $subcategories = $this->seedCategories($attributes);

        $this->seedProducts($subcategories, max(0, (int) $this->option('products')));
        $this->seedBlogPosts();

        $this->info('Demo data seeded.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<string, Attribute>
     */
    private function seedAttributes(): Collection
    {
        return collect(self::ATTRIBUTES)->map(function (array $definition, string $key): Attribute {
            /** @var AttributeType $type */
            $type = $definition['type'];

            return Attribute::query()->firstOrCreate(['key' => $key], [
                'name' => $definition['name'],
                'type' => $type,
                'options' => $definition['options'] ?? null,
                'filterable' => $type->isFilterable(),
            ]);
        });
    }

    /**
     * @param  Collection<string, Attribute>  $attributes
     * @return list<array{category: Category, attributes: Collection<int, Attribute>}>
     */
    private function seedCategories(Collection $attributes): array
    {
        $subcategories = [];

        foreach (self::CATEGORY_TREE as $parentDefinition) {
            $parentSlug = CategorySlugger::slug($parentDefinition['name']['en']);
            $parent = Category::query()->firstOrCreate(['slug' => $parentSlug], ['name' => $parentDefinition['name']]);

            foreach ($parentDefinition['children'] as $childDefinition) {
                $childSlug = CategorySlugger::slug($childDefinition['name']['en'], $parent->slug);
                $child = Category::query()->firstOrCreate(
                    ['slug' => $childSlug],
                    ['name' => $childDefinition['name'], 'parent_id' => $parent->id],
                );

                $childAttributes = $attributes->only($childDefinition['attributes'])->values();
                $child->attributes()->syncWithoutDetaching($childAttributes->pluck('id'));

                $subcategories[] = ['category' => $child, 'attributes' => $childAttributes];
            }
        }

        return $subcategories;
    }

    /**
     * @param  list<array{category: Category, attributes: Collection<int, Attribute>}>  $subcategories
     */
    private function seedProducts(array $subcategories, int $total): void
    {
        if ($total < 1 || $subcategories === []) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->setMessage('Seeding products...');

        for ($i = 0; $i < $total; $i++) {
            $subcategory = $subcategories[$i % count($subcategories)];
            $imageKey = 'product-images/tmp/'.Str::uuid()->toString().'.jpg';
            Storage::disk(StorageDisk::S3)->put($imageKey, $this->fetchDummyImageBytes());

            Product::query()->create([
                'category_id' => $subcategory['category']->id,
                'name' => Str::title(implode(' ', $this->fakeWords(rand(2, 4)))),
                'description' => implode("\n\n", $this->fakeParagraphs(rand(2, 4))),
                'price_cents' => fake()->numberBetween(1999, 499999),
                'attributes' => $subcategory['attributes']
                    ->mapWithKeys(fn (Attribute $attribute) => [$attribute->key => $this->randomAttributeValue($attribute)])
                    ->all(),
                'main_image' => $imageKey,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function seedBlogPosts(): void
    {
        foreach (self::BLOG_POST_TITLES as $title) {
            $slug = Str::slug($title);

            if (BlogPost::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $imageKey = 'blog-post-images/tmp/'.Str::uuid()->toString().'.jpg';
            Storage::disk(StorageDisk::S3)->put($imageKey, $this->fetchDummyImageBytes());

            BlogPost::query()->create([
                'title' => $title,
                'slug' => $slug,
                'content' => collect($this->fakeParagraphs(4))->map(fn (string $paragraph) => "<p>{$paragraph}</p>")->implode(''),
                'published_at' => now()->subDays(rand(1, 30)),
                'preview_image' => $imageKey,
            ]);
        }
    }

    private function randomAttributeValue(Attribute $attribute): mixed
    {
        return match ($attribute->type) {
            AttributeType::Number => $this->randomNumberFor($attribute->key),
            AttributeType::Select => fake()->randomElement(array_column($attribute->options ?? [], 'key')),
            AttributeType::MultiSelect => fake()->randomElements(
                array_column($attribute->options ?? [], 'key'),
                rand(1, min(3, count($attribute->options ?? []))),
            ),
            AttributeType::Text => $attribute->key === 'brand' ? fake()->company() : fake()->numerify('##x##x##'),
            AttributeType::TextTranslatable => ['en' => fake()->sentence(), 'pl' => fake()->sentence()],
        };
    }

    private function randomNumberFor(string $key): int|float
    {
        return match ($key) {
            'weight_kg' => round(fake()->randomFloat(1, 0.1, 30), 1),
            'screen_size_inch' => round(fake()->randomFloat(1, 5, 17), 1),
            'battery_life_h' => fake()->numberBetween(3, 40),
            'capacity_l' => round(fake()->randomFloat(1, 0.5, 5), 1),
            'length_cm' => fake()->numberBetween(10, 200),
            default => fake()->numberBetween(1, 100),
        };
    }

    /**
     * @return list<string>
     */
    private function fakeWords(int $count): array
    {
        /** @var list<string> $words */
        $words = fake()->words($count);

        return $words;
    }

    /**
     * @return list<string>
     */
    private function fakeParagraphs(int $count): array
    {
        /** @var list<string> $paragraphs */
        $paragraphs = fake()->paragraphs($count);

        return $paragraphs;
    }

    private function fetchDummyImageBytes(): string
    {
        $response = Http::timeout(15)->get('https://picsum.photos/800/600');

        if (! $response->successful()) {
            throw new RuntimeException("Failed to fetch a dummy image from picsum.photos: HTTP {$response->status()}");
        }

        return $response->body();
    }
}
