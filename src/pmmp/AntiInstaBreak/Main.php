<?php

namespace pmmp\AntiInstaBreak;

use pocketmine\entity\Effect;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{
	/** @var float[] */
	private $breakTimes = [];

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK){
			$this->breakTimes[$event->getPlayer()->getRawUniqueId()] = floor(microtime(true) * 20);
		}
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		if(!$event->getInstaBreak()){
			do{
				$player = $event->getPlayer();
				if(!isset($this->breakTimes[$uuid = $player->getRawUniqueId()])){
					$this->getLogger()->debug("Player " . $player->getName() . " tried to break a block without a start-break action");
					$event->setCancelled();
					break;
				}

				$target = $event->getBlock();
				$item = $event->getItem();

				$expectedTime = ceil($target->getBreakTime($item) * 20);

				if(($haste = $player->getEffect(Effect::HASTE)) !== null){
					$expectedTime *= 1 - (0.2 * $haste->getEffectLevel());
				}

				if(($miningFatigue = $player->getEffect(Effect::MINING_FATIGUE)) !== null){
					$expectedTime *= 1 + (0.3 * $miningFatigue->getEffectLevel());
				}

				$expectedTime -= 1; //1 tick compensation

				$actualTime = ceil(microtime(true) * 20) - $this->breakTimes[$uuid = $player->getRawUniqueId()];

				if($actualTime < $expectedTime){
					$this->getLogger()->debug("Player " . $player->getName() . " tried to break a block too fast, expected $expectedTime ticks, got $actualTime ticks");
					$event->setCancelled();
					break;
				}

				unset($this->breakTimes[$uuid]);
			}while(false);
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		unset($this->breakTimes[$event->getPlayer()->getRawUniqueId()]);
	}
}
