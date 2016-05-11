monolog-postgres
================

Handler para o Monolog que permite a gravação dos registros de log em uma tabela PostgresSQL.

Como utilizar:

```

use Monolog\Logger;
use PGHandler\PGHandler;

$pdo = new PDO('pgsql:dbname=nomedobanco;host=servidor;user=usuario;password=senha');
$PGHandler = new PGHandler($pdo, 'log_erros');

//Instancia um Logger com um channel teste
$logger = new \Monolog\Logger('teste');
//Adiciona o handler  
$logger->pushHandler($PGHandler);

//Grava um log de exemplo
$logger->addWarning("Esta é uma mensagem de teste.", array('teste'=>'teste'));
```

