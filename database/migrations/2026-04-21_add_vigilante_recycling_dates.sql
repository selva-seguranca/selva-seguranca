ALTER TABLE vigilantes
    ADD COLUMN IF NOT EXISTS data_ultima_reciclagem DATE,
    ADD COLUMN IF NOT EXISTS validade_reciclagem DATE;
