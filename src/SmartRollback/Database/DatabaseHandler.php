<?php

namespace SmartRollback\Database;

use SmartRollback\Main;
use pocketmine\scheduler\ClosureTask;

class DatabaseHandler {

    private Main $plugin;
    private array $queue = [];
    private string $dbPath;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->dbPath = $plugin->getDataFolder() . $plugin->getConfig()->get("database.filename", "smart_rollback.sqlite");
        $this->initDatabase();
        
        // Tarefa de Auto-Flush
        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->flush();
        }), $plugin->getConfig()->get("performance.flush-interval", 120));
    }

    private function initDatabase(): void {
        // Inicializa tabela e limpa logs antigos (TTL) na thread principal APENAS na primeira vez
        // para garantir que a tabela exista antes de escrever.
        $db = new \SQLite3($this->dbPath);
        $db->exec("PRAGMA journal_mode=WAL;"); // Alta performance
        $db->exec("PRAGMA synchronous=NORMAL;");
        
        $db->exec("CREATE TABLE IF NOT EXISTS history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player TEXT,
            action INTEGER, -- 0: Break, 1: Place, 2: Interact
            world TEXT,
            x INTEGER, y INTEGER, z INTEGER,
            old_state TEXT,
            new_state TEXT,
            timestamp INTEGER
        )");
        
        // Índices compostos para busca O(log n)
        $db->exec("CREATE INDEX IF NOT EXISTS idx_lookup ON history (world, x, z);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_player ON history (player, timestamp);");
        
        // Limpeza TTL
        $days = $this->plugin->getConfig()->get("database.ttl-days", 7);
        $limitTime = time() - ($days * 86400);
        $db->exec("DELETE FROM history WHERE timestamp < $limitTime");
        $db->close();
    }

    public function log(string $player, int $action, string $world, int $x, int $y, int $z, string $oldState, string $newState): void {
        $this->queue[] = [$player, $action, $world, $x, $y, $z, $oldState, $newState, time()];
        
        if (count($this->queue) >= $this->plugin->getConfig()->get("performance.write-batch-size", 50)) {
            $this->flush();
        }
    }

    public function flush(): void {
        if (empty($this->queue)) return;
        
        $batch = $this->queue;
        $this->queue = [];
        
        $this->plugin->getServer()->getAsyncPool()->submitTask(new WriteTask($this->dbPath, $batch));
    }

    public function queryRollback(string $type, string $param, int $time, int $radius = 0, ?string $world = null, ?array $center = null, callable $callback): void {
        $this->plugin->getServer()->getAsyncPool()->submitTask(new ReadTask($this->dbPath, $type, $param, $time, $radius, $world, $center, $callback));
    }

    public function close(): void {
        $this->flush();
    }
}
