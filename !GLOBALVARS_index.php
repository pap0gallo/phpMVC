<?php

echo date('Y');
echo "<br>";

foreach ($_GET as $key => $value) {
    echo "<br>{$key} => {$value}<br>";
}