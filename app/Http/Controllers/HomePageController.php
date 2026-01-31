<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Location;
use Spatie\Activitylog\Models\Activity;

class HomePageController
{
    public function getCategories()
    {
        $categories = Category::withCount('products')->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name ?? null,
                'slug' => $category->slug ?? null,
                'image' => $this->absoluteUrl($category->getFirstMediaUrl('categories')),
                'products_count' => $category->products_count ?? 0,
            ];
        });

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function highDemand()
    {
        return response()->json([
            'products' => $this->latestProducts(),
        ]);
    }

    public function featured()
    {
        return response()->json([
            'products' => $this->latestProducts(),
        ]);
    }

    public function priceChanges()
    {
        $activities = Activity::query()
            ->where('log_name', 'products')
            ->where('subject_type', Product::class)
            ->whereNotNull('properties->old->price')
            ->whereNotNull('properties->attributes->price')
            ->with('subject')
            ->latest()
            ->take(50)
            ->get()
            ->unique('subject_id')
            ->take(4);

        $changes = $activities->map(function (Activity $activity) {
            /** @var Product|null $product */
            $product = $activity->subject;
            if (!$product) {
                return null;
            }

            $old = $this->toFloat(data_get($activity->changes(), 'old.price'));
            $new = $this->toFloat(data_get($activity->changes(), 'attributes.price', $product->price));

            if ($old === null || $new === null || $old == 0.0) {
                $percent = null;
            } else {
                $percent = (($new - $old) / $old) * 100;
            }

            $thumbnailUrl = $this->absoluteUrl($product->getFirstMediaUrl('thumbnail'));
            $imageCollection = $product->getMedia('images');
            $primaryImage = $thumbnailUrl ?: $this->absoluteUrl(optional($imageCollection->first())->getUrl());

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'change_percent' => $percent !== null ? round($percent, 1) : null,
                'image' => $primaryImage,
            ];
        })->filter()->values();

        return response()->json([
            'products' => $changes,
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with(['category', 'location', 'tags']);

        // Category filter: accepts id, slug or name
        if ($request->filled('category')) {
            $cat = $request->input('category');
            $query->where(function ($q) use ($cat) {
                $q->where('category_id', $cat)
                  ->orWhereHas('category', function ($q2) use ($cat) {
                      $q2->where('slug', $cat)->orWhere('name', $cat);
                  });
            });
        }

        // Location filter: accepts id or name
        if ($request->filled('location')) {
            $loc = $request->input('location');
            $query->where(function ($q) use ($loc) {
                $q->where('location_id', $loc)
                  ->orWhereHas('location', function ($q2) use ($loc) {
                      $q2->where('name', $loc);
                  });
            });
        }

        // Purity filter: supports ranges like "95-100", operators like ">95", ">=95", "<90"
        if ($request->filled('purity')) {
            $p = trim($request->input('purity'));
            // range
            if (Str::contains($p, '-')) {
                [$min, $max] = array_map('trim', explode('-', $p, 2));
                $min = floatval(str_replace('%', '', $min));
                $max = floatval(str_replace('%', '', $max));
                $query->whereRaw('CAST(purity AS SIGNED) BETWEEN ? AND ?', [$min, $max]);
            } elseif (Str::startsWith($p, '>=')) {
                $val = floatval(str_replace('%', '', substr($p, 2)));
                $query->whereRaw('CAST(purity AS SIGNED) >= ?', [$val]);
            } elseif (Str::startsWith($p, '>')) {
                $val = floatval(str_replace('%', '', substr($p, 1)));
                $query->whereRaw('CAST(purity AS SIGNED) > ?', [$val]);
            } elseif (Str::startsWith($p, '<=')) {
                $val = floatval(str_replace('%', '', substr($p, 2)));
                $query->whereRaw('CAST(purity AS SIGNED) <= ?', [$val]);
            } elseif (Str::startsWith($p, '<')) {
                $val = floatval(str_replace('%', '', substr($p, 1)));
                $query->whereRaw('CAST(purity AS SIGNED) < ?', [$val]);
            } else {
                // exact value
                $val = floatval(str_replace('%', '', $p));
                $query->whereRaw('CAST(purity AS SIGNED) = ?', [$val]);
            }
        }

        $perPage = (int) $request->input('per_page', 20);

        $paginated = $query->latest()->paginate($perPage);

        $transformed = $paginated->getCollection()->map(function ($product) {
            $thumbnailUrl = $this->absoluteUrl($product->getFirstMediaUrl('thumbnail'));
            $imageCollection = $product->getMedia('images');
            $primaryImage = $thumbnailUrl ?: $this->absoluteUrl(optional($imageCollection->first())->getUrl());

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'purity' => $product->purity ?? null,
                'grade' => $this->formatPurity($product->purity ?? null),
                'thumbnail' => $thumbnailUrl,
                'image' => $primaryImage,
                'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name, 'slug' => $product->category->slug] : null,
                'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                'tags' => $product->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug]),
            ];
        });

        $paginated->setCollection($transformed);

        return response()->json($paginated);
    }

    public function locations()
    {
        $locations = Location::orderBy('name')->get()->map(function ($loc) {
            return [
                'id' => $loc->id,
                'name' => $loc->name,
            ];
        });

        return response()->json(['locations' => $locations]);
    }

    private function latestProducts(int $limit = 6)
    {
        return Product::with(['category', 'location', 'tags'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($product) {
                $thumbnailUrl = $this->absoluteUrl($product->getFirstMediaUrl('thumbnail'));
                $imageCollection = $product->getMedia('images');
                $primaryImage = $thumbnailUrl ?: $this->absoluteUrl(optional($imageCollection->first())->getUrl());

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'purity' => $product->purity ?? null,
                    'grade' => $this->formatPurity($product->purity ?? null),
                    'status' => $product->status,
                    'detail' => $product->detail,
                    'thumbnail' => $thumbnailUrl,
                    'images' => collect($imageCollection)->map(fn($m) => $this->absoluteUrl($m->getUrl())),
                    'image' => $primaryImage,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                        'image' => $this->absoluteUrl($product->category->getFirstMediaUrl('categories')),
                    ] : null,
                    'location' => $product->location ? [
                        'id' => $product->location->id,
                        'name' => $product->location->name,
                    ] : null,
                    'origin' => $product->location ? $product->location->name : null,
                    'tags' => $product->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug]),
                    'badges' => $product->tags->pluck('name')->values(),
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ];
            });
    }

    private function absoluteUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }

    private function formatPurity(?string $purity): ?string
    {
        if ($purity === null) {
            return null;
        }

        return str_contains($purity, '%') ? $purity : $purity . '%';
    }

    private function toFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Strip non-numeric (except dot and minus)
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $clean === '' ? null : (float) $clean;
    }
}
