-- #!sqlite
-- #{ auctionhouse

-- #  { init
CREATE TABLE IF NOT EXISTS listings(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid CHAR(36),
    username VARCHAR(36),
    price INT,
    item TEXT,
    created INT,
    end_time INT,
    expired BOOLEAN DEFAULT FALSE
);
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
SELECT COUNT(*) FROM listings WHERE uuid = :uuid AND expired = FALSE;
-- #        }

-- #        { username
-- #          :username string
SELECT COUNT(*) FROM listings WHERE username = :username AND expired = FALSE;
-- #        }

-- #    }

-- #    { expired

-- #        { all
SELECT COUNT(*) FROM listings WHERE expired = TRUE;
-- #        }

-- #        { uuid
-- #          :uuid string
SELECT COUNT(*) FROM listings WHERE uuid = :uuid AND expired = TRUE;
-- #        }

-- #    }

-- # }

-- # { fetch

-- #    { all
-- #        :id int
-- #        :limit int
SELECT * FROM listings LIMIT :id, :limit;
-- #    }

-- #    { id
-- #        :id int
SELECT * FROM listings WHERE id = :id;
-- #    }

-- #    { active

-- #        { next
-- #            :id int
-- #            :limit int
SELECT * FROM listings WHERE expired = FALSE LIMIT :id, :limit;
-- #        }

-- #        { uuid
-- #            :id int
-- #            :limit int
-- #            :uuid string
SELECT * FROM listings WHERE uuid = :uuid AND expired = FALSE LIMIT :id, :limit;
-- #        }

-- #        { username
-- #            :id int
-- #            :limit int
-- #            :username string
SELECT * FROM listings WHERE username = :username AND expired = FALSE LIMIT :id, :limit;
-- #        }

-- #    }

-- #    { expired

-- #        { next
-- #            :id int
-- #            :limit int
SELECT * FROM listings WHERE expired = TRUE LIMIT :id, :limit;
-- #        }

-- #        { uuid
-- #            :id int
-- #            :limit int
-- #            :uuid string
SELECT * FROM listings WHERE uuid = :uuid AND expired = TRUE LIMIT :id, :limit;
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
-- #    :uuid string
-- #    :username string
-- #    :price int
-- #    :item string
-- #    :created int
-- #    :end_time int
-- #    :expired bool
INSERT INTO listings(id, uuid, username, price, item, created, end_time, expired) VALUES (NULL, :uuid, :username, :price, :item, :created, :end_time, :expired);
-- # }

-- # }