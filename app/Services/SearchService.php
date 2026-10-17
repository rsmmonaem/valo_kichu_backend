<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\SearchSynonym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SearchService
{
    /**
     * Baseline seed dictionary of bidirectional synonyms for Bengali, English, and Banglish terms.
     */
    protected static array $seedSynonyms = [
        ['chair', 'chairs', 'cheyar', 'চেয়ার', 'চেয়ার', 'কেদারা'],
        ['table', 'desk', 'tables', 'desks', 'tebil', 'টেবিল', 'ডেস্ক'],
        ['bag', 'bags', 'backpack', 'backpacks', 'handbag', 'handbags', 'byag', 'ব্যাগ', 'ব্যাগপ্যাক', 'হ্যান্ডব্যাগ', 'ট্রাভেল ব্যাগ', 'লেদার ব্যাগ'],
        ['shoe', 'shoes', 'sneaker', 'sneakers', 'loafer', 'loafers', 'sandal', 'sandals', 'juta', 'juto', 'জুতা', 'জুতো', 'স্নিকার্স', 'লোফার', 'স্যান্ডেল'],
        ['watch', 'watches', 'smartwatch', 'smartwatches', 'wrist watch', 'ghori', 'ghuri', 'ঘড়ি', 'ঘড়ি', 'হাতঘড়ি', 'হাতঘড়ি', 'স্মার্টওয়াচ', 'স্মার্টওয়াচ'],
        ['shirt', 'shirts', 'shart', 'শার্ট', 'ফরমাল শার্ট', 'ক্যাজুয়াল শার্ট'],
        ['t-shirt', 'tshirt', 't-shirts', 'tshirts', 'tee', 'tees', 'polo', 't shart', 'টিশার্ট', 'টি-শার্ট', 'পোলো শার্ট'],
        ['pant', 'pants', 'trouser', 'trousers', 'jeans', 'paynt', 'প্যান্ট', 'জিন্স', 'ট্রাউজার', 'পায়জামা'],
        ['panjabi', 'punjabi', 'kurta', 'kurtas', 'পাঞ্জাবি', 'পাঞ্জাবী', 'কুর্তা'],
        ['saree', 'sari', 'sharee', 'saris', 'sarees', 'শাড়ি', 'শাড়ি'],
        ['borkha', 'borka', 'abaya', 'hijab', 'burqa', 'বোরকা', 'বোরখা', 'হিজাব', 'আবায়া'],
        ['glass', 'glasses', 'sunglass', 'sunglasses', 'spectacles', 'eyeglass', 'choshma', 'চশমা', 'সানগ্লাস', 'আইগ্লাস'],
        ['mobile', 'phone', 'phones', 'smartphone', 'smartphones', 'cellphone', 'phon', 'মোবাইল', 'ফোন', 'স্মার্টফোন'],
        ['laptop', 'laptops', 'notebook', 'computer', 'pc', 'ল্যাপটপ', 'কম্পিউটার'],
        ['headphone', 'headphones', 'earphone', 'earphones', 'earbuds', 'airpods', 'হেডফোন', 'ইয়ারফোন', 'ইয়ারফোন', 'এয়ারপডস'],
        ['powerbank', 'power bank', 'charger', 'fast charger', 'পাওয়ার ব্যাংক', 'পাওয়ার ব্যাংক', 'চার্জার'],
        ['bottle', 'bottles', 'water bottle', 'flask', 'thermos', 'botol', 'বোতল', 'ফ্লাস্ক', 'থার্মোফ্লাস্ক'],
        ['wallet', 'wallets', 'purse', 'purses', 'moneybag', 'manibyag', 'মানিব্যাগ', 'ওয়ালেট', 'ওয়ালেট', 'পার্স'],
        ['belt', 'belts', 'leather belt', 'বেল্ট', 'কোমরবন্ধ'],
        ['cap', 'caps', 'hat', 'hats', 'tupi', 'টুপি', 'ক্যাপ'],
        ['cream', 'lotion', 'lotions', 'moisturizer', 'ক্রিম', 'লোশন', 'ময়েশ্চারাইজার', 'ময়েশ্চারাইজার'],
        ['oil', 'hair oil', 'mustard oil', 'tel', 'তেল', 'হেয়ার অয়েল', 'হেয়ার অয়েল'],
        ['soap', 'bodywash', 'shaban', 'সাবান', 'বডিওয়াশ', 'বডিওয়াশ'],
        ['shampoo', 'shampu', 'শ্যাম্পু'],
        ['perfume', 'perfumes', 'attar', 'itar', 'body spray', 'fragrance', 'পারফিউম', 'আতর', 'বডি স্প্রে', 'সুগন্ধি'],
        ['fan', 'fans', 'mini fan', 'table fan', 'ceiling fan', 'pakha', 'ফ্যান', 'মিনি ফ্যান', 'টেবিল ফ্যান', 'পাখা'],
        ['light', 'lights', 'lamp', 'lamps', 'bulb', 'bulbs', 'led', 'bati', 'লাইট', 'বাতি', 'ল্যাম্প', 'বাল্ব'],
        ['toy', 'toys', 'khelna', 'খেলনা', 'টয়'],
        ['baby', 'babies', 'kids', 'kid', 'child', 'children', 'shishu', 'বাচ্চা', 'শিশু', 'বেবি'],
        ['umbrella', 'chata', 'ছাতা'],
        ['bedsheet', 'bed sheet', 'chador', 'বেডশিট', 'চাদর'],
        ['pillow', 'cushion', 'balish', 'বালিশ', 'কুশন'],
        ['mug', 'cup', 'cups', 'মগ', 'কাপ'],
        ['ring', 'necklace', 'earring', 'jewellery', 'jewelry', 'গহনা', 'রিং', 'আংটি', 'দুল', 'নেকলেস'],
        ['cover', 'mobile cover', 'case', 'কভার', 'মোবাইল কভার', 'কেস'],
    ];

    /**
     * Get all active synonym groups combined from:
     * 1. Dynamic database records from `search_synonyms` table
     * 2. Auto-learned category name + slug pairs
     * 3. Auto-learned product tags
     * 4. Seed baseline dictionary
     */
    public static function getAllSynonymGroups(): array
    {
        try {
            return Cache::remember('search_synonyms_active_groups', 3600, function () {
                return self::loadSynonymGroupsFromSources();
            });
        } catch (\Throwable $e) {
            return self::$seedSynonyms;
        }
    }

    /**
     * Load dynamic synonym groups from database and seed list.
     */
    protected static function loadSynonymGroupsFromSources(): array
    {
        $groups = self::$seedSynonyms;

        // 1. Fetch from search_synonyms database table (if migrated)
        try {
            if (Schema::hasTable('search_synonyms')) {
                $dbSynonyms = SearchSynonym::where('is_active', true)->pluck('terms')->toArray();
                foreach ($dbSynonyms as $terms) {
                    if (is_array($terms) && count($terms) > 1) {
                        $groups[] = $terms;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Table might not exist yet
        }

        // 2. Auto-learn from Category name + slug pairs
        try {
            if (Schema::hasTable('categories')) {
                $categories = Category::where('is_active', true)->select('name', 'slug')->get();
                foreach ($categories as $cat) {
                    $slugWords = str_replace(['-', '_'], ' ', $cat->slug);
                    $groups[] = [
                        mb_strtolower($cat->name, 'UTF-8'),
                        mb_strtolower($cat->slug, 'UTF-8'),
                        mb_strtolower($slugWords, 'UTF-8'),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 3. Auto-learn from Product tags (if tags have both bangla and english)
        try {
            if (Schema::hasTable('products')) {
                $productTags = Product::where('is_active', true)
                    ->whereNotNull('tags')
                    ->limit(200)
                    ->pluck('tags');

                foreach ($productTags as $rawTags) {
                    $tagsArray = is_array($rawTags) ? $rawTags : json_decode($rawTags, true);
                    if (is_array($tagsArray) && count($tagsArray) >= 2) {
                        $cleaned = array_filter(array_map('trim', $tagsArray));
                        if (count($cleaned) >= 2) {
                            $groups[] = array_values(array_unique(array_map(function ($t) {
                                return mb_strtolower($t, 'UTF-8');
                            }, $cleaned)));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $groups;
    }

    /**
     * Phonetic rule-based transliteration generator (English <-> Bengali)
     * Dynamically maps new products' phonetic pronunciation without needing hardcoded lists!
     */
    public static function phoneticTransliterate(string $term): array
    {
        $term = mb_strtolower(trim($term), 'UTF-8');
        $results = [];

        // Check if string contains Bengali characters
        $isBengali = (bool) preg_match('/[\p{Bengali}]/u', $term);

        if (!$isBengali) {
            // Common e-commerce words phonetic rules
            $specialCases = [
                'fryer' => ['ফ্রায়ার', 'ফ্রাইয়ার', 'ফ্রাইয়ার'],
                'air' => ['এয়ার', 'এয়ার'],
                'air fryer' => ['এয়ার ফ্রায়ার', 'এয়ার ফ্রাইয়ার', 'এয়ার ফ্রায়ার'],
                'drone' => ['ড্রোন', 'ড্রন'],
                'geyser' => ['গিজার'],
                'trimmer' => ['ট্রিমার'],
                'blender' => ['ব্লেন্ডার'],
                'oven' => ['ওভেন'],
                'kettle' => ['কেটলি'],
                'smart' => ['স্মার্ট'],
                'pro' => ['প্রো'],
                'mini' => ['মিনি'],
                'stand' => ['স্ট্যান্ড'],
                'iron' => ['ইস্ত্রি', 'আয়রন', 'আয়রন'],
                'purifier' => ['পিউরিফায়ার', 'পিউরিফায়ার'],
                'speaker' => ['স্পিকার'],
                'keyboard' => ['কীবোর্ড'],
                'mouse' => ['মাউস'],
            ];

            if (isset($specialCases[$term])) {
                return $specialCases[$term];
            }

            // English -> Bengali phonetic map
            $enToBn = [
                'ph' => 'ফ', 'th' => 'থ', 'sh' => 'শ', 'ch' => 'চ', 'kh' => 'খ',
                'gh' => 'ঘ', 'bh' => 'ভ', 'dh' => 'ধ', 'zh' => 'ঝ',
                'ee' => 'ী', 'oo' => 'ু', 'ai' => 'াই', 'oi' => 'ই',
                'b' => 'ব', 'c' => 'ক', 'd' => 'ড', 'f' => 'ফ', 'g' => 'গ',
                'h' => 'হ', 'j' => 'জ', 'k' => 'ক', 'l' => 'ল', 'm' => 'ম',
                'n' => 'ন', 'p' => 'প', 'q' => 'ক', 'r' => 'র', 's' => 'স',
                't' => 'ট', 'v' => 'ভ', 'w' => 'ও', 'x' => 'ক্স', 'y' => 'য়',
                'z' => 'জ', 'a' => 'া', 'e' => 'ে', 'i' => 'ি', 'o' => 'ো',
                'u' => 'ু',
            ];

            $converted = str_ireplace(array_keys($enToBn), array_values($enToBn), $term);
            if (!empty($converted) && preg_match('/[\p{Bengali}]/u', $converted)) {
                $results[] = $converted;
            }
        } else {
            // Bengali -> English phonetic rules
            $bnToEn = [
                'ফ্রায়ার' => ['fryer', 'air fryer'],
                'ফ্রাইয়ার' => ['fryer', 'air fryer'],
                'এয়ার ফ্রায়ার' => ['air fryer'],
                'এয়ার ফ্রায়ার' => ['air fryer'],
                'এয়ার' => ['air'],
                'এয়ার' => ['air'],
                'ড্রোন' => ['drone'],
                'গিজার' => ['geyser'],
                'ট্রিমার' => ['trimmer'],
                'ব্লেন্ডার' => ['blender'],
                'ওভেন' => ['oven'],
                'কেটলি' => ['kettle'],
                'আয়রন' => ['iron'],
                'ইস্ত্রি' => ['iron'],
                'স্পিকার' => ['speaker'],
                'মাউস' => ['mouse'],
                'কীবোর্ড' => ['keyboard'],
            ];

            if (isset($bnToEn[$term])) {
                return $bnToEn[$term];
            }
        }

        return $results;
    }

    /**
     * Normalize and tokenize search text while preserving Bengali, English, numbers, and hyphens.
     */
    public static function tokenize(string $text): array
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Replace dashes and punctuation with spaces
        $cleaned = str_replace(['-', '–', '—', '_', '(', ')', '[', ']', '{', '}', '/', '\\', '|', '+'], ' ', $text);
        // Replace other punctuation (except Bengali characters, alphanumeric, and spaces) with spaces
        $cleaned = preg_replace('/[^\p{Bengali}\p{L}\p{N}\s]/u', ' ', $cleaned);
        $parts = preg_split('/[\s,]+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($parts)));
    }

    /**
     * Find all synonyms and related terms for a given keyword/phrase.
     * Utilizes database synonyms + auto-learned tags/categories + phonetic rules + seed dictionary.
     */
    public static function expandKeyword(string $word): array
    {
        $word = mb_strtolower(trim($word), 'UTF-8');
        $expanded = [$word];

        $allGroups = self::getAllSynonymGroups();

        foreach ($allGroups as $group) {
            $inGroup = false;
            foreach ($group as $synonym) {
                if (mb_strtolower(trim($synonym), 'UTF-8') === $word) {
                    $inGroup = true;
                    break;
                }
            }

            if ($inGroup) {
                foreach ($group as $synonym) {
                    $cleanSyn = mb_strtolower(trim($synonym), 'UTF-8');
                    if (!in_array($cleanSyn, $expanded)) {
                        $expanded[] = $cleanSyn;
                    }
                }
            }
        }

        // Add phonetic transliteration expansion
        $phonetics = self::phoneticTransliterate($word);
        foreach ($phonetics as $p) {
            $cleanP = mb_strtolower(trim($p), 'UTF-8');
            if (!in_array($cleanP, $expanded)) {
                $expanded[] = $cleanP;
            }
        }

        return $expanded;
    }

    /**
     * Apply intelligent bilingual search filters and relevance scoring to a product query.
     */
    public static function applySearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        if (empty($search)) {
            return $query;
        }

        $tokens = self::tokenize($search);
        if (empty($tokens)) {
            return $query;
        }

        // Collect all expanded terms across all tokens
        $allExpandedTerms = [];
        $tokenExpandedGroups = [];

        foreach ($tokens as $token) {
            $expanded = self::expandKeyword($token);
            $tokenExpandedGroups[] = $expanded;
            foreach ($expanded as $term) {
                $allExpandedTerms[] = $term;
            }
        }
        $allExpandedTerms = array_values(array_unique($allExpandedTerms));

        // Add search conditions: Product must match the expanded query tokens
        $query->where(function (Builder $outerQ) use ($search, $tokens, $tokenExpandedGroups, $allExpandedTerms) {
            // Match 1: Exact search phrase in name, code, sku, or tags
            $outerQ->where('name', 'LIKE', "%{$search}%")
                   ->orWhere('product_code', 'LIKE', "%{$search}%")
                   ->orWhere('product_sku', 'LIKE', "%{$search}%")
                   ->orWhere('tags', 'LIKE', "%{$search}%")
                   ->orWhere('brand', 'LIKE', "%{$search}%");

            // Match 2: Check matching category names
            $outerQ->orWhereHas('category', function ($catQuery) use ($search, $allExpandedTerms) {
                $catQuery->where(function ($subQ) use ($search, $allExpandedTerms) {
                    $subQ->where('name', 'LIKE', "%{$search}%");
                    foreach (array_slice($allExpandedTerms, 0, 8) as $term) {
                        $subQ->orWhere('name', 'LIKE', "%{$term}%");
                    }
                });
            });

            // Match 3: Each token must match at least one of its synonyms (AND logic across tokens)
            $outerQ->orWhere(function (Builder $tokenQ) use ($tokenExpandedGroups) {
                foreach ($tokenExpandedGroups as $synonyms) {
                    $tokenQ->where(function (Builder $synQ) use ($synonyms) {
                        foreach ($synonyms as $syn) {
                            $synQ->orWhere('name', 'LIKE', "%{$syn}%")
                                 ->orWhere('tags', 'LIKE', "%{$syn}%")
                                 ->orWhere('brand', 'LIKE', "%{$syn}%");
                        }
                    });
                }
            });

            // Match 4: Any of the expanded terms in name or tags
            foreach ($allExpandedTerms as $syn) {
                $outerQ->orWhere('name', 'LIKE', "%{$syn}%")
                       ->orWhere('tags', 'LIKE', "%{$syn}%");
            }
        });

        // Smart Multi-Token Weighted Relevance Scoring (Token Density & Hit Count)
        // Products that match MORE query keywords, consecutive bigrams, or variations will rank at the top!
        $scoreSqlParts = [];
        $bindings = [];

        // Tier 1: Exact full title match
        $scoreSqlParts[] = "(CASE WHEN LOWER(name) = ? THEN 500 ELSE 0 END)";
        $bindings[] = mb_strtolower($search, 'UTF-8');

        // Tier 2: Starts with search query
        $scoreSqlParts[] = "(CASE WHEN LOWER(name) LIKE ? THEN 300 ELSE 0 END)";
        $bindings[] = mb_strtolower($search, 'UTF-8') . '%';

        // Tier 3: Contains full search string anywhere
        $scoreSqlParts[] = "(CASE WHEN LOWER(name) LIKE ? THEN 200 ELSE 0 END)";
        $bindings[] = '%' . mb_strtolower($search, 'UTF-8') . '%';

        // Tier 4: Exact SKU or product_code
        $scoreSqlParts[] = "(CASE WHEN product_code = ? OR product_sku = ? THEN 350 ELSE 0 END)";
        $bindings[] = $search;
        $bindings[] = $search;

        // Tier 5: Individual Token Hits (High density of matching keywords)
        // Each matching keyword in product name awards 25 points
        foreach (array_slice($tokens, 0, 8) as $t) {
            $scoreSqlParts[] = "(CASE WHEN LOWER(name) LIKE ? THEN 25 ELSE 0 END)";
            $bindings[] = '%' . mb_strtolower($t, 'UTF-8') . '%';

            // Keyword in tags or brand awards 12 points
            $scoreSqlParts[] = "(CASE WHEN tags LIKE ? OR brand LIKE ? THEN 12 ELSE 0 END)";
            $bindings[] = '%' . $t . '%';
            $bindings[] = '%' . $t . '%';

            // Keyword in colors or variations awards 20 points (e.g. for color queries like Red, Blue, etc.)
            $scoreSqlParts[] = "(CASE WHEN colors LIKE ? OR variations LIKE ? THEN 20 ELSE 0 END)";
            $bindings[] = '%' . $t . '%';
            $bindings[] = '%' . $t . '%';
        }

        // Tier 6: Consecutive Bigram Hits (2-word phrases in title like "baby led", "pearl mini", "mini handbag")
        for ($i = 0; $i < count($tokens) - 1 && $i < 6; $i++) {
            $bigram = $tokens[$i] . ' ' . $tokens[$i + 1];
            $scoreSqlParts[] = "(CASE WHEN LOWER(name) LIKE ? THEN 35 ELSE 0 END)";
            $bindings[] = '%' . mb_strtolower($bigram, 'UTF-8') . '%';
        }

        // Tier 7: Top Synonym Hits
        foreach (array_slice($allExpandedTerms, 0, 6) as $syn) {
            if (!in_array($syn, $tokens)) {
                $scoreSqlParts[] = "(CASE WHEN LOWER(name) LIKE ? THEN 15 ELSE 0 END)";
                $bindings[] = '%' . mb_strtolower($syn, 'UTF-8') . '%';
            }
        }

        $finalScoreSql = implode(' + ', $scoreSqlParts);
        $query->orderByRaw("({$finalScoreSql}) DESC", $bindings);

        return $query;
    }

    /**
     * Provide instant search suggestions for autocomplete dropdown (Daraz-style).
     */
    public static function getSuggestions(string $query, int $productLimit = 6, int $categoryLimit = 3): array
    {
        $query = trim($query);
        if (mb_strlen($query, 'UTF-8') < 1) {
            return [
                'products' => [],
                'categories' => [],
                'keywords' => [],
            ];
        }

        // 1. Fetch matching products
        $productQuery = Product::query()->where('is_active', true);
        $productQuery = self::applySearch($productQuery, $query);

        $products = $productQuery->select([
            'id', 'name', 'slug', 'base_price', 'sale_price', 'unit_price', 'image', 'category_id'
        ])
        ->with('category:id,name,slug')
        ->limit($productLimit)
        ->get();

        // 2. Fetch matching categories (both direct name and expanded synonym)
        $tokens = self::tokenize($query);
        $expandedTerms = [];
        foreach ($tokens as $t) {
            $expandedTerms = array_merge($expandedTerms, self::expandKeyword($t));
        }
        $expandedTerms = array_values(array_unique($expandedTerms));

        $categoryQuery = Category::query()->where('is_active', true);
        $categoryQuery->where(function ($q) use ($query, $expandedTerms) {
            $q->where('name', 'LIKE', "%{$query}%");
            foreach (array_slice($expandedTerms, 0, 6) as $term) {
                $q->orWhere('name', 'LIKE', "%{$term}%");
            }
        });
        $categories = $categoryQuery->select(['id', 'name', 'slug', 'image'])
            ->limit($categoryLimit)
            ->get();

        // 3. Keyword suggestions
        $keywords = [];
        foreach (array_slice($expandedTerms, 0, 5) as $term) {
            if ($term !== mb_strtolower($query, 'UTF-8')) {
                $keywords[] = $term;
            }
        }

        return [
            'products' => $products,
            'categories' => $categories,
            'keywords' => $keywords,
        ];
    }
}
