<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';


// No user selections or readings are stored or submitted to this endpoint.
runApi(static function (): never {
    requireMethod('GET');
    $topics = [
        'numbers-mean' => [
            'title' => 'What do the numbers mean?',
            'paragraphs' => [
                'The top (systolic) number is the pressure when your heart beats. The bottom (diastolic) number is the pressure when it rests between beats.',
                'For adults who are not pregnant, normal means below 120 AND below 80. Elevated means 120–129 AND below 80. Stage 1 means 130–139 OR 80–89. Stage 2 means 140 or higher OR 90 or higher. The higher category applies when the two numbers fall in different categories.',
                'A single reading cannot diagnose hypertension. Record several readings and discuss them with a healthcare professional. Pregnancy uses different thresholds.'
            ],
            'source_url' => 'https://www.heart.org/en/health-topics/high-blood-pressure/understanding-blood-pressure-readings',
            'source_label' => 'American Heart Association — Understanding Blood Pressure Readings',
        ],
        'risk' => [
            'title' => 'Risk of high blood pressure',
            'paragraphs' => [
                'High blood pressure often has no obvious symptoms, but over time it can contribute to heart disease, stroke, and kidney problems.',
                'Checking blood pressure and discussing patterns with a clinician helps identify when follow-up may be needed. Healthy eating, activity, and other lifestyle changes can help manage risk.'
            ],
            'source_url' => 'https://www.heart.org/en/health-topics/high-blood-pressure/the-facts-about-high-blood-pressure',
            'source_label' => 'American Heart Association — High Blood Pressure Facts',
        ],
        'check-home' => [
            'title' => 'How can you check at home?',
            'paragraphs' => [
                'Use a validated automatic upper-arm cuff that fits. Avoid exercise, caffeine, and smoking for 30 minutes before measuring; empty your bladder and sit quietly for at least five minutes.',
                'Keep your back supported, feet on the floor, arm at heart level, and cuff on bare skin. Take two readings one minute apart, record the results, and share your log with your clinician.',
                'If the top number is higher than 180 and/or the bottom number higher than 120, wait at least one minute and repeat. If still this high, contact your healthcare professional immediately. If you also have chest pain, shortness of breath, weakness, vision change, or trouble speaking, call 911.'
            ],
            'source_url' => 'https://www.heart.org/en/health-topics/high-blood-pressure/understanding-blood-pressure-readings/monitoring-your-blood-pressure-at-home',
            'source_label' => 'American Heart Association — Home Blood Pressure Monitoring',
        ],
        'pregnancy' => [
            'title' => 'Pregnancy and blood pressure',
            'paragraphs' => [
                'Blood pressure needs special attention before, during, and after pregnancy. Adult ranges on the Home tab are not intended to classify pregnancy readings.',
                'During pregnancy, 140 systolic or 90 diastolic or higher needs clinical assessment. Contact your pregnancy care team for an elevated reading or concerning symptoms. Severe headache, vision changes, chest pain, severe abdominal pain, or shortness of breath can be emergency warning signs; call 911 for severe symptoms.'
            ],
            'source_url' => 'https://www.heart.org/en/health-topics/high-blood-pressure/the-facts-about-high-blood-pressure/high-bp-top-10',
            'source_label' => 'American Heart Association — 2025 Blood Pressure Guideline Overview',
        ],
        'midlife-and-menopause' => [
            'title' => 'Midlife and menopause changes',
            'paragraphs' => [
                'Heart and metabolic risk factors can change during midlife and the menopause transition. Tracking blood pressure over time gives you and your clinician useful context.',
                'Ask your healthcare professional about blood pressure checks and any questions about medications or hormone therapies. Individual risks and treatment decisions vary.'
            ],
            'source_url' => 'https://newsroom.heart.org/news/perimenopause-may-offer-a-window-of-opportunity-for-heart-disease-prevention-in-women',
            'source_label' => 'American Heart Association — Perimenopause and Cardiovascular Prevention (2026)',
        ],
        'talk-to-provider' => [
            'title' => 'Talking to your provider',
            'paragraphs' => [
                'Bring the dates, times, and numbers from several readings, plus your medication list and questions. Ask how often to check, what your own target should be, and whether you need follow-up.',
                'CareBP only provides general education. A healthcare professional can interpret your readings in the context of pregnancy, other conditions, and personal risk factors.'
            ],
            'source_url' => 'https://www.heart.org/en/health-topics/high-blood-pressure/understanding-blood-pressure-readings/monitoring-your-blood-pressure-at-home',
            'source_label' => 'American Heart Association — Home Blood Pressure Monitoring',
        ],
    ];
    $slug = queryString('topic', 50);
    if ($slug === '' || !isset($topics[$slug])) {
        throw new ApiException(404, 'not_found', 'This education topic is not available.');
    }
    jsonResponse(['data' => $topics[$slug]]);
});
