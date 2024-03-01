# ⚠️ Please help me fix a bugs! I check issues and pull requests every day!
# ⚠️Пожалуйста, помогите с исправлением багов! Я проверяю сообщения об ошибках и пулл реквесты каждый день!

# AuctionHouse [![](https://poggit.pmmp.io/shield.state/AuctionHouse)](https://poggit.pmmp.io/p/AuctionHouse) [![](https://poggit.pmmp.io/shield.dl.total/AuctionHouse)](https://poggit.pmmp.io/p/AuctionHouse)
Feature-packed AuctionHouse plugin for PocketMine-MP  
   
## Overview  
AuctionHouse allows players to list their items for sale and purchase items that others have listed for sale
  
![AuctionHouse](https://github.com/Shock95x/AuctionHouse/blob/master/img/auctionhouse.png)  
---  
## Features  
>- Chest GUI  
>- Admin tools
>- Categories
>- Config (See below)
>- Multi-lang support
>- Cancel listings
>- Listing cooldown
>- Custom events
>- Economy plugin support
>- MySQL and SQLite database support
>- Custom listings limit per player (See permissions)
>- Customizable messages

---  
## Download  
Download the plugin from [Poggit](https://poggit.pmmp.io/p/AuctionHouse) or [GitHub releases](https://github.com/Shock95x/AuctionHouse/releases)
  
---  
## Config  

<details>
  <summary>Click to open</summary>

```yaml  
---  
# DO NOT EDIT THIS VALUE, INTERNAL USE ONLY.
config-version: 5

# Sets the prefix for this plugin.
prefix: "[&l&6Auction House&r]"
# Minimum price required to create a listing
min-price: 0
# Maximum price a listing can have (-1 = No limit)
max-price: -1
# Sets the default language for the plugin, you can edit text and messages in this file.
default-language: en_US
# Sets the amount of hours a listing is active before being automatically cancelled and expired.
expire-interval: 48
# Sets the price it costs to list an item on the auction house.
listing-price: 0
# Sets a cooldown between listing items in seconds
listing-cooldown: 0
# Allows or blocks players in creative mode from selling items.
creative-sale: false
# Maximum amount of listings a player can have by default
max-listings: 45
# Shows item lore on the auction house
show-lore: true
# Days to automatically delete expired listings (-1 to disable)
expired-duration: 15
# Formats price with commas (ex. 1,000,000)
price-formatted: true
# Items that cannot be listed on the auction. Refer to https://minecraftitemids.com/ or https://minecraft-ids.grahamedgecombe.com/ for a list of item ids.
blacklist:
- '1000' # Example items
- '1234:5'
- 'minecraft:air'

# AH sign triggers
sign-triggers:
- "[AuctionHouse]"
- "[AH]"

# Menu button items
buttons:
stats: "minecraft:chest"
back: "minecraft:paper"
previous: "minecraft:paper"
next: "minecraft:paper"
info: "minecraft:book"
howto: "minecraft:emerald"
return_all: "minecraft:redstone_block"
player_listings: "minecraft:diamond"
expired_listings: "minecraft:poisonous_potato"
admin_menu: "minecraft:redstone"
confirm_purchase: "minecraft:stained_glass_pane:5"
cancel_purchase: "minecraft:stained_glass_pane:14"
...
```  
</details>

---  
## Commands  
  
| Command        | Description           |  
| ------------- |:--------------|  
| /ah      | AuctionHouse main command, opens the shop menu if there are no specified parameters |  
| /ah shop | Opens the shop menu    |  
| /ah sell **[price]** | Allows player to list items in their hand on the auction house. **[price]** is the amount that the player is listing the item to sell for     |  
| /ah listings | Shows all active listings of the player |  
| /ah listings **[player]**| Shows all active listings of a specific player |  
| /ah category | Opens category menu |  
| /ah admin | Opens the AuctionHouse admin menu (OP Command) |  
| /ah reload | Allows player to reload configuration files (OP command) |  
| /ah about | Shows AuctionHouse version the server is running |  
---  
## API  
### Events  
| Event        | Description           |  
| ------------- | -------------- |  
| [shock95x\auctionhouse\event\AuctionStartEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/AuctionStartEvent.php) | Called when an auction is started |  
| [shock95x\auctionhouse\event\AuctionEndEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/AuctionEndEvent.php)   | Called when an auction has ended    |  
| [shock95x\auctionhouse\event\ItemListedEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/ItemListedEvent.php)   | Called when an item is listed by player (cancellable) | 
| [shock95x\auctionhouse\event\ItemPurchasedEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/ItemPurchasedEvent.php)   | Called when an item is purchased by player (cancellable) |  
| [shock95x\auctionhouse\event\MenuCloseEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/MenuCloseEvent.php) | Called when a menu is closed by player |  
  
## Contributing  
You can contribute to this project by creating or modifying a language file and opening a PR!  
### Contributors 
- [Shock95x](https://github.com/Shock95x) (English)  
- [ipad54](https://github.com/ipad54) (Russian)
- [No4NaMe](https://github.com/No4NaMe) (Russian)
- [XackiGiFF](https://github.com/XackiGiFF) (Russian)
- [Unickorn](https://github.com/Unickorn) (German)
- Chaosfelix4451#0157 (German)
- [xAliTura01](https://github.com/xAliTura01) (Turkish)
- [NotEnriko](https://github.com/NotEnriko) (Indonesian)

## Credits / Virions Used
- [InvMenu](https://github.com/Muqsit/InvMenu) (Muqsit)  
- [libasynql](https://github.com/poggit/libasynql) (SOFe)
- [await-generator](https://github.com/SOF3/await-generator) (SOFe)
- [Commando](https://github.com/JinodkDevTeam/Commando) (CortexPE and JinodkDevTeam)
- [UpdateNotifier](https://github.com/Aboshxm2/UpdateNotifier) (Ifera and Aboshxm2)
