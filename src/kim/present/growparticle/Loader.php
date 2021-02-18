<?php
declare(strict_types=1);

namespace kim\present\growparticle;

use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase implements Listener{
    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    protected function onDisable() : void{
    }

    /** @priority MONITOR */
    public function onBlockGrow(BlockGrowEvent $event) : void{
        $block = $event->getBlock();
        $pos = $block->getPos();

        $pk = new SpawnParticleEffectPacket();
        $pk->position = $pos->add(0.5, 0, 0.5);
        $pk->particleName = "minecraft:crop_growth_emitter";

        foreach($pos->getWorld()->getViewersForPosition($pos) as $player){
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }
}