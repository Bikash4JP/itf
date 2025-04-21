<?php
header('Content-Type: application/json; charset=utf-8');

$newsData = [
    [
        "category" => "入社情報",
        "date" => "2025-04-01",
        "title" => "2025年4月1日より新しいスタッフが東京オフィスに加わりました。",
        "summary" => "インドネシア出身の皆様との円滑なコミュニケーションを目的に、インドネシア担当コーディネーターとしてMADE（マデ）さんが入社しました。インドネシア出身の皆様との円滑なコミュニケーションを目的に、インドネシア担当コーディネーターとしてMADE（マデ）さんが入社しました。インドネシア出身の皆様との円滑なコミュニケーションを目的に、インドネシア担当コーディネーターとしてMADE（マデ）さんが入社しました。",
        "image" => "images/news1.jpg",
        "postedBy" => "ITF Admin"
    ],
    [
        "category" => "連携",
        "date" => "2025-04-01",
        "title" => "近隣パートナー企業への新メンバーご紹介",
        "summary" => "2025年4月上旬、ITFでは新たに入社したスタッフをご紹介するため、東京近郊の提携パートナー企業様に向けて...東京近郊の提携パートナー企業様に向けて...東京近郊の提携パートナー企業様に向けて...",
        "image" => "images/news2.jpg",
        "postedBy" => "HR Team"
    ],
    [
        "category" => "募集",
        "date" => "2025-04-04",
        "title" => "『緑樹会』介護施設にて正社員募集を開始しました",
        "summary" => "ITFの提携先である介護施設『緑樹会（りょくじゅかい）』様にて、介護職の正社員募集がスタートしました。ITFの提携先である介護施設『緑樹会（りょくじゅかい）』様にて、介護職の正社員募集がスタートしました。ITFの提携先である介護施設『緑樹会（りょくじゅかい）』様にて、介護職の正社員募集がスタートしました。",
        "image" => "images/news3.jpg",
        "postedBy" => "ITF Admin"
    ],
    [
        "category" => "イベント",
        "date" => "2025-04-12",
        "title" => "外国人材向けキャリアフェア2025を開催",
        "summary" => "ITF主催のキャリアフェアが大阪で開催されます。ベトナム、インドネシア、フィリピンからの人材と日本企業をつなぐイベントです。ベトナム、インドネシア、フィリピンからの人材と日本企業をつなぐイベントです。ベトナム、インドネシア、フィリピンからの人材と日本企業をつなぐイベントです。",
        "image" => "images/news4.jpg",
        "postedBy" => "HR Team"
    ],
    [
        "category" => "連携",
        "date" => "2025-04-15",
        "title" => "タイ企業との新たなパートナーシップ締結",
        "summary" => "ITFはタイのトップ人材紹介会社と提携し、タイ人エンジニアの日本企業への紹介を強化します。タイ人エンジニアの日本企業への紹介を強化します。タイ人エンジニアの日本企業への紹介を強化します。",
        "image" => "images/news5.jpg",
        "postedBy" => "ITF Admin"
    ],
    [
        "category" => "募集",
        "date" => "2025-04-18",
        "title" => "東京オフィスで通訳スタッフ募集開始",
        "summary" => "英語と日本語のバイリンガル通訳者を募集開始。外国人材と企業のスムーズなコミュニケーションをサポートします。外国人材と企業のスムーズなコミュニケーションをサポートします。外国人材と企業のスムーズなコミュニケーションをサポートします。Lorem ipsum dolor sit amet consectetur adipisicing elit. Magni nesciunt nisi sunt doloremque libero. Nostrum sequi fuga non obcaecati mollitia, quasi dolor enim sit et atque molestias. Mollitia neque incidunt autem nemo, amet nisi impedit soluta accusamus dicta ab maiores delectus assumenda, reiciendis omnis consequatur ratione laudantium voluptate, quisquam ullam nostrum pariatur reprehenderit. Consequuntur perspiciatis, quibusdam facere vel, nisi libero facilis ducimus tenetur, aut dicta officiis maxime tempora ad repellat eum quod vitae ut! Consectetur maiores ad reprehenderit nemo. Ullam nobis repellendus officia aut repudiandae esse accusamus amet nemo. Cupiditate ex soluta et error repellat! Doloribus sapiente nulla distinctio deserunt totam et maiores consequuntur consectetur culpa eum repellendus est libero nisi inventore a dolorem eaque rem, iusto aliquam architecto. Atque magnam perferendis labore, quisquam consectetur molestias est distinctio corporis officia enim dolorum eius magni, optio quis dolore aspernatur facilis adipisci praesentium. Facere vero, nam quis iusto ab enim quidem id sit! Quae quia qui amet ratione deserunt magnam? Eligendi ratione, eum quae, rem unde exercitationem, in itaque delectus voluptas vel dolorem quisquam. Consequatur rem quidem mollitia accusamus hic aperiam maxime impedit temporibus iusto fugiat cumque expedita nesciunt odio possimus recusandae id eligendi suscipit nulla soluta, illum nobis dolores illo aliquid! Deserunt, voluptatem. Quis dolorum cupiditate delectus odio? Eligendi at hic reprehenderit modi est et illum nihil animi, deleniti adipisci maxime natus optio eos quasi inventore? Id pariatur ducimus aliquam perferendis itaque enim veniam totam reprehenderit obcaecati laboriosam, impedit atque debitis eum officiis asperiores saepe, corporis fugit possimus deserunt eos eligendi dolor optio. Quia velit consequuntur consectetur odit sunt quo non aliquid? Dicta, inventore unde perferendis ullam molestias molestiae, cupiditate non incidunt eveniet voluptatem deserunt, velit consequuntur necessitatibus aliquid ipsa laudantium pariatur sed iste quia quos? Laboriosam alias vel, excepturi eaque laborum sapiente. Optio aliquam deserunt deleniti, vel sunt molestiae laudantium!",
        "image" => "images/cat.jpg",
        "postedBy" => "HR Team"
    ]
];

echo json_encode($newsData);
?>