<?php

namespace SmartRollback\Database;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use SmartRollback\Main;

class ReadTask extends AsyncTask {

    public function __construct(
        private string $path,
        private string $type, // "player" ou "area"
        private string $param, // Nome do player ou vazio
        private int $minTime,
        private int $radius,
        private ?string $world,
        private ?array $center, // [x, y, z]
        private $callback // Não é serializado, tratado no completion
    ) {
        $this->storeLocal($callback);
    }

    public function onRun(): void {
        $db = new \SQLite3($this->path);
        $results = [];
        
        $sql = "SELECT * FROM history WHERE timestamp >= $this->minTime";
        
        if ($this->type === "player") {
            $sql .= " AND player = '" . $db->escapeString($this->param) . "'";
        } elseif ($this->type === "area" && $this->center !== null) {
            $x = $this->center[0];
            $z = $this->center[2];
            $w = $db->escapeString($this->world);
            // Aproximação quadrada para performance (SQL não tem sqrt nativo rápido)
            $minX = $x - $this->radius; $maxX = $x + $this->radius;
            $minZ = $z - $this->radius; $maxZ = $z + $this->radius;
            $sql .= " AND world = '$w' AND x BETWEEN $minX AND $maxX AND z BETWEEN $minZ AND $maxZ";
        }
        
        // ORDENAÇÃO DECRESCENTE (Desfazer do mais novo para o mais velho)
        $sql .= " ORDER BY id DESC";

        $result = $db->query($sql);
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $results[] = $row;
        }
        $db->close();
        
        $this->setResult($results);
    }

    public function onCompletion(): void {
        $server = Server::getInstance();
        $plugin = Main::getInstance();
        $callback = $this->fetchLocal();
        
        if ($plugin !== null && $callback !== null) {
            ($callback)($this->getResult());
        }
    }
}
