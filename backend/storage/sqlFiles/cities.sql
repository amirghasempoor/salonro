INSERT INTO cities
(province_id, name, center_lat, center_lng, created_at, updated_at)
VALUES
-- آذربایجان شرقی
((SELECT id FROM provinces WHERE name = 'آذربایجان شرقی'), 'تبریز', 38.0800, 46.2919, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان شرقی'), 'مراغه', 37.3917, 46.2397, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان شرقی'), 'میانه', 37.4247, 47.7128, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان شرقی'), 'مرند', 38.4297, 45.7642, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان شرقی'), 'اهر', 38.4772, 47.0653, NOW(), NOW()),
-- آذربایجان غربی
((SELECT id FROM provinces WHERE name = 'آذربایجان غربی'), 'ارومیه', 37.5527, 45.0761, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان غربی'), 'خوی', 38.5503, 44.9531, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان غربی'), 'مهاباد', 36.7631, 45.7175, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان غربی'), 'بوکان', 36.5208, 46.2131, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'آذربایجان غربی'), 'سلماس', 38.1963, 44.7654, NOW(), NOW()),
-- اردبیل
((SELECT id FROM provinces WHERE name = 'اردبیل'), 'اردبیل', 38.2498, 48.2933, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اردبیل'), 'مشکین‌شهر', 38.3903, 47.6789, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اردبیل'), 'خلخال', 37.6208, 48.5297, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اردبیل'), 'پارس‌آباد', 39.6483, 47.9153, NOW(), NOW()),
-- اصفهان
((SELECT id FROM provinces WHERE name = 'اصفهان'), 'اصفهان', 32.6546, 51.6680, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اصفهان'), 'کاشان', 33.9850, 51.4100, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اصفهان'), 'نجف‌آباد', 32.6339, 51.3667, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اصفهان'), 'خمینی‌شهر', 32.6975, 51.5556, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'اصفهان'), 'شاهین‌شهر', 32.8500, 51.5333, NOW(), NOW()),
-- البرز
((SELECT id FROM provinces WHERE name = 'البرز'), 'کرج', 35.8400, 50.9391, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'البرز'), 'فردیس', 35.7228, 50.9861, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'البرز'), 'نظرآباد', 35.9522, 50.6, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'البرز'), 'هشتگرد', 35.9647, 50.6853, NOW(), NOW()),
-- ایلام
((SELECT id FROM provinces WHERE name = 'ایلام'), 'ایلام', 33.6374, 46.4227, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'ایلام'), 'دهلران', 32.6944, 47.2681, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'ایلام'), 'آبدانان', 32.9931, 47.4192, NOW(), NOW()),
-- بوشهر
((SELECT id FROM provinces WHERE name = 'بوشهر'), 'بوشهر', 28.9234, 50.8203, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'بوشهر'), 'برازجان', 29.2667, 51.2167, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'بوشهر'), 'گناوه', 29.5786, 50.5164, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'بوشهر'), 'کنگان', 27.8342, 52.0642, NOW(), NOW()),
-- تهران
((SELECT id FROM provinces WHERE name = 'تهران'), 'تهران', 35.6892, 51.3890, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'تهران'), 'ری', 35.5933, 51.4381, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'تهران'), 'اسلامشهر', 35.5450, 51.2261, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'تهران'), 'پاکدشت', 35.4783, 51.6797, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'تهران'), 'شهریار', 35.6606, 51.0592, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'تهران'), 'ورامین', 35.3269, 51.6453, NOW(), NOW()),
-- چهارمحال و بختیاری
((SELECT id FROM provinces WHERE name = 'چهارمحال و بختیاری'), 'شهرکرد', 32.3256, 50.8547, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'چهارمحال و بختیاری'), 'بروجن', 31.9686, 51.2, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'چهارمحال و بختیاری'), 'فارسان', 32.2564, 50.5711, NOW(), NOW()),
-- خراسان جنوبی
((SELECT id FROM provinces WHERE name = 'خراسان جنوبی'), 'بیرجند', 32.8649, 59.2262, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان جنوبی'), 'قائنات', 33.7256, 59.1811, NOW(), NOW()),
-- خراسان رضوی
((SELECT id FROM provinces WHERE name = 'خراسان رضوی'), 'مشهد', 36.2970, 59.6062, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان رضوی'), 'نیشابور', 36.2133, 58.7958, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان رضوی'), 'سبزوار', 36.2126, 57.6819, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان رضوی'), 'تربت حیدریه', 35.2725, 59.2192, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان رضوی'), 'کاشمر', 35.2394, 58.4650, NOW(), NOW()),
-- خراسان شمالی
((SELECT id FROM provinces WHERE name = 'خراسان شمالی'), 'بجنورد', 37.4747, 57.3290, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان شمالی'), 'شیروان', 37.4128, 57.9264, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خراسان شمالی'), 'اسفراین', 37.0781, 57.5142, NOW(), NOW()),
-- خوزستان
((SELECT id FROM provinces WHERE name = 'خوزستان'), 'اهواز', 31.3183, 48.6706, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خوزستان'), 'آبادان', 30.3392, 48.3043, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خوزستان'), 'خرمشهر', 30.4397, 48.1664, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خوزستان'), 'دزفول', 32.3814, 48.4058, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خوزستان'), 'ماهشهر', 30.5589, 49.1, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'خوزستان'), 'شوشتر', 32.0433, 48.8575, NOW(), NOW()),
-- زنجان
((SELECT id FROM provinces WHERE name = 'زنجان'), 'زنجان', 36.6764, 48.4963, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'زنجان'), 'ابهر', 36.1503, 49.2214, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'زنجان'), 'خدابنده', 36.1394, 48.5, NOW(), NOW()),
-- سمنان
((SELECT id FROM provinces WHERE name = 'سمنان'), 'سمنان', 35.5729, 53.3971, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'سمنان'), 'شاهرود', 36.4182, 54.9764, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'سمنان'), 'دامغان', 36.1671, 54.3481, NOW(), NOW()),
-- سیستان و بلوچستان
((SELECT id FROM provinces WHERE name = 'سیستان و بلوچستان'), 'زاهدان', 29.4963, 60.8629, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'سیستان و بلوچستان'), 'چابهار', 25.2919, 60.6430, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'سیستان و بلوچستان'), 'ایرانشهر', 27.2025, 60.6850, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'سیستان و بلوچستان'), 'زابل', 31.0287, 61.4939, NOW(), NOW()),
-- فارس
((SELECT id FROM provinces WHERE name = 'فارس'), 'شیراز', 29.5918, 52.5837, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'فارس'), 'مرودشت', 29.8933, 52.8, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'فارس'), 'جهرم', 28.5000, 53.5600, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'فارس'), 'کازرون', 29.6194, 51.6531, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'فارس'), 'فسا', 28.9414, 53.6467, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'فارس'), 'لار', 27.6781, 54.3, NOW(), NOW()),
-- قزوین
((SELECT id FROM provinces WHERE name = 'قزوین'), 'قزوین', 36.2688, 50.0041, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'قزوین'), 'تاکستان', 36.0433, 49.6, NOW(), NOW()),
-- قم
((SELECT id FROM provinces WHERE name = 'قم'), 'قم', 34.6416, 50.8746, NOW(), NOW()),
-- کردستان
((SELECT id FROM provinces WHERE name = 'کردستان'), 'سنندج', 35.3111, 46.9923, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کردستان'), 'سقز', 36.2494, 46.2750, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کردستان'), 'بانه', 35.9958, 45.8853, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کردستان'), 'مریوان', 35.5261, 46.1811, NOW(), NOW()),
-- کرمان
((SELECT id FROM provinces WHERE name = 'کرمان'), 'کرمان', 30.2839, 57.0834, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کرمان'), 'رفسنجان', 30.4067, 55.9931, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کرمان'), 'سیرجان', 29.4522, 55.6, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کرمان'), 'بم', 29.1061, 58.3, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کرمان'), 'جیرفت', 28.6753, 57.7, NOW(), NOW()),
-- کرمانشاه
((SELECT id FROM provinces WHERE name = 'کرمانشاه'), 'کرمانشاه', 34.3142, 47.0650, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کرمانشاه'), 'اسلام‌آباد غرب', 34.1103, 46.5, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کرمانشاه'), 'سنقر', 34.7789, 47.5, NOW(), NOW()),
-- کهگیلویه و بویراحمد
((SELECT id FROM provinces WHERE name = 'کهگیلویه و بویراحمد'), 'یاسوج', 30.6685, 51.5876, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کهگیلویه و بویراحمد'), 'گچساران', 30.3586, 50.7, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'کهگیلویه و بویراحمد'), 'دوگنبدان', 30.3586, 50.7, NOW(), NOW()),
-- گلستان
((SELECT id FROM provinces WHERE name = 'گلستان'), 'گرگان', 36.8388, 54.4392, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'گلستان'), 'گنبد کاووس', 37.2506, 55.1, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'گلستان'), 'علی‌آباد کتول', 36.9, 54.8, NOW(), NOW()),
-- گیلان
((SELECT id FROM provinces WHERE name = 'گیلان'), 'رشت', 37.2809, 49.5832, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'گیلان'), 'بندر انزلی', 37.4711, 49.4611, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'گیلان'), 'لاهیجان', 37.2, 50.0, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'گیلان'), 'آستارا', 38.4258, 48.8756, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'گیلان'), 'لنگرود', 37.1958, 50.1517, NOW(), NOW()),
-- لرستان
((SELECT id FROM provinces WHERE name = 'لرستان'), 'خرم‌آباد', 33.4878, 48.3558, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'لرستان'), 'بروجرد', 33.8973, 48.7517, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'لرستان'), 'دورود', 33.4933, 49.0611, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'لرستان'), 'الیگودرز', 33.4, 49.7, NOW(), NOW()),
-- مازندران
((SELECT id FROM provinces WHERE name = 'مازندران'), 'ساری', 36.5656, 53.0588, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مازندران'), 'بابل', 36.5514, 52.6789, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مازندران'), 'آمل', 36.4692, 52.3, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مازندران'), 'قائم‌شهر', 36.4639, 52.8, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مازندران'), 'چالوس', 36.6558, 51.4, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مازندران'), 'نور', 36.5, 52.0, NOW(), NOW()),
-- مرکزی
((SELECT id FROM provinces WHERE name = 'مرکزی'), 'اراک', 34.0917, 49.6892, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مرکزی'), 'ساوه', 35.0213, 50.3564, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مرکزی'), 'خمین', 33.6, 50.0, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'مرکزی'), 'محلات', 33.9, 50.4, NOW(), NOW()),
-- هرمزگان
((SELECT id FROM provinces WHERE name = 'هرمزگان'), 'بندرعباس', 27.1865, 56.2808, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'هرمزگان'), 'قشم', 26.9581, 56.2, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'هرمزگان'), 'میناب', 27.1350, 57.0, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'هرمزگان'), 'بندر لنگه', 26.5, 54.8, NOW(), NOW()),
-- همدان
((SELECT id FROM provinces WHERE name = 'همدان'), 'همدان', 34.7988, 48.5145, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'همدان'), 'ملایر', 34.2958, 48.8, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'همدان'), 'نهاوند', 34.1911, 48.3, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'همدان'), 'تویسرکان', 34.5, 48.4, NOW(), NOW()),
-- یزد
((SELECT id FROM provinces WHERE name = 'یزد'), 'یزد', 31.8974, 54.3569, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'یزد'), 'میبد', 32.2483, 54.0, NOW(), NOW()),
((SELECT id FROM provinces WHERE name = 'یزد'), 'اردکان', 32.3, 54.0, NOW(), NOW());
