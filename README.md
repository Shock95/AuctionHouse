# AuctionHouse
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
>- SQLite database support (MySQL coming soon)
>- Customizable messages
- And more coming soon!
---
## Download
Check the [releases tab](https://github.com/Shock95x/AuctionHouse/releases) or [PoggitCI](https://poggit.pmmp.io/ci/Shock95x/AuctionHouse/AuctionHouse/)

---
## Config
```yaml
---
# DO NOT EDIT THIS VALUE, INTERNAL USE ONLY.
config-version: 1
# Sets the prefix for this plugin.
prefix: "[&l&6Auction House&r]"
# Sets the default language for the plugin, you can edit text and messages in this file.
default-language: en-US
# Sets the amount of hours a listing is active before being automatically cancelled and expired.
expire-interval: 48
# Sets the price it costs to list one item on the auction house.
listing-price: 0
# Allows or blocks players in creative mode from selling items.
creative-sale: false
# The maximum amount of listings a player can have.
max-items: 45
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
| /ah sell **[price]**      | Allows player to list items in their hand on the auction house. **[price]** is the amount that the player is listing the item to sell for     |
| /ah listings | Shows all active listings of the player|
| /ah update | Allows player to reload the config and save the database (OP command) |
---
## API
### Events
- [AuctionHouse\event\AuctionStartEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/AuctionHouse/events/AuctionStartEvent.php)
- [AuctionHouse\event\AuctionEndEvent](https://github.com/Shock95x/AuctionHouse/blob/master/src/AuctionHouse/events/AuctionEndEvent.php)
