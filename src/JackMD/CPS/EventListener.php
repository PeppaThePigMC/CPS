<?php
declare(strict_types = 1);

namespace JackMD\CPS;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\PlayMode;

class EventListener implements Listener{
	
	/** @var CPS */
	private $plugin;
	
	/**
	 * EventListener constructor.
	 *
	 * @param CPS $plugin
	 */
	public function __construct(CPS $plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event){

	    $player = $event->getPlayer();
		$packet = $event->getPacket();

		if($packet instanceof LoginPacket) {

		    $name = $player->getLowerCaseName();
		    $input = (int)$packet->clientData["CurrentInputMode"];
		    $this->plugin->setDeviceInput($name, $input);

		} elseif ($packet instanceof LevelSoundEventPacket) {
		    $sound = $packet->sound;
		    if(in_array($sound, CPS::SWISH_SOUNDS)) {
		        $this->plugin->addClick($player);
            }

		} elseif ($packet instanceof PlayerActionPacket) {

		    $position = new Position($packet->x, $packet->y, $packet->z, $player->getLevel());
		    $device = $this->plugin->getDeviceInput($player);
		    $this->plugin->setAction($player, $packet->action);

		    if($packet->action === PlayerActionPacket::ACTION_START_BREAK and ($device === InputMode::MOUSE_KEYBOARD || $device === InputMode::GAME_PAD)) {
		        $this->plugin->addClick($player, $position);
            }
        }
	}


    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event) : void {

        $player = $event->getPlayer();

        $action = $event->getAction();

        $input = $this->plugin->getDeviceInput($player);

        if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK and ($input === InputMode::TOUCHSCREEN)) {
            $this->plugin->addClick($player);
        }
    }
}