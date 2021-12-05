<?php

namespace Source\Models;

use Source\Core\Model;

class Product extends Model
{
    /** @var array $safe */
    protected static $safe = ["id", "created_at", "updated_at"];

    /** @var array $required */
    protected static $required = ["code", "description", "sale_price"];

    protected static string $entity = "products";

    /**
     * Populando um objeto do tipo produto.
     *
     * @return Product|null
     */
    public function initializeProduct(string $code, string $description, float $sale_price, ?float $purchase_price = null): ?Product
    {
        $this->code = $code;
        $this->description = $description;
        $this->sale_price = $sale_price;
        $this->purchase_price = $purchase_price;

        return $this;
    }

    /**
     * Pesquisando um produto por ID
     *
     * @param integer $id
     * @param string $column
     * @return Product|null
     */
    public function findById(int $id, string $column = "*"): ?Product
    {
        $findById = $this->read("SELECT {$column} FROM " . self::$entity . " WHERE id = :id", "id={$id}");
        if ($this->fail() || !$findById->rowCount()) {
            $this->message = "Produto não encontrado para o ID informado!!!";
            return null;
        }

        return $findById->fetchObject(__CLASS__);
    }

    /**
     * Pesquisando um produto por código.
     *
     * @param string $code
     * @param string $column
     * @return Product|null
     */
    public function findByCode(string $code, string $column = "*"): ?Product
    {
        $findByCode = $this->read("SELECT {$column} FROM " . self::$entity . " WHERE code = :code", "code={$code}");
        if ($this->fail() || !$findByCode->rowCount()) {
            $this->message = "Produto não encontrado para o código informado!!!";
            return null;
        }

        return $findByCode->fetchObject(__CLASS__);
    }

    /**
     * Pesquisando todos os produtos com limite e offset.
     *
     * @param int $limit
     * @param int $offset
     * @param string $column
     * @return array|null
     */
    public function all(int $limit = 30, int $offset = 0, string $column = "*"): ?array
    {
        $all = $this->read(
            "SELECT {$column} FROM " . self::$entity . " LIMIT :limit OFFSET :offset",
            "limit={$limit}&offset={$offset}"
        );
        if ($this->fail() || !$all->rowCount()) {
            $this->message = "Nenhum produto foi encontrado!!!";
            return null;
        }

        return $all->fetchAll(\PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Função salva o registro caso o mesmo ainda não esteja cadastrado e atualiza caso já exista o registro.
     *
     * @return $this|null
     */
    public function save()
    {
        if (!$this->required()) {
            return null;
        }

        /** Atualizar Produto */
        if (!empty($this->id)) {
            $id = $this->id;
            $result = $this->read(
                "SELECT id FROM " . self::$entity . " WHERE code = :code AND id != :id",
                "code={$this->code}&id={$id}"
            );

            if ($result->rowCount()) {
                $this->message = "O produto informado já está cadastrado!!!";
                return null;
            }

            $this->update(self::$entity, $this->safe(), "id = :id", "id={$id}");

            if ($this->fail()) {
                $this->message = "Erro ao atualizar, favor verifque os dados e tente novamente!!!";
            }
            $this->message = "Produto atualizado com sucesso!!!";
        }

        /** Cadastrar Produto */
        if (empty($this->id)) {
            if ($this->findByCode($this->code)) {
                $this->message = "O produto informado já está cadastrado!!!";
                return null;
            }

            $id = $this->create(self::$entity, $this->safe());

            if ($this->fail()) {
                $this->message = "Erro ao cadastrar, favor verifque os dados e tente novamente!!!";
            }
            $this->message = "Cadastro realizado com sucesso!!!";
        }
        // Alimentando os dados com o registro atualizado (Vindo do banco).
        $this->data = $this->read("SELECT * FROM " . self::$entity . " WHERE id = :id", "id={$id}")->fetch();

        return $this;
    }

    /**
     * Deletar produto com registro ativo
     *
     * @return Product|null
     */
    public function destroy(): ?Product
    {
        if (!empty($this->id)) {
            $this->delete(self::$entity, "id = :id", "id={$this->id}");
        }

        if ($this->fail()) {
            $this->message = "Erro ao remover o produto!!!";
            return null;
        }
        $this->message = "Produto removido com sucesso!!!";
        $this->data = null;

        return $this;
    }
}
