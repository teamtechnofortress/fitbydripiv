<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductBenefit;
use App\Models\ProductPricing;
use App\Models\PricingOption;
use App\Models\ProductResearchLink;
use App\Models\Faq;
use App\Models\CmsCategory;

class ProductSeeder extends Seeder
{
    public function run(): void
{
    $categories = CmsCategory::pluck('id', 'slug')->toArray();

    $this->seedSemaglutide($categories);
    $this->seedTirzepatide($categories);
    $this->seedB12($categories);
    $this->seedGlutathione($categories);
    $this->seedNad($categories);
}

    // ─────────────────────────────────────────────
    // SEMAGLUTIDE
    // ─────────────────────────────────────────────

    private function seedSemaglutide(array $categories): void
    {
        /** @var Product $product */
        $product = Product::updateOrCreate(['slug' => 'semaglutide'], [
            'name'                          => 'Semaglutide',
            'category_id'                   => $categories['weight-loss'],
            'description'                   => 'Utilize Compounded Semaglutide for Your Weight Loss Goals AND Improve Metabolic Health. A high percentage seeing results.',
            'about_treatment'               => "A high percentage of Semaglutide patients are seeing beneficial prescription weight loss therapy results \xe2\x80\x93 losing weekly, monthly and more. Since being prescribed, patients report significant improvements in weight, energy, physical health, and overall confidence. Our comprehensive program includes medical oversight, customized dosing, and continuous support throughout your weight loss journey. Complete our Telehealth intake about your Health and Goals.",
            'how_it_works'                  => 'Semaglutide is a GLP-1 receptor agonist that mimics a natural hormone your body produces. It works by regulating appetite, slowing gastric emptying, and improving insulin sensitivity. This helps you feel fuller longer, reduces cravings, and supports sustainable weight loss. The medication also improves metabolic health markers including blood sugar control. When combined with lifestyle changes, Semaglutide provides powerful weight loss results with medical supervision and support.',
            'key_ingredients'               => 'Semaglutide (GLP-1 receptor agonist), Compounded pharmaceutical grade',
            'treatment_duration'            => 'Results typically seen within 2-4 weeks of starting therapy. Significant weight loss develops over 3-6 months. Treatment duration varies based on individual goals, response, and health markers. Continuous medical oversight throughout.',
            'usage_instructions'            => 'Administered as a once-weekly subcutaneous injection (under the skin). Dosage is gradually increased under medical supervision to optimize results and minimize side effects. Our team provides complete injection training and ongoing support.',
            'research_description'          => null,
            'clinical_research_description' => null,
            'is_featured'                   => true,
            'is_published'                  => true,
            'completion_status'             => 'complete',
            'completion_percentage'         => 100,
            'completion_step'               => 5,
        ]);

        $this->upsertCoverImage($product, '/images/simagulatide.png');

        $this->syncBenefits($product, [
            'Significant Weight Loss - Proven results with medical oversight',
            'Improved Metabolic Health - Better blood sugar and metabolism',
            'Reduced Appetite - Feel fuller longer, reduce cravings',
            'Increased Energy - More energy for daily activities',
            'Improved Physical Health - Overall health improvements',
            'Enhanced Confidence - Better self-image and confidence',
            'Cost Effective - Affordable monthly therapy pricing',
            'Convenient Dosing - Once-weekly injection',
            'Medical Supervision - Comprehensive professional support',
        ]);

        $this->syncSubscriptionPricing($product, basePrice: 140.00, microDosePrice: 122.50, samplePrice: 59.50);

        $this->syncResearchLinks($product, [
            [
                'title'            => 'ScienceDirect: GLP-1 Receptor Agonist Mechanisms',
                'authors'          => 'Various Authors',
                'journal'          => 'ScienceDirect',
                'publication_year' => 2024,
                'article_url'      => 'https://www.sciencedirect.com/science/article/pii/s221324220038',
            ],
            [
                'title'            => 'NIH Gov/Articles: GLP-1 Treatment Efficacy',
                'authors'          => 'Various Authors',
                'journal'          => 'NIH Gov/Articles',
                'publication_year' => 2024,
                'pubmed_id'        => 'Pmc 8947838',
                'article_url'      => 'https://pmc.ncbi.nlm.nih.gov/articles/pmc8947838',
            ],
            [
                'title'            => 'PubMed: Semaglutide for Weight Management',
                'authors'          => 'Various Authors',
                'journal'          => 'PubMed NCBI',
                'publication_year' => 2024,
                'pubmed_id'        => 'Pmc 9418657',
                'article_url'      => 'https://pubmed.ncbi.nlm.nih.gov/',
            ],
        ]);

        $this->syncFaqs($product, [
            ['What is Semaglutide?', 'Semaglutide is a GLP-1 (glucagon-like peptide-1) receptor agonist medication approved by the FDA for chronic weight management and type 2 diabetes treatment. It works by mimicking a natural hormone that regulates appetite, blood sugar, and digestion.'],
            ['How does Semaglutide work for weight loss?', 'Semaglutide activates GLP-1 receptors in the brain, pancreas, and digestive system. This slows gastric emptying (making you feel full longer), reduces appetite signals, improves insulin sensitivity, and decreases food cravings. Clinical studies show average weight loss of 15-20% of body weight over 68 weeks.'],
            ['What are the common side effects?', 'The most common side effects include nausea (usually mild to moderate), vomiting, diarrhea, constipation, abdominal pain, and decreased appetite. These typically occur during the initial weeks and improve as your body adjusts. Starting with a low dose and gradually increasing helps minimize side effects.'],
            ['How is Semaglutide administered?', 'Semaglutide is administered via subcutaneous injection once weekly, typically in the abdomen, thigh, or upper arm. The medication comes in pre-filled pens or vials with syringes. Our medical team provides detailed injection training and ongoing support.'],
            ['How long does it take to see weight loss results?', 'Most patients begin noticing appetite suppression within the first week. Visible weight loss typically starts within 4-8 weeks. Optimal results occur over 6-12 months when combined with a healthy diet and regular physical activity. Consistency is key for sustainable results.'],
            ['Do I need a prescription for Semaglutide?', 'Yes, Semaglutide is a prescription medication that requires medical evaluation and approval. Our licensed healthcare providers will review your health history, current medications, and weight loss goals during the telehealth intake process to determine if Semaglutide is appropriate for you.'],
            ['What is included with my Semaglutide prescription?', 'Your prescription includes the compounded Semaglutide + B12 medication, sterile syringes, alcohol prep pads, detailed administration instructions, dosing guidelines, and access to our medical support team for questions and adjustments throughout your treatment journey.'],
            ['Can I use Semaglutide if I have diabetes?', 'Semaglutide was originally developed for type 2 diabetes management and is safe for diabetic patients. However, it requires careful monitoring and potential adjustment of other diabetes medications. Discuss your complete medical history with our providers during your consultation.'],
            ['What happens if I miss a dose?', 'If you miss a dose and it has been less than 5 days, take it as soon as you remember. If more than 5 days have passed, skip the missed dose and resume your regular weekly schedule. Never take two doses within 48 hours of each other.'],
            ['How should I store Semaglutide?', 'Store unopened Semaglutide in the refrigerator at 36-46°F (2-8°C). Do not freeze. Once in use, the medication can be kept at room temperature (up to 86°F/30°C) for up to 56 days. Protect from light and keep out of reach of children.'],
            ['Will I regain weight after stopping Semaglutide?', 'Weight regain is possible after discontinuing any weight loss medication. To maintain results, it is important to establish healthy lifestyle habits during treatment including balanced nutrition, regular exercise, stress management, and adequate sleep. Many patients transition to maintenance doses or periodic treatment cycles.'],
            ['Are there any contraindications for Semaglutide?', 'Semaglutide is not recommended for patients with personal or family history of medullary thyroid carcinoma (MTC), Multiple Endocrine Neoplasia syndrome type 2 (MEN2), severe gastrointestinal disease, or pancreatitis. Pregnant or breastfeeding women should not use Semaglutide. Full medical screening is conducted during your telehealth intake.'],
        ]);
    }

