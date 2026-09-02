<?php

namespace App\Support\Seo;

use App\Models\Post;
use App\Models\Product;
use App\Services\Pricing\ResolvedPrice;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;

/** تولید JSON-LD — همه اسکیماها از اینجا می‌آیند تا یکدست بمانند. */
final class Schema
{
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => config('shop.name'),
            'url'      => url('/'),
            'logo'     => url('/images/logo.svg'),
            'contactPoint' => [
                '@type'       => 'ContactPoint',
                'telephone'   => \App\Models\Setting::get('support_phone'),
                'contactType' => 'customer service',
                'areaServed'  => 'IR',
                'availableLanguage' => ['fa'],
            ],
        ];
    }

    public static function product(Product $product, ResolvedPrice $price): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product->name,
            'sku'         => $product->sku,
            'description' => $product->seoDescription(),
            'brand'       => ['@type' => 'Brand', 'name' => $product->brand?->name ?? config('shop.name')],
            'url'         => route('shop.product', $product),
        ];

        if ($image = $product->primary_image) {
            $schema['image'] = Storage::disk('public')->url($image);
        }

        if (! $price->hidden && $price->amount > 0) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => Money::toToman($price->amount),
                'priceCurrency' => 'IRR',
                'availability'  => $product->isAvailable()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url'           => route('shop.product', $product),
                'seller'        => ['@type' => 'Organization', 'name' => config('shop.name')],
            ];
        }

        if ($product->rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round($product->rating_avg, 1),
                'reviewCount' => $product->rating_count,
            ];
        }

        return $schema;
    }

    public static function article(Post $post): array
    {
        return array_filter([
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $post->title,
            'description'   => $post->seoDescription(),
            'image'         => $post->cover ? Storage::disk('public')->url($post->cover) : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified'  => $post->updated_at?->toIso8601String(),
            'author'        => ['@type' => 'Organization', 'name' => config('shop.name')],
            'publisher'     => ['@type' => 'Organization', 'name' => config('shop.name')],
            'mainEntityOfPage' => route('blog.show', $post),
        ]);
    }

    public static function faq(array $items): ?array
    {
        if (empty($items)) {
            return null;
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => collect($items)->map(fn ($item) => [
                '@type'          => 'Question',
                'name'           => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ])->all(),
        ];
    }

    public static function render(?array $schema): string
    {
        if (! $schema) {
            return '';
        }

        return '<script type="application/ld+json">'
            .json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .'</script>';
    }
}
