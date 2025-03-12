-- #! sqlite

-- #{ table
	-- #{ init
		CREATE TABLE IF NOT EXISTS data (
			id VARCHAR(255) PRIMARY KEY,
			`by` VARCHAR(255),
			reason TEXT,
			confirmed BOOLEAN DEFAULT FALSE,
			trigger BOOLEAN DEFAULT FALSE,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		);
	-- #}
-- #}

-- #{ data
	-- #{ get_all_pending_datas
		SELECT * FROM data WHERE trigger = FALSE AND confirmed = FALSE;
	-- #}

	-- #{ get_data
		-- # :id string
		SELECT * FROM data WHERE id = :id;
	-- #}

	-- #{ ban
		-- # :id string
		-- # :by string
		-- # :reason string
		INSERT INTO data (id, `by`, reason, confirmed, trigger, created_at)
		VALUES (:id, :by, :reason, FALSE, FALSE, CURRENT_TIMESTAMP)
		ON CONFLICT(id) DO UPDATE SET
			`by` = excluded.`by`,
			reason = excluded.reason,
			confirmed = FALSE,
			trigger = FALSE,
			created_at = CURRENT_TIMESTAMP;
	-- #}

	-- #{ confirm
		-- # :id string
		UPDATE data SET confirmed = TRUE WHERE id = :id;
	-- #}

	-- #{ trigger
		-- # :id string
		UPDATE data SET trigger = TRUE WHERE id = :id;
	-- #}
-- #}