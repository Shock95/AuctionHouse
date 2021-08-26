-- #!mysql
-- #{ auctionhouse

-- #  { init
CREATE TABLE IF NOT EXISTS auctions(
    uuid BLOB,
    username VARCHAR(16),
    price INT,
    nbt BLOB,
    end_time INT,
    expired BOOLEAN DEFAULT FALSE,
    id INT PRIMARY KEY);
-- #  }

-- # { fetch

-- #    { all
SELECT * FROM auctions;
-- #    }
-- #  }

-- # { delete
-- #    :id int
DELETE FROM auctions
WHERE id = :id;
-- # }

-- # { expired
-- #    :id int
-- #    :expired bool
UPDATE auctions
SET expired = :expired
WHERE id = :id;
-- # }

-- # { insert
-- #    :uuid string
-- #    :username string
-- #    :price int
-- #    :nbt string
-- #    :id int
-- #    :end_time int
-- #    :expired bool
INSERT INTO auctions(uuid, username, price, nbt, id, end_time, expired) VALUES (:uuid, :username, :price, :nbt, :id, :end_time, :expired)
ON DUPLICATE KEY UPDATE id = id + 1;
-- # }

-- # }