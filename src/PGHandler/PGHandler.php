<?php

namespace PGHandler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * This class is a handler for Monolog, which can be used
 * to write records in a PostgreSQL table
 */
class PGHandler extends AbstractProcessingHandler {

    /**
     * @var bool defines whether the Postgres connection is been initialized
     */
    private $initialized = false;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var string pg statement name
     */
    private $statement;

    /**
     * @var string default table name to store the logs in
     */
    private $table = 'logs';

    /**
     * @param resource $connection
     * @param string $table
     * @param integer $level
     * @param bool $bubble
     */
    public function __construct($connection, $table, $level = Logger::DEBUG, $bubble = true)
    {
        if (!is_resource($connection)) {
            throw new \InvalidArgumentException('A connection must either be a resource.');
        }
        $this->connection = $connection;
        $this->table = $table;
        parent::__construct($level, $bubble);
    }

    /**
     * Initializes this handler by creating the table if it not exists
     */
    private function initialize() {

        pg_query(
            $this->connection,
            'CREATE TABLE IF NOT EXISTS '.$this->table.' ('
            . 'channel varchar(255),'
            . 'level_name varchar(10),'
            . 'message text,'
            . 'context json,'
            . 'extra json,'
            . 'datetime timestamp'
            . ')'
        );

        pg_prepare(
            $this->connection,
            $this->statement,
            'INSERT INTO '.$this->table.' (channel, level_name, message, context, extra, datetime) VALUES ($1, $2, $3, $4, $5, $6)'
        );

        $this->initialized = true;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  $record[]
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $content = [
            'channel'    => $record['channel'],
            'level_name' => $record['level_name'],
            'message'    => $record['message'],
            'context'    => json_encode($record['context']),
            'extra'      => json_encode($record['extra']),
            'datetime'   => $record['datetime']->format('Y-m-d G:i:s'),
        ];
        pg_get_result($this->connection);
        pg_send_execute($this->connection, $this->statement, $content);
    }
}
