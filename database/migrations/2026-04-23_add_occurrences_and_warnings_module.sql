-- Módulo: Ocorrências e Advertências
-- Objetivo:
-- 1. Criar a tabela de ocorrências administrativas de colaboradores.
-- 2. Permitir o registro de ocorrências internas separadas das ocorrências operacionais de ronda.
-- 3. Indexar os campos mais usados em busca e listagem.

CREATE TABLE IF NOT EXISTS ocorrencias_colaboradores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    colaborador_id UUID NOT NULL REFERENCES colaboradores(id) ON DELETE CASCADE,
    vigilante_id UUID NOT NULL REFERENCES vigilantes(id) ON DELETE RESTRICT,
    posto_servico VARCHAR(150) NOT NULL,
    data_ocorrencia DATE NOT NULL,
    tipo_ocorrencia VARCHAR(80) NOT NULL,
    descricao TEXT NOT NULL,
    classificacao VARCHAR(20) NOT NULL DEFAULT 'Média',
    responsavel_usuario_id UUID REFERENCES usuarios(id) ON DELETE SET NULL,
    responsavel_nome VARCHAR(150) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE ocorrencias_colaboradores IS 'Registra ocorrências administrativas e disciplinares vinculadas ao colaborador vigilante.';
COMMENT ON COLUMN ocorrencias_colaboradores.posto_servico IS 'Posto ou local de serviço informado no momento do registro.';
COMMENT ON COLUMN ocorrencias_colaboradores.data_ocorrencia IS 'Data em que a ocorrência administrativa aconteceu.';
COMMENT ON COLUMN ocorrencias_colaboradores.tipo_ocorrencia IS 'Tipo resumido da ocorrência informada pelo RH.';
COMMENT ON COLUMN ocorrencias_colaboradores.descricao IS 'Descrição detalhada do fato registrado.';
COMMENT ON COLUMN ocorrencias_colaboradores.classificacao IS 'Grau da ocorrência: Leve, Média ou Grave.';
COMMENT ON COLUMN ocorrencias_colaboradores.responsavel_nome IS 'Nome do usuário responsável pelo lançamento da ocorrência.';

CREATE INDEX IF NOT EXISTS idx_ocorrencias_colaboradores_colaborador_id
    ON ocorrencias_colaboradores (colaborador_id);

CREATE INDEX IF NOT EXISTS idx_ocorrencias_colaboradores_vigilante_id
    ON ocorrencias_colaboradores (vigilante_id);

CREATE INDEX IF NOT EXISTS idx_ocorrencias_colaboradores_data_ocorrencia
    ON ocorrencias_colaboradores (data_ocorrencia);

CREATE INDEX IF NOT EXISTS idx_ocorrencias_colaboradores_classificacao
    ON ocorrencias_colaboradores (classificacao);
