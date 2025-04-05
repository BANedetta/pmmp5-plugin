-- #! mysql

-- #{ table
	-- #{ init
		CREATE TABLE IF NOT EXISTS data (
			id VARCHAR(255) PRIMARY KEY,
			`by` VARCHAR(255),
			reason TEXT,
			confirmed TINYINT(1) DEFAULT 0,
			`trigger` TINYINT(1) DEFAULT 0,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		);
	-- #}
-- #}

-- #{ data
	-- #{ get_all_pending_datas
		SELECT * FROM data WHERE `trigger` = FALSE AND confirmed = FALSE;
	-- #}

	-- #{ get_data
		-- # :id string
		SELECT * FROM data WHERE id = :id;
	-- #}

	-- #{ ban
		-- # :id string
		-- # :by string
		-- # :reason string
		INSERT INTO data (id, `by`, reason, confirmed, `trigger`, created_at)
		VALUES (:id, :by, :reason, 0, 0, CURRENT_TIMESTAMP)
		ON DUPLICATE KEY UPDATE
			`by` = VALUES(`by`),
			reason = VALUES(reason),
			confirmed = VALUES(confirmed),
			`trigger` = VALUES(`trigger`),
			created_at = VALUES(created_at);
	-- #}

	-- #{ unban
		-- # :id string
		DELETE FROM data WHERE id = :id;
	-- #}

	-- #{ confirm
		-- # :id string
		UPDATE data SET confirmed = 1 WHERE id = :id;
	-- #}

	-- #{ trigger
		-- # :id string
		UPDATE data SET `trigger` = 1 WHERE id = :id;
	-- #}
-- #}
