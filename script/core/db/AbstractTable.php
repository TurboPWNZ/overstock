<?php
namespace Slando\core\db;

use Slando\core\Configurator;

abstract class AbstractTable
{
    protected static $pdo;

    protected static $config;

    protected $_table;

    public function __construct()
    {
        if (empty(self::$config)) {
            self::$config = Configurator::load();
        }

        try {
            if (empty(self::$pdo)) {
                self::$pdo = new \PDO(
                    self::$config['db']['dsn'],
                    self::$config['db']['user'],
                    self::$config['db']['password'],
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // выбрасывать исключения при ошибках
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // по умолчанию ассоциативный массив
                    ]
                );
            }
        } catch (\PDOException $e) {
            die("❌ Ошибка подключения: " . $e->getMessage());
        }

        $this->_table = $this->camelCaseToSnakeCase(get_class($this));
    }

    /**
     * 1 запись
     *
     * @param $id
     *
     * @return mixed
     */
    public function findByPk($id)
    {
        $stmt = self::$pdo->prepare("SELECT * FROM `{$this->_table}` WHERE {$this->id} = :id");
        $stmt->execute([
            $this->id => $id
        ]);

        return $stmt->fetch(); // одна строка
    }

    public function find($condition, $params)
    {
        $stmt = self::$pdo->prepare("SELECT * FROM `{$this->_table}` WHERE " . $condition);
        $stmt->execute($params);

        return $stmt->fetch(); // одна строка
    }

    public function insert($params)
    {
        $keys = array_keys($params);
        $values = array_map(function ($key) {
            return ':'.$key;
        }, $keys);
        $prefix = implode(',', $keys);
        $sufix = implode(',', $values);

        $stmt = self::$pdo->prepare("INSERT INTO `{$this->_table}` ({$prefix}) VALUES ({$sufix})");
        $stmt->execute($params);

        return $this->findByPk(self::$pdo->lastInsertId());
    }

    public function update($search, $updateData)
    {
        $keys = array_keys($updateData);
        $values = array_map(function ($key) {
            return "{$key} = :{$key}";
        }, $keys);
        $prefix = implode(',', $values);

        $stmt = self::$pdo->prepare("UPDATE `{$this->_table}` SET {$prefix} WHERE {$search}");
        $stmt->execute($updateData);

        return $stmt->rowCount();
    }

    public function removeFromPk($id)
    {
        $stmt = self::$pdo->prepare("DELETE FROM `{$this->_table}` WHERE {$this->id} = :id");
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->rowCount();
    }

    private function camelCaseToSnakeCase($camelCaseString)
    {
        $camelCaseString = explode('\\', $camelCaseString);
        $camelCaseString = end($camelCaseString);

        // Insert an underscore before each uppercase letter that is preceded by a lowercase letter or a digit
        $snakeCaseString = preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $camelCaseString);

        // Convert the entire string to lowercase
        return strtolower($snakeCaseString);
    }
}