<?php
namespace Vendimia\Database\Mysql;

use Vendimia\Database;
use Vendimia\Database\ValueInterface;
use Vendimia\Database\Field;
use mysqli;

class Connector implements Database\ConnectorInterface
{
    const Fields = [
        Field::Bool => 'tinyint',
        Field::Byte => 'tinyint',
        Field::SmallInt => 'smallint',
        Field::Integer => 'int',
        Field::BigInt => 'bigint',

        Field::Float => 'float',
        Field::Double => 'double',
        Field::Decimal => 'decimal',

        Field::Char => 'varchar',
        Field::FixChar => 'char',
        Field::Text => 'text',
        Field::Blob => 'blob',

        Field::Date => 'date',
        Field::Time => 'time',
        Field::DateTime => 'datetime',

        Field::JSON => 'text',

        Field::ForeignKey => 'int',

    ];

    public function __construct($def)
    {
        $host = 'localhost';
        $username = null;
        $password = null;
        $database = null;
        $charset = 'utf8';
        extract ($def, EXTR_IF_EXISTS);

        if (is_null($database)) {
            throw new \RuntimeException('Database name is missing.');
        }

        $this->connection = mysqli_init();

        @$connected = $this->connection->real_connect(
            'p:' . $host, // Conexión persistente por defecto.
            $username,
            $password,
            $database,
            null,
            null,
            MYSQLI_CLIENT_FOUND_ROWS
        );

        if(!$connected) {
            throw new \RuntimeException("Error connection to MySQL database: " . $this->connection->connect_error);
        }

        $this->connection->set_charset($charset);
    }

    public function getFieldString($id)
    {
        return self::Fields[$id];
    }

    public function escape($string, $quotation = '\'')
    {
        if (is_array($string)) {
            $that = $this;
            array_map(function($str) use ($that, $quotation){
                return $that->escape($str, $quotation);
            }, $string);

            return $string;
        } elseif (is_numeric($string)) {
            // Los números no requieren ser escapados.
            return $string;
        } elseif (is_null($string)) {
            return 'NULL';
        } elseif (is_string($string)) {
            return $quotation .
                $this->connection->real_escape_string($string) .
                $quotation;
        } else {
            throw new \InvalidArgumentException('Tried to escape a non-string value (' . gettype($string) . ').');
        }
    }

    public function escapeIdentifier($string)
    {
        return $this->escape($string, '`');
    }

    public function fieldValue($field, $value)
    {
        return $this->escapeIdentifier($field) . '=' .
            $this->valueFromPHP($value);
    }

    /**
     * Converts a PHP value to a type of this database
     * @param  mixed $value PHP value to converted
     * @return mixed Database-valid value
     */
    public function valueFromPHP($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value?1:0;
        } elseif (is_numeric($value)) {
            return $value;
        } elseif (is_array($value)) {
            return $this->escape(json_encode($value));
        } elseif (is_object($value)) {
            if ($value instanceof ValueInterface) {
               return $value->getDatabaseValue($this);
            } else {
               throw new \RuntimeException("Object of type '" .
                   get_class($value) . "' cannot be directly converted to a database value.") ;
            }
        } else {
            return $this->escape($value);
        }
    }

    /**
     * Builds a WHERE from an array
     *
     * @return string Stringified where
     */
    public function generateWhere(array $where)
    {
        $result = [];
        $primera_condicion = true;

        /*
        NO SE PARA QUÉ LE PUSE $not_list = false !!!! :-|
        if ($not_list) {
            $where = [$where];
        }
        */

        foreach ($where as $part) {

            if (!$primera_condicion) {
                $result[] = $part[0];
            }

            // Estamos negando?
            if ($part[1]) {
                $result[] = 'NOT';
            }

            // Procesamos el where
            if (is_string($part[2])) {
                $result[] = $part[2];
            } else {
                $result[] = $this->generateWhere($part[2]);
            }

            $primera_condicion = false;
        }

        return '(' . join(' ', $result) . ')';
    }

    /**
     * Builds and exceutes a SQL SELECT query from an array.
     */
    public function buildAndExecuteQuery($query)
    {
        $table = $this->escapeIdentifier($query['table']);

        if ($query['fields']) {
            // Los campos ya deben estar escapados
            $fields = [];
            foreach($query['fields'] as $field => $alias) {
                if (is_numeric($field)) {
                    $fields[] = $alias;
                } else {
                    $fields[] = "$field AS $alias";
                }
            }
        } else {
            $fields[] = $table. '.*';
        }

        $sql = 'SELECT ' . join(', ' , $fields) . ' FROM ' . $table;

        if ($query['where']) {
            $sql .= ' WHERE ' . $this->generateWhere($query['where']);
        }

        // ORDER BY
        if ($query['order']) {
            $order = [];
            foreach ($query['order'] as $o) {
                $desc = '';
                if ($o{0} == '-') {
                    $desc = ' DESC';
                    $o = substr($o, 1);
                }
                $order[] = $this->escapeIdentifier($o) . $desc;
            }
            $sql .= ' ORDER BY ' . join(', ', $order);
        }

        // LIMIT
        if ($query['limit']) {
            $sql .= ' LIMIT ' . intval($query['limit']);

            // no hay OFFSET sin LIMIT
            if ($query['offset']) {
                $sql .= ' OFFSET ' . intval($query['offset']);
            }
        }

        // GROUP BY
        if ($query['group']) {
            $sql .= 'GROUP BY ';
            $fields = $this->escape($query['group']);
            if (is_string($fields)) {
                $sql .= $fields;
            } else {
                $sql .= join(', ', $fields);
            }
        }

        return $this->execute($sql);
    }

    public function execute($query)
    {
        $result = $this->connection->query($query);
        if ($result === false) {
            throw new Database\QueryException($this->connection->error, [
                'Query' => $query,
            ]);
        }
        return $result;
    }

    public function fetchOne($cursor) {
        return $cursor->fetch_assoc();
    }

    public function insert($table, array $data)
    {
        // No insertamos nada.
        if (!$data) {
            return null;
        }

        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = $this->escapeIdentifier($field);
            $values[] = $this->valueFromPHP($value);
        }

        $sql = 'INSERT INTO ' . $this->escapeIdentifier($table). ' (';
        $sql .= join(', ', $fields) . ') VALUES (' . join(', ', $values) . ')';

        $this->execute($sql);

        return $this->connection->insert_id;
    }

    public function update($table, array $data, $where = null)
    {
        $values = [];
        foreach ($data as $field => $value) {
            $values[] = $this->escapeIdentifier($field) . '=' .
                $this->valueFromPHP($value);
        }

        $sql = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ' .
            join (', ', $values);

        if (!is_null($where)) {
            $sql .= ' WHERE ' . $where;
        }

        $result = $this->execute($sql);
        return $this->connection->affected_rows;
    }

    public function delete($table, $where)
    {
        $sql = "DELETE FROM " . $this->escapeIdentifier($table);

        if ($where) {

            if (is_array($where)) {
                $where = $this->generateWhere($where);
            }

            $sql .= ' WHERE ' . $where;
        }

        $result = $this->execute($sql);
        return $this->connection->affected_rows;
    }

    public function startTransaction()
    {
        $this->execute ('START TRANSACTION');
    }

    public function commitTransaction()
    {
        $this->execute('COMMIT');
    }

    public function rollbackTransaction()
    {
        $this->execute('ROLLBACK');
    }
}
