-- #!sqlite
-- #{ auctionhouse

-- #  { init

-- #    { tables
CREATE TABLE IF NOT EXISTS players(
    uuid BINARY(16) PRIMARY KEY,
    username VARCHAR(16) NOT NULL
);
-- #&
CREATE TABLE IF NOT EXISTS listings(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    player_uuid BINARY(16),
    item BLOB NOT NULL,
    price INT,
    created_at INT,
    expires_at INT,
    expired BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (player_uuid) REFERENCES players(uuid) ON DELETE CASCADE
);
-- #    }

-- #  }

-- # { count

-- #    { all
SELECT COUNT(*) FROM listings;
-- #    }

-- #    { username
-- #          :username string
SELECT COUNT(*) FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE players.username = :username;
-- #    }

-- #    { active

-- #        { all
SELECT COUNT(*) FROM listings WHERE expired = FALSE;
-- #        }

-- #        { uuid
-- #          :uuid string
SELECT COUNT(*) FROM listings WHERE player_uuid = :uuid AND expired = FALSE;
-- #        }

-- #        { username
-- #          :username string
SELECT COUNT(*) FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE players.username = :username AND expired = FALSE;
-- #        }
-- #    }

-- #    { expired

-- #        { all
SELECT COUNT(*) FROM listings WHERE expired = TRUE;
-- #        }

-- #        { uuid
-- #          :uuid string
SELECT COUNT(*) FROM listings WHERE player_uuid = :uuid AND expired = TRUE;
-- #        }

-- #        { username
-- #          :username string
SELECT COUNT(*) FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE players.username = :username AND expired = TRUE;
-- #        }

-- #    }

-- # }

-- # { fetch

-- #    { all
-- #        :id int
-- #        :limit int
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid LIMIT :id, :limit;
-- #    }

-- #    { id
-- #        :id int
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE id = :id;
-- #    }

-- #    { username
-- #        :id int
-- #        :limit int
-- #        :username string
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE players.username = :username LIMIT :id, :limit;
-- #    }

-- #    { active

-- #        { next
-- #            :id int
-- #            :limit int
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE listings.expired = FALSE LIMIT :id, :limit;
-- #        }

-- #        { uuid
-- #            :id int
-- #            :limit int
-- #            :uuid string
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE player_uuid = :uuid AND expired = FALSE LIMIT :id, :limit;
-- #        }

-- #        { username
-- #            :id int
-- #            :limit int
-- #            :username string
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE players.username = :username AND expired = FALSE LIMIT :id, :limit;
-- #        }

-- #    }

-- #    { expired

-- #        { next
-- #            :id int
-- #            :limit int
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE expired = TRUE LIMIT :id, :limit;
-- #        }

-- #        { uuid
-- #            :id int
-- #            :limit int
-- #            :uuid string
SELECT listings.*, players.username FROM listings JOIN players ON listings.player_uuid = players.uuid WHERE player_uuid = :uuid AND expired = TRUE LIMIT :id, :limit;
-- #        }

-- #    }

-- #    }

-- # { delete

-- #    { id
-- #        :id int
DELETE FROM listings WHERE id = :id;
-- #    }

-- #    { username
-- #        :username string
DELETE FROM listings
WHERE player_uuid IN (
    SELECT uuid FROM players
    WHERE username = :username
);
-- #    }

-- # }

-- # { expire

-- #    { id
-- #        :id int
UPDATE listings SET expired = TRUE WHERE id = :id;
-- #    }

-- #    { username
-- #        :username string
UPDATE listings SET expired = TRUE
WHERE player_uuid IN (
    SELECT uuid FROM players
    WHERE players.username = :username
);
-- #    }

-- #    { all
UPDATE listings SET expired = TRUE;
-- #    }

-- # }

-- # { relist

-- #    { username
-- #        :expires_at int
-- #        :username string
UPDATE listings SET expired = FALSE, expires_at = :expires_at
WHERE player_uuid IN (
    SELECT uuid FROM players
    WHERE players.username = :username
);
-- #    }

-- #    { all
-- #        :expires_at int
UPDATE listings SET expired = FALSE, expires_at = :expires_at;
-- #    }

-- # }

-- # { insert
-- #    :player_uuid string
-- #    :username string
-- #    :price int
-- #    :item string
-- #    :created_at int
-- #    :expires_at int
INSERT INTO players(uuid, username) VALUES (:player_uuid, :username)
ON CONFLICT DO UPDATE SET username = :username;
-- #&
INSERT INTO listings(player_uuid, item, price, created_at, expires_at) VALUES (:player_uuid, :item, :price, :created_at, :expires_at);
-- # }

-- # }