<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $table = 'pages';

    protected $fillable = [
        'page_type',
        'title',
        'content',
        'status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'image',
    ];

    protected $casts = [
        'status' => 'boolean',
        'meta_keywords' => 'array',
    ];

    // Page types constants
    const TYPE_TERMS_AND_CONDITIONS = 'terms_and_conditions';
    const TYPE_PRIVACY_POLICY = 'privacy_policy';
    const TYPE_ABOUT_US = 'about_us';
    const TYPE_RETURN_POLICY = 'return_policy';

    public static function getTermsAndConditions()
    {
        return self::where('page_type', self::TYPE_TERMS_AND_CONDITIONS)->first();
    }

    public static function getPrivacyPolicy()
    {
        return self::where('page_type', self::TYPE_PRIVACY_POLICY)->first();
    }

    public static function getAboutUs()
    {
        return self::where('page_type', self::TYPE_ABOUT_US)->first();
    }

    public static function getReturnPolicy()
    {
        return self::where('page_type', self::TYPE_RETURN_POLICY)->first();
    }
}

