<?php

use Source\Models\Product;

require __DIR__ . "/autoload.php";

// Popular o objeto
$product = (new Product())->initializeProduct(
    "0101",
    "PRODUTO 01",
    25000.0
);
/**
 * Criando registro de forma ativa, ou seja, o próprio objeto já é alimentado com os dados novos já vindos do banco.
 * (inclusive com ID e data de criação).
 */
$product->save();

$product = (new Product)->findByCode("0101");
$product->description = "PRODUTO ALTERADO";
$product->sale_price = 1500.0;

// Atualizando
$product->save();

var_dump($product);
