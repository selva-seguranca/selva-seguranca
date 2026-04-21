CREATE TABLE IF NOT EXISTS colaborador_documentos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    colaborador_id UUID NOT NULL REFERENCES colaboradores(id) ON DELETE CASCADE,
    nome_original VARCHAR(255) NOT NULL,
    arquivo_url TEXT NOT NULL,
    arquivo_path TEXT,
    storage_driver VARCHAR(30),
    bucket VARCHAR(100),
    mime_type VARCHAR(100) DEFAULT 'application/pdf',
    tamanho_bytes INTEGER,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_colaborador_documentos_colaborador_id
    ON colaborador_documentos (colaborador_id);
