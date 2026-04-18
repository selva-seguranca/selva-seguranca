-- schema.sql
-- Banco de Dados: PostgreSQL
-- Projeto: Selva Segurança CRM

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Perfis
CREATE TABLE IF NOT EXISTS perfis (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

-- Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil_id INTEGER NOT NULL REFERENCES perfis(id) ON DELETE RESTRICT,
    ativo BOOLEAN DEFAULT true,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sessoes persistentes de login
CREATE TABLE IF NOT EXISTS auth_persistent_logins (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    selector VARCHAR(32) NOT NULL UNIQUE,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_auth_persistent_logins_usuario_id
    ON auth_persistent_logins (usuario_id);

-- Vigilantes
CREATE TABLE IF NOT EXISTS vigilantes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    cnh VARCHAR(20),
    validade_cnh DATE,
    formacao VARCHAR(150),
    validade_reciclagem DATE,
    numero_cnv VARCHAR(30),
    validade_cnv DATE,
    curso_formacao_concluido BOOLEAN DEFAULT false,
    data_ultima_reciclagem DATE,
    situacao_reciclagem VARCHAR(30),
    curso_escolta_armada BOOLEAN DEFAULT false,
    curso_seguranca_eventos BOOLEAN DEFAULT false,
    curso_seguranca_vip BOOLEAN DEFAULT false,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Veículos
CREATE TABLE IF NOT EXISTS veiculos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    placa VARCHAR(10) NOT NULL UNIQUE,
    modelo VARCHAR(50) NOT NULL,
    marca VARCHAR(50) NOT NULL,
    ano INTEGER,
    km_atual INTEGER DEFAULT 0,
    data_prox_troca_oleo DATE,
    km_prox_troca_oleo INTEGER,
    data_prox_revisao DATE,
    km_prox_revisao INTEGER,
    status VARCHAR(30) DEFAULT 'disponivel', -- disponivel, em_uso, manutencao
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rondas
CREATE TABLE IF NOT EXISTS rondas (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vigilante_id UUID NOT NULL REFERENCES vigilantes(id) ON DELETE RESTRICT,
    veiculo_id UUID REFERENCES veiculos(id) ON DELETE SET NULL,
    data_inicio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_fim TIMESTAMP,
    status VARCHAR(30) DEFAULT 'em_andamento', -- em_andamento, concluido
    observacoes TEXT
);

-- Checklist do Veículo ANTES da ronda
CREATE TABLE IF NOT EXISTS checklist_veiculos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vigilante_id UUID NOT NULL REFERENCES vigilantes(id) ON DELETE RESTRICT,
    veiculo_id UUID NOT NULL REFERENCES veiculos(id) ON DELETE RESTRICT,
    ronda_id UUID REFERENCES rondas(id) ON DELETE CASCADE,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    km_inicial INTEGER NOT NULL,
    foto_painel_url VARCHAR(255),
    combustivel_nivel VARCHAR(30), -- cheio, 3/4, 1/2, 1/4, reserva
    condicao_pneus VARCHAR(30), -- bom, regular, ruim
    condicao_iluminacao VARCHAR(30),
    condicao_freios VARCHAR(30),
    observacoes TEXT
);

-- Ocorrências (registradas durante a ronda)
CREATE TABLE IF NOT EXISTS ocorrencias (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ronda_id UUID NOT NULL REFERENCES rondas(id) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL, -- suspeita, invasao, veiculo_suspeito, pane, outros
    descricao TEXT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    foto_url VARCHAR(255),
    video_url VARCHAR(255),
    audio_url VARCHAR(255),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Manutenções
CREATE TABLE IF NOT EXISTS manutencoes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    veiculo_id UUID NOT NULL REFERENCES veiculos(id) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL, -- troca_oleo, revisao, corretiva
    data_realizada DATE NOT NULL,
    km_realizada INTEGER NOT NULL,
    observacoes TEXT,
    valor DECIMAL(10,2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nome_razao_social VARCHAR(200) NOT NULL,
    cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(150),
    endereco TEXT,
    status VARCHAR(20) DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Colaboradores (Internos / Geral)
CREATE TABLE IF NOT EXISTS colaboradores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    data_admissao DATE,
    salario DECIMAL(10,2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Detalhes de Colaboradores
CREATE TABLE IF NOT EXISTS colaborador_detalhes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    colaborador_id UUID NOT NULL UNIQUE REFERENCES colaboradores(id) ON DELETE CASCADE,
    tipo_cadastro VARCHAR(50) NOT NULL DEFAULT 'financeiro_administrativo',
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
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contratos
CREATE TABLE IF NOT EXISTS contratos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tipo VARCHAR(30) NOT NULL, -- cliente, colaborador
    cliente_id UUID REFERENCES clientes(id) ON DELETE CASCADE,
    colaborador_id UUID REFERENCES colaboradores(id) ON DELETE CASCADE,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    valor DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'ativo',
    arquivo_pdf_url VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Estoque
CREATE TABLE IF NOT EXISTS estoque (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nome_item VARCHAR(100) NOT NULL,
    categoria VARCHAR(50), -- uniforme, epi, armamento, equipamento
    quantidade INTEGER DEFAULT 0,
    validade DATE,
    numero_serie VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Financeiro
CREATE TABLE IF NOT EXISTS financeiro (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tipo VARCHAR(20) NOT NULL, -- receita, despesa
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status VARCHAR(20) DEFAULT 'pendente', -- pendente, pago, atrasado
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Documentos
CREATE TABLE IF NOT EXISTS documentos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    entidade_tipo VARCHAR(50), -- usuario, cliente, veiculo, etc.
    entidade_id UUID,
    titulo VARCHAR(150) NOT NULL,
    arquivo_url VARCHAR(255) NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Logs de Auditoria
CREATE TABLE IF NOT EXISTS logs_auditoria (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    usuario_id UUID REFERENCES usuarios(id) ON DELETE SET NULL,
    acao VARCHAR(50) NOT NULL, -- CREATE, UPDATE, DELETE, LOGIN
    tabela_afetada VARCHAR(50),
    registro_id UUID,
    dados_antigos JSONB,
    dados_novos JSONB,
    ip VARCHAR(45),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
