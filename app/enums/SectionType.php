<?php

namespace App\Enums;

enum SectionType: string
{
    case DEFAULT = 'default';
    case CONTENT_BLOCK = 'content_block';
    case SPACER = 'spacer';
    case PRODUCT_GRID = 'product_grid';
    case PRODUCT_DETAILS = 'product_details';
    case SECTION_HEADER = 'section_header';
    case HERO = 'hero';
    case FAQ = 'faq';
    case FEATURES = 'features';
    case PRICING = 'pricing';
    case FEATURED_PRODUCTS = 'featured_products';
    case CATEGORY_CARDS = 'category_cards';
    case PROCESS = 'process';
    case TELEHEALTH_CTA = 'telehealth_cta';
    case PERSONALIZED_PRICING = 'personalized_pricing';
    case RESEARCH_LINKS = 'research_links';
    case PRODUCT_SPECIFIC_QUESTIONS = 'product_specific_questions';
    case GENERAL_FAQS = 'general_faqs';
    case PROCEEDING_BUTTONS = 'proceeding_buttons';
}
