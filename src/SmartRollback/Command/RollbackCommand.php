<?php

namespace SmartRollback\Command;

use SmartRollback\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RollbackCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("rb", "Sistema SmartRollback", "/rb <help|player|area> <tempo>");
        $this->setPermission("smartrollback.admin");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) return false;

        if (count($args) < 2) {
            $sender->sendMessage("§eUso: /rb player <nick> <tempo> ou /rb area <raio> <tempo>");
            $sender->sendMessage("§eExemplo: /rb player Steve 1h");
            return false;
        }

        $type = strtolower($args[0]);
        $param = $args[1];
        
        // Conversão de tempo simples (ex: 10m, 1h)
        $timeStr = isset($args[2]) ? $args[2] : (is_numeric($param) ? "1h" : $args[1]); // Lógica ajustável
        if ($type === "player") $timeStr = $args[2] ?? "1h";
        if ($type === "area") $timeStr = $args[2] ?? "1h";

        $seconds = $this->parseTime($timeStr);
        $minTime = time() - $seconds;

        if ($type === "player") {
            $sender->sendMessage("§7Buscando dados de §f$param§7...");
            $this->plugin->getDatabaseHandler()->queryRollback(
                "player", 
                $param, 
                $minTime, 
                0, 
                null, 
                null,
                function(array $rows) use ($sender) {
                    $this->plugin->getRollbackManager()->startRollback($rows, $sender->getName());
                }
            );
        } 
        elseif ($type === "area") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("§cUse in-game.");
                return false;
            }
            $radius = (int)$param;
            $sender->sendMessage("§7Buscando dados na área (Raio $radius)...");
            
            $pos = $sender->getPosition();
            $center = [$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()];
            
            $this->plugin->getDatabaseHandler()->queryRollback(
                "area", 
                "", 
                $minTime, 
                $radius, 
                $pos->getWorld()->getFolderName(), 
                $center,
                function(array $rows) use ($sender) {
                    $this->plugin->getRollbackManager()->startRollback($rows, $sender->getName());
                }
            );
        }

        return true;
    }

    private function parseTime(string $str): int {
        $char = substr($str, -1);
        $val = (int)substr($str, 0, -1);
        return match($char) {
            'm' => $val * 60,
            'h' => $val * 3600,
            'd' => $val * 86400,
            default => (int)$str // assume segundos se sem sufixo
        };
    }
}
