<?php
namespace Vendimia\Database;

interface ConnectorInterface
{
    /**
     * Converts a PHP value to a DB escaped value, with quotes if needed
     */
    public function valueFromPHP($value);

    /**
     * Gets the database field type from a Vendimia field id
     */
    public function getFieldString($id);

    /**
     * Escapes a string or an array of string
     */
    public function escape($string, $quotation = '');

    /**
     * Escapes a string for use as identifier, like table names
     */
    public function escapeIdentifier($string);

    /**
     * Returns an escaped "field = value" string
     */
    public function fieldValue($field, $value);
    
    /**
     * Executes a SQL query. Returns a cursor
     */
    public function execute($query);

    /**
     * Obtains one register from a result
     */
    public function fetchOne($result);

    /**
     * Inserts $data into $table
     *
     * @return int Inserted autoincrement field value.
     */
    public function insert($table, array $data);

    /**
     * Updates $table with $data
     *
     * @param string $table Affected db table
     * @param array $data Associative array of fields and values
     * @param string $where Condition for update the table
     * @return int Updated rows count
     */
    public function update($table, array $data, $where = null);

    /**
     * Deletes a register
     *
     * @param string $table Affected db table
     * @param string $where Condition for deleting registers
     * @return int Delete rows count
     */
    public function delete($table, $where);

    /**
     * Starts a SQL Transaction
     */
    public function startTransaction();

    /**
     * Commits a SQL Transaction
     */
    public function commitTransaction();

    /**
     * Rollbacks a transaction
     */
    public function rollbackTransaction();
}