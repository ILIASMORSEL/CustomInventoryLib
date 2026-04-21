# IM_CustomInventoryLib

```
 ██╗███╗   ███╗     ██████╗██╗   ██╗███████╗████████╗ ██████╗ ███╗   ███╗
 ██║████╗ ████║    ██╔════╝██║   ██║██╔════╝╚══██╔══╝██╔═══██╗████╗ ████║
 ██║██╔████╔██║    ██║     ██║   ██║███████╗   ██║   ██║   ██║██╔████╔██║
 ██║██║╚██╔╝██║    ██║     ██║   ██║╚════██║   ██║   ██║   ██║██║╚██╔╝██║
 ██║██║ ╚═╝ ██║    ╚██████╗╚██████╔╝███████║   ██║   ╚██████╔╝██║ ╚═╝ ██║
 ╚═╝╚═╝     ╚═╝     ╚═════╝ ╚═════╝ ╚══════╝   ╚═╝    ╚═════╝ ╚═╝     ╚═╝
  ██╗███╗   ██╗██╗   ██╗███████╗███╗   ██╗████████╗ ██████╗ ██████╗ ██╗   ██╗
  ██║████╗  ██║██║   ██║██╔════╝████╗  ██║╚══██╔══╝██╔═══██╗██╔══██╗╚██╗ ██╔╝
  ██║██╔██╗ ██║██║   ██║█████╗  ██╔██╗ ██║   ██║   ██║   ██║██████╔╝ ╚████╔╝
  ██║██║╚██╗██║╚██╗ ██╔╝██╔══╝  ██║╚██╗██║   ██║   ██║   ██║██╔══██╗  ╚██╔╝
  ██║██║ ╚████║ ╚████╔╝ ███████╗██║ ╚████║   ██║   ╚██████╔╝██║  ██║   ██║
  ╚═╝╚═╝  ╚═══╝  ╚═══╝  ╚══════╝╚═╝  ╚═══╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝   ╚═╝
     ██╗     ██╗██████╗
     ██║     ██║██╔══██╗
     ██║     ██║██████╔╝
     ██║     ██║██╔══██╗
     ███████╗██║██████╔╝
     ╚══════╝╚═╝╚═════╝
```

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![PocketMine-MP](https://img.shields.io/badge/PocketMine--MP-5.x-orange?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)
![Version](https://img.shields.io/badge/version-1.0.0-green?style=flat-square)
![Bedrock](https://img.shields.io/badge/Bedrock-1.21.x%20%2B%2026.x%2B-brightgreen?style=flat-square)

**The only virtual inventory library for PocketMine-MP 5 that works correctly on ALL Bedrock client versions.**

[English](#english) | [Русский](#русский)

</div>

---

## English

### What is this?

A virtual inventory library for PocketMine-MP 5. Open fake Chest, Double Chest or Hopper inventories for players without any real blocks in the world.

### Why not just use InvMenu?

| Feature | InvMenu | CustomInventoryLib |
|---|---|---|
| Double chest on legacy clients (1.21.x) | Broken (opens as 27 slots) | Works correctly |
| Double chest on modern clients (26.x+) | Works | Works |
| Client version detection | None | Automatic |
| Conflict-free loading | Conflicts if embedded in multiple plugins or server core | Always conflict-free |
| API simplicity | Verbose, requires manual type registration | Clean one-liner API |
| PHP Enum types | No | Yes (`InvType::DOUBLE_CHEST`) |

The double chest bug in InvMenu exists because legacy Bedrock clients (1.21.x) require the two fake chest blocks to be paired along a different axis than modern clients (26.x+). CustomInventoryLib detects the client version automatically and handles this transparently.

### Installation

1. Download `CustomInventoryLib.phar` and place it in your `plugins/` folder
2. Restart the server
3. Add `depend: [CustomInventoryLib]` to your plugin's `plugin.yml`

### Usage

```php
use imorsel\custominv\CustomInventory;

// Single chest
$inv = CustomInventory::chest("My Shop");
$inv->setItem(0, VanillaItems::DIAMOND());
$inv->send($player);

// Double chest — works on ALL client versions
$inv = CustomInventory::doubleChest("Auction House");
$inv->setItem(0, VanillaItems::NETHERITE_INGOT());
$inv->send($player);

// Hopper
$inv = CustomInventory::hopper("Quick Select");
$inv->send($player);
```

### Callbacks

```php
$inv = CustomInventory::doubleChest("Shop");

// Called when a player clicks a slot — return false to cancel item movement
$inv->onTransaction(function(Player $player, int $slot, Item $from, Item $to) : bool {
    $player->sendMessage("You clicked slot $slot");
    return false;
});

// Called when inventory opens
$inv->onOpen(function(Player $player, CustomInv $inv) : void {
    $player->sendMessage("Opened!");
});

// Called when inventory closes
$inv->onClose(function(Player $player, CustomInv $inv) : void {
    $player->sendMessage("Closed!");
});

$inv->send($player);
```

### Send to multiple players

```php
$inv = CustomInventory::chest("Shared View");
$inv->sendToAll($player1, $player2, $player3);
```

### Utility methods

```php
$inv = CustomInventory::chest("Decorated");

// Fill all slots with one item
$inv->fill(VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::GRAY)->asItem()->setCustomName(" "));

// Set specific items
$inv->setItem(13, VanillaItems::DIAMOND()->setCustomName("Click me!"));

$inv->send($player);
```

### Project structure

```
src/imorsel/custominv/
├── CustomInventoryLib.php     Plugin entry point
├── CustomInventory.php        Public API facade
├── inventory/
│   └── CustomInv.php          Virtual inventory instance with callbacks
├── session/
│   ├── SessionManager.php     Event listener, manages per-player sessions
│   └── PlayerSession.php      Core: fake blocks, version detection, packets
└── type/
    └── InvType.php            PHP enum defining all supported inventory types
```

---

## Русский

### Что это?

Библиотека виртуальных инвентарей для PocketMine-MP 5. Открывает игрокам фейковые инвентари (Сундук, Двойной Сундук, Воронка) без реальных блоков в мире.

### Почему не InvMenu?

| Фича | InvMenu | CustomInventoryLib |
|---|---|---|
| Двойной сундук на старых клиентах (1.21.x) | Сломано (открывается как 27 слотов) | Работает корректно |
| Двойной сундук на новых клиентах (26.x+) | Работает | Работает |
| Определение версии клиента | Нет | Автоматическое |
| Загрузка без конфликтов | Конфликтует если вшит в несколько плагинов или ядро | Всегда без конфликтов |
| Простота API | Многословный, требует ручной регистрации типов | Чистый однострочный API |
| PHP Enum типы | Нет | Да (`InvType::DOUBLE_CHEST`) |

Баг двойного сундука в InvMenu существует потому что старые клиенты Bedrock (1.21.x) требуют чтобы два фейковых блока сундука были спарены по другой оси чем новые клиенты (26.x+). CustomInventoryLib автоматически определяет версию клиента и обрабатывает это прозрачно.

### Установка

1. Скачай `CustomInventoryLib.phar` и положи в папку `plugins/`
2. Перезапусти сервер
3. Добавь `depend: [CustomInventoryLib]` в `plugin.yml` своего плагина

### Использование

```php
use imorsel\custominv\CustomInventory;

// Одиночный сундук
$inv = CustomInventory::chest("Мой магазин");
$inv->setItem(0, VanillaItems::DIAMOND());
$inv->send($player);

// Двойной сундук — работает на ВСЕХ версиях клиентов
$inv = CustomInventory::doubleChest("Аукцион");
$inv->setItem(0, VanillaItems::NETHERITE_INGOT());
$inv->send($player);

// Воронка
$inv = CustomInventory::hopper("Быстрый выбор");
$inv->send($player);
```

### Колбэки

```php
$inv = CustomInventory::doubleChest("Магазин");

// Вызывается при клике на слот — верни false чтобы отменить перемещение предмета
$inv->onTransaction(function(Player $player, int $slot, Item $from, Item $to) : bool {
    $player->sendMessage("Вы кликнули на слот $slot");
    return false;
});

// Вызывается при открытии инвентаря
$inv->onOpen(function(Player $player, CustomInv $inv) : void {
    $player->sendMessage("Открыто!");
});

// Вызывается при закрытии инвентаря
$inv->onClose(function(Player $player, CustomInv $inv) : void {
    $player->sendMessage("Закрыто!");
});

$inv->send($player);
```

---

## License

MIT License. See [LICENSE](LICENSE) for details.

---

<div align="center">

Made with care by **Ilias Morsel**

*If this library saved you time, consider leaving a star.*

</div>
