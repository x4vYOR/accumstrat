<?php


function ema200($array){
    echo "Holiiis";
}
function holamundo2(){
    echo "Holiaaaaaaaaaas";
}
function getDivisor($initial, $index, $period){
    return ($initial * (1-($index * $period)));
}

function epochToDatetime($epoch){
    return date("Y-m-d H:i:s", substr($epoch, 0, 10));
}

function newAverage($avg, $price, $qty, $totalqty){
    return ($avg*$totalqty + $price*$qty)/($qty+$totalqty);
}