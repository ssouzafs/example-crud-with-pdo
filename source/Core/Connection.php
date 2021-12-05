<?php

namespace Source\Core;

use PDO;
use PDOException;

/**
 * Classe Connection
 * @package Source\Core
 */
class Connection
{
    private static $instance;

    private const OPTIONS = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ];

    /**
     * Garantir que o construtor não seja herdado.
     */
    final private function __construct()
    {
    }

    /**
     * Garantir que nenhum objeto dessa classe seja clonado.
     */
    final private function __clone()
    {
    }

    /**
     * Retorna uma conexão se já existir ou cria uma nova caso ainda não exista.
     *
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (empty(self::$instance)) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . CONF_DB_HOST . ";dbname=" . CONF_DB_NAME,
                    CONF_DB_USER,
                    CONF_DB_PASSWD,
                    self::OPTIONS
                );
            } catch (PDOException $e) {
                die("Oops! Erro inesperado ao conectar...");
            }
        }
        return self::$instance;
    }

}