    // ─────────────────────────────────────────────
    // TIRZEPATIDE
    // ─────────────────────────────────────────────

    private function seedTirzepatide(array $categories): void
    {
        /** @var Product $product */
        $product = Product::updateOrCreate(['slug' => 'tirzepatide'], [
            'name'                          => 'Tirzepatide',
            'category_id'                   => $categories['weight-loss'],
            'description'                   => 'A High Percentage of Tirzepatide Patients Are Seeing Healthier Swiss Being Prescription Weight Loss Therapy Results.',
            'about_treatment'               => "Healthier SWIMM BEING Prescription Weight Loss Therapy - utilizing compounded Tirzepatide for your weight loss goals AND improved metabolic health. Patients report significant weight loss results, increased energy, improved physical health, enhanced confidence of appearance, and better overall wellbeing. This dual-action medication activates both GIP and GLP-1 receptors for superior results. Complete our Telehealth intake about your Health and Goals, and our medical provider will review for eligibility.",
            'how_it_works'                  => 'Tirzepatide is an advanced dual-action medication that activates both GIP (glucose-dependent insulinotropic polypeptide) and GLP-1 (glucagon-like peptide-1) receptors. This dual activation provides superior appetite suppression, improved insulin sensitivity, and enhanced metabolic benefits compared to single-action medications. It helps you feel fuller longer, reduces cravings, improves energy expenditure, and supports more effective weight loss. Combined with lifestyle changes and medical supervision, Tirzepatide offers powerful results for your health goals.',
            'key_ingredients'               => 'Tirzepatide (GIP and GLP-1 receptor agonist), Compounded pharmaceutical grade',
            'treatment_duration'            => 'Initial results within 2-4 weeks. Significant weight loss develops over 3-6 months of treatment. Optimal effects achieved with consistent use and lifestyle modifications. Treatment duration personalized to individual goals and response.',
            'usage_instructions'            => 'Administered as once-weekly subcutaneous injection. Gradual dose escalation under medical supervision to optimize results and tolerance. Complete injection training provided. Continuous support throughout your journey.',
            'research_description'          => null,
            'clinical_research_description' => null,
            'is_featured'                   => true,
            'is_published'                  => true,
            'completion_status'             => 'complete',
            'completion_percentage'         => 100,
            'completion_step'               => 5,
        ]);

        $this->upsertCoverImage($product, '/images/Tirziptide+b12.png');

        $this->syncBenefits($product, [
            'Superior Weight Loss - Advanced dual-action medication',
            'Dual GIP and GLP-1 Activation - More effective than single-action',
            'Improved Metabolic Health - Better blood sugar and metabolism',
            'Increased Physical Energy - More energy throughout the day',
            'Enhanced Confidence - Better appearance and self-image',
            'Improved Physical Health - Overall health improvements',
            'Cost Effective - Affordable monthly subscription program',
            'Medical Oversight - Continuous professional support',
            'Convenient Dosing - Once-weekly injection',
            'Comprehensive Support - Full telehealth support system',
        ]);

        $this->syncSubscriptionPricing($product, basePrice: 150.00, microDosePrice: 131.25, samplePrice: 63.75);

        $this->syncResearchLinks($product, [
            [
                'title'            => 'GLP-1 Receptor Agonists: Central Nervous System Effects on Energy Balance',
                'authors'          => 'Various Authors',
                'journal'          => 'ScienceDirect',
                'publication_year' => 2024,
                'article_url'      => 'https://www.sciencedirect.com/science/article/pii/S221324220038',
            ],
            [
                'title'            => 'GLP-1 and GIP Receptor Dual Agonist Treatment Effects',
                'authors'          => 'Various Authors',
                'journal'          => 'NIH Gov/Articles',
                'publication_year' => 2024,
                'pubmed_id'        => 'Pmc 9418649',
                'article_url'      => 'https://pmc.ncbi.nlm.nih.gov/articles/pmc9418649',
            ],
            [
                'title'            => 'Dual GIP/GLP-1 Receptor Co-Agonist for Weight Management',
                'authors'          => 'Various Authors',
                'journal'          => 'PubMed',
                'publication_year' => 2024,
                'pubmed_id'        => 'Pmc 9418657',
                'article_url'      => 'https://pubmed.ncbi.nlm.nih.gov/articles/',
            ],
            [
                'title'            => 'Full Clinical Trial Results for Tirzepatide in Weight Loss',
                'authors'          => 'Various Authors',
                'journal'          => 'JAMA Internal Medicine',
                'publication_year' => 2024,
                'article_url'      => 'https://jamanetwork.com/journals/jamainternalmedicine/fullarticle/2802038',
            ],
            [
                'title'            => 'Mechanisms and Clinical Efficacy of Dual GIP/GLP-1 Therapy',
                'authors'          => 'Various Authors',
                'journal'          => 'Mayo Clinic Drug Supplements',
                'publication_year' => 2024,
                'article_url'      => 'https://mayoclinic.org/drugs-supplements/tirzepatide/description/drg-20534047',
            ],
        ]);

        $this->syncFaqs($product, [
            ['What is Tirzepatide?', 'Tirzepatide is a dual GIP/GLP-1 receptor agonist medication approved by the FDA for chronic weight management and type 2 diabetes. It is the first medication to activate both glucose-dependent insulinotropic polypeptide (GIP) and glucagon-like peptide-1 (GLP-1) receptors, offering enhanced metabolic benefits.'],
            ['How is Tirzepatide different from Semaglutide?', 'Unlike Semaglutide which only activates GLP-1 receptors, Tirzepatide activates both GIP and GLP-1 receptors. This dual mechanism may provide superior weight loss results, better blood sugar control, and improved metabolic health. Clinical trials show Tirzepatide users lost an average of 20-25% of body weight compared to 15-20% with GLP-1-only medications.'],
            ['How does Tirzepatide work?', 'Tirzepatide works through dual receptor activation. GLP-1 activation reduces appetite, slows gastric emptying, and improves insulin secretion. GIP activation enhances these effects while also improving fat metabolism, reducing inflammation, and promoting energy expenditure. This synergistic action provides comprehensive metabolic support.'],
            ['What are the benefits of the added B12 and B6?', 'B12 (Methylcobalamin) supports energy production, metabolism, and nervous system health during weight loss. B6 (Pyridoxine) aids in protein metabolism, neurotransmitter synthesis, and helps maintain muscle mass during caloric restriction. This combination supports overall wellness and helps prevent common deficiencies during weight loss treatment.'],
            ['How often do I inject Tirzepatide?', 'Tirzepatide is administered once weekly via subcutaneous injection. Consistent weekly dosing on the same day each week helps maintain stable medication levels. The injection can be done at any time of day, with or without meals, in the abdomen, thigh, or upper arm.'],
            ['What side effects should I expect?', 'Common side effects include nausea, diarrhea, decreased appetite, vomiting, constipation, and mild abdominal discomfort. These typically occur during the initial weeks and dose escalation periods. Most side effects improve over time as your body adjusts. Eating smaller portions and avoiding fatty foods can help minimize gastrointestinal symptoms.'],
            ['How quickly will I see weight loss results?', 'Many patients notice appetite suppression within the first few days to weeks. Measurable weight loss typically begins within 4-6 weeks. Significant results become evident after 8-12 weeks of consistent treatment. Maximum benefits are typically achieved over 6-12 months when combined with healthy lifestyle modifications.'],
            ['Do I need a prescription for Tirzepatide?', 'Yes, Tirzepatide requires a valid prescription from a licensed healthcare provider. Our telehealth team will conduct a thorough medical evaluation including your health history, current medications, BMI, and weight loss goals to determine if Tirzepatide is appropriate and safe for you.'],
            ['What comes with my Tirzepatide prescription?', 'Your prescription includes compounded Tirzepatide + B12 + B6 medication, sterile syringes, alcohol prep pads, sharps disposal container, detailed injection instructions, dosing schedule, and access to our medical support team for ongoing guidance and dose adjustments.'],
            ['Can I drink alcohol while taking Tirzepatide?', 'While there is no absolute contraindication, alcohol consumption should be limited. Alcohol can worsen gastrointestinal side effects, affect blood sugar levels, add empty calories that hinder weight loss, and increase the risk of pancreatitis. If you choose to drink, do so in moderation and monitor how your body responds.'],
            ['How should Tirzepatide be stored?', 'Store unopened Tirzepatide in the refrigerator at 36-46°F (2-8°C). Do not freeze. Once in use, it can be kept at room temperature (up to 86°F/30°C) for up to 21 days. Always store in the original packaging to protect from light. Inspect the solution before each use - it should be clear and colorless.'],
            ['Who should not take Tirzepatide?', 'Tirzepatide is not recommended for individuals with personal or family history of medullary thyroid carcinoma, Multiple Endocrine Neoplasia syndrome type 2, history of pancreatitis, severe gastrointestinal disease, or diabetic retinopathy. It should not be used during pregnancy or breastfeeding. Complete medical screening is performed during your consultation.'],
        ]);
    }

