<?php
namespace Vendimia\Database\Sqlite;

use Vendimia\Database;
use Vendimia\Database\ValueInterface;
use Vendimia\Database\Field;
use SQLite3;

class Connector // implements Database\ConnectionInterface
{
    /** Field names used in this type of db */
    const Fields = [
        Field::Bool => 'integer',
        Field::Byte => 'integer',
        Field::SmallInt => 'integer',
        Field::Integer => 'integer',
        Field::BigInt => 'integer',

        Field::Float => 'real',
        Field::Double => 'real',
        Field::Decimal => 'numeric',

        Field::Char => 'text',
        Field::FixChar => 'text',
        Field::Text => 'text',
        Field::Blob => 'blob',

        Field::Date => 'text',
        Field::Time => 'text',
        Field::DateTime => 'text',

        Field::ForeignKey => 'integer',
    ];

    public function __construct($def)
    {
        $filename = 'database.sqlite';
        $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        $encryption_key = null;
        extract ($def, EXTR_IF_EXISTS);

        $this->connection = new SQLite3($filename, $flags, $encryption_key);
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
            // Si comillas
            return $string;
        } elseif (is_null($string)) {
            return 'NULL';
        } else {
            return $quotation .
                $this->connection->escapeString($string) .
                $quotation;
        }
    }

    public function escapeIdentifier($string)
    {
        return $this->escape($string, '"');
    }

    public function valueFromPHP($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value?1:0;
        } elseif (is_numeric($value)) {
            return $value;
        } elseif (is_object($value)) {
             if ($value instanceof ValueInterface) {
                return $value->getDatabaseValue($this);
             } else {
                throw new \RuntimeException("Object of type '" .
                    get_class($value) . "' can't be directly converted to a database value.") ;
             }
        } else {
            return $this->escape($value);
        }
    }

    public function generateWhere(array $where)
    {
        $result = [];
        $primera_condicion = true;

         foreach ($where as $part) {

            if (!$primera_condicion) {
                $resuzlt[] = $part[0];
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
                if ($o[0] == '-') {
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
        //var_dump($query);exit;
        $result = $this->connection->query($query);
        if ($result === false) {
            throw new Database\QueryException($this->connection->lastErrorMsg(), [
                'Query' => $query,
            ]);
        }
        return $result;
    }

    public function fetchOne($cursor) {
        return $cursor->fetchArray();
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
