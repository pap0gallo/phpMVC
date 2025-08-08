<?php

$links = [
    ['url' => 'https://google.com', 'name' => 'Google'],
    ['url' => 'https://yandex.com', 'name' => 'Yandex'],
    ['url' => 'https://bingo.com', 'name' => 'Bingo']
];

?>

<!-- BEGIN (write your solution here) -->
<?php foreach ($links as ['url' => $url, 'name' => $name]) : ?>
    <div>
        <a href=<?= $url?>><?= $name?></a>
    </div>
<?php endforeach; ?>
<!-- END -->
