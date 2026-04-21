<?php

/*
 * CustomInventoryLib — Main plugin entry point.
 *
 * Bootstraps the library on server start: registers event listeners
 * that manage per-player sessions and container-open callbacks.
 * Other plugins depend on this plugin and call CustomInventory::create()
 * to open virtual inventories without embedding any library code themselves.
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

namespace imorsel\custominv;

use imorsel\custominv\session\SessionManager;
use pocketmine\plugin\PluginBase;

final class CustomInventoryLib extends PluginBase{

    private static self $instance;

    public function onEnable() : void{
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents(
            new SessionManager($this),
            $this
        );
    }

    public static function getInstance() : self{
        return self::$instance;
    }
}