    // ─────────────────────────────────────────────
    // B12
    // ─────────────────────────────────────────────

    private function seedB12(array $categories): void
    {
        /** @var Product $product */
        $product = Product::updateOrCreate(['slug' => 'b12-injection'], [
            'name'                          => 'B12 (Methylcobalamin)',
            'category_id'                   => $categories['wellness'],
            'description'                   => 'Boost Energy, Enhance Metabolism, and Support Nervous System Health',
            'about_treatment'               => "Vitamin B12 (Methylcobalamin) is an essential nutrient that plays a crucial role in energy production, red blood cell formation, DNA synthesis, and neurological function. As we age or face dietary restrictions, B12 deficiency becomes increasingly common, leading to fatigue, cognitive decline, and metabolic issues. Our pharmaceutical-grade B12 injections bypass digestive limitations, delivering this vital nutrient directly into your system for maximum absorption and immediate benefits. Unlike oral supplements that lose potency during digestion, injectable B12 provides 100% bioavailability, ensuring your body receives the full therapeutic dose. Regular B12 supplementation supports healthy energy levels, mental clarity, cardiovascular health, and overall vitality.",
            'how_it_works'                  => 'Vitamin B12 (Methylcobalamin) is the active, bioavailable form of B12 that requires no conversion in the body. It serves as a crucial cofactor in cellular metabolism, particularly in the conversion of homocysteine to methionine and the metabolism of fatty acids. B12 is essential for myelin synthesis, protecting nerve cells and ensuring proper neurological function. It also plays a vital role in red blood cell formation in bone marrow and DNA replication in all cells. Injectable B12 bypasses the gastrointestinal tract, avoiding absorption issues caused by age, medications, or digestive conditions. The methylcobalamin form is immediately utilized by the body for energy production in mitochondria, supporting ATP synthesis and combating fatigue at the cellular level.',
            'key_ingredients'               => 'Methylcobalamin (Vitamin B12), Sterile Water for Injection, Preservatives (Benzyl Alcohol)',
            'treatment_duration'            => 'Most patients notice increased energy within 24-48 hours after their first injection. Optimal benefits accumulate over 4-8 weeks with regular administration. Monthly maintenance injections help sustain therapeutic B12 levels and prevent deficiency symptoms from returning.',
            'usage_instructions'            => 'B12 injections are typically administered intramuscularly (IM) into the deltoid or gluteal muscle. Initial therapy may involve weekly injections for 4-6 weeks to build adequate stores, followed by monthly maintenance injections. Dosing is customized based on your B12 levels, symptoms, and health goals. Our medical team will provide detailed injection instructions and ongoing support throughout your treatment.',
            'research_description'          => null,
            'clinical_research_description' => null,
            'is_featured'                   => true,
            'is_published'                  => true,
            'completion_status'             => 'complete',
            'completion_percentage'         => 100,
            'completion_step'               => 5,
        ]);

        $this->upsertCoverImage($product, '/images/B12.png');

        $this->syncBenefits($product, [
            'Increased Energy and Reduced Fatigue',
            'Enhanced Metabolism and Weight Management',
            'Improved Cognitive Function and Mental Clarity',
            'Nervous System Health and Protection',
            'Red Blood Cell Formation',
            'Cardiovascular Health Support',
            'Mood Enhancement and Stress Reduction',
            'DNA Synthesis and Cellular Health',
            'Superior Bioavailability via Injection',
            'Safe and Well-Tolerated',
        ]);

        $this->syncB12Pricing($product);

        $this->syncResearchLinks($product, [
            [
                'title'            => 'Vitamin B12 Deficiency: Recognition and Management',
                'authors'          => 'Langan RC, Goodbred AJ',
                'journal'          => 'American Family Physician',
                'publication_year' => 2017,
                'pubmed_id'        => '28671426',
                'article_url'      => 'https://pubmed.ncbi.nlm.nih.gov/28671426/',
            ],
            [
                'title'            => 'The Role of Vitamin B12 in Energy Metabolism',
                'authors'          => 'Kennedy DO',
                'journal'          => 'Nutrients',
                'publication_year' => 2016,
                'pubmed_id'        => '27338459',
                'doi'              => '10.3390/nu8070394',
                'article_url'      => 'https://pubmed.ncbi.nlm.nih.gov/27338459/',
            ],
            [
                'title'            => 'Methylcobalamin: A Potential Vitamin for Chronic Pain',
                'authors'          => 'Zhang Y, Sun C, Zhang X',
                'journal'          => 'Neural Regeneration Research',
                'publication_year' => 2013,
                'article_url'      => 'https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4146230/',
            ],
            [
                'title'            => 'Vitamin B12 and Cognition in Older Adults',
                'authors'          => 'Moore K, Hughes CF, Ward M',
                'journal'          => 'Mayo Clinic Proceedings',
                'publication_year' => 2021,
                'pubmed_id'        => '33840514',
                'doi'              => '10.1016/j.mayocp.2020.12.032',
                'article_url'      => 'https://pubmed.ncbi.nlm.nih.gov/33840514/',
            ],
            [
                'title'            => 'Intramuscular vs Oral Vitamin B12: A Bioavailability Study',
                'authors'          => 'Andrès E, Serraj K, Zhu J',
                'journal'          => 'BMJ Case Reports',
                'publication_year' => 2013,
                'doi'              => '10.1136/bcr-2012-007755',
                'article_url'      => 'https://casereports.bmj.com/content/2013/bcr-2012-007755',
            ],
        ]);

        $this->syncFaqs($product, [
            ['What is Vitamin B12?', 'Vitamin B12 (Cobalamin) is an essential water-soluble vitamin crucial for red blood cell formation, DNA synthesis, and nervous system function. Our body cannot produce B12, so it must be obtained through diet or supplementation. Methylcobalamin is the active, bioavailable form used in our injections.'],
            ['What are the benefits of B12 injections?', 'B12 injections provide numerous benefits including increased energy levels, enhanced metabolism, improved cognitive function, better mood regulation, support for cardiovascular health, maintenance of healthy nervous system, red blood cell production, and DNA synthesis. Injectable B12 offers superior bioavailability compared to oral supplements.'],
            ['Who should consider B12 supplementation?', 'B12 supplementation may benefit individuals with deficiency symptoms (fatigue, weakness, memory problems), vegetarians and vegans (plant-based diets lack B12), older adults (absorption decreases with age), people with digestive disorders affecting absorption, those taking certain medications (metformin, PPIs), pregnant or nursing women, and anyone seeking increased energy and metabolic support.'],
            ['What is the difference between Methylcobalamin and Cyanocobalamin?', 'Methylcobalamin is the active, natural form of B12 that is immediately usable by the body without conversion. Cyanocobalamin is synthetic and must be converted by the liver. Methylcobalamin offers superior bioavailability, better retention in tissues, more direct neurological benefits, and does not require metabolic conversion.'],
            ['How is B12 administered?', 'Our B12 therapy is administered via subcutaneous injection, typically in the stomach area around the naval, upper arm or soft tissue area of thigh. This delivery method bypasses the digestive system, ensuring 100% bioavailability and rapid absorption directly into the bloodstream.'],
            ['How often should I receive B12 injections?', 'Frequency depends on individual needs and deficiency levels. Common protocols include weekly injections initially to build adequate stores, transitioning to bi-weekly or monthly maintenance doses. Our medical team will determine the optimal schedule based on your health assessment, blood work results, and response to treatment.'],
            ['When will I feel the effects of B12?', 'Many patients report increased energy within 24-48 hours after their first injection. Some notice immediate improvements in mood and mental clarity. Full benefits for metabolism, cognitive function, and overall wellbeing typically develop over 2-4 weeks with consistent treatment as your body replenishes B12 stores.'],
            ['Are B12 injections safe?', 'B12 injections are extremely safe with virtually no risk of toxicity, even at high doses, because it is water-soluble and excess is excreted. Side effects are rare and typically minor, such as mild discomfort at injection site or temporary flushing. All treatments are administered by licensed healthcare professionals following strict medical protocols.'],
            ['Can I get too much B12?', 'B12 is water-soluble, making toxicity extremely rare. The body absorbs what it needs and excretes excess through urine. There is no established upper limit for B12 because adverse effects from high doses are virtually non-existent. Our medical team ensures appropriate dosing based on your individual needs.'],
            ['What are signs of B12 deficiency?', 'Common signs include persistent fatigue and weakness, memory problems or brain fog, mood changes or depression, pale or jaundiced skin, shortness of breath, tingling or numbness in hands/feet, difficulty walking or balance problems, glossitis (inflamed tongue), and unexplained weight loss. If experiencing these symptoms, consult with our medical team for evaluation.'],
            ['Why choose injections over oral B12?', 'Injections offer superior advantages: 100% bioavailability vs. 1-10% for oral supplements, bypass digestive absorption issues, immediate availability to cells, more predictable and sustained results, ideal for those with absorption problems, and guaranteed therapeutic levels. Injectable B12 ensures optimal benefits regardless of digestive health or genetic factors.'],
            ['Is B12 therapy covered by insurance?', 'No, none of our treatments are covered by insurance. Many patients find our transparent pricing makes B12 therapy affordable even as an out-of-pocket investment in their health and wellness.'],
        ]);
    }

