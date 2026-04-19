BEGIN;

WITH mock_user AS (
    SELECT
        u.id AS usuario_id,
        v.id AS vigilante_id
    FROM usuarios u
    LEFT JOIN vigilantes v ON v.usuario_id = u.id
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.email = 'joao@selvaseguranca.com'
      AND p.nome = 'Vigilante'
    LIMIT 1
)
DELETE FROM ocorrencias o
USING rondas r, mock_user mu
WHERE o.ronda_id = r.id
  AND r.vigilante_id = mu.vigilante_id;

WITH mock_user AS (
    SELECT
        u.id AS usuario_id,
        v.id AS vigilante_id
    FROM usuarios u
    LEFT JOIN vigilantes v ON v.usuario_id = u.id
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.email = 'joao@selvaseguranca.com'
      AND p.nome = 'Vigilante'
    LIMIT 1
)
DELETE FROM checklist_veiculos cv
USING mock_user mu
WHERE cv.vigilante_id = mu.vigilante_id;

WITH mock_user AS (
    SELECT
        u.id AS usuario_id,
        v.id AS vigilante_id
    FROM usuarios u
    LEFT JOIN vigilantes v ON v.usuario_id = u.id
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.email = 'joao@selvaseguranca.com'
      AND p.nome = 'Vigilante'
    LIMIT 1
)
DELETE FROM rondas r
USING mock_user mu
WHERE r.vigilante_id = mu.vigilante_id;

DELETE FROM logs_auditoria
WHERE usuario_id IN (
    SELECT u.id
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.email = 'joao@selvaseguranca.com'
      AND p.nome = 'Vigilante'
);

DELETE FROM usuarios u
USING perfis p
WHERE u.perfil_id = p.id
  AND u.email = 'joao@selvaseguranca.com'
  AND p.nome = 'Vigilante';

COMMIT;
