<?php /**  */
/**  */

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection SpellCheckingInspection
 * @noinspection PhpUnused
 * @noinspection PhpPureAttributeCanBeAddedInspection
 */

declare(strict_types=1);

namespace kim\present\growparticle;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

use function array_fill_keys;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function mkdir;

final class GrowParticle extends PluginBase implements Listener{
    use SingletonTrait;

    /** @var array<string, true>|null (string) xuid => true, List of grow particle disable */
    private ?array $disablePlayers = null;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $dataFolder = $this->getDataFolder();
        if(!file_exists($dataFolder)){
            mkdir($dataFolder);
        }

        $dataFile = $dataFolder . "disable_players.json";
        if(!file_exists($dataFile))
            return;

        $contents = file_get_contents($dataFile);
        if($contents !== false){
            $json = json_decode($contents, true);
            if(!is_array($json)){
                $this->getLogger()->error("Failed to read data file ($dataFile)");
                return;
            }

            $this->disablePlayers = array_fill_keys($json, true);
        }
    }

    protected function onDisable() : void{
        if($this->disablePlayers === null){
            return; //To avoid data initialization that occurs when a load fails
        }
        $dataFolder = $this->getDataFolder();
        if(!file_exists($dataFolder)){
            mkdir($dataFolder);
        }

        $dataFile = $dataFolder . "disable_players.json";
        $ret = file_put_contents($dataFile, json_encode(array_keys($this->disablePlayers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if($ret === false){
            $this->getLogger()->error("Failed to save data file ($dataFile)");
        }else{
            $this->disablePlayers = [];
        }
    }

    /**
     * @priority MONITOR
     *
     * @internal
     */
    public function onBlockGrow(BlockGrowEvent $event) : void{
        $block = $event->getBlock();
        $pos = $block->getPosition();

        $pk = new SpawnParticleEffectPacket();
        $pk->position = $pos->add(0.5, 0, 0.5);
        $pk->particleName = "minecraft:crop_growth_emitter";

        foreach($pos->getWorld()->getViewersForPosition($pos) as $player){
            if($this->isViewer($player)){
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    /**
     * @param string[] $args
     *
     * @internal
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "It can only be used in-game");
            return true;
        }

        if($this->isViewer($sender)){
            $this->disableParticle($sender);
            $sender->sendMessage(TextFormat::AQUA . "[GrowParticle] Disable grow particle");
        }else{
            $this->enableParticle($sender);
            $sender->sendMessage(TextFormat::AQUA . "[GrowParticle] Enable grow particle");
        }
        return true;
    }

    public function isViewer(Player $player) : bool{
        return !isset($this->disablePlayers[$player->getXuid()]);
    }

    public function disableParticle(Player $player) : void{
        $this->disablePlayers[$player->getXuid()] = true;
    }

    public function enableParticle(Player $player) : void{
        unset($this->disablePlayers[$player->getXuid()]);
    }
}