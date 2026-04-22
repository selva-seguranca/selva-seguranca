ALTER TABLE advertencias_colaboradores
    ADD COLUMN IF NOT EXISTS arquivo_pdf_nome VARCHAR(255),
    ADD COLUMN IF NOT EXISTS arquivo_pdf_url TEXT,
    ADD COLUMN IF NOT EXISTS arquivo_pdf_path TEXT,
    ADD COLUMN IF NOT EXISTS arquivo_pdf_storage_driver VARCHAR(30),
    ADD COLUMN IF NOT EXISTS arquivo_pdf_bucket VARCHAR(100),
    ADD COLUMN IF NOT EXISTS arquivo_pdf_tamanho_bytes INTEGER;
