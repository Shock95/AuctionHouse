-- #!mysql
-- #{ auctionhouse

-- #  { init

-- #    { tables
CREATE TABLE IF NOT EXISTS players(
    uuid BINARY(16) PRIMARY KEY,
    username VARCHAR(16) NOT NULL
);
-- #&
CREATE TABLE IF NOT EXISTS listings(
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    player_uuid BINARY(16),
    item BLOB NOT NULL,
    price INT,
    created_at BIGINT,
    expires_at BIGINT,
    expired BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (player_uuid) REFERENCES players(uuid) ON DELETE CASCADE
);
-- #    }

-- #    { events
-- #        :duration int
DROP PROCEDURE IF EXISTS expire_listings;
-- #&
CREATE PROCEDURE expire_listings()
BEGIN
    UPDATE listings SET expired = TRUE WHERE expired = FALSE AND expires_at <= UNIX_TIMESTAMP();
    IF :duration > 0 THEN
        DELETE FROM listings
        WHERE expired = TRUE AND (expires_at + 120) <= UNIX_TIMESTAMP();
    END IF;
END;
-- #&
CREATE EVENT IF NOT EXISTS expire_event
ON SCHEDULE EVERY 1 MINUTE
DO CALL expire_listings();
-- #    }

-- #  }

-- # { count

-- #    { all
SELECT COUNT(*) FROM listings;
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
-- #    :id int
DELETE FROM listings WHERE id = :id;
-- # }

-- # { expired
-- #    :id int
-- #    :expired bool
UPDATE listings SET expired = :expired WHERE id = :id;
-- # }

-- # { insert
-- #    :player_uuid string
-- #    :username string
-- #    :item string
-- #    :price int
-- #    :created_at int
-- #    :expires_at int
INSERT INTO players(uuid, username) VALUES (:player_uuid, :username)
ON DUPLICATE KEY UPDATE username = VALUES(username);
-- #&
INSERT INTO listings(player_uuid, item, price, created_at, expires_at) VALUES (:player_uuid, :item, :price, :created_at, :expires_at);
-- # }

-- # }