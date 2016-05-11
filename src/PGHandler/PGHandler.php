<?php

namespace PGHandler;

<?php

namespace Monolog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
/**
 * Classe Handler para o Monolog, utilizada para gravar os registros em uam tabela PostgreSQL
 */
class PGHandler extends AbstractProcessingHandler {
    /**
     * @var bool define se uma conexão foi inicializada
     */
    private $initialized = false;
    /**
     * @var Handler da conexão
     */
    protected $connection;
    /**
     * @var string 
     */
    private $statement;
    /**
     * @var string nome padrão da tabela
     */
    private $table = 'log_erros';
    /**
     * @param resource $connection
     * @param string $table
     * @param integer $level
     * @param bool $bubble
     */
    public function __construct($connection, $table, $level = Logger::DEBUG, $bubble = true)
    {
        $this->connection = $connection;
        $this->table = $table;
        parent::__construct($level, $bubble);
    }
    /**
     * Inicializa o handler criando uma tabela caso não exista
     */
    private function initialize() {
        $this->statement = $this->connection->prepare(
            'CREATE TABLE IF NOT EXISTS '.$this->table
            .' ('
            . 'channel varchar(255),'
            . 'level_name varchar(10),'
            . 'message text,'
            . 'context json,'
            . 'extra json,'
            . 'datetime timestamp DEFAULT now()'
            . ')'
        );   

        $this->statement->execute();

        $this->statement = $this->connection->prepare(
               'INSERT INTO '
               .$this->table
               .' (channel, level_name, message, context, extra, datetime) VALUES (:channel, :level_name, :message, :context, :extra, :datetime)'
        );
        $this->initialized = true;
    }
    /**
     * Grava o registro no log através do handler
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
        $this->statement->execute($content);
    }
}
