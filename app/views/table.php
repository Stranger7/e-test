<?php
/**
 * Created by PhpStorm.
 * User: Сергей
 * Date: 18.03.2015
 * Time: 10:37
 * @var int   $column_count
 * @var int   $row_count
 * @var array $data
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
</head>
<body>
<table border="1">
    <?php for($i = 0; $i < $row_count; $i++): ?>
        <tr>
        <?php for($j = 0; $j < $column_count; $j++): ?>
            <td><?= $data[$j * $row_count + $i] ?></td>
        <?php endfor; ?>
        </tr>
    <?php endfor; ?>
</table>
</body>
</html>