    // ─────────────────────────────────────────────
    // GLUTATHIONE
    // ─────────────────────────────────────────────

    private function seedGlutathione(array $categories): void
    {
        /** @var Product $product */
        $product = Product::updateOrCreate(['slug' => 'glutathione'], [
            'name'                          => 'Glutathione',
            'category_id'                   => $categories['wellness'],
            'description'                   => 'Boost Your Immunity, Improve Energy, Tone and Detox. Plays a prime role in protecting the body and every cell.',
            'about_treatment'               => "Glutathione is manually found in every cell in the human body and is your body's master antioxidant. Plays a prime role in protecting the body against oxidative stress and supporting energy production AND detoxification. Glutathione supports energy production, detoxification, and overall cellular health. NAD+ is also crucial for cellular health, working together with glutathione. Our therapy bypasses limitations of oral supplementation through advanced delivery methods via subcutaneous injection for maximum bioavailability and effectiveness.",
            'how_it_works'                  => "Glutathione works at the cellular level as your body's most powerful antioxidant, found in every cell. It neutralizes free radicals, supports detoxification pathways in the liver and cells, and helps protect against oxidative stress. Our therapy uses advanced delivery methods (Subcutaneous) that bypass the limitations of oral supplementation, which has reduced absorption. This ensures maximum bioavailability and cellular protection throughout your body.",
            'key_ingredients'               => 'Glutathione (reduced form - master antioxidant), Supporting cofactors for optimal absorption, Bioavailable formulation',
            'treatment_duration'            => 'Benefits may be noticed within 2-4 weeks of consistent treatment. Optimal results typically seen over 2-3 months of regular therapy. Long-term cellular benefits continue to build.',
            'usage_instructions'            => 'Available in Subcutaneous injection for maximum bioavailability and effectiveness. Treatment frequency and method personalized based on your needs, goals, and lifestyle preferences.',
            'research_description'          => null,
            'clinical_research_description' => null,
            'is_featured'                   => true,
            'is_published'                  => true,
            'completion_status'             => 'complete',
            'completion_percentage'         => 100,
            'completion_step'               => 5,
        ]);

        $this->upsertCoverImage($product, '/images/Glutathione.png');

        $this->syncBenefits($product, [
            'Boost Immunity - Strengthen immune system function and response',
            'Improve Energy - Enhance cellular energy production',
            'Support Detoxification - Aid in removing toxins at cellular level',
            'Powerful Antioxidant - Master antioxidant protecting every cell',
            'Cellular Health - Support overall cellular function and protection',
            'Anti-Aging Benefits - Promote healthy aging and longevity',
            'Enhanced Bioavailability - Superior absorption vs oral supplements',
        ]);

        $this->syncSubscriptionPricing($product, basePrice: 149.00);

        $this->syncResearchLinks($product, [
            [
                'title'            => 'Glutathione: Master Antioxidant and Cellular Defense',
                'authors'          => 'Various Authors',
                'journal'          => 'ScienceDirect',
                'publication_year' => 2023,
                'article_url'      => 'https://www.sciencedirect.com/science/article/pii/S0DMS-24423/18300378-8#61646',
            ],
            [
                'title'            => 'NIH PubMed: Glutathione Biology and Clinical Applications',
                'authors'          => 'Various Authors',
                'journal'          => 'NIH Gov/Articles',
                'publication_year' => 2023,
                'pubmed_id'        => 'Pmc 9173531',
                'article_url'      => 'https://pmc.ncbi.nlm.nih.gov/articles/pmc9173531',
            ],
            [
                'title'            => 'WWW Research: Glutathione as Antioxidant Therapy',
                'authors'          => 'Various Authors',
                'journal'          => 'ResearchGate/Net',
                'publication_year' => 2023,
                'article_url'      => 'https://www.researchgate.net/post/about-h-glutathione',
            ],
            [
                'title'            => 'E15 Med ORG: Clinical Applications of Glutathione',
                'authors'          => 'Various Authors',
                'journal'          => 'E15 Med ORG/Indexed',
                'publication_year' => 2023,
                'article_url'      => 'https://e15med.org/indexed/article/net2/web',
            ],
        ]);

        $this->syncFaqs($product, [
            ['What is Glutathione?', 'Glutathione is a powerful tripeptide antioxidant composed of three amino acids: glutamine, cysteine, and glycine. It is naturally produced by the body and is found in every cell. Glutathione is often called the "master antioxidant" because it neutralizes free radicals, supports immune function, detoxifies harmful substances, and protects cellular health.'],
            ['What are the benefits of Glutathione injections?', 'Glutathione injections provide antioxidant protection against cellular damage, enhance immune system function, support liver detoxification, promote skin health and brightening, reduce inflammation, improve energy levels, protect against oxidative stress, support healthy aging, and aid in recovery from illness or intense physical activity.'],
            ['Why injectable Glutathione instead of oral supplements?', 'Injectable Glutathione bypasses the digestive system, providing superior bioavailability compared to oral supplements which are broken down by stomach acid and digestive enzymes. Injections deliver Glutathione directly into the bloodstream, ensuring maximum absorption and therapeutic effectiveness. This is particularly important since oral Glutathione has very poor absorption rates.'],
            ['How does Glutathione support detoxification?', 'Glutathione is the primary molecule used by the liver to neutralize and eliminate toxins, heavy metals, chemicals, and metabolic waste products. It binds to these harmful substances, making them water-soluble so they can be safely excreted through urine or bile. This process is essential for maintaining cellular health and preventing toxic accumulation.'],
            ['Can Glutathione help with skin brightening?', 'Yes, Glutathione has skin-brightening properties. It inhibits melanin production by interfering with tyrosinase enzyme activity, which can lead to a more even skin tone and reduction in hyperpigmentation. Many patients report improved skin clarity, radiance, and reduced appearance of dark spots over time with consistent treatment.'],
            ['How often should I receive Glutathione injections?', 'Treatment frequency varies based on individual goals and health status. Common protocols include 1-2 injections per week initially for intensive support, transitioning to weekly or bi-weekly maintenance doses. Our medical team will create a personalized treatment schedule based on your specific needs and desired outcomes.'],
            ['Are there any side effects of Glutathione?', 'Glutathione injections are generally very safe and well-tolerated. Some patients may experience mild injection site reactions, temporary flushing, or minor gastrointestinal discomfort. Serious side effects are rare. As a naturally occurring substance in the body, Glutathione has an excellent safety profile when administered properly.'],
            ['Do I need a prescription for Glutathione injections?', 'Yes, injectable Glutathione requires a prescription from a licensed healthcare provider. Our telehealth team will evaluate your health history, current medications, and treatment goals to determine if Glutathione therapy is appropriate and safe for you.'],
            ['How long does it take to see results from Glutathione?', 'Results vary depending on the intended benefit. Some patients notice increased energy and improved well-being within 1-2 weeks. Skin brightening effects typically become visible after 4-8 weeks of consistent treatment. Detoxification and immune support benefits build gradually over several weeks to months of regular use.'],
            ['Can I take Glutathione with other medications or supplements?', 'Glutathione is generally safe to combine with most medications and supplements. However, it is important to disclose all current medications, supplements, and health conditions during your telehealth consultation. Our providers will review potential interactions and ensure Glutathione is safe for your specific situation.'],
            ['Who should consider Glutathione therapy?', 'Glutathione therapy may benefit individuals seeking antioxidant support, those exposed to environmental toxins or pollutants, people with chronic inflammation, those recovering from illness or surgery, individuals with compromised immune function, patients seeking skin health improvement, people experiencing oxidative stress, and anyone interested in healthy aging and cellular protection.'],
            ['How should Glutathione be stored?', 'Store Glutathione in the refrigerator at 36-46°F (2-8°C) to maintain potency. Do not freeze. Protect from light by keeping it in the original packaging. Once reconstituted (if applicable), use within the timeframe specified by your pharmacist. Always inspect the solution before use - it should be clear and free of particles.'],
        ]);
    }

