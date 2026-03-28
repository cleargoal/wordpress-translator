<?php
/**
 * Database Operations
 *
 * Handles all database queries for the plugin.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class
 */
class Database
{
    /**
     * WordPress database object
     *
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get table name with prefix
     *
     * @param string $table Table name without prefix
     * @return string Full table name
     */
    public function get_table_name(string $table): string
    {
        return $this->wpdb->prefix . 'wpste_' . $table;
    }

    /**
     * Insert record
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int|false Insert ID or false on failure
     */
    public function insert(string $table, array $data)
    {
        $result = $this->wpdb->insert(
            $this->get_table_name($table),
            $data
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update record
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return int|false Number of rows updated or false on failure
     */
    public function update(string $table, array $data, array $where)
    {
        return $this->wpdb->update(
            $this->get_table_name($table),
            $data,
            $where
        );
    }

    /**
     * Delete record
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return int|false Number of rows deleted or false on failure
     */
    public function delete(string $table, array $where)
    {
        return $this->wpdb->delete(
            $this->get_table_name($table),
            $where
        );
    }

    /**
     * Get single row
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @param string $output Output type
     * @return object|array|null
     */
    public function get_row(string $table, array $where, string $output = OBJECT)
    {
        $where_clause = $this->build_where_clause($where);

        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->get_table_name($table)} WHERE {$where_clause['sql']}",
            ...$where_clause['values']
        );

        return $this->wpdb->get_row($sql, $output);
    }

    /**
     * Get multiple rows
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @param string $output Output type
     * @return array
     */
    public function get_results(string $table, array $where = [], string $output = OBJECT): array
    {
        if (empty($where)) {
            $sql = "SELECT * FROM {$this->get_table_name($table)}";
            return $this->wpdb->get_results($sql, $output);
        }

        $where_clause = $this->build_where_clause($where);

        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->get_table_name($table)} WHERE {$where_clause['sql']}",
            ...$where_clause['values']
        );

        return $this->wpdb->get_results($sql, $output);
    }

    /**
     * Build WHERE clause from array
     *
     * @param array $where Where conditions
     * @return array SQL and values
     */
    protected function build_where_clause(array $where): array
    {
        $conditions = [];
        $values = [];

        foreach ($where as $column => $value) {
            $conditions[] = "`{$column}` = %s";
            $values[] = $value;
        }

        return [
            'sql' => implode(' AND ', $conditions),
            'values' => $values
        ];
    }

    /**
     * Get var
     *
     * @param string $query SQL query
     * @param int $column Column number
     * @param int $row Row number
     * @return string|null
     */
    public function get_var(string $query, int $column = 0, int $row = 0): ?string
    {
        return $this->wpdb->get_var($query, $column, $row);
    }

    /**
     * Execute query
     *
     * @param string $query SQL query
     * @return int|bool Number of rows affected or false on error
     */
    public function query(string $query)
    {
        return $this->wpdb->query($query);
    }
}
