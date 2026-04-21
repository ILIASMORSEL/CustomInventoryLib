<?php

/*
 * CustomInv — Virtual inventory instance.
 *
 * Represents a single virtual inventory that can be sent to one or
 * multiple players simultaneously. Supports item transaction callbacks,
 * open/close listeners, and shared inventory mode where all viewers
 * see the same contents in real time.
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

namespace imorsel\custominv\inventory;

use imorsel\custominv\session\PlayerSession;
use imorsel\custominv\session\SessionManager;
use imorsel\custominv\type\InvType;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class CustomInv extends SimpleInventory{

    private ?string $title;
    private InvType $type;
    private ?\Closure $onTransaction = null;
    private ?\Closure $onOpen = null;
    private ?\Closure $onClose = null;

    public function __construct(InvType $type, string $title = ''){
        $this->type = $type;
        $this->title = $title;
        parent::__construct($type->getSize());
    }

    public function getType() : InvType{
        return $this->type;
    }

    public function getTitle() : string{
        return $this->title ?? '';
    }

    public function setTitle(string $title) : void{
        $this->title = $title;
    }

    public function setContents(array $items) : void{
        parent::setContents($items);
    }

    public function fill(Item $item) : void{
        $contents = [];
        for($i = 0; $i < $this->getSize(); $i++){
            $contents[$i] = clone $item;
        }
        $this->setContents($contents);
    }

    public function onTransaction(\Closure $handler) : self{
        $this->onTransaction = $handler;
        return $this;
    }

    public function onOpen(\Closure $handler) : self{
        $this->onOpen = $handler;
        return $this;
    }

    public function onClose(\Closure $handler) : self{
        $this->onClose = $handler;
        return $this;
    }

    public function getTransactionHandler() : ?\Closure{
        return $this->onTransaction;
    }

    public function getOpenHandler() : ?\Closure{
        return $this->onOpen;
    }

    public function getCloseHandler() : ?\Closure{
        return $this->onClose;
    }

    public function send(Player $player) : void{
        $session = SessionManager::getSession($player);
        if($session === null) return;
        $session->open($this);
    }

    public function sendToAll(Player ...$players) : void{
        foreach($players as $player){
            $this->send($player);
        }
    }

    public function close(Player $player) : void{
        $session = SessionManager::getSession($player);
        if($session === null) return;
        $session->close($this);
    }

    public function onClose(Player $who) : void{
        parent::onClose($who);
        $session = SessionManager::getSession($who);
        $session?->onInventoryClosed($this);
        if($this->onClose !== null){
            ($this->onClose)($who, $this);
        }
    }
}