    // ─────────────────────────────────────────────
    // NAD+
    // ─────────────────────────────────────────────

    private function seedNad(array $categories): void
    {
        /** @var Product $product */
        $product = Product::updateOrCreate(['slug' => 'nad-therapy'], [
            'name'                          => 'NAD+ Therapy',
            'category_id'                   => $categories['longevity'],
            'description'                   => 'Aging Repair Your Own Boost. NAD+ (Nicotinamide Adenine Dinucleotide) is naturally found in every cell and diminishes with age.',
            'about_treatment'               => "NAD+ is an acronym for Nicotinamide Adenine Dinucleotide - a vital coenzyme naturally found in every cell in the human body. Diminishing levels of NAD+ may assist in hyperness (aging decline). Our NAD+ therapy helps replenish these critical levels through advanced delivery methods that bypass oral limitations. Available via Subcutaneous injection for optimal cellular absorption. Support energy production, mental clarity, and anti-aging at the cellular level.",
            'how_it_works'                  => "NAD+ is essential for mitochondrial function and energy production in every cell of your body. It activates sirtuins - proteins that regulate cellular health, DNA repair, and longevity. Diminishing levels occur naturally with age, affecting energy, cognition, and cellular repair. Our therapy restores NAD+ levels through one of the best and effective delivery methods (Subcutaneous injection) that bypass traditional oral limitations and maximize absorption. This supports optimal cellular function, metabolic processes, and healthy aging throughout your entire body.",
            'key_ingredients'               => 'NAD+ (Nicotinamide Adenine Dinucleotide), Supporting vitamins and cofactors, Bioavailable formulation',
            'treatment_duration'            => 'Energy effects may be felt during or immediately after treatment. Long-term cellular benefits and anti-aging effects develop over 4-8 weeks of consistent therapy. Many patients report sustained improvements in energy and mental clarity.',
            'usage_instructions'            => 'Available in the most effective delivery form via Subcutaneous injection for high bioavailability. Treatment frequency personalized to your individual needs, goals, and lifestyle. Our medical team will provide your best option based on your telehealth information provided.',
            'research_description'          => null,
            'clinical_research_description' => null,
            'is_featured'                   => true,
            'is_published'                  => true,
            'completion_status'             => 'complete',
            'completion_percentage'         => 100,
            'completion_step'               => 5,
        ]);

        $this->upsertCoverImage($product, '/images/NAD +.png');

        $this->syncBenefits($product, [
            'Naturally Found in Every Cell - Essential coenzyme for cellular function',
            'Increase Energy Production - Boost cellular energy and metabolism',
            'Slow Aging Process - Support healthy aging and longevity',
            'Enhance Mental Clarity - Improve cognitive function and focus',
            'Support Cellular Health - Optimize cellular repair and function',
            'Improve Memory and Focus - Better cognitive performance',
            'Multiple Delivery Methods - Oral, Nasal, IV, Subcutaneous options',
            'Bypass Oral Limitations - Superior absorption for better results',
        ]);

        $this->syncSubscriptionPricing($product, basePrice: 199.00);

        $this->syncResearchLinks($product, [
            [
                'title'            => 'NAD+ Metabolism and Cellular Function',
                'authors'          => 'Various Authors',
                'journal'          => 'OnlineLibrary Wiley',
                'publication_year' => 2023,
                'article_url'      => 'https://onlinelibrary.wiley.com/doi/journal/10.1111/nep.7043',
            ],
            [
                'title'            => 'Nicotinamide Adenine Dinucleotide and Aging',
                'authors'          => 'Various Authors',
                'journal'          => 'ScienceDirect',
                'publication_year' => 2023,
                'article_url'      => 'https://www.sciencedirect.com/science/article/pii/s0000-06291240180',
            ],
            [
                'title'            => 'NAD+ Supplementation for Longevity and Health',
                'authors'          => 'Various Authors',
                'journal'          => 'PubMed NCBI',
                'publication_year' => 2023,
                'pubmed_id'        => 'Pmc 9512338',
                'article_url'      => 'https://pmc.ncbi.nlm.nih.gov/articles/pmc9512338',
            ],
            [
                'title'            => 'PLoS ONE: NAD+ Biology and Therapeutic Applications',
                'authors'          => 'Various Authors',
                'journal'          => 'JOURNALS.PLOS.ORG',
                'publication_year' => 2023,
                'article_url'      => 'https://journals.plos.org/plosone/article?id=10.1371/journal.pone.2014237#53',
            ],
        ]);

        $this->syncFaqs($product, [
            ['What is NAD+?', 'NAD+ (Nicotinamide Adenine Dinucleotide) is a critical coenzyme found in every cell of your body. It plays an essential role in cellular energy production, DNA repair, gene expression, and metabolic function. NAD+ levels naturally decline with age, contributing to fatigue, cognitive decline, and various age-related conditions.'],
            ['What are the benefits of NAD+ therapy?', 'NAD+ therapy offers numerous benefits including increased cellular energy and ATP production, enhanced mental clarity and focus, improved metabolism and weight management, better sleep quality, reduced inflammation, DNA repair and cellular rejuvenation, support for healthy aging and longevity, improved athletic performance and recovery, and neuroprotective effects.'],
            ['How is NAD+ administered?', 'NAD+ can be administered through several methods: intravenous (IV) infusion for maximum bioavailability, intramuscular injection for convenience, subcutaneous injection, nasal spray for ease of use, or oral supplements. Our medical team will recommend the best administration method based on your goals, lifestyle, and treatment response.'],
            ['How does NAD+ support longevity and anti-aging?', 'NAD+ activates sirtuins, a family of proteins that regulate cellular health, DNA repair, and stress resistance. It also supports mitochondrial function, which is crucial for energy production and cellular vitality. By restoring NAD+ levels, therapy may slow cellular aging processes, improve tissue repair, enhance metabolic health, and support overall longevity.'],
            ['How often should I receive NAD+ therapy?', 'Treatment frequency depends on individual goals and health status. Common protocols include weekly IV infusions for intensive therapy, bi-weekly maintenance treatments, or daily nasal/oral supplementation. Initial loading phases may involve more frequent treatments, transitioning to maintenance schedules. Your provider will customize a protocol for your specific needs.'],
            ['When will I feel the effects of NAD+ therapy?', 'Many patients report increased energy and mental clarity within hours to days after IV or injection therapy. Cumulative benefits for metabolism, sleep, and overall vitality typically build over 2-4 weeks of consistent treatment. Long-term cellular and anti-aging benefits continue to develop over months of regular therapy.'],
            ['Are there any side effects of NAD+ therapy?', 'NAD+ therapy is generally well-tolerated. Some patients may experience mild flushing, nausea, cramping, or anxiety during IV infusion, which typically resolves by slowing the infusion rate. Injection site reactions may occur with intramuscular administration. Serious side effects are rare. Starting with lower doses and gradually increasing helps minimize discomfort.'],
            ['Do I need a prescription for NAD+ therapy?', 'Yes, NAD+ therapy requires a prescription from a licensed healthcare provider. Our telehealth team will evaluate your health status, treatment goals, and medical history to determine if NAD+ therapy is appropriate and to create a personalized treatment plan.'],
            ['Can NAD+ help with fatigue and low energy?', 'Yes, NAD+ is essential for mitochondrial function and ATP (cellular energy) production. Many patients with chronic fatigue, low energy, or brain fog experience significant improvement with NAD+ therapy. By supporting cellular energy metabolism, NAD+ helps restore vitality, mental clarity, and physical stamina.'],
            ['How does NAD+ support brain health and cognition?', 'NAD+ supports neuronal energy production, protects against oxidative stress, promotes neurotransmitter synthesis, and facilitates DNA repair in brain cells. It activates neuroprotective pathways and supports healthy brain aging. Clinical research suggests NAD+ therapy may improve memory, focus, mental clarity, and overall cognitive function.'],
            ['Who is a good candidate for NAD+ therapy?', 'Good candidates include individuals experiencing age-related decline in energy or cognition, those seeking anti-aging and longevity support, athletes looking for enhanced performance and recovery, people with chronic fatigue or metabolic concerns, individuals recovering from substance use, patients with neurodegenerative risk factors, and anyone interested in optimizing cellular health and vitality.'],
            ['How should NAD+ be stored?', 'Store NAD+ products in the refrigerator at 36-46°F (2-8°C) to maintain stability and potency. Protect from light and heat. Do not freeze. For IV formulations, follow specific reconstitution and storage instructions provided by your pharmacist. Always inspect the solution before use - it should be clear or slightly yellow without particles.'],
        ]);
    }

