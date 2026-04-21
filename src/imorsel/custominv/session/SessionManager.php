<?php

/*
 * SessionManager — Player session lifecycle manager.
 *
 * Listens to join/quit events to create and destroy per-player sessions.
 * Also registers the ContainerOpen callback on join so that the library
 * can intercept inventory open packets and supply the correct block
 * position for all client versions.
 *
 *  ██╗██╗     ██╗ █████╗ ███████╗    ███╗   ███╗ ██████╗ ██████╗ ███████╗███████╗██╗
 *  ██║██║     ██║██╔══██╗██╔════╝    ████╗ ████║██╔═══██╗██╔══██╗██╔════╝██╔════╝██║
 *  ██║██║     ██║███████║███████╗    ██╔████╔██║██║   ██║██████╔╝███████╗█████╗  ██║
 *  ██║██║     ██║██╔══██║╚════██║    ██║╚██╔╝██║██║   ██║██╔══██╗╚════██║██╔══╝  ██║
 *  ██║███████╗██║██║  ██║███████║    ██║ ╚═╝ ██║╚██████╔╝██║  ██║███████║███████╗███████╗
 *  ╚═╝╚══════╝╚═╝╚═╝  ╚═╝╚══════╝    ╚═╝     ╚═╝ ╚═════╝ ╚═╝  ╚═╝╚══════╝╚══════╝╚══════╝
 *
 * @author Ilias Morsel
 */

declare(strict_types=1);

namespace imorsel\custominv\session;

use imorsel\custominv\CustomInventoryLib;
use imorsel\custominv\inventory\CustomInv;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

final class SessionManager implements Listener{

    private static array $sessions = [];

    public function __construct(private Plugin $plugin){}

    public function onJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        self::$sessions[$player->getId()] = new PlayerSession($player, $this->plugin);
    }

    public function onQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        $session = self::$sessions[$player->getId()] ?? null;
        if($session !== null){
            $session->onPlayerQuit();
        }
        unset(self::$sessions[$player->getId()]);
    }

    public function onTransaction(InventoryTransactionEvent $event) : void{
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $session = self::getSession($player);
        if($session === null) return;

        foreach($transaction->getActions() as $action){
            if(!$action instanceof SlotChangeAction) continue;
            $inv = $action->getInventory();
            if(!$inv instanceof CustomInv) continue;

            $handler = $inv->getTransactionHandler();
            if($handler === null){
                $event->cancel();
                return;
            }

            $result = $handler($player, $action->getSlot(), $action->getSourceItem(), $action->getTargetItem(), $event);
            if($result === false){
                $event->cancel();
            }
            return;
        }
    }

    public static function getSession(Player $player) : ?PlayerSession{
        return self::$sessions[$player->getId()] ?? null;
    }
}
