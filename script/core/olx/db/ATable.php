<?php
namespace Slando\core\olx\db;

use Slando\core\Configurator;
use Slando\core\db\AbstractTable;

class ATable extends AbstractTable
{
    public function __construct()
    {
        if (empty(self::$config)) {
            self::$config = Configurator::load();
        }

        try {
            if (empty(self::$pdo)) {
                self::$pdo = new \PDO(
                    self::$config['params']['secrets']['olx']['db']['dsn'],
                    self::$config['params']['secrets']['olx']['db']['user'],
                    self::$config['params']['secrets']['olx']['db']['password'],
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
}