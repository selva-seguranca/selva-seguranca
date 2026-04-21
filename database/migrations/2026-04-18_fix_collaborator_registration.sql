CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

INSERT INTO perfis (nome) VALUES
    ('Coordenador Geral'),
    ('Administrador'),
    ('Colaborador Interno'),
    ('Vigilante')
ON CONFLICT (nome) DO NOTHING;

CREATE TABLE IF NOT EXISTS colaborador_detalhes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    colaborador_id UUID NOT NULL UNIQUE REFERENCES colaboradores(id) ON DELETE CASCADE,
    tipo_cadastro VARCHAR(50) NOT NULL DEFAULT 'financeiro_administrativo',
    modulo_rh VARCHAR(50) NOT NULL DEFAULT 'seguranca_privada',
    foto_url VARCHAR(255),
    cpf VARCHAR(14) NOT NULL UNIQUE,
    rg VARCHAR(30),
    data_nascimento DATE,
    telefone_principal VARCHAR(20),
    telefone_familiar VARCHAR(20),
    cep VARCHAR(9),
    logradouro VARCHAR(150),
    numero VARCHAR(20),
    bairro VARCHAR(100),
    complemento VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    endereco_completo TEXT,
    nome_mae VARCHAR(150),
    tipo_sanguineo VARCHAR(3),
    fator_rh VARCHAR(1),
    tipo_vinculo VARCHAR(30),
    numero_admissao VARCHAR(30),
    situacao VARCHAR(30) DEFAULT 'Ativo',
    banco_nome VARCHAR(100),
    agencia_bancaria VARCHAR(20),
    conta_bancaria VARCHAR(30),
    tipo_conta VARCHAR(30),
    chave_pix VARCHAR(150),
    titular_conta VARCHAR(150),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS numero_cnv VARCHAR(30);
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS validade_cnv DATE;
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_formacao_concluido BOOLEAN DEFAULT false;
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS validade_reciclagem DATE;
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS data_ultima_reciclagem DATE;
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS situacao_reciclagem VARCHAR(30);
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_escolta_armada BOOLEAN DEFAULT false;
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_seguranca_eventos BOOLEAN DEFAULT false;
ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_seguranca_vip BOOLEAN DEFAULT false;
