<?php

namespace SmartRollback;

use pocketmine\plugin\PluginBase;
use SmartRollback\Command\RollbackCommand;
use SmartRollback\Database\DatabaseHandler;
use SmartRollback\Listener\EventListener;
use SmartRollback\Manager\RollbackManager;

class Main extends PluginBase {

    private static self $instance;
    private DatabaseHandler $databaseHandler;
    private RollbackManager $rollbackManager;

    protected function onLoad(): void {
        self::$instance = $this;
    }

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        
        $this->databaseHandler = new DatabaseHandler($this);
        $this->rollbackManager = new RollbackManager($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("smartrollback", new RollbackCommand($this));
        
        $this->getLogger()->info("§aSmartRollback ativado com SQLite WAL Mode.");
    }

    protected function onDisable(): void {
        if (isset($this->databaseHandler)) {
            $this->databaseHandler->close();
        }
    }

    public static function getInstance(): self {
        return self::$instance;
    }

    public function getDatabaseHandler(): DatabaseHandler {
        return $this->databaseHandler;
    }

    public function getRollbackManager(): RollbackManager {
        return $this->rollbackManager;
    }
}
