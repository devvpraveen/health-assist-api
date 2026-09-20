<?php

namespace Database\Seeders;

use App\Models\SeoEntity;
use App\Models\SeoFaq;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeoContentSeeder extends Seeder
{
    /**
     * Seed platform SEO entities (en + hi stubs) for AEO/hreflang demos.
     * Citations intentionally empty — do not invent medical sources.
     */
    public function run(): void
    {
        $reviewStamp = now();
        $reviewerId = User::query()->where('email', 'admin@healthassist.test')->value('id');

        $backPainEn = $this->upsertEntity([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'back-pain',
            'locale' => 'en',
            'title' => 'Back pain',
            'summary' => 'Educational overview of common back pain patterns and when to seek care.',
            'body' => "Back pain is a common reason people seek physiotherapy or medical evaluation. It can relate to posture, strain, degenerative changes, or other causes that only a clinician can assess.\n\nThis page is general education from Health Assist. It is not a diagnosis.",
            'direct_answer' => 'Back pain is discomfort in the spine region that often improves with activity modification and guided care, but sudden severe pain, weakness, or bladder/bowel changes need urgent clinical evaluation.',
            'structured_facts' => [
                'Common contributors include muscle strain, prolonged sitting, and overuse.',
                'Self-care may include relative rest, gentle movement, and posture breaks — not prolonged bed rest for most people.',
                'Seek urgent care for trauma, progressive weakness, numbness in the saddle area, or loss of bladder/bowel control.',
                'A licensed clinician can assess red flags and recommend imaging or referral when needed.',
            ],
            'seo_title' => 'Back pain — education | Health Assist',
            'seo_description' => 'Learn general facts about back pain from Health Assist. Not a diagnosis — talk to a clinician for personal advice.',
            'canonical_path' => '/en/conditions/back-pain',
            'schema_type' => 'MedicalCondition',
        ], $reviewStamp, $reviewerId);

        $backPainHi = $this->upsertEntity([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'back-pain',
            'locale' => 'hi',
            'title' => 'पीठ दर्द',
            'summary' => 'पीठ दर्द के सामान्य पैटर्न और कब देखभाल लें — शैक्षिक जानकारी।',
            'body' => "पीठ दर्द कई लोगों को फिजियोथेरेपी या चिकित्सकीय मूल्यांकन के लिए लाता है। यह मुद्रा, तनाव या अन्य कारणों से जुड़ा हो सकता है — केवल चिकित्सक सही आकलन कर सकते हैं।\n\nयह Health Assist की सामान्य शिक्षा है। यह निदान नहीं है।",
            'direct_answer' => 'पीठ दर्द रीढ़ के क्षेत्र में असुविधा है; कई मामलों में गतिविधि में बदलाव और मार्गदर्शित देखभाल से सुधार होता है, पर अचानक तेज दर्द, कमजोरी या मूत्राशय/आंत्र परिवर्तन पर तुरंत चिकित्सकीय मदद लें।',
            'structured_facts' => [
                'आम योगदान: मांसपेशी तनाव, लंबे बैठना, अधिक उपयोग।',
                'ज्यादातर लोगों के लिए लंबे बिस्तर आराम की सलाह नहीं दी जाती।',
                'चोट, बढ़ती कमजोरी, या मूत्राशय/आंत्र नियंत्रण खोने पर तुरंत देखभाल लें।',
            ],
            'seo_title' => 'पीठ दर्द — शिक्षा | Health Assist',
            'seo_description' => 'Health Assist से पीठ दर्द की सामान्य जानकारी। यह निदान नहीं है।',
            'canonical_path' => '/hi/conditions/back-pain',
            'schema_type' => 'MedicalCondition',
        ], $reviewStamp, $reviewerId);

        $kneePainEn = $this->upsertEntity([
            'type' => SeoEntity::TYPE_SYMPTOM,
            'slug' => 'knee-pain',
            'locale' => 'en',
            'title' => 'Knee pain',
            'summary' => 'Educational overview of knee pain as a symptom and when evaluation helps.',
            'body' => "Knee pain can follow activity, injury, or gradual wear. Swelling, locking, instability, or inability to bear weight deserve prompt clinical assessment.\n\nHealth Assist provides education only — not a diagnosis.",
            'direct_answer' => 'Knee pain is discomfort in or around the knee joint; many mild cases settle with load management, but locking, giving-way, fever, or sudden swelling after injury warrant clinician review.',
            'structured_facts' => [
                'Pain may relate to soft tissue, cartilage, or joint surfaces — only exam can narrow causes.',
                'Ice, elevation, and activity pacing are common short-term comfort measures, not cures.',
                'Persistent pain lasting weeks, night pain, or deformity needs professional evaluation.',
            ],
            'seo_title' => 'Knee pain — education | Health Assist',
            'seo_description' => 'General education on knee pain from Health Assist. Not a diagnosis.',
            'canonical_path' => '/en/symptoms/knee-pain',
            'schema_type' => 'MedicalSymptom',
        ], $reviewStamp, $reviewerId);

        $kneePainHi = $this->upsertEntity([
            'type' => SeoEntity::TYPE_SYMPTOM,
            'slug' => 'knee-pain',
            'locale' => 'hi',
            'title' => 'घुटने का दर्द',
            'summary' => 'घुटने के दर्द के लक्षण की शैक्षिक जानकारी।',
            'body' => "घुटने का दर्द गतिविधि, चोट या क्रमिक घिसाव के बाद हो सकता है। सूजन, लॉक होना या वजन न सह पाना तुरंत मूल्यांकन के संकेत हो सकते हैं।\n\nHealth Assist केवल शिक्षा देता है — निदान नहीं।",
            'direct_answer' => 'घुटने का दर्द जोड़ के आसपास असुविधा है; हल्के मामलों में भार प्रबंधन से राहत हो सकती है, पर लॉक होना, अस्थिरता या चोट के बाद अचानक सूजन पर चिकित्सक से मिलें।',
            'structured_facts' => [
                'केवल चिकित्सकीय जांच कारण स्पष्ट कर सकती है।',
                'लगातार दर्द या रात का दर्द पेशेवर मूल्यांकन का संकेत हो सकता है।',
            ],
            'seo_title' => 'घुटने का दर्द — शिक्षा | Health Assist',
            'seo_description' => 'Health Assist से घुटने के दर्द की सामान्य जानकारी। यह निदान नहीं है।',
            'canonical_path' => '/hi/symptoms/knee-pain',
            'schema_type' => 'MedicalSymptom',
        ], $reviewStamp, $reviewerId);

        $physioEn = $this->upsertEntity([
            'type' => SeoEntity::TYPE_SPECIALTY,
            'slug' => 'physiotherapy',
            'locale' => 'en',
            'title' => 'Physiotherapy',
            'summary' => 'How physiotherapy supports movement, recovery, and function — educational overview.',
            'body' => "Physiotherapy focuses on restoring movement and function through assessment, exercise, and education. It may help after injury, surgery, or with persistent musculoskeletal symptoms — under clinician guidance.\n\nThis is not a diagnosis or a guarantee of outcomes.",
            'direct_answer' => 'Physiotherapy is a healthcare specialty that assesses movement and function and uses exercise, education, and hands-on care to support recovery — always individualized by a licensed professional.',
            'structured_facts' => [
                'Sessions typically include assessment, goal setting, and progressive exercise.',
                'Physiotherapists do not replace emergency care for red-flag symptoms.',
                'Ask your clinician whether physiotherapy fits your situation.',
            ],
            'seo_title' => 'Physiotherapy — overview | Health Assist',
            'seo_description' => 'Learn what physiotherapy involves. Educational content from Health Assist.',
            'canonical_path' => '/en/specialties/physiotherapy',
            'schema_type' => 'MedicalSpecialty',
        ], $reviewStamp, $reviewerId);

        $physioHi = $this->upsertEntity([
            'type' => SeoEntity::TYPE_SPECIALTY,
            'slug' => 'physiotherapy',
            'locale' => 'hi',
            'title' => 'फिजियोथेरेपी',
            'summary' => 'फिजियोथेरेपी गति और पुनर्प्राप्ति में कैसे मदद कर सकती है — शैक्षिक अवलोकन।',
            'body' => "फिजियोथेरेपी मूल्यांकन, व्यायाम और शिक्षा के माध्यम से गति और कार्य बहाल करने पर केंद्रित है। यह चिकित्सक मार्गदर्शन में उपयोगी हो सकती है।\n\nयह निदान या परिणाम की गारंटी नहीं है।",
            'direct_answer' => 'फिजियोथेरेपी एक स्वास्थ्य विशेषता है जो गति का आकलन करती है और व्यायाम व शिक्षा से पुनर्प्राप्ति का समर्थन करती है — हमेशा लाइसेंस प्राप्त पेशेवर द्वारा व्यक्तिगत रूप से।',
            'structured_facts' => [
                'सत्रों में आमतौर पर मूल्यांकन और प्रगतिशील व्यायाम शामिल होते हैं।',
                'लाल झंडे वाले लक्षणों के लिए आपातकालीन देखभाल का विकल्प नहीं है।',
            ],
            'seo_title' => 'फिजियोथेरेपी — अवलोकन | Health Assist',
            'seo_description' => 'फिजियोथेरेपी क्या है — Health Assist की शैक्षिक जानकारी।',
            'canonical_path' => '/hi/specialties/physiotherapy',
            'schema_type' => 'MedicalSpecialty',
        ], $reviewStamp, $reviewerId);

        $treatmentEn = $this->upsertEntity([
            'type' => SeoEntity::TYPE_TREATMENT,
            'slug' => 'guided-exercise',
            'locale' => 'en',
            'title' => 'Guided exercise therapy',
            'summary' => 'Educational note on clinician-guided exercise as a common musculoskeletal approach.',
            'body' => "Guided exercise programs are often used in physiotherapy to rebuild strength and control. Programs must be tailored; copying exercises without assessment can be unsafe.\n\nNot a prescription from Health Assist.",
            'direct_answer' => 'Guided exercise therapy uses progressive, clinician-prescribed movements to support recovery and function; it is not one-size-fits-all and is not medical advice from this page.',
            'structured_facts' => [
                'Progression usually starts with pain-tolerant range and builds load gradually.',
                'Stop and seek care if exercise causes sharp new pain, swelling, or neurological symptoms.',
            ],
            'seo_title' => 'Guided exercise therapy | Health Assist',
            'seo_description' => 'Educational overview of guided exercise. Not a treatment prescription.',
            'canonical_path' => '/en/treatments/guided-exercise',
            'schema_type' => 'MedicalTherapy',
        ], $reviewStamp, $reviewerId);

        // Relations (en cluster around back pain)
        $this->relate($backPainEn, $kneePainEn, SeoEntity::RELATION_RELATED);
        $this->relate($backPainEn, $physioEn, SeoEntity::RELATION_SPECIALTY_FOR);
        $this->relate($backPainEn, $treatmentEn, SeoEntity::RELATION_TREATS);
        $this->relate($kneePainEn, $physioEn, SeoEntity::RELATION_SPECIALTY_FOR);
        $this->relate($physioEn, $treatmentEn, SeoEntity::RELATION_RELATED);

        $this->relate($backPainHi, $kneePainHi, SeoEntity::RELATION_RELATED);
        $this->relate($backPainHi, $physioHi, SeoEntity::RELATION_SPECIALTY_FOR);

        $faqs = [
            [
                'entity' => $backPainEn,
                'locale' => 'en',
                'sort_order' => 10,
                'question' => 'Is back pain always serious?',
                'answer' => 'Many episodes are mechanical and improve, but red-flag symptoms (trauma, progressive weakness, saddle numbness, bladder/bowel changes) need urgent clinical care. This is not a diagnosis.',
            ],
            [
                'entity' => $backPainEn,
                'locale' => 'en',
                'sort_order' => 20,
                'question' => 'Should I rest completely if my back hurts?',
                'answer' => 'Prolonged bed rest is usually not recommended for typical back pain; gentle movement within comfort often helps. Ask a clinician what is safe for you.',
            ],
            [
                'entity' => $kneePainEn,
                'locale' => 'en',
                'sort_order' => 10,
                'question' => 'When should I see someone for knee pain?',
                'answer' => 'Seek care after injury with swelling or instability, if you cannot bear weight, or if pain persists for weeks. Health Assist cannot diagnose your knee.',
            ],
            [
                'entity' => $physioEn,
                'locale' => 'en',
                'sort_order' => 10,
                'question' => 'Do I need a referral for physiotherapy?',
                'answer' => 'Referral rules vary by region and insurer. Check local regulations or ask your primary clinician or clinic.',
            ],
            [
                'entity' => $backPainHi,
                'locale' => 'hi',
                'sort_order' => 10,
                'question' => 'क्या पीठ दर्द हमेशा गंभीर होता है?',
                'answer' => 'कई एपिसोड यांत्रिक होते हैं, पर लाल झंडे वाले लक्षणों पर तुरंत चिकित्सकीय मदद लें। यह निदान नहीं है।',
            ],
            [
                'entity' => null,
                'locale' => 'en',
                'sort_order' => 5,
                'question' => 'Is Health Assist content a medical diagnosis?',
                'answer' => 'No. Public Health Assist pages are educational only and never replace a licensed clinician’s assessment, diagnosis, or treatment plan.',
            ],
        ];

        foreach ($faqs as $faq) {
            /** @var SeoEntity|null $entity */
            $entity = $faq['entity'];
            $question = $faq['question'];

            SeoFaq::query()->updateOrCreate(
                [
                    'tenant_key' => 'system',
                    'locale' => $faq['locale'],
                    'question' => $question,
                    'entity_id' => $entity?->id,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => null,
                    'answer' => $faq['answer'],
                    'sort_order' => $faq['sort_order'],
                    'status' => SeoFaq::STATUS_PUBLISHED,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertEntity(array $data, $reviewStamp, ?int $reviewerId): SeoEntity
    {
        return SeoEntity::query()->updateOrCreate(
            [
                'tenant_key' => 'system',
                'type' => $data['type'],
                'slug' => $data['slug'],
                'locale' => $data['locale'],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => null,
                'title' => $data['title'],
                'summary' => $data['summary'],
                'body' => $data['body'],
                'direct_answer' => $data['direct_answer'],
                'structured_facts' => $data['structured_facts'],
                'seo_title' => $data['seo_title'],
                'seo_description' => $data['seo_description'],
                'canonical_path' => $data['canonical_path'],
                'schema_type' => $data['schema_type'],
                'citations' => [],
                'status' => SeoEntity::STATUS_PUBLISHED,
                'last_reviewed_at' => $reviewStamp,
                'published_at' => $reviewStamp,
                'reviewer_user_id' => $reviewerId,
            ],
        );
    }

    private function relate(SeoEntity $from, SeoEntity $to, string $relation): void
    {
        if ($from->relatedEntities()->wherePivot('relation', $relation)->where('seo_entities.id', $to->id)->exists()) {
            return;
        }

        $from->relatedEntities()->attach($to->id, ['relation' => $relation]);
    }
}
