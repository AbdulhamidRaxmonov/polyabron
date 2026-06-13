<?php
/**
 * Seed script — foods + achievements
 * Run: php database/seed.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $_ENV['DB_HOST'], $_ENV['DB_PORT'] ?? 3306, $_ENV['DB_DATABASE']);

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    seedFoods($pdo);
    seedAchievements($pdo);

    echo "\n✅ Seeding yakunlandi!\n";
} catch (PDOException $e) {
    echo "❌ Xato: " . $e->getMessage() . "\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════════════
// FOODS
// ═══════════════════════════════════════════════════════════════════════════
function seedFoods(PDO $pdo): void
{
    $pdo->exec("DELETE FROM foods WHERE created_by IS NULL");   // clear seeded
    echo "🌱 Taomlar bazasi to'ldirilmoqda...\n";

    // [name_ru, name_uz, name_en, category, calories, protein, fat, carbs, fiber, sugar, sodium, serving_g, serving_name, is_liquid]
    $foods = [

        // ── Loviya, don mahsulotlari ─────────────────────────────────────
        ['Guruch (pishgan)',        'Guruch (pishgan)',         'Cooked rice',            'grains',    130,  2.7,  0.3, 28.0, 0.4, 0.1,  1,  100, '100г',      0],
        ['Guruch (xom)',            'Guruch (xom)',             'Raw rice',               'grains',    360,  6.5,  0.9, 79.0, 1.3, 0.1,  5,  100, '100г',      0],
        ['Makaron (pishgan)',       'Makaron (pishgan)',        'Cooked pasta',           'grains',    158,  5.8,  0.9, 30.9, 1.8, 0.6,  1,  100, '100г',      0],
        ['Non (oq)',                'Non (oq)',                 'White bread',            'grains',    265, 9.0,  3.2, 49.0, 2.7, 5.0,480,  30, '1 bo\'lak',  0],
        ['Non (bug\'doy)',          'Non (bug\'doy)',           'Whole wheat bread',      'grains',    247, 13.0, 3.5, 41.0, 7.0, 5.0,400,  30, '1 bo\'lak',  0],
        ['Lavash',                  'Lavash',                   'Lavash',                 'grains',    275,  9.1,  1.1, 57.8, 2.0, 0.8,600,  50, 'bir dona',  0],
        ['Sho\'rva non (somsa)',    'Somsa',                    'Samsa',                  'grains',    300,  9.5, 14.0, 34.0, 1.2, 0.5,420, 100, 'bir dona',  0],
        ['Javdar noni',             'Javdar noni',              'Rye bread',              'grains',    259,  8.5,  3.3, 48.3, 6.2, 3.5,600,  30, '1 bo\'lak',  0],
        ['Bug\'doy uni',            'Bug\'doy uni',             'Wheat flour',            'grains',    364, 10.3,  1.0, 76.3, 2.7, 0.3,  2, 100, '100г',      0],
        ['Suli unundan (ovsyanka)', 'Jo\'xori bo\'tqasi',       'Oatmeal (cooked)',       'grains',     71,  2.5,  1.5, 12.0, 1.7, 0.1, 49, 100, '100г',      0],
        ['Qovoqli bo\'tqa',        'Qovoqli bo\'tqa',          'Pumpkin porridge',       'grains',     85,  2.0,  2.5, 13.5, 0.7, 6.0, 25, 100, '100г',      0],
        ['Grechka (pishgan)',       'Grechka (pishgan)',        'Buckwheat (cooked)',     'grains',    132,  4.5,  0.6, 28.2, 2.7, 0.9,  4, 100, '100г',      0],
        ['Tarvuzsimob',             'Tarvuzsimob',              'Millet porridge',        'grains',    119,  3.5,  1.1, 23.7, 0.9, 0.2, 15, 100, '100г',      0],
        ['Arpa bo\'tqasi',          'Arpa bo\'tqasi',           'Barley porridge',        'grains',    123,  2.3,  0.4, 28.2, 3.8, 0.4,  3, 100, '100г',      0],
        ['Corn flakes',             'Corn flakes',              'Corn flakes',            'grains',    357,  7.5,  0.8, 84.0, 3.8,10.0,660, 100, '100г',      0],

        // ── Go'sht ──────────────────────────────────────────────────────
        ['Mol go\'shti (qovurilgan)', 'Mol go\'shti',          'Beef (roasted)',          'meat',      250, 26.4, 15.4,  0.0, 0.0, 0.0, 60, 100, '100г',      0],
        ['Tovuq (fileli, pishgan)', 'Tovuq filesi',            'Chicken breast (cooked)','meat',      165, 31.0,  3.6,  0.0, 0.0, 0.0, 74, 100, '100г',      0],
        ['Tovuq (butun, qovurilgan)','Tovuq (qovurilgan)',     'Chicken (roasted)',       'meat',      215, 25.0, 12.4,  0.0, 0.0, 0.0, 77, 100, '100г',      0],
        ['Cho\'chqa go\'shti (yog\'li)', 'Cho\'chqa go\'shti', 'Pork (fatty)',            'meat',      395, 14.0, 37.5,  0.0, 0.0, 0.0, 63, 100, '100г',      0],
        ['Cho\'chqa go\'shti (ariq)','Cho\'chqa go\'shti (ariq)','Pork (lean)',           'meat',      142, 20.2,  6.6,  0.0, 0.0, 0.0, 62, 100, '100г',      0],
        ['Qo\'y go\'shti',          'Qo\'y go\'shti',          'Lamb (roasted)',          'meat',      258, 25.6, 16.5,  0.0, 0.0, 0.0, 72, 100, '100г',      0],
        ['Kolbasa (varёnaya)',       'Kolbasa',                 'Boiled sausage',          'meat',      257, 12.8, 22.2,  1.5, 0.0, 1.8,850, 100, '100г',      0],
        ['Sosiska',                 'Sosiska',                  'Sausages',                'meat',      300, 11.0, 26.9,  2.3, 0.0, 0.8,930,  60, '1 dona',    0],
        ['Tuna (konserva)',          'Tuna (konserva)',          'Tuna (canned)',           'meat',      116, 25.5,  0.8,  0.0, 0.0, 0.0,400, 100, '100г',      0],
        ['Qo\'y gushti (qovurilgan)','Qo\'y gushti',           'Mutton (fried)',          'meat',      289, 21.0, 22.0,  0.0, 0.0, 0.0, 65, 100, '100г',      0],
        ['Bekon',                   'Bekon',                    'Bacon',                   'meat',      541, 37.0, 42.0,  1.3, 0.0, 0.0,1717, 30, 'bir qatlam',0],
        ['Jigar (mol)',             'Jigar (mol)',              'Beef liver',              'meat',      135, 20.4,  3.1,  4.4, 0.0, 3.1, 69, 100, '100г',      0],
        ['Jigar (tovuq)',           'Jigar (tovuq)',            'Chicken liver',           'meat',      119, 16.9,  4.8,  0.7, 0.0, 0.0, 71, 100, '100г',      0],
        ['Hind go\'shti (turkey)',  'Hind go\'shti',           'Turkey',                  'meat',      189, 29.0,  7.0,  0.0, 0.0, 0.0, 79, 100, '100г',      0],

        // ── Baliq ────────────────────────────────────────────────────────
        ['Losos (qovurilgan)',      'Losos',                    'Salmon (cooked)',         'fish',      206, 20.4, 13.4,  0.0, 0.0, 0.0, 59, 100, '100г',      0],
        ['Mintay',                  'Mintay',                   'Pollock',                 'fish',       72, 15.9,  0.9,  0.0, 0.0, 0.0, 73, 100, '100г',      0],
        ['Sel\'d (seld)',           'Sel\'d',                   'Herring',                 'fish',      218, 17.7, 15.8,  0.0, 0.0, 0.0, 90, 100, '100г',      0],
        ['Karp',                    'Karp',                     'Carp',                    'fish',       96, 16.0,  3.6,  0.0, 0.0, 0.0, 54, 100, '100г',      0],
        ['Toshbaqa baliq (trout)',  'Alabaliq',                 'Trout',                   'fish',      141, 19.2,  6.8,  0.0, 0.0, 0.0, 52, 100, '100г',      0],
        ['Sardina (konserva)',      'Sardina',                  'Sardines (canned)',        'fish',      208, 24.6, 11.4,  0.0, 0.0, 0.0,505, 100, '100г',      0],
        ['Krevetka',                'Krevetka',                 'Shrimp',                  'fish',       99, 18.0,  1.5,  0.9, 0.0, 0.0,111, 100, '100г',      0],

        // ── Tuxum ────────────────────────────────────────────────────────
        ['Tuxum (qaynatilgan)',     'Tuxum (qaynatilgan)',      'Boiled egg',              'eggs',      155, 12.6, 10.6,  1.1, 0.0, 1.1, 124,  60, '1 dona',    0],
        ['Tuxum (qovurilgan)',      'Tuxum (qovurilgan)',       'Fried egg',               'eggs',      196, 13.6, 14.8,  0.9, 0.0, 0.9, 207,  60, '1 dona',    0],
        ['Tuxum oqi',               'Tuxum oqi',                'Egg white',               'eggs',       52, 10.9,  0.2,  0.7, 0.0, 0.7, 163,  30, 'oq',        0],
        ['Tuxum sarig\'i',          'Tuxum sarig\'i',           'Egg yolk',                'eggs',      322, 15.9, 26.5,  3.6, 0.0, 0.5,  48,  18, 'sarig\'',   0],
        ['Omlet',                   'Omlet',                    'Omelette',                'eggs',      154, 10.0, 11.5,  2.0, 0.0, 2.0, 340, 100, '100г',      0],

        // ── Sut mahsulotlari ─────────────────────────────────────────────
        ['Sut (3.2%)',              'Sut',                      'Whole milk',              'dairy',      58,  3.0,  3.2,  4.7, 0.0, 4.7, 50,  200, 'bir stakan', 1],
        ['Kefir (1%)',              'Kefir (1%)',               'Kefir (1%)',              'dairy',      40,  3.3,  1.0,  4.0, 0.0, 4.0, 50,  200, '200 мл',     1],
        ['Kefir (3.2%)',            'Kefir (3.2%)',             'Kefir (3.2%)',            'dairy',      59,  2.8,  3.2,  4.1, 0.0, 4.1, 50,  200, '200 мл',     1],
        ['Qatiq (yogurt, 5%)',      'Qatiq',                    'Yogurt (5%)',             'dairy',      98,  4.3,  5.0,  8.5, 0.0, 6.0, 60,  150, '150г',       0],
        ['Suzma (tvorog, 9%)',      'Suzma',                    'Cottage cheese (9%)',     'dairy',      159,18.0,  9.0,  3.3, 0.0, 3.3, 40,  100, '100г',       0],
        ['Suzma (kam yog\'li)',     'Suzma (0%)',               'Cottage cheese (0%)',     'dairy',       71,16.5,  0.6,  1.3, 0.0, 1.3, 44,  100, '100г',       0],
        ['Qaymoq (20%)',            'Qaymoq',                   'Sour cream (20%)',        'dairy',      206,  2.8, 20.0,  3.2, 0.0, 3.2, 45,  100, '100г',       0],
        ['Sariyog\'',               'Sariyog\'',                'Butter',                  'dairy',      748,  0.5, 82.5,  0.8, 0.0, 0.1,10,   10, '1 qoshiq',   0],
        ['Pishloq (Rossiysky)',     'Pishloq',                  'Cheese (Russian)',        'dairy',      364, 23.4, 29.5,  0.0, 0.0, 0.0,1000, 30, 'bir bo\'lak',0],
        ['Pishloq (Suluguni)',      'Suluguni pishloq',         'Suluguni cheese',         'dairy',      285, 20.5, 22.0,  0.0, 0.0, 0.0,1000, 30, 'bir bo\'lak',0],
        ['Pishloq (Adygeysky)',     'Adygey pishloq',           'Adyghe cheese',           'dairy',      264, 18.5, 20.1,  0.0, 0.0, 0.0,1200, 30, 'bir bo\'lak',0],
        ['Qo\'shimcha sut (2.5%)', 'Sut (2.5%)',               'Milk (2.5%)',             'dairy',       54,  2.9,  2.5,  4.7, 0.0, 4.7, 50,  200, '200 мл',     1],
        ['Morojenoe (plombir)',     'Muzqaymoq',                'Ice cream',               'dairy',      227,  3.3, 11.0, 30.6, 0.0,25.0, 80,  100, '100г',       0],
        ['Ryazhenka (4%)',         'Ryazhenka',                 'Ryazhenka (4%)',          'dairy',       67,  2.8,  4.0,  4.2, 0.0, 4.2, 50,  200, '200 мл',     1],

        // ── Sabzavotlar ──────────────────────────────────────────────────
        ['Kartoshka (pishgan)',     'Kartoshka (pishgan)',      'Boiled potato',           'vegetables',  77,  2.0,  0.1, 17.0, 2.2, 1.2,  6, 100, '100г',      0],
        ['Kartoshka (qovurilgan)', 'Kartoshka (qovurilgan)',   'Fried potato',            'vegetables', 312,  3.3, 17.0, 38.0, 2.5, 0.3,230, 100, '100г',      0],
        ['Pomidor',                 'Pomidor',                  'Tomato',                  'vegetables',  20,  0.9,  0.2,  3.9, 1.2, 2.6,  5, 100, '100г',      0],
        ['Bodring',                 'Bodring',                  'Cucumber',                'vegetables',  15,  0.7,  0.1,  3.6, 0.5, 1.7,  2, 100, '100г',      0],
        ['Kabin (kapusta)',         'Karam',                    'White cabbage',           'vegetables',  25,  1.8,  0.1,  5.4, 2.0, 2.8, 18, 100, '100г',      0],
        ['Sabzi',                   'Sabzi',                    'Carrot',                  'vegetables',  35,  0.9,  0.2,  8.0, 2.8, 4.7, 58, 100, '100г',      0],
        ['Piyoz (qo\'ng\'ir)',      'Piyoz',                    'Onion',                   'vegetables',  40,  1.1,  0.1,  9.3, 1.7, 4.2,  4, 100, '100г',      0],
        ['Sarimsoq piyoz',          'Sarimsoq',                 'Garlic',                  'vegetables', 149,  6.4,  0.5, 33.1, 2.1, 1.0, 17, 100, '100г',      0],
        ['Qalampir (qizil)',        'Qizil qalampir',           'Red bell pepper',         'vegetables',  31,  1.0,  0.3,  7.2, 2.1, 4.2,  4, 100, '100г',      0],
        ['Qalampir (yashil)',       'Yashil qalampir',          'Green bell pepper',       'vegetables',  20,  0.9,  0.2,  4.6, 1.7, 2.4,  3, 100, '100г',      0],
        ['Baqlajon',                'Baqlajon',                 'Eggplant',                'vegetables',  25,  1.2,  0.2,  5.9, 3.0, 3.5,  2, 100, '100г',      0],
        ['Qovoq',                   'Qovoq',                    'Pumpkin',                 'vegetables',  26,  1.0,  0.1,  6.5, 0.5, 2.8,  1, 100, '100г',      0],
        ['Salat bargi',             'Salat bargi',              'Lettuce',                 'vegetables',  15,  1.4,  0.2,  2.9, 1.3, 0.8, 28, 100, '100г',      0],
        ['Shpinat',                 'Shpinat',                  'Spinach',                 'vegetables',  23,  2.9,  0.4,  3.6, 2.2, 0.4, 79, 100, '100г',      0],
        ['Brokkoli',                'Brokkoli',                 'Broccoli',                'vegetables',  34,  2.8,  0.4,  7.0, 2.6, 1.7, 33, 100, '100г',      0],
        ['Tarvuz',                  'Tarvuz',                   'Watermelon',              'fruits',      30,  0.6,  0.1,  7.6, 0.4, 6.2,  1, 200, 'bir bo\'lak',0],
        ['Qovun',                   'Qovun',                    'Cantaloupe',              'fruits',      34,  0.8,  0.1,  8.2, 0.9, 7.9, 16, 200, 'bir bo\'lak',0],
        ['Patatlar',                'Kartoshka chips',          'Potato chips',            'snacks',     536,  7.0, 35.0, 53.0, 4.8, 0.5,730,  30, 'bir paket',  0],

        // ── Mevalar ──────────────────────────────────────────────────────
        ['Olma',                    'Olma',                     'Apple',                   'fruits',      52,  0.3,  0.2, 13.8, 2.4, 10.4, 1, 150, 'bir dona',   0],
        ['Banan',                   'Banan',                    'Banana',                  'fruits',      96,  1.1,  0.2, 22.8, 2.6, 12.2, 1, 120, 'bir dona',   0],
        ['Apelsin',                 'Apelsin',                  'Orange',                  'fruits',      47,  0.9,  0.1, 11.8, 2.4, 9.4,  0, 150, 'bir dona',   0],
        ['Mandarín',                'Mandarin',                 'Mandarin',                'fruits',      38,  0.8,  0.2,  9.2, 1.8, 7.0,  2,  80, 'bir dona',   0],
        ['Uzum',                    'Uzum',                     'Grapes',                  'fruits',      67,  0.6,  0.2, 17.2, 0.9, 16.3, 2, 100, '100г',       0],
        ['Shaftoli',                'Shaftoli',                 'Peach',                   'fruits',      39,  0.9,  0.1,  9.5, 1.5, 8.4,  0, 120, 'bir dona',   0],
        ['O\'rik',                  'O\'rik',                   'Apricot',                 'fruits',      48,  1.4,  0.4, 11.1, 2.0, 9.2,  1,  40, 'bir dona',   0],
        ['Olcha',                   'Olcha',                    'Cherry',                  'fruits',      63,  1.1,  0.4, 16.0, 2.1, 12.8, 3,  80, '10 dona',    0],
        ['Anjir',                   'Anjir',                    'Fig',                     'fruits',      74,  0.7,  0.2, 19.2, 2.9, 16.3, 1,  80, '1-2 dona',   0],
        ['Qulupnay',                'Qulupnay',                 'Strawberry',              'fruits',      32,  0.7,  0.3,  7.7, 2.0, 4.9,  1, 100, '100г',       0],
        ['Limon',                   'Limon',                    'Lemon',                   'fruits',      29,  1.1,  0.3,  9.3, 2.8, 2.5,  2,  60, 'bir dona',   0],
        ['Nok',                     'Nok',                      'Pear',                    'fruits',      57,  0.4,  0.1, 15.2, 3.1, 9.8,  1, 150, 'bir dona',   0],
        ['Kivi',                    'Kivi',                     'Kiwi',                    'fruits',      61,  1.1,  0.5, 14.7, 3.0, 9.0,  3,  70, 'bir dona',   0],
        ['Ananas',                  'Ananas',                   'Pineapple',               'fruits',      50,  0.5,  0.1, 13.1, 1.4, 9.9,  1, 100, '100г',       0],
        ['Qovoq mevasi (papaya)',   'Papaya',                   'Papaya',                  'fruits',      43,  0.5,  0.3, 10.8, 1.7, 7.8,  8, 100, '100г',       0],

        // ── Yong\'oqlar va quruq mevalar ────────────────────────────────
        ['Yong\'oq (gretsky)',      'Yong\'oq',                 'Walnuts',                 'nuts',       654, 15.2, 65.2, 13.7, 6.7, 2.6,  2,  30, 'bir hovuch',  0],
        ['Bodom',                   'Bodom',                    'Almonds',                 'nuts',       579, 21.2, 49.9, 21.6, 12.5,3.9, 1,  30, 'bir hovuch',  0],
        ['Araxis (yer yong\'oq)',   'Araxis',                   'Peanuts',                 'nuts',       567, 25.8, 49.2, 16.1, 8.5, 4.7, 18,  30, 'bir hovuch',  0],
        ['Keshyu',                  'Keshyu',                   'Cashews',                 'nuts',       553, 18.2, 43.8, 30.2, 3.3, 5.9, 12,  30, 'bir hovuch',  0],
        ['Pista',                   'Pista',                    'Pistachios',              'nuts',       560, 20.0, 45.0, 28.0, 10.6,8.0,121,  30, 'bir hovuch',  0],
        ['Kunduz yong\'oq (hazelnut)','Kunduz yong\'oq',       'Hazelnuts',               'nuts',       628, 15.0, 60.7, 16.7, 9.7, 4.3,  0,  30, 'bir hovuch',  0],
        ['Quruq o\'rik',            'Quruq o\'rik',             'Dried apricots',          'nuts',       241,  3.4,  0.5, 62.6, 7.3, 53.4, 10, 30, '5-6 dona',   0],
        ['Maíz',                    'Quruq banan',              'Dates',                   'nuts',       282,  2.5,  0.4, 75.0, 8.0, 64.2, 2,  30, '3-4 dona',   0],
        ['Kunjut (sezam)',          'Kunjut',                   'Sesame seeds',            'nuts',       573, 17.7, 49.7, 23.5, 11.8,0.3, 11, 15, '1 qoshiq',   0],

        // ── O'zbek milliy taomlar ────────────────────────────────────────
        ['Palov (osh)',             'Palov (osh)',               'Plov (Uzbek pilaf)',      'uzbek',      280, 10.0, 12.0, 32.0, 0.8, 0.5,400, 300, '1 porsiya',  0],
        ['Lagman',                  'Lagman',                    'Lagman',                 'uzbek',      198,  9.5,  6.5, 27.0, 1.2, 1.0,650, 400, '1 porsiya',  0],
        ['Manti',                   'Manti',                     'Manti',                  'uzbek',      210,  9.0,  8.0, 25.0, 1.0, 0.5,450, 200, '3 dona',     0],
        ['Somsa',                   'Somsa',                     'Samsa',                  'uzbek',      290,  9.0, 13.0, 34.0, 1.5, 0.5,420, 120, '1 dona',     0],
        ['Shashlik (qo\'y)',        'Shashlik',                  'Shashlik (lamb)',         'uzbek',      280, 22.0, 19.0,  2.0, 0.0, 0.5,420, 200, '1 porsiya',  0],
        ['Tandir kabob',            'Tandir kabob',              'Tandir kabob',            'uzbek',      245, 21.0, 16.0,  2.5, 0.0, 0.5,380, 150, '1 porsiya',  0],
        ['Mastava',                 'Mastava',                   'Mastava',                'uzbek',       95,  5.0,  3.5, 11.0, 0.8, 0.5,500, 350, '1 kosa',     0],
        ['Dimlama',                 'Dimlama',                   'Dimlama',                'uzbek',      130,  8.0,  7.0,  9.0, 1.5, 1.0,400, 300, '1 porsiya',  0],
        ['Qozon kabob',             'Qozon kabob',               'Kazan kabob',            'uzbek',      260, 20.0, 18.0,  3.0, 0.5, 0.5,450, 200, '1 porsiya',  0],
        ['Non (tandır)',            'Tandır noni',               'Tandır bread',           'uzbek',      258,  8.0,  2.0, 53.0, 2.0, 1.5,350, 150, '1 bo\'lak',  0],
        ['Samsa (karam)',           'Karam somsa',               'Cabbage samsa',          'uzbek',      230,  6.0,  9.0, 30.0, 2.0, 1.5,380, 100, '1 dona',     0],
        ['Shurva',                  'Shurva',                    'Shurva',                 'uzbek',       85,  7.0,  3.5,  6.0, 0.8, 0.5,650, 400, '1 kosa',     0],
        ['Do\'lma',                 'Do\'lma',                   'Dolma',                  'uzbek',      175,  8.0,  8.5, 15.5, 1.2, 1.0,400, 150, '3-4 dona',   0],
        ['Narın',                   'Narın',                     'Narin',                  'uzbek',      220,  9.0,  7.0, 28.0, 0.5, 0.5,380, 300, '1 porsiya',  0],
        ['Chuchvara',               'Chuchvara',                 'Chuchvara',              'uzbek',      195,  9.0,  7.0, 22.0, 0.8, 0.5,420, 200, '10 dona',    0],
        ['Kasom (holodec)',         'Kasom',                     'Holodec',                'uzbek',       80,  9.0,  4.5,  0.5, 0.0, 0.0,500, 100, '100г',       0],
        ['Muzaffar oshi',           'Muzaffar oshi',             'Muzaffar oshi',          'uzbek',      260,  8.0, 10.0, 32.0, 0.8, 0.5,350, 300, '1 porsiya',  0],
        ['Qozon kabob patır bilan', 'Patır',                     'Patir bread',            'uzbek',      295,  8.5,  9.0, 44.0, 1.5, 1.0,350, 100, '1 bo\'lak',  0],
        ['Osh ko\'k (ko\'k palov)', 'Ko\'k palov',              'Green plov',             'uzbek',      250,  9.0, 11.0, 29.0, 1.5, 0.5,380, 300, '1 porsiya',  0],

        // ── Ichimliklar ──────────────────────────────────────────────────
        ['Suv',                     'Suv',                      'Water',                   'drinks',       0,  0.0,  0.0,  0.0, 0.0, 0.0,  1, 200, '1 stakan',  1],
        ['Choy (qora)',             'Qora choy',                'Black tea',               'drinks',       2,  0.1,  0.0,  0.5, 0.0, 0.0,  3, 200, '1 stakan',  1],
        ['Choy (ko\'k)',            'Ko\'k choy',               'Green tea',               'drinks',       1,  0.2,  0.0,  0.3, 0.0, 0.0,  2, 200, '1 stakan',  1],
        ['Qahva (qora)',            'Qahva',                    'Black coffee',            'drinks',       2,  0.3,  0.0,  0.0, 0.0, 0.0,  2, 200, '1 stakan',  1],
        ['Kapucino (sutli)',        'Kapucino',                 'Cappuccino',              'drinks',      74,  3.8,  3.0,  6.6, 0.0, 6.2, 80, 200, '1 stakan',  1],
        ['Apelsin sharbati',        'Apelsin sharbati',         'Orange juice',            'drinks',      45,  0.7,  0.2, 10.4, 0.2, 8.4,  1, 200, '200 мл',    1],
        ['Limonád',                 'Gazli suv',                'Carbonated water',        'drinks',       0,  0.0,  0.0,  0.0, 0.0, 0.0, 10, 200, '200 мл',    1],
        ['Kola (Cola)',             'Kola',                     'Cola',                    'drinks',      37,  0.0,  0.0,  9.6, 0.0, 9.6, 11, 330, '1 banka',   1],
        ['Limon sharbati (toza)',   'Limon sharbati',           'Fresh lemon juice',       'drinks',      22,  0.4,  0.3,  6.9, 0.3, 2.5,  1, 100, '100 мл',    1],

        // ── Qandolat va shirinliklar ─────────────────────────────────────
        ['Shakar',                  'Shakar',                   'Sugar',                   'sweets',     400,  0.0,  0.0,100.0, 0.0,100.0,  1,  10, '1 qoshiq',  0],
        ['Asil (asal)',             'Asal',                     'Honey',                   'sweets',     304,  0.3,  0.0, 82.4, 0.2, 82.1, 4,  20, '1 osh qoshiq',0],
        ['Shokolad (qora)',         'Qora shokolad',            'Dark chocolate',          'sweets',     546,  5.4, 31.3, 63.1, 7.0, 47.9, 12, 25, '1/4 plitka',0],
        ['Shokolad (sut)',          'Sut shokoladi',            'Milk chocolate',          'sweets',     535,  7.6, 29.7, 59.4, 3.4, 52.0, 79, 25, '1/4 plitka',0],
        ['Zefir',                   'Zefir',                    'Marshmallow',             'sweets',     304,  0.8,  0.0, 78.3, 0.0, 67.0, 10, 50, '2 dona',    0],
        ['Konfet (karamel)',        'Konfet',                   'Candy (caramel)',         'sweets',     380,  0.2,  2.0, 90.0, 0.0, 80.0, 30, 20, '2-3 dona',  0],
        ['Печенье (Oreo)',          'Oreo pechene',             'Oreo cookies',            'sweets',     480,  4.5, 21.0, 71.0, 1.7, 33.0,400, 44, '4 dona',    0],
        ['Keks',                    'Keks',                     'Cupcake',                 'sweets',     410,  5.0, 18.0, 56.0, 0.5, 32.0,330, 80, '1 dona',    0],
        ['Tort',                    'Tort',                     'Cake',                    'sweets',     400,  4.5, 17.0, 58.0, 0.5, 40.0,200,100, '1 bo\'lak',  0],
        ['Vafli',                   'Vafli',                    'Waffles',                 'sweets',     402,  6.0, 17.5, 58.5, 0.5, 18.0,300, 100,'100г',      0],
        ['Jam (murabbo)',           'Murabbo',                  'Jam',                     'sweets',     238,  0.5,  0.0, 62.6, 0.9, 48.0, 25,  20, '1 qoshiq',  0],
        ['Pechen\'ye (smetannoye)','Pechene',                   'Cookies',                 'sweets',     426,  5.8, 15.8, 67.2, 1.2, 25.0,280,  50, '5-6 dona',  0],

        // ── Fast food ────────────────────────────────────────────────────
        ['Gamburger',               'Gamburger',                'Hamburger',               'fastfood',   295, 17.0, 14.0, 24.0, 1.3, 5.0,560, 180, '1 dona',    0],
        ['Cheeseburger',            'Cheeseburger',             'Cheeseburger',            'fastfood',   313, 15.0, 15.0, 29.0, 1.5, 6.0,750, 200, '1 dona',    0],
        ['Kartoshka fri',           'Kartoshka fri',            'French fries',            'fastfood',   312,  3.4, 15.0, 41.4, 3.8, 0.5,210, 100, 'kichik',    0],
        ['Pitsa (margarita)',       'Pitsa',                    'Pizza Margherita',        'fastfood',   266, 11.0, 10.0, 33.0, 2.2, 3.8,500, 200, '2 bo\'lak',  0],
        ['Hot-dog',                 'Hot-dog',                  'Hot dog',                 'fastfood',   290, 11.0, 15.0, 27.0, 1.5, 4.5,690, 140, '1 dona',    0],
        ['Shawarma',                'Shawarma',                 'Shawarma',                'fastfood',   250, 13.0, 12.0, 22.0, 1.5, 2.0,700, 250, '1 dona',    0],

        // ── Souslar va spetsiyalar ───────────────────────────────────────
        ['Majonez (67%)',           'Majonez',                  'Mayonnaise',              'sauces',     624,  2.8, 67.0,  2.6, 0.0, 1.5,920,  15, '1 qoshiq',  0],
        ['Ketchup',                 'Ketchup',                  'Ketchup',                 'sauces',     112,  1.8,  0.1, 27.2, 0.7, 22.9,1110, 15, '1 qoshiq',  0],
        ['O\'simlik yog\'i',       'O\'simlik yogi',           'Vegetable oil',           'sauces',     899,  0.0,100.0,  0.0, 0.0, 0.0,  0,  10, '1 qoshiq',  1],
        ['Zaytun yog\'i',          'Zaytun yogi',              'Olive oil',               'sauces',     884,  0.0, 100.0, 0.0, 0.0, 0.0,  2,  10, '1 qoshiq',  1],
        ['Soya sousi',              'Soya sousi',               'Soy sauce',               'sauces',      53,  8.1,  0.0,  4.8, 0.1, 1.7,6000, 15, '1 qoshiq',  1],
        ['Tuz',                     'Tuz',                      'Salt',                    'sauces',       0,  0.0,  0.0,  0.0, 0.0, 0.0,38750,  5, '1 choy qoshiq',0],

        // ── Dukkaklilar ─────────────────────────────────────────────────
        ['No\'xot (pishgan)',       'No\'xot',                  'Chickpeas (cooked)',      'legumes',    164,  8.9,  2.6, 27.4, 7.6, 4.8, 10, 100, '100г',      0],
        ['Fasol (pishgan)',         'Fasol',                    'Beans (cooked)',          'legumes',    127,  8.7,  0.5, 22.8, 6.4, 0.3,  2, 100, '100г',      0],
        ['Yasmiq (chechevitsa)',    'Yasmiq',                   'Lentils (cooked)',        'legumes',    116,  9.0,  0.4, 20.1, 7.9, 1.8,  2, 100, '100г',      0],
        ['Mosh (mung bean)',        'Mosh',                     'Mung beans (cooked)',     'legumes',    105,  7.0,  0.4, 19.2, 7.6, 1.9,  2, 100, '100г',      0],

        // ── Sog'lom taomlar ─────────────────────────────────────────────
        ['Avokado',                 'Avokado',                  'Avocado',                 'healthy',    160,  2.0, 14.7,  8.5, 6.7, 0.7, 7,  100, '100г',      0],
        ['Kinoa',                   'Kinoa',                    'Quinoa (cooked)',         'healthy',    120,  4.4,  1.9, 21.3, 2.8, 0.9, 7,  100, '100г',      0],
        ['Tofu',                    'Tofu',                     'Tofu',                    'healthy',     76,  8.0,  4.3,  1.9, 0.3, 0.5, 7,  100, '100г',      0],
        ['Chia urug\'lari',        'Chia',                      'Chia seeds',             'healthy',    486, 16.5, 30.7, 42.1, 34.4,0.0, 16,  15, '1 qoshiq',  0],
        ['Proteinli bar',          'Protein bar',               'Protein bar',            'healthy',    370, 25.0, 10.0, 45.0, 3.0, 20.0,200, 60, '1 dona',    0],
        ['Granola',                 'Granola',                  'Granola',                 'healthy',    471,  8.0, 14.0, 77.0, 4.4, 27.0, 24,  50, 'bir kosa',  0],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO foods
            (name_ru, name_uz, name_en, category, calories, protein_g, fat_g, carbs_g,
             fiber_g, sugar_g, sodium_mg, serving_size_g, serving_name, is_liquid, is_verified)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)"
    );

    $count = 0;
    foreach ($foods as $f) {
        $stmt->execute($f);
        $count++;
    }

    echo "  ✅ {$count} ta taom qo'shildi\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════════════════
function seedAchievements(PDO $pdo): void
{
    $pdo->exec("TRUNCATE achievements");
    echo "🏅 Yutuqlar qo'shilmoqda...\n";

    $achievements = [
        // Streak achievements
        ['streak_3',   '🔥 Uch kun',       '3 kun ketma-ket kundalik yuritildi',        '🔥', 'streak',        3],
        ['streak_7',   '🔥 Bir hafta',      '7 kun ketma-ket kundalik yuritildi',        '🔥', 'streak',        7],
        ['streak_14',  '⚡ Ikki hafta',     '14 kun ketma-ket kundalik yuritildi',       '⚡', 'streak',       14],
        ['streak_30',  '💫 Bir oy',         '30 kun ketma-ket kundalik yuritildi',       '💫', 'streak',       30],
        ['streak_60',  '🌟 Ikki oy',        '60 kun ketma-ket kundalik yuritildi',       '🌟', 'streak',       60],
        ['streak_100', '👑 100 kun',        '100 kun ketma-ket kundalik yuritildi',      '👑', 'streak',      100],
        // Total days
        ['days_7',     '🌱 Boshlandi',      'Botdan 7 kun foydalanildi',                '🌱', 'total_days',    7],
        ['days_30',    '🌿 Bir oylik',      '30 kun davomida kundalik yuritildi',        '🌿', 'total_days',   30],
        ['days_100',   '🌲 100 kun',        '100 kun faol foydalanildi',                '🌲', 'total_days',  100],
        // Entries count
        ['entries_10', '📝 Birinchi o\'nta','10 ta yozuv kiritildi',                   '📝', 'entries_count', 10],
        ['entries_50', '📊 50 yozuv',       '50 ta yozuv kiritildi',                   '📊', 'entries_count', 50],
        ['entries_100','📈 100 yozuv',      '100 ta yozuv kiritildi',                  '📈', 'entries_count',100],
        ['entries_500','🏆 500 yozuv',      '500 ta yozuv kiritildi',                  '🏆', 'entries_count',500],
        // Weight loss
        ['weight_1',   '⚖️ -1 кг',          '1 кг yo\'qotildi',                        '⚖️', 'weight_lost',   1],
        ['weight_3',   '💪 -3 кг',          '3 кг yo\'qotildi',                        '💪', 'weight_lost',   3],
        ['weight_5',   '🎯 -5 кг',          '5 кг yo\'qotildi',                        '🎯', 'weight_lost',   5],
        ['weight_10',  '🌟 -10 кг',         '10 кг yo\'qotildi',                       '🌟', 'weight_lost',  10],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO achievements (code, title, description, icon, condition_type, condition_value)
         VALUES (?,?,?,?,?,?)"
    );

    $count = 0;
    foreach ($achievements as $a) {
        $stmt->execute($a);
        $count++;
    }

    echo "  ✅ {$count} ta yutuq qo'shildi\n";
}
