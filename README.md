# AuctionHouse [![](https://poggit.pmmp.io/shield.state/AuctionHouse)](https://poggit.pmmp.io/p/AuctionHouse) [![](https://poggit.pmmp.io/shield.dl.total/AuctionHouse)](https://poggit.pmmp.io/p/AuctionHouse)
Feature-packed AuctionHouse plugin for PocketMine-MP  
   
## Overview  
AuctionHouse allows players to list their items for sale and purchase items that others have listed for sale.  
  
![AuctionHouse](https://github.com/Shock95x/AuctionHouse/blob/master/img/auctionhouse.png)  
---  
## Features  
>- Chest GUI  
>- Config (See below)  
>- Multi-lang support  
>- Custom Events  
>- Economy plugin support (EconomyAPI as of now)  
>- MySQL and SQLite database support  
>- Customizable messages  
- And more coming soon!  
---  
## Download  
Check the [releases tab](https://github.com/Shock95x/AuctionHouse/releases) or [PoggitCI](https://poggit.pmmp.io/ci/Shock95x/AuctionHouse/AuctionHouse/)  
  
---  
## Config  
```yaml  
---  
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
# Sets the price it costs to list one item on the auction house.
listing-price: 0
# Allows or blocks players in creative mode from selling items.
creative-sale: false
# The maximum amount of listings a player can have.
max-items: 45
# Shows item lore on the auction house (Allows custom enchants to show)
show-lore: true
# Items that cannot be listed on the auction. Refer to https://minecraftitemids.com/ or https://minecraft-ids.grahamedgecombe.com/ for a list of item ids.
blacklist:
  - '1000' #Example items, these items dont exist in MC, but you should use ones that do if you want.
  - '1001:12'
  - '12333:4'
...  
```  
---  
## Commands  
  
| Command        | Description           |  
| ------------- |:--------------|  
| /ah      | AuctionHouse main command, opens the shop menu if there are no specified parameters |  
| /ah shop | Opens the shop menu    |  
| /ah sell **[price]** | Allows player to list items in their hand on the auction house. **[price]** is the amount that the player is listing the item to sell for     |  
| /ah listings | Shows all active listings of the player|  
| /ah listings **[player]**| Shows all active listings of a specific player|  
| /ah admin | Opens the AuctionHouse admin menu (OP Command)|  
| /ah reload | Allows player to reload the config and save the database (OP command) |  
| /ah about | Shows AuctionHouse version the server is running and author of this plugin |  
---  
## API  
### Events  
- [shock95x\auctionhouse\event\AuctionStartEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/AuctionStartEvent.php)  
- [shock95x\auctionhouse\event\AuctionEndEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/shock95x/auctionhouse/event/AuctionEndEvent.php)  
  
## Contributing  
You can contribute to this project by creating a new language file and opening a PR!  
### Supported languages and contributors 
- [Shock95x](https://github.com/Shock95x) (English)  
- [No4NaMe](https://github.com/No4NaMe) (Russian)
- Chaosfelix4451#0157 (German)

## Credits / Virions Used
- [InvMenu](https://github.com/Muqsit/InvMenu) (Muqsit)  
- [Commando](https://github.com/CortexPE/Commando) (CortexPE)
- [ConfigUpdater](https://github.com/JackMD/ConfigUpdater) (JackMD)
- [UpdateNotifier](https://github.com/JackMD/UpdateNotifier) (JackMD)