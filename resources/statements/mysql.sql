-- #!mysql
-- #{ auctionhouse

-- #  { init
CREATE TABLE IF NOT EXISTS auctions(
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36),
    username VARCHAR(36),
    price INT,
    item JSON,
    created INT,
    end_time INT,
    expired BOOLEAN DEFAULT FALSE
);
-- #  }

-- # { count

-- #    { all
SELECT COUNT(*) FROM auctions;
-- #    }

-- #    { active

-- #        { all
SELECT COUNT(*) FROM auctions WHERE expired = FALSE;
-- #        }

-- #        { uuid
-- #          :uuid string
SELECT COUNT(*) FROM auctions WHERE uuid = :uuid AND expired = FALSE;
-- #        }

-- #        { username
-- #          :username string
SELECT COUNT(*) FROM auctions WHERE username = :username AND expired = FALSE;
-- #        }

-- #    }

-- #    { expired

-- #        { all
SELECT COUNT(*) FROM auctions WHERE expired = TRUE;
-- #        }

-- #        { uuid
-- #          :uuid string
SELECT COUNT(*) FROM auctions WHERE uuid = :uuid AND expired = TRUE;
-- #        }

-- #    }

-- # }

-- # { fetch

-- #    { all
-- #    :id int
-- #    :limit int
SELECT * FROM auctions LIMIT :id, :limit;
-- #    }

-- #    { id
-- #    :id int
SELECT * FROM auctions WHERE id = :id;
-- #    }

-- #    { active

-- #        { next
-- #        :id int
-- #        :limit int
SELECT * FROM auctions WHERE expired = FALSE LIMIT :id, :limit;
-- #        }

-- #        { uuid
-- #        :id int
-- #        :limit int
-- #        :uuid string
SELECT * FROM auctions WHERE uuid = :uuid AND expired = FALSE LIMIT :id, :limit;
-- #        }

-- #        { username
-- #        :id int
-- #        :limit int
-- #        :username string
SELECT * FROM auctions WHERE username = :username AND expired = FALSE LIMIT :id, :limit;
-- #        }

-- #    }

-- #    { expired

-- #        { next
-- #        :id int
-- #        :limit int
SELECT * FROM auctions WHERE expired = TRUE LIMIT :id, :limit;
-- #        }

-- #        { uuid
-- #        :id int
-- #        :limit int
-- #        :uuid string
SELECT * FROM auctions WHERE uuid = :uuid AND expired = TRUE LIMIT :id, :limit;
-- #        }

-- #    }

-- #    }

-- # { delete
-- #    :id int
DELETE FROM auctions WHERE id = :id;
-- # }

-- # { expired
-- #    :id int
-- #    :expired bool
UPDATE auctions SET expired = :expired WHERE id = :id;
-- # }

-- # { insert
-- #    :uuid string
-- #    :username string
-- #    :price int
-- #    :item string
-- #    :created int
-- #    :end_time int
-- #    :expired bool
INSERT INTO auctions(id, uuid, username, price, item, created, end_time, expired) VALUES (NULL, :uuid, :username, :price, :item, :created, :end_time, :expired);
-- # }

-- # }