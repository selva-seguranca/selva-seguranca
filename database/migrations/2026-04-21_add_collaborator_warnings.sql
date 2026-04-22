CREATE TABLE IF NOT EXISTS advertencias_colaboradores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    colaborador_id UUID NOT NULL REFERENCES colaboradores(id) ON DELETE CASCADE,
    vigilante_id UUID NOT NULL REFERENCES vigilantes(id) ON DELETE RESTRICT,
    ocorrencia_id UUID NOT NULL REFERENCES ocorrencias(id) ON DELETE RESTRICT,
    posto_servico VARCHAR(150) NOT NULL,
    data_ocorrencia DATE NOT NULL,
    data_advertencia DATE NOT NULL,
    tipo_advertencia VARCHAR(20) NOT NULL,
    motivo VARCHAR(120) NOT NULL,
    descricao TEXT NOT NULL,
    classificacao_falta VARCHAR(20) NOT NULL,
    medida_disciplinar VARCHAR(30) NOT NULL DEFAULT 'Advertência',
    responsavel_usuario_id UUID REFERENCES usuarios(id) ON DELETE SET NULL,
    responsavel_nome VARCHAR(150) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_advertencias_colaborador_id
    ON advertencias_colaboradores (colaborador_id);

CREATE INDEX IF NOT EXISTS idx_advertencias_vigilante_id
    ON advertencias_colaboradores (vigilante_id);

CREATE INDEX IF NOT EXISTS idx_advertencias_ocorrencia_id
    ON advertencias_colaboradores (ocorrencia_id);

CREATE INDEX IF NOT EXISTS idx_advertencias_data_advertencia
    ON advertencias_colaboradores (data_advertencia);
