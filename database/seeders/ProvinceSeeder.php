<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /** zone: ۱ = تهران و البرز، ۲ = مراکز استان، ۳ = مناطق دورافتاده */
    public function run(): void
    {
        $provinces = [
            ['تهران', 'tehran', 1],
            ['البرز', 'alborz', 1],
            ['اصفهان', 'isfahan', 2],
            ['فارس', 'fars', 2],
            ['خراسان رضوی', 'khorasan-razavi', 2],
            ['آذربایجان شرقی', 'east-azerbaijan', 2],
            ['آذربایجان غربی', 'west-azerbaijan', 2],
            ['خوزستان', 'khuzestan', 2],
            ['مازندران', 'mazandaran', 2],
            ['گیلان', 'gilan', 2],
            ['قم', 'qom', 2],
            ['قزوین', 'qazvin', 2],
            ['کرمان', 'kerman', 2],
            ['کرمانشاه', 'kermanshah', 2],
            ['یزد', 'yazd', 2],
            ['مرکزی', 'markazi', 2],
            ['همدان', 'hamedan', 2],
            ['گلستان', 'golestan', 2],
            ['اردبیل', 'ardabil', 2],
            ['زنجان', 'zanjan', 2],
            ['سمنان', 'semnan', 2],
            ['لرستان', 'lorestan', 2],
            ['بوشهر', 'bushehr', 2],
            ['هرمزگان', 'hormozgan', 3],
            ['کردستان', 'kurdistan', 2],
            ['چهارمحال و بختیاری', 'chaharmahal', 2],
            ['کهگیلویه و بویراحمد', 'kohgiluyeh', 3],
            ['ایلام', 'ilam', 3],
            ['خراسان شمالی', 'north-khorasan', 3],
            ['خراسان جنوبی', 'south-khorasan', 3],
            ['سیستان و بلوچستان', 'sistan-baluchestan', 3],
        ];

        foreach ($provinces as [$name, $slug, $zone]) {
            Province::updateOrCreate(['slug' => $slug], ['name' => $name, 'zone' => $zone]);
        }
    }
}
