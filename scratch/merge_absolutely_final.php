<?php
// scratch/merge_absolutely_final.php

$projectRoot = __DIR__ . '/..';
$arJsonPath = $projectRoot . '/messages/ar.json';

// Load existing translations
$arTranslations = [];
if (file_exists($arJsonPath)) {
    $arTranslations = json_decode(file_get_contents($arJsonPath), true) ?: [];
}

// Absolutely final translations mapping
$finalExtra = [
    "Toggle Light/Dark Theme" => "تبديل المظهر الفاتح/الداكن",
    "Access Denied" => "تم رفض الوصول",
    "Therapist" => "معالج",
    "Volunteer" => "متطوع",
    "User ID" => "معرف المستخدم",
    "Specialty" => "التخصص",
    "Rate" => "السعر",
    "View License/Certificate" => "عرض الترخيص/الشهادة",
    "Only the Therapist Manager or Super Admins can verify volunteer profiles, approve/reject applications, and view volunteer stats." => "يمكن لمدير المعالجين أو المسؤولين الفائقين فقط التحقق من ملفات المتطوعين، وقبول/رفض الطلبات، وعرض إحصاءات المتطوعين.",
    "Volunteers awaiting certificate verification check" => "متطوعون بانتظار التحقق من الشهادة",
    "Languages" => "اللغات",
    "Skills" => "المهارات",
    "Only Support Staff, Therapist Managers, or Super Admins can manage individual session bookings, create/cancel group rooms, and reassign facilitators." => "يمكن لموظفي الدعم أو مديري المعالجين أو المسؤولين الفائقين فقط إدارة حجوزات الجلسات الفردية، وإنشاء/إلغاء غرف المجموعات، وإعادة تعيين الميسرين.",
    "1-on-1 Sessions Booking Directory" => "دليل حجز الجلسات الفردية",
    "Feedback" => "التقييمات والتعليقات",
    "Group Therapy Rooms" => "غرف العلاج الجماعي",
    "Topic" => "الموضوع",
    "Only Support Staff or Super Admins can access moderation reports, add action notes, and dismiss/resolve infractions." => "يمكن لموظفي الدعم أو المسؤولين الفائقين فقط الوصول إلى تقارير الإشراف، وإضافة ملاحظات الإجراءات، ورفض/حل المخالفات.",
    "Reported User" => "المستخدم المبلغ عنه",
    "Notes / Action" => "الملاحظات / الإجراء",
    "Review community discussion questions, replies, and hide/show posts" => "مراجعة أسئلة مناقشة المجتمع، والردود، وإخفاء/إظهار المنشورات",
    "Pro" => "مهني",
    "Only the Content Manager or Super Admins can upload resources, add PDFs/Videos, and manage resource details." => "يمكن لمدير المحتوى أو المسؤولين الفائقين فقط رفع الموارد، وإضافة ملفات PDF/مقاطع الفيديو، وإدارة تفاصيل الموارد.",
    "Type" => "النوع",
    "URL/Link" => "الرابط",
    "Message" => "الرسالة",
    "Account Status" => "حالة الحساب",
    "Super Admin" => "مسؤول فائق",
    "Support Staff" => "موظف دعم",
    "Specialties" => "التخصصات",
    "Session Date & Time" => "تاريخ ووقت الجلسة",
    "e.g. Anxiety & Stress Support" => "مثال: دعم القلق والتوتر",
    "e.g. Quiet Haven" => "مثال: الملاذ الهادئ",
    "Keep User Active / No Action" => "إبقاء المستخدم نشطاً / لا إجراء",
    "Ban / Delete User Account" => "حظر / حذف حساب المستخدم",
    "Under Review" => "تحت المراجعة",
    "Stress" => "الضغط النفسي",
    "Mindfulness" => "الوعي التام",
    "Short Content Abstract / Description" => "ملخص المحتوى القصير / الوصف",
    "Resource URL / Download Link" => "رابط المورد / التحميل",
    "T" => "ت",
    "Rating" => "التقييم",
    "e.g. Grounding Techniques Guide" => "مثال: دليل تقنيات التأريض",
    "https://safehaven.com/resource.pdf" => "https://safehaven.com/resource.pdf",
    "e.g. Scheduled Maintenance" => "مثال: الصيانة المجدولة",
    "Use this panel to force-start sessions early for testing purposes." => "استخدم هذه اللوحة لبدء الجلسات قسرياً بشكل مبكر لأغراض الاختبار.",
    "Microphone on' : '" => "الميكروفون قيد التشغيل' : '",
    "Audio on' : '" => "الصوت قيد التشغيل' : '"
];

// Merge and save
foreach ($finalExtra as $key => $val) {
    $normKey = trim($key);
    $arTranslations[$normKey] = $val;
}

// Format nicely and write back to messages/ar.json
file_put_contents($arJsonPath, json_encode($arTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Successfully merged absolutely final " . count($finalExtra) . " translations into ar.json.\n";
