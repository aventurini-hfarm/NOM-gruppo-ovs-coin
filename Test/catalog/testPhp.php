<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 22/06/15
 * Time: 23:13
 */

$order="AMZ200011124";
$idx = strpos($order, 'AMZ');
echo "\nIDX: ".$idx;
if (strpos($order, 'AMZ')!==false) {
    echo "\nStringa presente";
}
