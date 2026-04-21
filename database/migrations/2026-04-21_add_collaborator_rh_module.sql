ALTER TABLE colaborador_detalhes
    ADD COLUMN IF NOT EXISTS modulo_rh VARCHAR(50) NOT NULL DEFAULT 'seguranca_privada';

UPDATE colaborador_detalhes cd
SET modulo_rh = 'servicos_terceirizacoes'
FROM colaboradores c
WHERE c.id = cd.colaborador_id
  AND (
      lower(coalesce(c.cargo, '') || ' ' || coalesce(c.departamento, '')) LIKE '%porteiro%'
      OR lower(coalesce(c.cargo, '') || ' ' || coalesce(c.departamento, '')) LIKE '%vigitante%'
      OR lower(coalesce(c.cargo, '') || ' ' || coalesce(c.departamento, '')) LIKE '%terceir%'
      OR lower(coalesce(c.cargo, '') || ' ' || coalesce(c.departamento, '')) LIKE '%portaria%'
      OR lower(coalesce(c.cargo, '') || ' ' || coalesce(c.departamento, '')) LIKE '%servico%'
      OR lower(coalesce(c.cargo, '') || ' ' || coalesce(c.departamento, '')) LIKE '%serviço%'
  );
