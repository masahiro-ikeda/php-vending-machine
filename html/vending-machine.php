<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
</head>

<?php
const PRICE_KEY = 'price';
const STOCK_KEY = 'stock';

// パラメーター
$pay10 = $_GET['pay10'];
$pay50 = $_GET['pay50'];
$pay100 = $_GET['pay100'];
$pay500 = $_GET['pay500'];
$pay1000 = $_GET['pay1000'];
$drink = $_GET['drink'];

/* 引数の形式チェック */
if (isset($pay10) && !is_numeric($pay10)) {
    exit('Bad Request');
}
if (isset($pay50) && !is_numeric($pay50)) {
    exit('Bad Request');
}
if (isset($pay100) && !is_numeric($pay100)) {
    exit('Bad Request');
}
if (isset($pay500) && !is_numeric($pay500)) {
    exit('Bad Request');
}
if (isset($pay1000) && !is_numeric($pay1000)) {
    exit('Bad Request');
}
if ($drink == null) {
    exit('Bad Request');
}

/* 支払データをまとめる */
$payments = [];
$payments[10] = $pay10;
$payments[50] = $pay50;
$payments[100] = $pay100;
$payments[50] = $pay500;
$payments[1000] = $pay1000;
foreach ($payments as $yen => $count) {
    $paymentAmount += $yen * $count;
}

/* ドリンク情報を読み込み */
$drinkFilePath = __DIR__ . "/data/drinks.csv";
if (($handle = fopen($drinkFilePath, "r")) !== FALSE) {
    while (($line = fgetcsv($handle, ",")) !== FALSE) {
        $drinks[$line[0]] = [PRICE_KEY => $line[1], STOCK_KEY => $line[2]];
    }
    fclose($handle);
}

/* ドリンクが購入可能かチェック */
if (!array_key_exists($drink, $drinks)) {
    exit('不正なドリンクです');
}
$stock = $drinks[$drink][STOCK_KEY];
if ($stock <= 0) {
    exit("${drink}は売り切れです");
}
$price = $drinks[$drink][PRICE_KEY];
if ($price > $paymentAmount) {
    exit('お金が足りません');
}

/* ドリンクを購入する */
$drinks[$drink][STOCK_KEY] -= 1;
$changeAmount = $paymentAmount - $price;

/* 現金残高を読み込み */
$cashFilePath = __DIR__ . "/data/cash.csv";
if (($handle = fopen($cashFilePath, "r")) !== FALSE) {
    while (($line = fgetcsv($handle, ",")) !== FALSE) {
        $cashStock[$line[0]] = $line[1];
        $cashAmount += $line[0] * $line[1];
    }
    fclose($handle);
}

/* おつりの枚数を計算 */
krsort($cashStock); // 枚数が最小になるよう、額の高い硬貨から返せるようにする
$outAmount = 0;
$changeAmountCash = [];
foreach ($cashStock as $yen => $count) {
    $leftAmount = $changeAmount - $outAmount;
    if ($leftAmount == 0) {
        break;
    }
    if ($count == 0 || $yen > $leftAmount) {
        continue;
    }
    if ($leftAmount >= $yen * $count) {
        $outCount = $count;
    } else {
        $outCount = floor($leftAmount / $yen); // 切り捨て
    }
    $outAmount += $yen * $outCount;

    $changeAmountCash[$yen] = $outCount;
    $cashStock[$yen] = $count - $outCount;
}


/* 支払ったお金を現金残高に加える */
foreach ($cashStock as $yen => $count) {
    $cashStock[$yen] = $count + $payments[$yen];
}

/* ドリンク情報を更新 */
$file = fopen($drinkFilePath, "w");
foreach ($drinks as $key => $value) {
    fwrite($file, $key . "," . $value[PRICE_KEY] . "," . $value[STOCK_KEY] . "\n");
}
fclose($file);

/* 現金残高を更新 */
ksort($cashStock);
$file = fopen($cashFilePath, "w");
foreach ($cashStock as $yen => $count) {
    fwrite($file, $yen . "," . $count . "\n");
}
fclose($file);

/* 処理結果を出力する */
print_r("${paymentAmount}円のお金を入れました");
print_r("</br>");
print_r("${drink}をお買い上げありがとうございます");
print_r("</br>");
print_r("${changeAmount}円のおつりです");
print_r("</br>");
foreach ($changeAmountCash as $yen => $count) {
    print_r("${yen}円硬貨が${count}枚でます");
    print_r("</br>");
}

?>

</body>

</html>