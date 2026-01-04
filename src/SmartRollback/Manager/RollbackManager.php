<?php

namespace SmartRollback\Manager;

use SmartRollback\Main;
use pocketmine\scheduler\Task;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\Server;
use pocketmine\world\Position;

class RollbackManager {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function startRollback(array $logs, string $requesterName): void {
        if (empty($logs)) {
            $p = Server::getInstance()->getPlayerExact($requesterName);
            $p?->sendMessage("§cNenhum registro encontrado para este período.");
            return;
        }

        $speed = $this->plugin->getConfig()->get("performance.rollback-speed", 100);
        
        // Task agendada
        $this->plugin->getScheduler()->scheduleRepeatingTask(new class($logs, $speed, $requesterName) extends Task {
            private int $index = 0;
            private int $total;

            public function __construct(
                private array $logs, 
                private int $speed,
                private string $requester
            ) {
                $this->total = count($logs);
            }

            public function onRun(): void {
                $count = 0;
                $server = Server::getInstance();

                while ($count < $this->speed && $this->index < $this->total) {
                    $log = $this->logs[$this->index];
                    $world = $server->getWorldManager()->getWorldByName($log['world']);

                    if ($world !== null && $world->isLoaded()) {
                        // A mágica: Restauramos SEMPRE o 'old_state'.
                        // Se foi quebra: old_state era o bloco (restaura).
                        // Se foi place: old_state era ar (restaura ar, removendo o bloco colocado).
                        
                        try {
                            $stateId = GlobalBlockStateHandlers::getDeserializer()->deserialize($log['old_state']);
                            $world->setBlockAt($log['x'], $log['y'], $log['z'], $stateId);
                        } catch (\Exception $e) {
                            // Ignora blocos inválidos de versões passadas
                        }
                    }
                    
                    $this->index++;
                    $count++;
                }

                if ($this->index >= $this->total) {
                    $p = $server->getPlayerExact($this->requester);
                    $p?->sendMessage("§aRollback finalizado. {$this->total} ações revertidas.");
                    $this->getHandler()->cancel();
                }
            }
        }, 1);
    }
}
