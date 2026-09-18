<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    /**
     * Seed the salon professions a hall can hire for.
     */
    public function run(): void
    {
        $professions = [
            'آرایشگر مو',
            'رنگ‌کار مو',
            'شینیون‌کار',
            'متخصص اکستنشن مو',
            'میکاپ آرتیست',
            'متخصص پوست',
            'اپیلاسیون‌کار',
            'متخصص بند و ابرو',
            'متخصص ناخن',
            'متخصص کاشت ناخن',
            'متخصص اکستنشن مژه',
            'متخصص اصلاح صورت',
        ];

        foreach ($professions as $name) {
            Profession::query()->firstOrCreate(['name' => $name]);
        }
    }
}
