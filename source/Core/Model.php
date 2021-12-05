<?php

namespace Source\Core;

abstract class Model
{
    /** @var object|null */
    protected $data;

    /** @var \PDOException|null */
    protected $fail;

    /** @var string|null */
    protected $message;

    /**
     * Encapsular as propriedades que não são declaradas dentro da classe, porém que são colunas na tabela.
     *
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        if (empty($this->data)) {
            $this->data = new \stdClass();
        }
        $this->data->$name = $value;
    }


    /**
     * Utilizado sempre que o objeto da classe tentar acessar uma propriedade que não está declarada dentro da classe.
     *
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return ($this->data->$name ?? null);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return (isset($this->data->$name));
    }

    /**
     * @return object|null
     */
    public function data(): ?object
    {
        return $this->data;
    }

    /**
     * @return \PDOException|null
     */
    public function fail(): ?\PDOException
    {
        return $this->fail;
    }

    /**
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * Cadastrar um registro no banco.
     *
     * @param string $entity
     * @param array $data
     * @return string|null
     */
    protected function create(string $entity, array $data)
    {
        try {
            $columns = implode(", ", array_keys($data));
            $values = ":" . implode(", :", array_keys($data));
            $stmt = Connection::getInstance()->prepare("INSERT INTO {$entity} ({$columns}) VALUES ({$values})");
            $stmt->execute($this->filter($data));
            return Connection::getInstance()->lastInsertId();
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     *  Prepara a consulta antes de executá-la de fato, depois a retorna para ser manipulada.
     *
     * @param string $select
     * @param string|null $params
     * @return \PDOStatement|null
     */
    protected function read(string $select, string $params = null): ?\PDOStatement
    {
        try {
            $stmt = Connection::getInstance()->prepare($select);
            if ($params) {
                parse_str($params, $paramsArr);

                foreach ($paramsArr as $key => $value) {
                    $type = (is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    $stmt->bindValue(":{$key}", $value, $type);
                }

                $stmt->execute();
                return $stmt;
            }
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * Atualizar um registro no banco.
     *
     * @param string $entity
     * @param array $data
     * @param string $terms
     * @param string $params
     * @return int|null
     */
    protected function update(string $entity, array $data, string $terms, string $params): ?int
    {
        try {
            $dataSet = [];
            parse_str($params, $paramsArray);
            foreach ($data as $key => $value) {
                $dataSet[] = "{$key} = :{$key}";
            }
            $dataSet = implode(", ", $dataSet);

            $stmt = Connection::getInstance()->prepare("UPDATE {$entity} SET {$dataSet} WHERE {$terms}");
            $stmt->execute($this->filter(array_merge($data, $paramsArray)));
            return ($stmt->rowCount() ?? 1);

            /** OUTRA OPÇÃO DE IMPLEMENTAÇÃO */

            // $string = "";
            // $dataSet = [];
            // foreach ($data as $key => $value) {
            //     $string .= $key . " = :{$key}, ";
            //     $dataSet[$key] = $value;
            // }
            // $string = rtrim($string, ", ");
            // $stmt = Connection::getInstance()->prepare("UPDATE {$entity} SET {$string} WHERE {$terms}");
            // parse_str($params, $params);
            // $stmt->execute($this->filter(array_merge($dataSet, $params)));
            // return ($stmt->rowCount() ?? 1);

        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * Deletar um registro ativo.
     *
     * @param string $entity
     * @param string $terms
     * @param string $params
     * @return integer|null
     */
    protected function delete(string $entity, string $terms, string $params): ?int
    {
        try {
            $stmt = Connection::getInstance()->prepare("DELETE FROM {$entity} WHERE {$terms}");
            parse_str($params, $params);
            $stmt->execute($params);

            return ($stmt->rowCount() ?? 1);
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * Retira todas as propriedades que não podem ser manipuladas manualmente. Ex.: created_at, id, etc
     *
     * @return array|null
     */
    protected function safe(): ?array
    {
        $data = (array)$this->data;
        foreach (static::$safe as $unset) {
            unset($data[$unset]);
        }
        return $data;
    }

    /**
     * Certificando-se de que as propriedades que devem obrigatoriamente ser informadas estão de fato
     * sendo informadas corretamente. Ou seja, valida os campos obrigatórios.
     *
     * @return boolean
     */
    protected function required(): bool
    {
        $data = (array)$this->data;
        foreach (static::$required as $item) {
            $notValidated = (mb_strlen(trim($data[$item])) === 0);
            if ($notValidated) {
                $this->message = "Informe todos os campos obrigatórios!";
                return false;
            }
        }
        return true;
    }

    /**
     * Função captura cada valor que está sendo informado, aplica um filtro para evitar ataques e retorna
     * um array com dados filtrados.
     *
     * @param array $data
     * @return array|null
     */
    protected function filter(array $data): ?array
    {
        $filter = [];
        foreach ($data as $key => $value) {
            $filter[$key] = (is_null($value) ? $value : filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
        }
        return $filter;
    }
}
