<?php

namespace SmartRollback\Database;

use pocketmine\scheduler\AsyncTask;

class WriteTask extends AsyncTask {

    public function __construct(
        private string $path,
        private array $rows
    ) {}

    public function onRun(): void {
        $db = new \SQLite3($this->path);
        $db->busyTimeout(5000);
        $db->exec("BEGIN TRANSACTION;");
        
        $stmt = $db->prepare("INSERT INTO history (player, action, world, x, y, z, old_state, new_state, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($this->rows as $row) {
            $stmt->bindValue(1, $row[0]);
            $stmt->bindValue(2, $row[1]);
            $stmt->bindValue(3, $row[2]);
            $stmt->bindValue(4, $row[3]);
            $stmt->bindValue(5, $row[4]);
            $stmt->bindValue(6, $row[5]);
            $stmt->bindValue(7, $row[6]);
            $stmt->bindValue(8, $row[7]);
            $stmt->bindValue(9, $row[8]);
            $stmt->execute();
        }
        
        $db->exec("COMMIT;");
        $db->close();
    }
}
