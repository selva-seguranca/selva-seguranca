-- seeds.sql
-- Banco de Dados: PostgreSQL
-- Projeto: Selva Seguranca CRM

-- Inserir Perfis
INSERT INTO perfis (nome) VALUES
('Coordenador Geral'),
('Administrador'),
('Colaborador Interno'),
('Vigilante')
ON CONFLICT (nome) DO NOTHING;

-- O id_perfil pode variar porque usamos SERIAL. Vamos usar subqueries para referenciar por nome.

-- Inserir Usuario Coordenador Geral Administrativo Default
-- Senha padrao: admin123
INSERT INTO usuarios (nome, email, senha_hash, perfil_id)
SELECT 'Admin Selva', 'admin@selvaseguranca.com', '$2y$10$J3jo29iM4JIbOedktKQeM.FcIzhh3HVRiNqqgl92StRBRGTkcvYVa', id
FROM perfis WHERE nome = 'Coordenador Geral'
ON CONFLICT (email) DO NOTHING;

-- Inserir Usuario Vigilante Exemplo
-- Senha padrao: vigilante123
INSERT INTO usuarios (nome, email, senha_hash, perfil_id)
SELECT 'Joao Vigilante', 'joao@selvaseguranca.com', '$2y$10$a4nHziZVZHbmdwdzCCNo3e4Lg6VVnWTs9xA47RvvLkgMC6XEbm2tm', id
FROM perfis WHERE nome = 'Vigilante'
ON CONFLICT (email) DO NOTHING;

-- Inserir Vigilante na respectiva tabela (referenciando o usuario criado)
INSERT INTO vigilantes (usuario_id, cnh, validade_cnh, formacao)
SELECT id, '12345678901', '2028-12-31', 'Curso Basico de Vigilancia'
FROM usuarios WHERE email = 'joao@selvaseguranca.com'
AND NOT EXISTS (
    SELECT 1
    FROM vigilantes
    WHERE vigilantes.usuario_id = usuarios.id
);

-- Inserir Veiculos Exemplo
INSERT INTO veiculos (placa, modelo, marca, ano, km_atual, data_prox_troca_oleo, km_prox_troca_oleo, data_prox_revisao, km_prox_revisao) VALUES
('ABC1D23', 'Gol', 'Volkswagen', 2021, 49500, '2026-05-10', 50000, '2026-06-15', 55000),
('XYZ9A87', 'Duster', 'Renault', 2023, 12000, '2026-10-20', 15000, '2026-11-20', 20000)
ON CONFLICT (placa) DO NOTHING;