    // ─────────────────────────────────────────────
    // SHARED HELPERS
    // ─────────────────────────────────────────────

    /**
     * Create or update the cover image record and wire it to the product.
     */
    private function upsertCoverImage(Product $product, string $imageUrl): void
    {
        $image = ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'slot_position' => 1],
            [
                'image_url'  => $imageUrl,
                'image_type' => 'cover',
                'sort_order' => 1,
            ]
        );

        $product->cover_image_id = $image->id;
        $product->save();
    }

    /**
     * Wipe and re-seed all benefits for a product in sort order.
     */
    private function syncBenefits(Product $product, array $benefits): void
    {
        $product->benefits()->delete();

        foreach ($benefits as $index => $text) {
            ProductBenefit::create([
                'product_id'   => $product->id,
                'benefit_text' => $text,
                'sort_order'   => $index + 1,
            ]);
        }
    }

    /**
     * Wipe and re-seed all FAQs for a product via the morph relationship.
     * scope_type = App\Models\Product, scope_id = product UUID.
     */
    private function syncFaqs(Product $product, array $faqs): void
    {
        Faq::where('scope_type', Product::class)
            ->where('scope_id', $product->id)
            ->delete();

        foreach ($faqs as $index => [$question, $answer]) {
            Faq::create([
                'scope_type' => Product::class,
                'scope_id'   => $product->id,
                'question'   => $question,
                'answer'     => $answer,
                'sort_order' => $index + 1,
                'is_active'  => true,
            ]);
        }
    }

    /**
     * Wipe and re-seed all research links for a product.
     */
    private function syncResearchLinks(Product $product, array $links): void
    {
        $product->researchLinks()->delete();

        foreach ($links as $index => $link) {
            ProductResearchLink::create(array_merge([
                'product_id'  => $product->id,
                'pubmed_id'   => null,
                'doi'         => null,
                'description' => null,
                'sort_order'  => $index + 1,
            ], $link));
        }
    }

    /**
     * Build the standard subscription pricing grid used by weight-loss and
     * most wellness / longevity products.
     *
     * Discount tiers mirror the original CmsSubscriptionDiscount records:
     *   1-month  → 10 %
     *   2-month  → 12 %
     *   3-month  → 15 %
     *
     * Weight-loss products also receive an optional micro-dose group
     * and an optional one-time sample group.
     */
    private function syncSubscriptionPricing(
        Product $product,
        float   $basePrice,
        ?float  $microDosePrice = null,
        ?float  $samplePrice    = null,
    ): void {
        // Purge all existing pricing for this product
        $product->pricing()->each(fn ($p) => $p->options()->delete());
        $product->pricing()->delete();

        // ── Standard subscription block ──────────────────────────────────
        $subGroup = ProductPricing::create([
            'product_id'   => $product->id,
            'pricing_type' => 'subscription',
            'title'        => 'Subscription Plans',
            'description'  => 'Choose your delivery frequency and save.',
            'is_active'    => true,
        ]);

        foreach ([
            [1, '1-Month Supply',  10.00, true],
            [2, '2-Month Supply',  12.00, false],
            [3, '3-Month Supply',  15.00, false],
        ] as $order => [$months, $label, $discount, $default]) {
            PricingOption::create([
                'pricing_id'       => $subGroup->id,
                'billing_interval' => 'month',
                'interval_count'   => $months,
                'label'            => $label,
                'price'            => $basePrice,
                'discount_percent' => $discount,
                'final_price'      => round($basePrice * (1 - $discount / 100), 2),
                'sort_order'       => $order + 1,
                'is_default'       => $default,
                'metadata'         => ['supply_duration' => "{$months} month" . ($months > 1 ? 's' : '')],
            ]);
        }

        // ── Micro-dose block (Semaglutide / Tirzepatide only) ────────────
        if ($microDosePrice !== null) {
            $microGroup = ProductPricing::create([
                'product_id'   => $product->id,
                'pricing_type' => 'subscription',
                'title'        => 'Micro-Dose Plans',
                'description'  => 'Lower-dose option for gradual titration.',
                'is_active'    => true,
            ]);

            foreach ([
                [1, '1-Month Micro-Dose', 10.00, true],
                [2, '2-Month Micro-Dose', 12.00, false],
                [3, '3-Month Micro-Dose', 15.00, false],
            ] as $order => [$months, $label, $discount, $default]) {
                PricingOption::create([
                    'pricing_id'       => $microGroup->id,
                    'billing_interval' => 'month',
                    'interval_count'   => $months,
                    'label'            => $label,
                    'price'            => $microDosePrice,
                    'discount_percent' => $discount,
                    'final_price'      => round($microDosePrice * (1 - $discount / 100), 2),
                    'sort_order'       => $order + 1,
                    'is_default'       => $default,
                    'metadata'         => [
                        'supply_duration' => "{$months} month" . ($months > 1 ? 's' : ''),
                        'dose_type'       => 'micro',
                    ],
                ]);
            }
        }

        // ── Sample / starter one-time block ─────────────────────────────
        if ($samplePrice !== null) {
            $sampleGroup = ProductPricing::create([
                'product_id'   => $product->id,
                'pricing_type' => 'one_time',
                'title'        => 'Sample / Starter',
                'description'  => 'One-time starter kit — try before subscribing.',
                'is_active'    => true,
            ]);

            PricingOption::create([
                'pricing_id'       => $sampleGroup->id,
                'billing_interval' => 'one_time',
                'interval_count'   => 1,
                'label'            => 'Sample / Starter Kit',
                'price'            => $samplePrice,
                'discount_percent' => 0.00,
                'final_price'      => $samplePrice,
                'sort_order'       => 1,
                'is_default'       => false,
                'metadata'         => ['supply_duration' => 'sample', 'dose_type' => 'sample'],
            ]);
        }
    }

    /**
     * B12 uses three named, explicitly-described plans rather than the generic
     * subscription grid, so it gets its own pricing builder.
     */
    private function syncB12Pricing(Product $product): void
    {
        $product->pricing()->each(fn ($p) => $p->options()->delete());
        $product->pricing()->delete();

        // ── Plan 1 : Single Injection (one-time, $35) ────────────────────
        $single = ProductPricing::create([
            'product_id'   => $product->id,
            'pricing_type' => 'one_time',
            'title'        => 'Single Injection',
            'description'  => 'Perfect for first-time users or occasional energy boost',
            'is_active'    => true,
        ]);

        PricingOption::create([
            'pricing_id'       => $single->id,
            'billing_interval' => 'one_time',
            'interval_count'   => 1,
            'label'            => 'Single Injection',
            'price'            => 35.00,
            'discount_percent' => 0.00,
            'final_price'      => 35.00,
            'sort_order'       => 1,
            'is_default'       => false,
            'metadata'         => [
                'supply_duration' => '1 injection',
                'is_popular'      => false,
                'features'        => [
                    '1 B12 (Methylcobalamin) injection',
                    'Medical consultation included',
                    'Self-administration instructions',
                    'Immediate energy benefits',
                    'No commitment required',
                ],
            ],
        ]);

        // ── Plan 2 : 1-Month Supply (monthly, $85, 4 injections) ─────────
        $monthly = ProductPricing::create([
            'product_id'   => $product->id,
            'pricing_type' => 'subscription',
            'title'        => '1-Month Supply',
            'description'  => 'Ideal for maintaining optimal B12 levels',
            'is_active'    => true,
        ]);

        PricingOption::create([
            'pricing_id'       => $monthly->id,
            'billing_interval' => 'month',
            'interval_count'   => 1,
            'label'            => '1-Month Supply (4 injections)',
            'price'            => 85.00,
            'discount_percent' => 0.00,
            'final_price'      => 85.00,
            'sort_order'       => 1,
            'is_default'       => true,
            'metadata'         => [
                'supply_duration' => '4 injections',
                'is_popular'      => true,
                'features'        => [
                    '4 B12 injections (1 per week)',
                    'Build healthy B12 stores',
                    'Sustained energy support',
                    'Medical oversight included',
                    'Flexible delivery schedule',
                ],
            ],
        ]);

        // ── Plan 3 : 3-Month Wellness Plan (quarterly, $225) ─────────────
        $quarterly = ProductPricing::create([
            'product_id'   => $product->id,
            'pricing_type' => 'subscription',
            'title'        => '3-Month Wellness Plan',
            'description'  => 'Best value for long-term health optimization',
            'is_active'    => true,
        ]);

        PricingOption::create([
            'pricing_id'       => $quarterly->id,
            'billing_interval' => 'month',
            'interval_count'   => 3,
            'label'            => '3-Month Wellness Plan (12 injections)',
            'price'            => 255.00,   // 3 × $85 un-discounted
            'discount_percent' => 11.76,    // saves exactly $30 → $225
            'final_price'      => 225.00,
            'sort_order'       => 1,
            'is_default'       => false,
            'metadata'         => [
                'supply_duration' => '12 injections',
                'is_popular'      => false,
                'features'        => [
                    '12 B12 injections (1 per week)',
                    'Save $30 vs monthly plan',
                    'Comprehensive wellness support',
                    'Priority medical support',
                    'Convenient quarterly delivery',
                    'Maximum therapeutic benefit',
                ],
            ],
        ]);
    }
}