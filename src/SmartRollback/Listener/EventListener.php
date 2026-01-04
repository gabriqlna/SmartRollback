<?php

namespace SmartRollback\Listener;

use SmartRollback\Main;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\block\Block;

class EventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    private function serialize(Block $block): string {
        return GlobalBlockStateHandlers::getSerializer()->serialize($block->getStateId())->getName();
    }

    public function onBreak(BlockBreakEvent $event): void {
        if ($event->isCancelled()) return;
        
        $block = $event->getBlock();
        $this->plugin->getDatabaseHandler()->log(
            $event->getPlayer()->getName(),
            0, // BREAK
            $block->getPosition()->getWorld()->getFolderName(),
            $block->getPosition()->getFloorX(),
            $block->getPosition()->getFloorY(),
            $block->getPosition()->getFloorZ(),
            $this->serialize($block), // O que era (precisa restaurar isso)
            "minecraft:air" // O que virou
        );
    }

    public function onPlace(BlockPlaceEvent $event): void {
        if ($event->isCancelled()) return;

        $blockReplaced = $event->getBlockReplaced(); // Geralmente ar
        $blockPlaced = $event->getBlockAgainst(); // Bug fix: Usar getBlock() do evento pode ser instável em alguns casos, mas no PM5 use getTransactions se precisar precisão, aqui simplificamos.
        
        // No PM5 o bloco colocado está nas transactions, mas vamos simplificar pegando o estado final
        $finalBlock = $event->getBlock();

        $this->plugin->getDatabaseHandler()->log(
            $event->getPlayer()->getName(),
            1, // PLACE
            $finalBlock->getPosition()->getWorld()->getFolderName(),
            $finalBlock->getPosition()->getFloorX(),
            $finalBlock->getPosition()->getFloorY(),
            $finalBlock->getPosition()->getFloorZ(),
            $this->serialize($blockReplaced), // O que era (Ar/Agua)
            $this->serialize($finalBlock) // O que virou
        );
    }
    
    // Interact logs para Chests podem ser adicionados aqui (Action 2)
    // Para simplificar o projeto base, focaremos em blocos físicos primeiro.
}
